param(
  [string]$ProjectRoot = "D:\Perso\Limap\AppCarteLimap",
  [int]$Rows = 0,
  [switch]$ResetData = $true
)

$ErrorActionPreference = 'Stop'

$xlsx = Join-Path $ProjectRoot 'CarteFournisseur.xlsx'
$mysql = 'C:\wamp64\bin\mysql\mysql8.2.0\bin\mysql.exe'
$tmp = Join-Path $ProjectRoot '_tmp_seed_excel'

if (!(Test-Path $xlsx)) { throw "Excel file not found: $xlsx" }
if (!(Test-Path $mysql)) { throw "mysql.exe not found: $mysql" }
if ($Rows -lt 0) { throw 'Rows must be >= 0 (0 means all rows)' }

function Get-ColIndex([string]$cellRef) {
  $letters = ([regex]::Match($cellRef, '^[A-Z]+')).Value
  $sum = 0
  foreach ($ch in $letters.ToCharArray()) {
    $sum = ($sum * 26) + ([int][char]$ch - [int][char]'A' + 1)
  }
  return $sum
}

function Remove-Accents([string]$text) {
  if ($null -eq $text) { return '' }
  $decomposed = $text.Normalize([Text.NormalizationForm]::FormD)
  $sb = New-Object System.Text.StringBuilder
  foreach ($ch in $decomposed.ToCharArray()) {
    if ([Globalization.CharUnicodeInfo]::GetUnicodeCategory($ch) -ne [Globalization.UnicodeCategory]::NonSpacingMark) {
      [void]$sb.Append($ch)
    }
  }
  return $sb.ToString().Normalize([Text.NormalizationForm]::FormC)
}

function Normalize-Name([string]$text) {
  return (Remove-Accents $text).ToLower().Trim()
}

function Escape-Sql([string]$text) {
  if ($null -eq $text) { return '' }
  return $text.Replace('\\', '\\\\').Replace("'", "''")
}

function Clean-Text([string]$text) {
  if ($null -eq $text) { return '' }
  $v = [string]$text
  if ($v -eq 'System.Xml.XmlElement') { return '' }
  return $v.Trim()
}

function To-SqlDecimalOrNull([string]$text) {
  $v = ''
  if ($null -ne $text) { $v = [string]$text }
  $v = $v.Trim()
  if ($v -eq '') { return 'NULL' }
  $v = $v -replace ',', '.'
  $n = 0.0
  $ok = [double]::TryParse($v, [Globalization.NumberStyles]::Float, [Globalization.CultureInfo]::InvariantCulture, [ref]$n)
  if (-not $ok) { return 'NULL' }
  return $n.ToString([Globalization.CultureInfo]::InvariantCulture)
}

function Invoke-MySqlFile([string]$mysqlExe, [string]$sqlFilePath) {
  if (!(Test-Path $sqlFilePath)) {
    throw "SQL file not found: $sqlFilePath"
  }
  $cmd = '"' + $mysqlExe + '" --default-character-set=utf8mb4 -uroot appcarte < "' + $sqlFilePath + '"'
  cmd /c $cmd | Out-Null
  if ($LASTEXITCODE -ne 0) {
    throw "MySQL execution failed for file: $sqlFilePath"
  }
}

function Split-List([string]$text) {
  if ([string]::IsNullOrWhiteSpace($text)) { return @() }
  return ($text -split '[,;|/]') |
    ForEach-Object { $_.Trim() } |
    Where-Object { $_ -ne '' } |
    Select-Object -Unique
}

function Get-CellValue([hashtable]$row, [int]$col) {
  if ($row.ContainsKey($col)) { return [string]$row[$col] }
  return ''
}

function Get-SheetPathByName([string]$sheetName, [string]$root) {
  $wbFile = Join-Path $root 'unz\xl\workbook.xml'
  $relFile = Join-Path $root 'unz\xl\_rels\workbook.xml.rels'

  $wb = New-Object System.Xml.XmlDocument
  $wb.Load($wbFile)
  $rel = New-Object System.Xml.XmlDocument
  $rel.Load($relFile)

  $nsWb = New-Object System.Xml.XmlNamespaceManager($wb.NameTable)
  $nsWb.AddNamespace('d', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main')
  $nsWb.AddNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships')
  $nsRel = New-Object System.Xml.XmlNamespaceManager($rel.NameTable)
  $nsRel.AddNamespace('p', 'http://schemas.openxmlformats.org/package/2006/relationships')

  $relMap = @{}
  foreach ($n in $rel.SelectNodes('//p:Relationship', $nsRel)) {
    $relMap[$n.Attributes['Id'].Value] = $n.Attributes['Target'].Value
  }

  foreach ($n in $wb.SelectNodes('//d:sheets/d:sheet', $nsWb)) {
    $name = $n.Attributes['name'].Value
    if ($name -ne $sheetName) { continue }
    $rid = $n.Attributes['r:id'].Value
    $target = $relMap[$rid]
    return Join-Path $root ('unz\\xl\\' + $target.Replace('/', '\\'))
  }

  return $null
}

function Read-SheetRows([string]$sheetPath, [array]$sharedStrings) {
  [xml]$sheetXml = Get-Content -Raw -Encoding UTF8 $sheetPath
  $rowNodes = $sheetXml.worksheet.sheetData.row
  if (!$rowNodes) { return @() }

  $table = @()
  foreach ($r in $rowNodes) {
    $rowMap = @{}
    foreach ($c in $r.c) {
      $idx = Get-ColIndex([string]$c.r)
      $t = [string]$c.t
      if ($t -eq 's') {
        $vIdx = [int]$c.InnerText
        $v = if ($vIdx -ge 0 -and $vIdx -lt $sharedStrings.Count) { [string]$sharedStrings[$vIdx] } else { '' }
      } else {
        $v = [string]$c.InnerText
      }
      $rowMap[$idx] = $v
    }
    $table += ,$rowMap
  }

  return $table
}

if (Test-Path $tmp) { Remove-Item $tmp -Recurse -Force }
New-Item -ItemType Directory -Path $tmp | Out-Null

Copy-Item $xlsx (Join-Path $tmp 'workbook.zip')
Expand-Archive (Join-Path $tmp 'workbook.zip') (Join-Path $tmp 'unz') -Force

[xml]$ssXml = Get-Content -Raw -Encoding UTF8 (Join-Path $tmp 'unz\xl\sharedStrings.xml')
$sharedStrings = @()
foreach ($si in $ssXml.sst.si) {
  if ($si.t) {
    $sharedStrings += [string]$si.t
  } elseif ($si.r) {
    $sharedStrings += (($si.r | ForEach-Object { $_.InnerText }) -join '')
  } else {
    $sharedStrings += ''
  }
}

$dataSheetPath = Get-SheetPathByName -sheetName 'data' -root $tmp
$clientsSheetPath = Get-SheetPathByName -sheetName 'Client' -root $tmp
$suppliersSheetPath = Get-SheetPathByName -sheetName 'Fournisseurs' -root $tmp

if ($null -eq $dataSheetPath) { throw 'Sheet "data" not found in workbook.' }
if ($null -eq $clientsSheetPath) { throw 'Sheet "Client" not found in workbook.' }
if ($null -eq $suppliersSheetPath) { throw 'Sheet "Fournisseurs" not found in workbook.' }

$dataRows = Read-SheetRows -sheetPath $dataSheetPath -sharedStrings $sharedStrings
$clientRows = Read-SheetRows -sheetPath $clientsSheetPath -sharedStrings $sharedStrings
$supplierRows = Read-SheetRows -sheetPath $suppliersSheetPath -sharedStrings $sharedStrings

$clientMap = @{}
$clientLogoByName = @{}

# data sheet: family/activity/icon + label/color + client/logo map
$activityRows = @()
$labelRows = @()
for ($i = 1; $i -lt $dataRows.Count; $i++) {
  $r = $dataRows[$i]
  $family = Clean-Text (Get-CellValue $r 1)
  $activity = Clean-Text (Get-CellValue $r 2)
  $iconUrl = Clean-Text (Get-CellValue $r 3)
  $labelName = Clean-Text (Get-CellValue $r 5)
  $labelColor = Clean-Text (Get-CellValue $r 6)
  $clientName = Clean-Text (Get-CellValue $r 8)
  $clientLogo = Clean-Text (Get-CellValue $r 9)

  if ($activity -ne '') {
    $activityRows += [pscustomobject]@{ name = $activity; family = $family; icon_url = $iconUrl }
  }

  if ($labelName -ne '') {
    $labelRows += [pscustomobject]@{ name = $labelName; color = $labelColor }
  }

  if ($clientName -ne '' -and $clientLogo -ne '') {
    $clientLogoByName[$clientName] = $clientLogo
  }
}

# client sheet: detailed client records
for ($i = 1; $i -lt $clientRows.Count; $i++) {
  $r = $clientRows[$i]
  $name = Clean-Text (Get-CellValue $r 1)
  if ($name -eq '') { continue }

  $clientMap[$name] = [pscustomobject]@{
    name = $name
    address = Clean-Text (Get-CellValue $r 2)
    postal = ((Clean-Text (Get-CellValue $r 3)) -replace '\\.0$', '')
    city = Clean-Text (Get-CellValue $r 4)
    phone = Clean-Text (Get-CellValue $r 5)
    email = Clean-Text (Get-CellValue $r 6)
    lundi = Clean-Text (Get-CellValue $r 7)
    mardi = Clean-Text (Get-CellValue $r 8)
    mercredi = Clean-Text (Get-CellValue $r 9)
    jeudi = Clean-Text (Get-CellValue $r 10)
    vendredi = Clean-Text (Get-CellValue $r 11)
    samedi = Clean-Text (Get-CellValue $r 12)
    dimanche = Clean-Text (Get-CellValue $r 13)
    website = Clean-Text (Get-CellValue $r 14)
    logo_url = Clean-Text (Get-CellValue $r 17)
    latitude = Clean-Text (Get-CellValue $r 18)
    longitude = Clean-Text (Get-CellValue $r 19)
  }
}

# suppliers sheet: supplier data + client linkage
$suppliers = @()
for ($i = 1; $i -lt $supplierRows.Count; $i++) {
  if ($Rows -gt 0 -and $suppliers.Count -ge $Rows) { break }

  $r = $supplierRows[$i]
  $name = Clean-Text (Get-CellValue $r 1)
  if ($name -eq '') { continue }

  $clientList = Split-List (Clean-Text (Get-CellValue $r 15))

  foreach ($cn in $clientList) {
    if (-not $clientMap.ContainsKey($cn)) {
      $clientMap[$cn] = [pscustomobject]@{
        name = $cn
        address = ''
        postal = ''
        city = ''
        phone = ''
        email = ''
        lundi = ''
        mardi = ''
        mercredi = ''
        jeudi = ''
        vendredi = ''
        samedi = ''
        dimanche = ''
        website = ''
        logo_url = if ($clientLogoByName.ContainsKey($cn)) { $clientLogoByName[$cn] } else { '' }
        latitude = ''
        longitude = ''
      }
    }
  }

  $suppliers += [pscustomobject]@{
    name = $name
    address = Clean-Text (Get-CellValue $r 2)
    postal = ((Clean-Text (Get-CellValue $r 3)) -replace '\\.0$', '')
    city = Clean-Text (Get-CellValue $r 4)
    activity = Clean-Text (Get-CellValue $r 5)
    supplier_type = Clean-Text (Get-CellValue $r 6)
    label = Clean-Text (Get-CellValue $r 7)
    email = Clean-Text (Get-CellValue $r 9)
    phone = Clean-Text (Get-CellValue $r 10)
    latitude = Clean-Text (Get-CellValue $r 16)
    longitude = Clean-Text (Get-CellValue $r 17)
    client_names = $clientList
  }
}

if ($suppliers.Count -eq 0) { throw 'No supplier rows extracted from Excel.' }

if ($ResetData) {
  $cleanupSql = @'
USE appcarte;
DELETE FROM supplier_labels;
DELETE FROM supplier_activities;
DELETE FROM client_suppliers;
DELETE FROM suppliers;
DELETE FROM activities;
DELETE FROM labels;
DELETE FROM clients;
'@
  $cleanupPath = Join-Path $tmp 'cleanup.sql'
  Set-Content -Path $cleanupPath -Value $cleanupSql -Encoding UTF8
  Invoke-MySqlFile -mysqlExe $mysql -sqlFilePath $cleanupPath
}

$sql = New-Object System.Collections.Generic.List[string]
$sql.Add('USE appcarte;')

foreach ($c in $clientMap.Values) {
  $n = Escape-Sql $c.name
  $a = Escape-Sql $c.address
  $pc = Escape-Sql $c.postal
  $city = Escape-Sql $c.city
  $phone = Escape-Sql $c.phone
  $email = Escape-Sql $c.email
  $lundi = Escape-Sql $c.lundi
  $mardi = Escape-Sql $c.mardi
  $mercredi = Escape-Sql $c.mercredi
  $jeudi = Escape-Sql $c.jeudi
  $vendredi = Escape-Sql $c.vendredi
  $samedi = Escape-Sql $c.samedi
  $dimanche = Escape-Sql $c.dimanche
  $web = Escape-Sql $c.website
  $logo = Escape-Sql $c.logo_url
  $latSql = To-SqlDecimalOrNull $c.latitude
  $lngSql = To-SqlDecimalOrNull $c.longitude
  $sql.Add("INSERT INTO clients (name, client_type, address, city, postal_code, country, latitude, longitude, phone, email, lundi, mardi, mercredi, jeudi, vendredi, samedi, dimanche, website, logo_url, is_active) VALUES ('$n', 'excel', '$a', '$city', '$pc', 'France', $latSql, $lngSql, '$phone', '$email', '$lundi', '$mardi', '$mercredi', '$jeudi', '$vendredi', '$samedi', '$dimanche', '$web', '$logo', 1) ON DUPLICATE KEY UPDATE address=VALUES(address), city=VALUES(city), postal_code=VALUES(postal_code), latitude=VALUES(latitude), longitude=VALUES(longitude), phone=VALUES(phone), email=VALUES(email), lundi=VALUES(lundi), mardi=VALUES(mardi), mercredi=VALUES(mercredi), jeudi=VALUES(jeudi), vendredi=VALUES(vendredi), samedi=VALUES(samedi), dimanche=VALUES(dimanche), website=VALUES(website), logo_url=VALUES(logo_url), is_active=1;")
}

foreach ($a in ($activityRows | Sort-Object -Property name -Unique)) {
  $name = Escape-Sql $a.name
  $family = Escape-Sql $a.family
  $icon = Escape-Sql $a.icon_url
  $sql.Add("INSERT INTO activities (name, family, icon_url, is_active) VALUES ('$name', '$family', '$icon', 1) ON DUPLICATE KEY UPDATE family=VALUES(family), icon_url=VALUES(icon_url), is_active=1;")
}

foreach ($l in ($labelRows | Sort-Object -Property name -Unique)) {
  $name = Escape-Sql $l.name
  $color = Escape-Sql $l.color
  $sql.Add("INSERT INTO labels (name, color, is_active) VALUES ('$name', '$color', 1) ON DUPLICATE KEY UPDATE color=VALUES(color), is_active=1;")
}

foreach ($s in $suppliers) {
  $name = Escape-Sql $s.name
  $norm = Escape-Sql (Normalize-Name $s.name)
  $address = Escape-Sql $s.address
  $postal = Escape-Sql $s.postal
  $city = Escape-Sql $s.city
  $activity = Escape-Sql $s.activity
  $supplierType = Escape-Sql $s.supplier_type
  $label = Escape-Sql $s.label
  $email = Escape-Sql $s.email
  $phone = Escape-Sql $s.phone

  $latSql = To-SqlDecimalOrNull $s.latitude
  $lngSql = To-SqlDecimalOrNull $s.longitude

  $sql.Add("INSERT INTO suppliers (name, normalized_name, address, city, postal_code, country, latitude, longitude, phone, email, supplier_type, activity_text, notes) VALUES ('$name', '$norm', '$address', '$city', '$postal', 'France', $latSql, $lngSql, '$phone', '$email', '$supplierType', '$activity', 'seed from excel');")
  $sql.Add('SET @sid = LAST_INSERT_ID();')

  foreach ($clientName in $s.client_names) {
    $cn = Escape-Sql $clientName
    $sql.Add("INSERT IGNORE INTO client_suppliers (client_id, supplier_id, source) SELECT id, @sid, 'seed' FROM clients WHERE name='$cn';")
  }

  if (-not [string]::IsNullOrWhiteSpace($s.activity)) {
    $activities = Split-List $s.activity
    foreach ($a in $activities) {
      $ea = Escape-Sql $a
      $sql.Add("INSERT INTO activities (name, family, is_active) VALUES ('$ea', '', 1) ON DUPLICATE KEY UPDATE is_active=1;")
      $sql.Add("INSERT IGNORE INTO supplier_activities (supplier_id, activity_id) SELECT @sid, id FROM activities WHERE name='$ea';")
    }
  }

  if (-not [string]::IsNullOrWhiteSpace($s.label)) {
    $labels = Split-List $s.label
    foreach ($l in $labels) {
      $el = Escape-Sql $l
      $sql.Add("INSERT INTO labels (name, color, is_active) VALUES ('$el', '', 1) ON DUPLICATE KEY UPDATE is_active=1;")
      $sql.Add("INSERT IGNORE INTO supplier_labels (supplier_id, label_id) SELECT @sid, id FROM labels WHERE name='$el';")
    }
  }
}

$seedPath = Join-Path $tmp 'seed.sql'
Set-Content -Path $seedPath -Value ($sql -join "`r`n") -Encoding UTF8
Invoke-MySqlFile -mysqlExe $mysql -sqlFilePath $seedPath

& $mysql --default-character-set=utf8mb4 -uroot -e "USE appcarte; SELECT 'clients' t, COUNT(*) c FROM clients UNION ALL SELECT 'suppliers', COUNT(*) FROM suppliers UNION ALL SELECT 'activities', COUNT(*) FROM activities UNION ALL SELECT 'labels', COUNT(*) FROM labels;"

Remove-Item $tmp -Recurse -Force
Write-Output ("Seed complete: $($suppliers.Count) suppliers, $($clientMap.Count) clients")

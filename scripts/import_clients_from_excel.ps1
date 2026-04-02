param(
  [string]$ProjectRoot = (Split-Path -Parent $PSScriptRoot),
  [string]$DbName = 'appcarte',
  [string]$MySqlPath = 'C:\wamp64\bin\mysql\mysql8.2.0\bin\mysql.exe'
)

$ErrorActionPreference = 'Stop'

$xlsx = Join-Path $ProjectRoot 'CarteFournisseur.xlsx'
$mysql = $MySqlPath
$tmp = Join-Path $ProjectRoot '_tmp_import_clients'

if (!(Test-Path $xlsx)) { throw "Excel file not found: $xlsx" }
if (!(Test-Path $mysql)) { throw "mysql.exe not found: $mysql" }

function Get-ColIndex([string]$cellRef) {
  $letters = ([regex]::Match($cellRef, '^[A-Z]+')).Value
  $sum = 0
  foreach ($ch in $letters.ToCharArray()) {
    $sum = ($sum * 26) + ([int][char]$ch - [int][char]'A' + 1)
  }
  return $sum
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
  $cmd = '"' + $mysqlExe + '" --default-character-set=utf8mb4 -uroot ' + $DbName + ' < "' + $sqlFilePath + '"'
  cmd /c $cmd | Out-Null
  if ($LASTEXITCODE -ne 0) {
    throw "MySQL execution failed for file: $sqlFilePath"
  }
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

if ($null -eq $dataSheetPath) { throw 'Sheet "data" not found in workbook.' }
if ($null -eq $clientsSheetPath) { throw 'Sheet "Client" not found in workbook.' }

$dataRows = Read-SheetRows -sheetPath $dataSheetPath -sharedStrings $sharedStrings
$clientRows = Read-SheetRows -sheetPath $clientsSheetPath -sharedStrings $sharedStrings

$clientLogoByName = @{}
for ($i = 1; $i -lt $dataRows.Count; $i++) {
  $r = $dataRows[$i]
  $clientName = Clean-Text (Get-CellValue $r 8)
  $clientLogo = Clean-Text (Get-CellValue $r 9)
  if ($clientName -ne '' -and $clientLogo -ne '') {
    $clientLogoByName[$clientName] = $clientLogo
  }
}

$clients = @()
for ($i = 1; $i -lt $clientRows.Count; $i++) {
  $r = $clientRows[$i]
  $name = Clean-Text (Get-CellValue $r 1)
  if ($name -eq '') { continue }

  $logoRaw = Clean-Text (Get-CellValue $r 17)
  if ($logoRaw -eq '' -and $clientLogoByName.ContainsKey($name)) {
    $logoRaw = [string]$clientLogoByName[$name]
  }

  $clients += [pscustomobject]@{
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
    logo_url = $logoRaw
    latitude = Clean-Text (Get-CellValue $r 18)
    longitude = Clean-Text (Get-CellValue $r 19)
  }
}

if ($clients.Count -eq 0) { throw 'No client rows extracted from Excel.' }

$sql = New-Object System.Collections.Generic.List[string]
$sql.Add("USE $DbName;")

foreach ($c in $clients) {
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

  $sql.Add("INSERT INTO clients (name, client_type, address, city, postal_code, country, latitude, longitude, phone, email, lundi, mardi, mercredi, jeudi, vendredi, samedi, dimanche, website, logo_url, is_active) VALUES ('$n', 'excel', '$a', '$city', '$pc', 'France', $latSql, $lngSql, '$phone', '$email', '$lundi', '$mardi', '$mercredi', '$jeudi', '$vendredi', '$samedi', '$dimanche', '$web', '$logo', 1);")
}

$seedPath = Join-Path $tmp 'import_clients.sql'
Set-Content -Path $seedPath -Value ($sql -join "`r`n") -Encoding UTF8
Invoke-MySqlFile -mysqlExe $mysql -sqlFilePath $seedPath

& $mysql --default-character-set=utf8mb4 -uroot -e "USE $DbName; SELECT COUNT(*) AS clients_count FROM clients; SELECT id, name, logo_url FROM clients ORDER BY name LIMIT 10;"

Remove-Item $tmp -Recurse -Force
Write-Output ("Clients import complete: $($clients.Count) rows")

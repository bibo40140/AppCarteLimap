param(
  [string]$ProjectRoot = "D:\Perso\Limap\AppCarteLimap"
)

$ErrorActionPreference = 'Stop'

$xlsx = Join-Path $ProjectRoot 'CarteFournisseur.xlsx'
$mysql = 'C:\wamp64\bin\mysql\mysql8.2.0\bin\mysql.exe'
$tmp = Join-Path $ProjectRoot '_tmp_reset_activities'

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

function Clean-Text([string]$text) {
  if ($null -eq $text) { return '' }
  $v = [string]$text
  if ($v -eq 'System.Xml.XmlElement') { return '' }
  return $v.Trim()
}

function Escape-Sql([string]$text) {
  if ($null -eq $text) { return '' }
  return $text.Replace('\\', '\\\\').Replace("'", "''")
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
if ($null -eq $dataSheetPath) { throw 'Sheet "data" not found in workbook.' }

$dataRows = Read-SheetRows -sheetPath $dataSheetPath -sharedStrings $sharedStrings

$activities = [ordered]@{}
for ($i = 1; $i -lt $dataRows.Count; $i++) {
  $r = $dataRows[$i]
  $family = Clean-Text (Get-CellValue $r 1)
  $activity = Clean-Text (Get-CellValue $r 2)
  $iconUrl = Clean-Text (Get-CellValue $r 3)

  if ($activity -eq '') { continue }

  if (-not $activities.Contains($activity)) {
    $activities[$activity] = [pscustomobject]@{
      family = $family
      icon_url = $iconUrl
    }
  }
}

if ($activities.Count -eq 0) {
  throw 'No activities found in sheet data.'
}

$sql = New-Object System.Collections.Generic.List[string]
$sql.Add('USE appcarte;')
$sql.Add('SET FOREIGN_KEY_CHECKS=0;')
$sql.Add('DELETE FROM supplier_activities;')
$sql.Add('TRUNCATE TABLE activities;')

foreach ($name in $activities.Keys) {
  $family = [string]$activities[$name].family
  $icon = [string]$activities[$name].icon_url
  $sql.Add("INSERT INTO activities (name, family, icon_url, is_active) VALUES ('$(Escape-Sql $name)', '$(Escape-Sql $family)', '$(Escape-Sql $icon)', 1);")
}

$sql.Add('SET FOREIGN_KEY_CHECKS=1;')
$sql.Add("SELECT COUNT(*) AS activities_count FROM activities;")
$sql.Add("SELECT id, family, name, icon_url FROM activities ORDER BY id;")

$sqlPath = Join-Path $tmp 'reset_activities.sql'
Set-Content -Path $sqlPath -Value ($sql -join "`r`n") -Encoding UTF8

$cmd = '"' + $mysql + '" --default-character-set=utf8mb4 -uroot < "' + $sqlPath + '"'
cmd /c $cmd
if ($LASTEXITCODE -ne 0) {
  throw 'MySQL execution failed for reset activities.'
}

Remove-Item $tmp -Recurse -Force
Write-Output ("Activities reset complete: $($activities.Count) rows")

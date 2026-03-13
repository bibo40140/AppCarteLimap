param(
  [ValidateSet('local','prod')]
  [string]$Mode = 'local'
)

$ErrorActionPreference = 'Stop'
$mysql = 'C:\wamp64\bin\mysql\mysql8.2.0\bin\mysql.exe'

if (!(Test-Path $mysql)) {
  throw "mysql.exe not found: $mysql"
}

if ($Mode -eq 'local') {
  $sql = @"
USE appcarte;
UPDATE clients SET logo_url=REPLACE(logo_url,'/Carte/assets/','/assets/') WHERE logo_url LIKE '/Carte/assets/%';
UPDATE activities SET icon_url=REPLACE(icon_url,'/Carte/assets/','/assets/') WHERE icon_url LIKE '/Carte/assets/%';
UPDATE suppliers SET logo_url=REPLACE(logo_url,'/Carte/assets/','/assets/') WHERE logo_url LIKE '/Carte/assets/%';
UPDATE suppliers SET photo_cover_url=REPLACE(photo_cover_url,'/Carte/assets/','/assets/') WHERE photo_cover_url LIKE '/Carte/assets/%';
UPDATE settings SET setting_value=REPLACE(setting_value,'/Carte/assets/','/assets/')
WHERE setting_key IN ('default_client_icon','default_producer_icon') AND setting_value LIKE '/Carte/assets/%';
SELECT 'clients_assets' AS check_name, COUNT(*) AS n FROM clients WHERE logo_url LIKE '/assets/%'
UNION ALL SELECT 'clients_carte_assets', COUNT(*) FROM clients WHERE logo_url LIKE '/Carte/assets/%'
UNION ALL SELECT 'activities_assets', COUNT(*) FROM activities WHERE icon_url LIKE '/assets/%'
UNION ALL SELECT 'activities_carte_assets', COUNT(*) FROM activities WHERE icon_url LIKE '/Carte/assets/%';
"@
} else {
  $sql = @"
USE appcarte;
UPDATE clients SET logo_url=REPLACE(logo_url,'/assets/','/Carte/assets/') WHERE logo_url LIKE '/assets/%';
UPDATE activities SET icon_url=REPLACE(icon_url,'/assets/','/Carte/assets/') WHERE icon_url LIKE '/assets/%';
UPDATE suppliers SET logo_url=REPLACE(logo_url,'/assets/','/Carte/assets/') WHERE logo_url LIKE '/assets/%';
UPDATE suppliers SET photo_cover_url=REPLACE(photo_cover_url,'/assets/','/Carte/assets/') WHERE photo_cover_url LIKE '/assets/%';
UPDATE settings SET setting_value=REPLACE(setting_value,'/assets/','/Carte/assets/')
WHERE setting_key IN ('default_client_icon','default_producer_icon') AND setting_value LIKE '/assets/%';
SELECT 'clients_assets' AS check_name, COUNT(*) AS n FROM clients WHERE logo_url LIKE '/assets/%'
UNION ALL SELECT 'clients_carte_assets', COUNT(*) FROM clients WHERE logo_url LIKE '/Carte/assets/%'
UNION ALL SELECT 'activities_assets', COUNT(*) FROM activities WHERE icon_url LIKE '/assets/%'
UNION ALL SELECT 'activities_carte_assets', COUNT(*) FROM activities WHERE icon_url LIKE '/Carte/assets/%';
"@
}

$tmpSql = Join-Path $env:TEMP ('switch_assets_mode_' + [Guid]::NewGuid().ToString('N') + '.sql')
Set-Content -Path $tmpSql -Value $sql -Encoding UTF8

$cmd = '"' + $mysql + '" --default-character-set=utf8mb4 -uroot < "' + $tmpSql + '"'
cmd /c $cmd
$exitCode = $LASTEXITCODE

Remove-Item $tmpSql -Force -ErrorAction SilentlyContinue
if ($exitCode -ne 0) {
  throw "MySQL command failed with exit code $exitCode"
}

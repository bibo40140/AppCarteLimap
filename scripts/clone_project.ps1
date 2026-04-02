param(
	[string]$SourceRoot = (Split-Path -Parent $PSScriptRoot),
	[string[]]$TargetNames = @('AppCarteCoopaz', 'AppCarteInterkoop'),
	[switch]$Force
)

$ErrorActionPreference = 'Stop'

function Get-DbNameFromFolder([string]$folderName) {
	$slug = ($folderName -replace '[^a-zA-Z0-9]', '').ToLowerInvariant()
	if ([string]::IsNullOrWhiteSpace($slug)) {
		throw "Cannot derive DbName from folder: $folderName"
	}
	return $slug
}

function Get-SessionNameFromFolder([string]$folderName) {
	$slug = ($folderName -replace '[^a-zA-Z0-9]', '').ToLowerInvariant()
	return "map_admin_session_$slug"
}

function Get-AppDisplayName([string]$folderName) {
	$base = $folderName
	if ($base -match '^AppCarte(.+)$') {
		return 'AppCarte ' + $Matches[1]
	}
	return $base
}

function Replace-InFile([string]$filePath, [string]$search, [string]$replace) {
	$content = Get-Content -Raw -Encoding UTF8 $filePath
	if ($content.Contains($search)) {
		$updated = $content.Replace($search, $replace)
		Set-Content -Path $filePath -Value $updated -Encoding UTF8
	}
}

if (!(Test-Path $SourceRoot)) {
	throw "SourceRoot not found: $SourceRoot"
}

$parentRoot = Split-Path -Parent $SourceRoot

foreach ($targetName in $TargetNames) {
	$targetRoot = Join-Path $parentRoot $targetName

	if (Test-Path $targetRoot) {
		if ($Force) {
			Remove-Item $targetRoot -Recurse -Force
		} else {
			throw "Target already exists: $targetRoot (use -Force to overwrite)"
		}
	}

	Copy-Item -Path $SourceRoot -Destination $targetRoot -Recurse -Force

	$dbName = Get-DbNameFromFolder $targetName
	$sessionName = Get-SessionNameFromFolder $targetName
	$displayName = Get-AppDisplayName $targetName

	$configPath = Join-Path $targetRoot 'api\config.php'
	$seedPath = Join-Path $targetRoot 'scripts\seed_from_excel.ps1'
	$importClientsPath = Join-Path $targetRoot 'scripts\import_clients_from_excel.ps1'
	$resetActivitiesPath = Join-Path $targetRoot 'scripts\reset_activities_from_excel_data.ps1'
	$switchAssetsPath = Join-Path $targetRoot 'scripts\switch_assets_mode.ps1'

	Replace-InFile $configPath "'name' => getenv('MAP_DB_NAME') ?: 'appcarte'," "'name' => getenv('MAP_DB_NAME') ?: '$dbName',"
	Replace-InFile $configPath "'session_name' => 'map_admin_session'," "'session_name' => '$sessionName',"
	Replace-InFile $configPath "'from_name' => getenv('MAP_NOTIFY_FROM_NAME') ?: 'AppCarte Limap'," "'from_name' => getenv('MAP_NOTIFY_FROM_NAME') ?: '$displayName',"

	Replace-InFile $seedPath "[string]`$DbName = 'appcarte'," "[string]`$DbName = '$dbName',"
	Replace-InFile $importClientsPath "[string]`$DbName = 'appcarte'," "[string]`$DbName = '$dbName',"
	Replace-InFile $resetActivitiesPath "[string]`$DbName = 'appcarte'," "[string]`$DbName = '$dbName',"
	Replace-InFile $switchAssetsPath "[string]`$DbName = 'appcarte'," "[string]`$DbName = '$dbName',"

	Write-Output "Cloned project created: $targetRoot"
	Write-Output "  DbName default: $dbName"
	Write-Output "  Session name: $sessionName"
}

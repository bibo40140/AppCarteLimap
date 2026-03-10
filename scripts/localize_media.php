<?php

declare(strict_types=1);

require __DIR__ . '/../api/db.php';

$pdo = get_db();
$root = realpath(__DIR__ . '/..');
if ($root === false) {
    fwrite(STDERR, "Project root not found" . PHP_EOL);
    exit(1);
}

$activityDirFs = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'activity-icons';
$clientDirFs = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'client-logos';
$activityDirWeb = '/assets/activity-icons';
$clientDirWeb = '/assets/client-logos';

ensure_dir($activityDirFs);
ensure_dir($clientDirFs);

$stats = [
    'activities_total' => 0,
    'activities_updated' => 0,
    'activities_skipped' => 0,
    'clients_total' => 0,
    'clients_updated' => 0,
    'clients_skipped' => 0,
];

$upActivity = $pdo->prepare('UPDATE activities SET icon_url=:url WHERE id=:id');
$rows = $pdo->query('SELECT id, name, icon_url FROM activities')->fetchAll();
$stats['activities_total'] = count($rows);
foreach ($rows as $row) {
    $url = trim((string)($row['icon_url'] ?? ''));
    if ($url === '' || !is_remote_url($url)) {
        $stats['activities_skipped']++;
        continue;
    }

    $safeName = slugify((string)$row['name']);
    $saved = download_to_local($url, $activityDirFs, $safeName);
    if ($saved === null) {
        $stats['activities_skipped']++;
        continue;
    }

    $webPath = $activityDirWeb . '/' . basename($saved);
    $upActivity->execute([':url' => $webPath, ':id' => (int)$row['id']]);
    $stats['activities_updated']++;
}

$upClient = $pdo->prepare('UPDATE clients SET logo_url=:url WHERE id=:id');
$rows = $pdo->query('SELECT id, name, logo_url FROM clients')->fetchAll();
$stats['clients_total'] = count($rows);
foreach ($rows as $row) {
    $url = trim((string)($row['logo_url'] ?? ''));
    if ($url === '' || !is_remote_url($url)) {
        $stats['clients_skipped']++;
        continue;
    }

    $safeName = slugify((string)$row['name']);
    $saved = download_to_local($url, $clientDirFs, $safeName);
    if ($saved === null) {
        $stats['clients_skipped']++;
        continue;
    }

    $webPath = $clientDirWeb . '/' . basename($saved);
    $upClient->execute([':url' => $webPath, ':id' => (int)$row['id']]);
    $stats['clients_updated']++;
}

foreach ($stats as $k => $v) {
    echo $k . '=' . $v . PHP_EOL;
}

echo "Done" . PHP_EOL;

function ensure_dir(string $path): void
{
    if (is_dir($path)) {
        return;
    }
    if (!mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Cannot create directory: ' . $path);
    }
}

function is_remote_url(string $url): bool
{
    return preg_match('#^https?://#i', $url) === 1;
}

function slugify(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return 'file';
    }
    $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
    $name = strtolower($name);
    $name = preg_replace('/[^a-z0-9]+/', '-', $name) ?: 'file';
    return trim($name, '-') ?: 'file';
}

function download_to_local(string $url, string $targetDir, string $baseName): ?string
{
    $content = http_get($url);
    if ($content === null || $content === '') {
        return null;
    }

    $ext = detect_extension($url, $content);
    $hash = substr(sha1($url), 0, 10);
    $fileName = $baseName . '-' . $hash . '.' . $ext;
    $dest = $targetDir . DIRECTORY_SEPARATOR . $fileName;

    if (file_put_contents($dest, $content) === false) {
        return null;
    }

    return $dest;
}

function http_get(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'AppCarteLimap/1.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!is_string($body) || $code < 200 || $code >= 400) {
            return null;
        }
        return $body;
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 15,
            'header' => "User-Agent: AppCarteLimap/1.0\r\n",
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    return is_string($body) ? $body : null;
}

function detect_extension(string $url, string $content): string
{
    $pathExt = strtolower((string)pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
    $allowed = ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'];
    if (in_array($pathExt, $allowed, true)) {
        return $pathExt;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->buffer($content);
    $map = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
    ];

    return $map[$mime] ?? 'bin';
}

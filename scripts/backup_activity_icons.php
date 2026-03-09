<?php

require __DIR__ . '/../api/db.php';

$pdo = get_db();
$rows = $pdo->query('SELECT id, name, family, icon_url FROM activities ORDER BY family, name')->fetchAll();

$snapshot = [
    'generated_at' => date('c'),
    'count' => count($rows),
    'items' => $rows,
];

$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
    fwrite(STDERR, "Impossible de créer le dossier backups" . PHP_EOL);
    exit(1);
}

$timestamp = date('Ymd_His');
$fileTs = $backupDir . '/activity-icons-' . $timestamp . '.json';
$fileLatest = $backupDir . '/activity-icons-latest.json';

$json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    fwrite(STDERR, "Impossible de sérialiser le backup" . PHP_EOL);
    exit(1);
}

file_put_contents($fileTs, $json);
file_put_contents($fileLatest, $json);

echo "Backup écrit: " . $fileTs . PHP_EOL;
echo "Backup latest: " . $fileLatest . PHP_EOL;
echo "Activités sauvegardées: " . count($rows) . PHP_EOL;

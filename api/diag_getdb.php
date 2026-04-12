<?php

header('Content-Type: application/json; charset=utf-8');

$out = [
    'ok' => true,
    'php_version' => PHP_VERSION,
    'steps' => [],
];

function step(array &$out, string $name, bool $ok, array $data = []): void
{
    $out['steps'][] = [
        'name' => $name,
        'ok' => $ok,
        'data' => $data,
    ];
    if (!$ok) {
        $out['ok'] = false;
    }
}

try {
    require __DIR__ . '/db.php';
    step($out, 'require db.php', true);
} catch (Throwable $e) {
    step($out, 'require db.php', false, ['error' => $e->getMessage()]);
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $pdo = get_db();
    $row = $pdo->query('SELECT DATABASE() AS db, NOW() AS now_ts')->fetch(PDO::FETCH_ASSOC);
    step($out, 'get_db + query', true, [
        'db' => $row['db'] ?? null,
        'now' => $row['now_ts'] ?? null,
    ]);
} catch (Throwable $e) {
    step($out, 'get_db + query', false, ['error' => $e->getMessage()]);
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

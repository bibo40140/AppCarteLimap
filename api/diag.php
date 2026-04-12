<?php

header('Content-Type: application/json; charset=utf-8');

$report = [
    'ok' => true,
    'php_version' => PHP_VERSION,
    'script' => __FILE__,
    'checks' => [],
];

function add_check(array &$report, string $name, bool $ok, array $data = []): void
{
    $report['checks'][] = [
        'name' => $name,
        'ok' => $ok,
        'data' => $data,
    ];
    if (!$ok) {
        $report['ok'] = false;
    }
}

try {
    $configPath = __DIR__ . '/config.local.php';
    $configExists = is_file($configPath);
    add_check($report, 'config.local.php exists', $configExists, ['path' => $configPath]);

    if (!$configExists) {
        echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    $config = require $configPath;
    $db = is_array($config) && isset($config['db']) && is_array($config['db']) ? $config['db'] : [];

    $host = (string)($db['host'] ?? '');
    $port = (string)($db['port'] ?? '3306');
    $name = (string)($db['name'] ?? '');
    $user = (string)($db['user'] ?? '');
    $pass = (string)($db['pass'] ?? '');

    add_check($report, 'db config fields', $host !== '' && $name !== '' && $user !== '', [
        'host' => $host,
        'port' => $port,
        'name' => $name,
        'user' => $user,
        'has_password' => $pass !== '',
    ]);

    $resolved = gethostbyname($host);
    $dnsOk = $host !== '' && $resolved !== $host;
    add_check($report, 'host DNS resolve', $dnsOk, [
        'host' => $host,
        'resolved' => $resolved,
    ]);

    if ($host !== '' && $name !== '' && $user !== '') {
        try {
            $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $row = $pdo->query('SELECT DATABASE() AS db, NOW() AS now_ts')->fetch();
            add_check($report, 'pdo connect + query', true, [
                'db' => $row['db'] ?? null,
                'now' => $row['now_ts'] ?? null,
            ]);
        } catch (Throwable $e) {
            add_check($report, 'pdo connect + query', false, [
                'error' => $e->getMessage(),
            ]);
        }
    }
} catch (Throwable $e) {
    $report['ok'] = false;
    $report['fatal'] = $e->getMessage();
}

echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

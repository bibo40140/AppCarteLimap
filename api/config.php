<?php

if (!function_exists('app_config_merge')) {
    function app_config_merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = app_config_merge($base[$key], $value);
                continue;
            }
            $base[$key] = $value;
        }

        return $base;
    }
}

$appEnv = strtolower(trim((string)(getenv('MAP_ENV') ?: 'local')));

$config = [
    'app_env' => $appEnv,
    'db' => [
        'host' => getenv('MAP_DB_HOST') ?: 'localhost',
        'port' => getenv('MAP_DB_PORT') ?: '3306',
        'name' => getenv('MAP_DB_NAME') ?: 'appcarte',
        'user' => getenv('MAP_DB_USER') ?: 'root',
        'pass' => getenv('MAP_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'admin' => [
        'username' => getenv('MAP_ADMIN_USER') ?: 'admin',
        'password_hash' => getenv('MAP_ADMIN_PASS_HASH') ?: '',
    ],
    'session_name' => 'map_admin_session',
    'notifications' => [
        // Comma/semicolon-separated list of recipient emails.
        'admin_emails' => getenv('MAP_NOTIFY_EMAILS') ?: '',
        // Optional From header. Leave empty to use PHP default mail sender.
        'from_email' => getenv('MAP_NOTIFY_FROM') ?: '',
        'from_name' => getenv('MAP_NOTIFY_FROM_NAME') ?: 'AppCarte Limap',
        // SMTP config (optional). If host is set, notifications use SMTP directly.
        'smtp_host' => getenv('MAP_SMTP_HOST') ?: '',
        'smtp_port' => getenv('MAP_SMTP_PORT') ?: '',
        // Supported values: '', 'tls', 'ssl'
        'smtp_encryption' => getenv('MAP_SMTP_ENCRYPTION') ?: 'tls',
        'smtp_username' => getenv('MAP_SMTP_USERNAME') ?: '',
        'smtp_password' => getenv('MAP_SMTP_PASSWORD') ?: '',
    ],
    'wordpress_sync' => [
        'enabled' => (getenv('MAP_WP_SYNC_ENABLED') ?: '0') === '1',
        'endpoint' => getenv('MAP_WP_SYNC_ENDPOINT') ?: '',
        'secret' => getenv('MAP_WP_SYNC_SECRET') ?: '',
        'timeout_seconds' => (int)(getenv('MAP_WP_SYNC_TIMEOUT') ?: 8),
    ],
];

$localConfigPath = __DIR__ . '/config.local.php';
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (is_array($localConfig)) {
        $config = app_config_merge($config, $localConfig);
    }
}

$appEnv = strtolower(trim((string)($config['app_env'] ?? $appEnv)));

if ($appEnv === 'production') {
    if (trim((string)$config['db']['pass']) === '') {
        throw new RuntimeException('Production config error: MAP_DB_PASS must be set.');
    }
    if (trim((string)$config['admin']['password_hash']) === '') {
        throw new RuntimeException('Production config error: MAP_ADMIN_PASS_HASH must be set.');
    }
}

unset($config['app_env']);

return $config;
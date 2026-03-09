<?php

return [
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
        'password' => getenv('MAP_ADMIN_PASS') ?: 'admin',
    ],
    'session_name' => 'map_admin_session',
];
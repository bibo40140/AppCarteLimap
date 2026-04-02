<?php

require __DIR__ . '/../api/db.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$username = $argv[1] ?? 'fabien';
$password = $argv[2] ?? 'ChangeMeNow!2026';
$clientId = isset($argv[3]) ? (int)$argv[3] : 1;
$role = $argv[4] ?? 'client_manager';

if ($clientId <= 0) {
    fwrite(STDERR, "Invalid client_id.\n");
    exit(1);
}

try {
    $pdo = get_db();

    $sql = 'INSERT INTO client_users (client_id, username, password_hash, role, is_active)
            VALUES (:client_id, :username, :password_hash, :role, 1)
            ON DUPLICATE KEY UPDATE
              client_id = VALUES(client_id),
              password_hash = VALUES(password_hash),
              role = VALUES(role),
              is_active = 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':client_id' => $clientId,
        ':username' => $username,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => $role,
    ]);

    echo "OK user={$username} client_id={$clientId} role={$role}\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

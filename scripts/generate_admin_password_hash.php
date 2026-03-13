<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this script from CLI only." . PHP_EOL);
    exit(1);
}

$password = $argv[1] ?? '';
if ($password === '') {
    fwrite(STDERR, "Usage: php scripts/generate_admin_password_hash.php \"YourStrongPassword\"" . PHP_EOL);
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
if (!is_string($hash) || $hash === '') {
    fwrite(STDERR, "Unable to generate hash." . PHP_EOL);
    exit(1);
}

echo $hash . PHP_EOL;

<?php

function get_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $db = $config['db'];
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['name'],
        $db['charset']
    );

    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    ensure_schema_upgrades($pdo);

    return $pdo;
}

function ensure_schema_upgrades(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $schemaName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    $stmt = $pdo->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema_name AND TABLE_NAME = :table_name');
    $stmt->execute([
        ':schema_name' => $schemaName,
        ':table_name' => 'clients',
    ]);
    $columns = array_fill_keys(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)), true);

    $alters = [];
    if (!isset($columns['facebook_url'])) $alters[] = 'ADD COLUMN facebook_url VARCHAR(255) NULL AFTER website';
    if (!isset($columns['instagram_url'])) $alters[] = 'ADD COLUMN instagram_url VARCHAR(255) NULL AFTER facebook_url';
    if (!isset($columns['linkedin_url'])) $alters[] = 'ADD COLUMN linkedin_url VARCHAR(255) NULL AFTER instagram_url';
    if (!isset($columns['photo_cover_url'])) $alters[] = 'ADD COLUMN photo_cover_url VARCHAR(255) NULL AFTER logo_url';
    if (!isset($columns['slug'])) $alters[] = 'ADD COLUMN slug VARCHAR(190) NULL AFTER photo_cover_url';
    if (!isset($columns['description_short'])) $alters[] = 'ADD COLUMN description_short TEXT NULL AFTER slug';
    if (!isset($columns['description_long'])) $alters[] = 'ADD COLUMN description_long TEXT NULL AFTER description_short';
    if (!isset($columns['is_public'])) $alters[] = 'ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 1 AFTER description_long';
    if (!isset($columns['public_updated_at'])) $alters[] = 'ADD COLUMN public_updated_at DATETIME NULL AFTER is_public';

    if ($alters) {
        $pdo->exec('ALTER TABLE clients ' . implode(', ', $alters));
    }

    $stmt->execute([
        ':schema_name' => $schemaName,
        ':table_name' => 'suppliers',
    ]);
    $supplierColumns = array_fill_keys(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)), true);

    $supplierAlters = [];
    if (!isset($supplierColumns['facebook_url'])) $supplierAlters[] = 'ADD COLUMN facebook_url VARCHAR(255) NULL AFTER website';
    if (!isset($supplierColumns['instagram_url'])) $supplierAlters[] = 'ADD COLUMN instagram_url VARCHAR(255) NULL AFTER facebook_url';
    if (!isset($supplierColumns['linkedin_url'])) $supplierAlters[] = 'ADD COLUMN linkedin_url VARCHAR(255) NULL AFTER instagram_url';
    if (!isset($supplierColumns['logo_url'])) $supplierAlters[] = 'ADD COLUMN logo_url VARCHAR(255) NULL AFTER linkedin_url';
    if (!isset($supplierColumns['photo_cover_url'])) $supplierAlters[] = 'ADD COLUMN photo_cover_url VARCHAR(255) NULL AFTER logo_url';
    if (!isset($supplierColumns['slug'])) $supplierAlters[] = 'ADD COLUMN slug VARCHAR(190) NULL AFTER photo_cover_url';
    if (!isset($supplierColumns['description_short'])) $supplierAlters[] = 'ADD COLUMN description_short TEXT NULL AFTER slug';
    if (!isset($supplierColumns['description_long'])) $supplierAlters[] = 'ADD COLUMN description_long TEXT NULL AFTER description_short';
    if (!isset($supplierColumns['is_public'])) $supplierAlters[] = 'ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 1 AFTER description_long';
    if (!isset($supplierColumns['public_updated_at'])) $supplierAlters[] = 'ADD COLUMN public_updated_at DATETIME NULL AFTER is_public';

    if ($supplierAlters) {
        $pdo->exec('ALTER TABLE suppliers ' . implode(', ', $supplierAlters));
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS client_supplier_creation_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            requested_by_user_id INT NOT NULL,
            name VARCHAR(190) NOT NULL,
            supplier_type VARCHAR(120) NULL,
            activity_text VARCHAR(255) NULL,
            labels_text VARCHAR(255) NULL,
            address VARCHAR(255) NULL,
            city VARCHAR(120) NULL,
            postal_code VARCHAR(30) NULL,
            country VARCHAR(120) NULL,
            phone VARCHAR(60) NULL,
            email VARCHAR(190) NULL,
            website VARCHAR(255) NULL,
            notes TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            approved_supplier_id INT NULL,
            reviewed_by_admin VARCHAR(120) NULL,
            reviewed_at TIMESTAMP NULL,
            review_note TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_client_supplier_creation_requests_client (client_id),
            INDEX idx_client_supplier_creation_requests_status (status),
            INDEX idx_client_supplier_creation_requests_approved_supplier (approved_supplier_id),
            CONSTRAINT fk_client_supplier_creation_requests_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            CONSTRAINT fk_client_supplier_creation_requests_user FOREIGN KEY (requested_by_user_id) REFERENCES client_users(id) ON DELETE CASCADE,
            CONSTRAINT fk_client_supplier_creation_requests_supplier FOREIGN KEY (approved_supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(120) NOT NULL,
            email VARCHAR(190) NULL,
            password_hash VARCHAR(255) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_login_at TIMESTAMP NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_admin_users_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $stmt->execute([
        ':schema_name' => $schemaName,
        ':table_name' => 'client_users',
    ]);
    $clientUserColumns = array_fill_keys(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)), true);
    if (!isset($clientUserColumns['email'])) {
        $pdo->exec('ALTER TABLE client_users ADD COLUMN email VARCHAR(190) NULL AFTER username');
    }

    $stmt->execute([
        ':schema_name' => $schemaName,
        ':table_name' => 'admin_users',
    ]);
    $adminUserColumns = array_fill_keys(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)), true);
    if (!isset($adminUserColumns['email'])) {
        $pdo->exec('ALTER TABLE admin_users ADD COLUMN email VARCHAR(190) NULL AFTER username');
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_type VARCHAR(20) NOT NULL,
            user_id INT NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_password_reset_lookup (user_type, user_id),
            INDEX idx_password_reset_expires (expires_at),
            UNIQUE KEY uq_password_reset_token_hash (token_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS password_reset_audit (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_type VARCHAR(20) NOT NULL,
            user_id INT NOT NULL,
            username VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            status VARCHAR(20) NOT NULL,
            error_message TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_password_reset_audit_created (created_at),
            INDEX idx_password_reset_audit_user (user_type, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS client_supplier_link_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            requested_by_user_id INT NOT NULL,
            supplier_id INT NOT NULL,
            note TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            reviewed_by_admin VARCHAR(120) NULL,
            reviewed_at TIMESTAMP NULL,
            review_note TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_client_supplier_link_requests_client (client_id),
            INDEX idx_client_supplier_link_requests_supplier (supplier_id),
            INDEX idx_client_supplier_link_requests_status (status),
            CONSTRAINT fk_client_supplier_link_requests_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            CONSTRAINT fk_client_supplier_link_requests_user FOREIGN KEY (requested_by_user_id) REFERENCES client_users(id) ON DELETE CASCADE,
            CONSTRAINT fk_client_supplier_link_requests_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $done = true;
}
<?php

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$pdo = get_db();

try {
    switch ($action) {
        case 'auth/login':
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            login($pdo);
            break;

        case 'auth/logout':
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            logout();
            break;

        case 'auth/me':
            auth_me();
            break;

        case 'admin/bootstrap':
            require_admin();
            admin_bootstrap($pdo);
            break;

        case 'admin/client/save':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_client($pdo);
            break;

        case 'admin/client-user/save':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_client_user($pdo);
            break;

        case 'admin/client-user/toggle-active':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            toggle_client_user_active($pdo);
            break;

        case 'admin/client-user/reset-password':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            reset_client_user_password($pdo);
            break;

        case 'admin/upload/client-logo':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            upload_client_logo();
            break;

        case 'admin/upload/activity-icon':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            upload_activity_icon();
            break;

        case 'admin/settings/save':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_settings($pdo);
            break;

        case 'admin/geocode':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            geocode_address_admin();
            break;

        case 'admin/type/save':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_supplier_type($pdo);
            break;

        case 'admin/type/delete':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            delete_supplier_type($pdo);
            break;

        case 'admin/activity/save':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_activity($pdo);
            break;

        case 'admin/activity/delete':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            delete_activity($pdo);
            break;

        case 'admin/label/save':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_label($pdo);
            break;

        case 'admin/label/delete':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            delete_label($pdo);
            break;

        case 'admin/supplier/save':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_supplier($pdo, 'manual');
            break;

        case 'admin/supplier/delete':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            delete_supplier($pdo);
            break;

        case 'admin/import/preview':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            preview_import($pdo);
            break;

        case 'admin/import/commit':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            commit_import($pdo);
            break;

        case 'client/bootstrap':
            require_client_or_admin();
            client_bootstrap($pdo);
            break;

        case 'client/supplier/profile/save':
            require_client_or_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_client_supplier_profile($pdo);
            break;

        case 'client/change-request/save':
            require_client_or_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_supplier_change_request($pdo);
            break;

        case 'client/change-request/list':
            require_client_or_admin();
            list_supplier_change_requests_for_client($pdo);
            break;

        case 'admin/change-request/list':
            require_admin();
            list_supplier_change_requests_for_admin($pdo);
            break;

        case 'admin/change-request/review':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            review_supplier_change_request($pdo);
            break;

        case 'admin/change-request/review-bulk':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            review_supplier_change_requests_bulk($pdo);
            break;

        case 'map-data':
            map_data($pdo);
            break;

        default:
            json_response(['ok' => false, 'error' => 'Action inconnue'], 404);
    }
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}

function login(PDO $pdo): void
{
    $input = get_json_input();
    $username = trim((string)($input['username'] ?? ''));
    $password = (string)($input['password'] ?? '');

    if ($username === '' || $password === '') {
        json_response(['ok' => false, 'error' => 'Identifiants invalides'], 401);
    }

    $config = require __DIR__ . '/config.php';
    if ($username === $config['admin']['username'] && $password === $config['admin']['password']) {
        start_app_session();
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_username'] = $username;
        unset($_SESSION['is_client_user'], $_SESSION['client_user_id'], $_SESSION['client_id'], $_SESSION['client_role']);
        json_response(['ok' => true, 'username' => $username, 'role' => 'admin']);
    }

    $stmt = $pdo->prepare('SELECT cu.id, cu.client_id, cu.username, cu.password_hash, cu.role, cu.is_active, c.name AS client_name FROM client_users cu JOIN clients c ON c.id = cu.client_id WHERE cu.username=:username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch();

    if (!$row || (int)$row['is_active'] !== 1 || !password_verify($password, (string)$row['password_hash'])) {
        json_response(['ok' => false, 'error' => 'Identifiants invalides'], 401);
    }

    $pdo->prepare('UPDATE client_users SET last_login_at=NOW() WHERE id=:id')->execute([':id' => (int)$row['id']]);

    start_app_session();
    $_SESSION['is_admin'] = false;
    $_SESSION['is_client_user'] = true;
    $_SESSION['client_user_id'] = (int)$row['id'];
    $_SESSION['client_id'] = (int)$row['client_id'];
    $_SESSION['client_role'] = (string)$row['role'];
    $_SESSION['client_username'] = (string)$row['username'];
    $_SESSION['client_name'] = (string)$row['client_name'];
    unset($_SESSION['admin_username']);

    json_response([
        'ok' => true,
        'username' => (string)$row['username'],
        'role' => (string)$row['role'],
        'client_id' => (int)$row['client_id'],
        'client_name' => (string)$row['client_name'],
    ]);
}

function logout(): void
{
    start_app_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    json_response(['ok' => true]);
}

function auth_me(): void
{
    start_app_session();
    json_response([
        'ok' => true,
        'is_admin' => !empty($_SESSION['is_admin']),
        'username' => $_SESSION['admin_username'] ?? null,
        'is_client_user' => !empty($_SESSION['is_client_user']),
        'client_user_id' => isset($_SESSION['client_user_id']) ? (int)$_SESSION['client_user_id'] : null,
        'client_id' => isset($_SESSION['client_id']) ? (int)$_SESSION['client_id'] : null,
        'client_role' => $_SESSION['client_role'] ?? null,
        'client_username' => $_SESSION['client_username'] ?? null,
        'client_name' => $_SESSION['client_name'] ?? null,
    ]);
}

function admin_bootstrap(PDO $pdo): void
{
    $clients = $pdo->query('SELECT * FROM clients ORDER BY name')->fetchAll();
    $clientUsers = $pdo->query('SELECT cu.id, cu.client_id, cu.username, cu.role, cu.is_active, cu.last_login_at, cu.created_at, c.name AS client_name FROM client_users cu JOIN clients c ON c.id = cu.client_id ORDER BY c.name, cu.username')->fetchAll();
    $activities = $pdo->query('SELECT * FROM activities ORDER BY family, name')->fetchAll();
    $labels = $pdo->query('SELECT * FROM labels ORDER BY name')->fetchAll();
    $supplierTypes = $pdo->query('SELECT * FROM supplier_types ORDER BY name')->fetchAll();

    $suppliers = $pdo->query(
        "SELECT s.*,
                (
                    SELECT GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ')
                    FROM client_suppliers cs
                    JOIN clients c ON c.id = cs.client_id
                    WHERE cs.supplier_id = s.id
                ) AS clients,
                (
                    SELECT GROUP_CONCAT(DISTINCT cs.client_id ORDER BY cs.client_id SEPARATOR ',')
                    FROM client_suppliers cs
                    WHERE cs.supplier_id = s.id
                ) AS client_ids,
                (
                    SELECT GROUP_CONCAT(DISTINCT l.name ORDER BY l.name SEPARATOR '; ')
                    FROM supplier_labels sl
                    JOIN labels l ON l.id = sl.label_id
                    WHERE sl.supplier_id = s.id
                ) AS labels
         FROM suppliers s
         ORDER BY s.name"
    )->fetchAll();

    $clients = array_map(function (array $client) {
        $client['phone'] = format_phone($client['phone'] ?? '');
        return $client;
    }, $clients);

    $suppliers = array_map(function (array $supplier) {
        $supplier['phone'] = format_phone($supplier['phone'] ?? '');
        return $supplier;
    }, $suppliers);

    $settingsRows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    $settings = [];
    foreach ($settingsRows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    json_response([
        'ok' => true,
        'clients' => $clients,
        'client_users' => $clientUsers,
        'activities' => $activities,
        'labels' => $labels,
        'supplier_types' => $supplierTypes,
        'suppliers' => $suppliers,
        'settings' => $settings,
    ]);
}

function save_client(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $name = trim((string)($input['name'] ?? ''));
    if ($name === '') {
        json_response(['ok' => false, 'error' => 'Nom client requis'], 422);
    }

    $payload = [
        ':name' => $name,
        ':client_type' => trim((string)($input['client_type'] ?? '')),
        ':address' => trim((string)($input['address'] ?? '')),
        ':city' => trim((string)($input['city'] ?? '')),
        ':postal_code' => trim((string)($input['postal_code'] ?? '')),
        ':country' => trim((string)($input['country'] ?? '')),
        ':latitude' => ($input['latitude'] ?? '') !== '' ? (float)$input['latitude'] : null,
        ':longitude' => ($input['longitude'] ?? '') !== '' ? (float)$input['longitude'] : null,
        ':phone' => format_phone($input['phone'] ?? ''),
        ':email' => trim((string)($input['email'] ?? '')),
        ':lundi' => array_key_exists('lundi', $input) ? trim((string)$input['lundi']) : null,
        ':mardi' => array_key_exists('mardi', $input) ? trim((string)$input['mardi']) : null,
        ':mercredi' => array_key_exists('mercredi', $input) ? trim((string)$input['mercredi']) : null,
        ':jeudi' => array_key_exists('jeudi', $input) ? trim((string)$input['jeudi']) : null,
        ':vendredi' => array_key_exists('vendredi', $input) ? trim((string)$input['vendredi']) : null,
        ':samedi' => array_key_exists('samedi', $input) ? trim((string)$input['samedi']) : null,
        ':dimanche' => array_key_exists('dimanche', $input) ? trim((string)$input['dimanche']) : null,
        ':website' => trim((string)($input['website'] ?? '')),
        ':logo_url' => trim((string)($input['logo_url'] ?? '')),
        ':is_active' => !empty($input['is_active']) ? 1 : 0,
    ];

    if ($id > 0) {
        $payload[':id'] = $id;
        $sql = "UPDATE clients SET
            name=:name, client_type=:client_type, address=:address, city=:city, postal_code=:postal_code,
            country=:country, latitude=:latitude, longitude=:longitude, phone=:phone, email=:email,
            lundi=COALESCE(:lundi, lundi), mardi=COALESCE(:mardi, mardi), mercredi=COALESCE(:mercredi, mercredi),
            jeudi=COALESCE(:jeudi, jeudi), vendredi=COALESCE(:vendredi, vendredi),
            samedi=COALESCE(:samedi, samedi), dimanche=COALESCE(:dimanche, dimanche),
            website=:website, logo_url=:logo_url, is_active=:is_active
            WHERE id=:id";
        $pdo->prepare($sql)->execute($payload);
    } else {
        $sql = "INSERT INTO clients
            (name, client_type, address, city, postal_code, country, latitude, longitude, phone, email, lundi, mardi, mercredi, jeudi, vendredi, samedi, dimanche, website, logo_url, is_active)
            VALUES
            (:name, :client_type, :address, :city, :postal_code, :country, :latitude, :longitude, :phone, :email, :lundi, :mardi, :mercredi, :jeudi, :vendredi, :samedi, :dimanche, :website, :logo_url, :is_active)";
        $pdo->prepare($sql)->execute($payload);
    }

    json_response(['ok' => true]);
}

function save_client_user(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $clientId = isset($input['client_id']) ? (int)$input['client_id'] : 0;
    $username = trim((string)($input['username'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $role = trim((string)($input['role'] ?? 'client_manager'));
    $isActive = !empty($input['is_active']) ? 1 : 0;

    if ($clientId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id requis'], 422);
    }
    if ($username === '') {
        json_response(['ok' => false, 'error' => 'Nom utilisateur requis'], 422);
    }

    $allowedRoles = ['client_manager', 'client_editor', 'client_reader'];
    if (!in_array($role, $allowedRoles, true)) {
        json_response(['ok' => false, 'error' => 'Rôle utilisateur invalide'], 422);
    }

    if ($id > 0) {
        $payload = [
            ':id' => $id,
            ':client_id' => $clientId,
            ':username' => $username,
            ':role' => $role,
            ':is_active' => $isActive,
        ];

        $sql = 'UPDATE client_users SET client_id=:client_id, username=:username, role=:role, is_active=:is_active';
        if ($password !== '') {
            if (mb_strlen($password, 'UTF-8') < 8) {
                json_response(['ok' => false, 'error' => 'Mot de passe trop court (8 caractères min)'], 422);
            }
            $sql .= ', password_hash=:password_hash';
            $payload[':password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id=:id';

        try {
            $pdo->prepare($sql)->execute($payload);
        } catch (PDOException $e) {
            if (($e->getCode() ?? '') === '23000') {
                json_response(['ok' => false, 'error' => 'Nom utilisateur déjà utilisé'], 422);
            }
            throw $e;
        }
        json_response(['ok' => true]);
    }

    if ($password === '' || mb_strlen($password, 'UTF-8') < 8) {
        json_response(['ok' => false, 'error' => 'Mot de passe requis (8 caractères min)'], 422);
    }

    try {
        $pdo->prepare('INSERT INTO client_users (client_id, username, password_hash, role, is_active) VALUES (:client_id, :username, :password_hash, :role, :is_active)')
            ->execute([
                ':client_id' => $clientId,
                ':username' => $username,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':role' => $role,
                ':is_active' => $isActive,
            ]);
    } catch (PDOException $e) {
        if (($e->getCode() ?? '') === '23000') {
            json_response(['ok' => false, 'error' => 'Nom utilisateur déjà utilisé'], 422);
        }
        throw $e;
    }

    json_response(['ok' => true]);
}

function toggle_client_user_active(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $isActive = !empty($input['is_active']) ? 1 : 0;

    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Utilisateur invalide'], 422);
    }

    $pdo->prepare('UPDATE client_users SET is_active=:is_active WHERE id=:id')->execute([
        ':is_active' => $isActive,
        ':id' => $id,
    ]);

    json_response(['ok' => true]);
}

function reset_client_user_password(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $newPassword = (string)($input['new_password'] ?? '');

    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Utilisateur invalide'], 422);
    }
    if ($newPassword === '' || mb_strlen($newPassword, 'UTF-8') < 8) {
        json_response(['ok' => false, 'error' => 'Mot de passe trop court (8 caractères min)'], 422);
    }

    $pdo->prepare('UPDATE client_users SET password_hash=:password_hash WHERE id=:id')->execute([
        ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ':id' => $id,
    ]);

    json_response(['ok' => true]);
}

function save_settings(PDO $pdo): void
{
    $input = get_json_input();
    $allowedKeys = ['org_name', 'org_logo_url', 'default_client_icon', 'default_producer_icon', 'farm_direct_icon'];

    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    foreach ($allowedKeys as $key) {
        if (!array_key_exists($key, $input)) {
            continue;
        }
        $stmt->execute([
            ':k' => $key,
            ':v' => trim((string)$input[$key]),
        ]);
    }

    json_response(['ok' => true]);
}

function upload_client_logo(): void
{
    if (empty($_FILES['logo']) || !is_array($_FILES['logo'])) {
        json_response(['ok' => false, 'error' => 'Fichier logo manquant'], 422);
    }

    $file = $_FILES['logo'];
    if (!empty($file['error'])) {
        json_response(['ok' => false, 'error' => 'Erreur upload (' . (int)$file['error'] . ')'], 422);
    }

    $tmpPath = $file['tmp_name'] ?? '';
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        json_response(['ok' => false, 'error' => 'Upload invalide'], 422);
    }

    $maxSize = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxSize) {
        json_response(['ok' => false, 'error' => 'Image trop volumineuse (max 5MB)'], 422);
    }

    $mime = mime_content_type($tmpPath) ?: '';
    $extByMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($extByMime[$mime])) {
        json_response(['ok' => false, 'error' => 'Format image non supporté'], 422);
    }

    $rootDir = dirname(__DIR__);
    $targetDir = $rootDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'clients';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        json_response(['ok' => false, 'error' => 'Impossible de créer le dossier upload'], 500);
    }

    $basename = 'client_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5));
    $filename = $basename . '.' . $extByMime[$mime];
    $destPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        json_response(['ok' => false, 'error' => 'Impossible de déplacer le fichier uploadé'], 500);
    }

    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api')), '/');
    $baseWebPath = preg_replace('#/api$#', '', $scriptDir);
    $url = ($baseWebPath ?: '') . '/uploads/clients/' . $filename;

    json_response(['ok' => true, 'url' => $url]);
}

function upload_activity_icon(): void
{
    if (empty($_FILES['icon']) || !is_array($_FILES['icon'])) {
        json_response(['ok' => false, 'error' => 'Fichier icône manquant'], 422);
    }

    $file = $_FILES['icon'];
    if (!empty($file['error'])) {
        json_response(['ok' => false, 'error' => 'Erreur upload (' . (int)$file['error'] . ')'], 422);
    }

    $tmpPath = $file['tmp_name'] ?? '';
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        json_response(['ok' => false, 'error' => 'Upload invalide'], 422);
    }

    $maxSize = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxSize) {
        json_response(['ok' => false, 'error' => 'Image trop volumineuse (max 5MB)'], 422);
    }

    $mime = mime_content_type($tmpPath) ?: '';
    $extByMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
    ];

    if (!isset($extByMime[$mime])) {
        json_response(['ok' => false, 'error' => 'Format image non supporté'], 422);
    }

    $rootDir = dirname(__DIR__);
    $targetDir = $rootDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'activities';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        json_response(['ok' => false, 'error' => 'Impossible de créer le dossier upload'], 500);
    }

    $basename = 'activity_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5));
    $filename = $basename . '.' . $extByMime[$mime];
    $destPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        json_response(['ok' => false, 'error' => 'Impossible de déplacer le fichier uploadé'], 500);
    }

    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api')), '/');
    $baseWebPath = preg_replace('#/api$#', '', $scriptDir);
    $url = ($baseWebPath ?: '') . '/uploads/activities/' . $filename;

    json_response(['ok' => true, 'url' => $url]);
}

function geocode_address_admin(): void
{
    $input = get_json_input();
    $address = trim((string)($input['address'] ?? ''));
    if ($address === '') {
        json_response(['ok' => false, 'error' => 'Adresse requise'], 422);
    }

    $geo = geocode_address_text($address);
    if ($geo === null) {
        json_response(['ok' => true, 'found' => false]);
    }

    json_response([
        'ok' => true,
        'found' => true,
        'lat' => (float)$geo['lat'],
        'lng' => (float)$geo['lng'],
        'display_name' => $geo['display_name'] ?? '',
    ]);
}

function geocode_address_text(string $address): ?array
{
    $address = trim($address);
    if ($address === '') {
        return null;
    }

    $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . rawurlencode($address);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => "User-Agent: AppCarteLimap/1.0\r\nAccept: application/json\r\n",
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw !== false) {
        $rows = json_decode($raw, true);
        if (is_array($rows) && count($rows) > 0) {
            $first = $rows[0];
            return [
                'lat' => isset($first['lat']) ? (float)$first['lat'] : null,
                'lng' => isset($first['lon']) ? (float)$first['lon'] : null,
                'display_name' => $first['display_name'] ?? '',
            ];
        }
    }

    // Fallback: many imported files omit country; default to France to improve hit rate.
    if (normalize_text($address) !== '' && strpos(normalize_text($address), 'france') === false) {
        $fallbackAddress = $address . ', France';
        $fallbackUrl = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . rawurlencode($fallbackAddress);
        $fallbackRaw = @file_get_contents($fallbackUrl, false, $context);
        if ($fallbackRaw !== false) {
            $fallbackRows = json_decode($fallbackRaw, true);
            if (is_array($fallbackRows) && count($fallbackRows) > 0) {
                $first = $fallbackRows[0];
                return [
                    'lat' => isset($first['lat']) ? (float)$first['lat'] : null,
                    'lng' => isset($first['lon']) ? (float)$first['lon'] : null,
                    'display_name' => $first['display_name'] ?? '',
                ];
            }
        }
    }

    return null;
}

function save_supplier_type(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $name = trim((string)($input['name'] ?? ''));
    if ($name === '') {
        json_response(['ok' => false, 'error' => 'Nom de type requis'], 422);
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE supplier_types SET name=:name, is_active=:is_active WHERE id=:id')
            ->execute([':name' => $name, ':is_active' => 1, ':id' => $id]);
    } else {
        $pdo->prepare('INSERT INTO supplier_types (name, is_active) VALUES (:name, 1) ON DUPLICATE KEY UPDATE name=name')
            ->execute([':name' => $name]);
    }
    json_response(['ok' => true]);
}

function delete_supplier_type(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Type invalide'], 422);
    }

    // Check if any supplier still uses this type name
    $row = $pdo->prepare('SELECT name FROM supplier_types WHERE id=:id');
    $row->execute([':id' => $id]);
    $typeName = (string)($row->fetch()['name'] ?? '');
    if ($typeName !== '') {
        $countStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM suppliers WHERE supplier_type=:name');
        $countStmt->execute([':name' => $typeName]);
        $linked = (int)($countStmt->fetch()['total'] ?? 0);
        if ($linked > 0) {
            json_response(['ok' => false, 'error' => 'Suppression impossible: ce type est utilisé par ' . $linked . ' fournisseur(s).'], 422);
        }
    }

    $pdo->prepare('DELETE FROM supplier_types WHERE id=:id')->execute([':id' => $id]);
    json_response(['ok' => true]);
}

function save_activity(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $name = trim((string)($input['name'] ?? ''));
    if ($name === '') {
        json_response(['ok' => false, 'error' => 'Nom activité requis'], 422);
    }

    $payload = [
        ':name' => $name,
        ':family' => trim((string)($input['family'] ?? '')),
        ':icon_url' => trim((string)($input['icon_url'] ?? '')),
        ':is_active' => !empty($input['is_active']) ? 1 : 0,
    ];

    if ($id > 0) {
        $payload[':id'] = $id;
        $pdo->prepare('UPDATE activities SET name=:name, family=:family, icon_url=:icon_url, is_active=:is_active WHERE id=:id')->execute($payload);
    } else {
        $pdo->prepare('INSERT INTO activities (name, family, icon_url, is_active) VALUES (:name, :family, :icon_url, :is_active)')->execute($payload);
    }

    json_response(['ok' => true]);
}

function delete_activity(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Activité invalide'], 422);
    }

    $countStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM supplier_activities WHERE activity_id=:id');
    $countStmt->execute([':id' => $id]);
    $linked = (int)($countStmt->fetch()['total'] ?? 0);
    if ($linked > 0) {
        json_response(['ok' => false, 'error' => 'Suppression impossible: cette catégorie est rattachée à ' . $linked . ' fournisseur(s).'], 422);
    }

    $pdo->prepare('DELETE FROM activities WHERE id=:id')->execute([':id' => $id]);
    json_response(['ok' => true]);
}

function save_label(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $name = trim((string)($input['name'] ?? ''));
    if ($name === '') {
        json_response(['ok' => false, 'error' => 'Nom label requis'], 422);
    }

    $payload = [
        ':name' => $name,
        ':color' => trim((string)($input['color'] ?? '')),
        ':is_active' => !empty($input['is_active']) ? 1 : 0,
    ];

    if ($id > 0) {
        $payload[':id'] = $id;
        $pdo->prepare('UPDATE labels SET name=:name, color=:color, is_active=:is_active WHERE id=:id')->execute($payload);
    } else {
        $pdo->prepare('INSERT INTO labels (name, color, is_active) VALUES (:name, :color, :is_active)')->execute($payload);
    }

    json_response(['ok' => true]);
}

function delete_label(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Label invalide'], 422);
    }

    $countStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM supplier_labels WHERE label_id=:id');
    $countStmt->execute([':id' => $id]);
    $linked = (int)($countStmt->fetch()['total'] ?? 0);
    if ($linked > 0) {
        json_response(['ok' => false, 'error' => 'Suppression impossible: ce label est rattaché à ' . $linked . ' fournisseur(s).'], 422);
    }

    $pdo->prepare('DELETE FROM labels WHERE id=:id')->execute([':id' => $id]);
    json_response(['ok' => true]);
}

function find_existing_supplier(PDO $pdo, array $supplier): ?array
{
    $normalizedName = normalize_text((string)($supplier['name'] ?? ''));
    $city = trim((string)($supplier['city'] ?? ''));
    $phone = normalize_phone($supplier['phone'] ?? '');
    $email = mb_strtolower(trim((string)($supplier['email'] ?? '')), 'UTF-8');

    if ($email !== '') {
        $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE LOWER(email)=:email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }

    if ($phone !== '') {
        $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE REPLACE(REPLACE(REPLACE(REPLACE(phone, " ", ""), ".", ""), "-", ""), "+", "") = :phone LIMIT 1');
        $stmt->execute([':phone' => $phone]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }

    if ($normalizedName !== '') {
        $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE normalized_name=:n AND city=:city LIMIT 1');
        $stmt->execute([':n' => $normalizedName, ':city' => $city]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }

    return null;
}

function save_supplier(PDO $pdo, string $source = 'manual'): void
{
    $input = get_json_input();
    persist_supplier($pdo, $input, $source, [], ['auto_geocode' => false]);
    json_response(['ok' => true]);
}

function persist_supplier(PDO $pdo, array $input, string $source, array $resolutions, array $options = []): array
{
    $requestedId = isset($input['id']) ? (int)$input['id'] : 0;
    $clientIds = array_map('intval', (array)($input['client_ids'] ?? []));
    $activityNames = parse_csv_list($input['activities'] ?? ($input['activity_text'] ?? ''));
    $labelNames = parse_csv_list($input['labels'] ?? '');

    $supplier = [
        'name' => trim((string)($input['name'] ?? '')),
        'address' => trim((string)($input['address'] ?? '')),
        'city' => trim((string)($input['city'] ?? '')),
        'postal_code' => trim((string)($input['postal_code'] ?? '')),
        'country' => trim((string)($input['country'] ?? '')),
        'latitude' => ($input['latitude'] ?? '') !== '' ? (float)$input['latitude'] : null,
        'longitude' => ($input['longitude'] ?? '') !== '' ? (float)$input['longitude'] : null,
        'phone' => format_phone($input['phone'] ?? ''),
        'email' => trim((string)($input['email'] ?? '')),
        'website' => trim((string)($input['website'] ?? '')),
        'supplier_type' => trim((string)($input['supplier_type'] ?? ($input['type'] ?? ''))),
        'activity_text' => implode('; ', $activityNames),
        'notes' => trim((string)($input['notes'] ?? '')),
    ];

    if ($supplier['name'] === '') {
        throw new RuntimeException('Nom fournisseur requis');
    }

    if ($supplier['country'] === '') {
        $supplier['country'] = 'France';
    }

    $autoGeocode = !empty($options['auto_geocode']);
    if ($autoGeocode && ($supplier['latitude'] === null || $supplier['longitude'] === null)) {
        $query = implode(', ', array_values(array_filter([
            $supplier['address'],
            $supplier['postal_code'],
            $supplier['city'],
            $supplier['country'],
        ], fn($v) => trim((string)$v) !== '')));

        if ($query !== '') {
            $geo = geocode_address_text($query);
            if (is_array($geo)) {
                if ($supplier['latitude'] === null && isset($geo['lat'])) {
                    $supplier['latitude'] = (float)$geo['lat'];
                }
                if ($supplier['longitude'] === null && isset($geo['lng'])) {
                    $supplier['longitude'] = (float)$geo['lng'];
                }
            }
        }
    }

    $existing = null;
    if ($requestedId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id=:id LIMIT 1');
        $stmt->execute([':id' => $requestedId]);
        $existing = $stmt->fetch() ?: null;
        if (!$existing) {
            throw new RuntimeException('Fournisseur introuvable');
        }
    } else {
        $existing = find_existing_supplier($pdo, $supplier);
    }
    $supplierId = null;

    if ($existing) {
        $supplierId = (int)$existing['id'];
        $fields = ['name', 'address', 'city', 'postal_code', 'country', 'latitude', 'longitude', 'phone', 'email', 'website', 'activity_text', 'supplier_type', 'notes'];
        $updatePayload = [];
        foreach ($fields as $field) {
            $incoming = (string)($supplier[$field] ?? '');
            $current = (string)($existing[$field] ?? '');
            if ($requestedId > 0) {
                if ($incoming === $current) {
                    continue;
                }
                $updatePayload[$field] = $supplier[$field];
                continue;
            }

            if ($incoming === '' || $incoming === $current) {
                continue;
            }
            $mode = $resolutions[$field] ?? 'keep_existing';
            if ($field === 'activity_text' && $mode === 'keep_existing') {
                // In import mode, keep existing should remain conservative; explicit merge is available.
                continue;
            }
            if ($field === 'activity_text' && $mode === 'merge_existing') {
                $merged = array_values(array_unique(array_merge(
                    parse_csv_list($current),
                    parse_csv_list($incoming)
                )));
                $mergedText = implode('; ', $merged);
                if ($mergedText !== '' && $mergedText !== $current) {
                    $updatePayload[$field] = $mergedText;
                }
                continue;
            }
            if ($mode === 'replace_existing') {
                $updatePayload[$field] = $incoming;
                continue;
            }

            if (
                $autoGeocode
                && in_array($field, ['latitude', 'longitude'], true)
                && $current === ''
            ) {
                $updatePayload[$field] = $incoming;
            }
        }

        if ($updatePayload) {
            $updatePayload['normalized_name'] = normalize_text($supplier['name']);
            $setParts = [];
            $params = [':id' => $supplierId];
            foreach ($updatePayload as $field => $value) {
                $setParts[] = $field . '=:' . $field;
                $params[':' . $field] = $value;
            }
            $sql = 'UPDATE suppliers SET ' . implode(', ', $setParts) . ' WHERE id=:id';
            $pdo->prepare($sql)->execute($params);
        }
    } else {
        $sql = "INSERT INTO suppliers
            (name, normalized_name, address, city, postal_code, country, latitude, longitude, phone, email, website, supplier_type, activity_text, notes)
            VALUES
            (:name, :normalized_name, :address, :city, :postal_code, :country, :latitude, :longitude, :phone, :email, :website, :supplier_type, :activity_text, :notes)";
        $pdo->prepare($sql)->execute([
            ':name' => $supplier['name'],
            ':normalized_name' => normalize_text($supplier['name']),
            ':address' => $supplier['address'],
            ':city' => $supplier['city'],
            ':postal_code' => $supplier['postal_code'],
            ':country' => $supplier['country'],
            ':latitude' => $supplier['latitude'],
            ':longitude' => $supplier['longitude'],
            ':phone' => $supplier['phone'],
            ':email' => $supplier['email'],
            ':website' => $supplier['website'],
            ':supplier_type' => $supplier['supplier_type'],
            ':activity_text' => $supplier['activity_text'],
            ':notes' => $supplier['notes'],
        ]);
        $supplierId = (int)$pdo->lastInsertId();
    }

    $linkMode = (string)($options['client_link_mode'] ?? 'replace');
    if ($linkMode !== 'merge') {
        $pdo->prepare('DELETE FROM client_suppliers WHERE supplier_id=:supplier_id')->execute([':supplier_id' => $supplierId]);
    }
    foreach ($clientIds as $clientId) {
        if ($clientId <= 0) {
            continue;
        }
        $stmt = $pdo->prepare('INSERT IGNORE INTO client_suppliers (client_id, supplier_id, source) VALUES (:client_id, :supplier_id, :source)');
        $stmt->execute([':client_id' => $clientId, ':supplier_id' => $supplierId, ':source' => $source]);
    }

    $replaceTaxonomyLinks = !($source === 'import' && !empty($existing));
    $activityResolution = (string)($resolutions['activity_text'] ?? '');
    if ($activityResolution === 'replace_existing') {
        $replaceTaxonomyLinks = true;
    }
    sync_activity_links($pdo, $supplierId, $activityNames, $replaceTaxonomyLinks);
    sync_label_links($pdo, $supplierId, $labelNames, $replaceTaxonomyLinks);

    return ['id' => $supplierId, 'existing' => (bool)$existing];
}

function delete_supplier(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Fournisseur invalide'], 422);
    }

    $pdo->prepare('DELETE FROM suppliers WHERE id=:id')->execute([':id' => $id]);
    json_response(['ok' => true]);
}

function sync_activity_links(PDO $pdo, int $supplierId, array $activityNames, bool $replace = true): void
{
    if ($replace) {
        $pdo->prepare('DELETE FROM supplier_activities WHERE supplier_id=:supplier_id')->execute([':supplier_id' => $supplierId]);
    }

    foreach ($activityNames as $name) {
        $stmt = $pdo->prepare('INSERT INTO activities (name, family, is_active) VALUES (:name, "", 1) ON DUPLICATE KEY UPDATE name=name');
        $stmt->execute([':name' => $name]);
        $activityId = (int)$pdo->lastInsertId();
        if ($activityId === 0) {
            $row = $pdo->prepare('SELECT id FROM activities WHERE name=:name');
            $row->execute([':name' => $name]);
            $activityId = (int)($row->fetch()['id'] ?? 0);
        }
        if ($activityId > 0) {
            $pdo->prepare('INSERT IGNORE INTO supplier_activities (supplier_id, activity_id) VALUES (:sid, :aid)')->execute([
                ':sid' => $supplierId,
                ':aid' => $activityId,
            ]);
        }
    }
}

function sync_label_links(PDO $pdo, int $supplierId, array $labelNames, bool $replace = true): void
{
    if ($replace) {
        $pdo->prepare('DELETE FROM supplier_labels WHERE supplier_id=:supplier_id')->execute([':supplier_id' => $supplierId]);
    }

    foreach ($labelNames as $name) {
        $stmt = $pdo->prepare('INSERT INTO labels (name, color, is_active) VALUES (:name, "", 1) ON DUPLICATE KEY UPDATE name=name');
        $stmt->execute([':name' => $name]);
        $labelId = (int)$pdo->lastInsertId();
        if ($labelId === 0) {
            $row = $pdo->prepare('SELECT id FROM labels WHERE name=:name');
            $row->execute([':name' => $name]);
            $labelId = (int)($row->fetch()['id'] ?? 0);
        }
        if ($labelId > 0) {
            $pdo->prepare('INSERT IGNORE INTO supplier_labels (supplier_id, label_id) VALUES (:sid, :lid)')->execute([
                ':sid' => $supplierId,
                ':lid' => $labelId,
            ]);
        }
    }
}

function preview_import(PDO $pdo): void
{
    $input = get_json_input();
    $rows = (array)($input['rows'] ?? []);
    $clientId = (int)($input['client_id'] ?? 0);
    $fileName = trim((string)($input['file_name'] ?? 'import.xlsx'));
    $autoGeocode = !empty($input['auto_geocode']);

    if ($clientId <= 0) {
        json_response(['ok' => false, 'error' => 'Client requis'], 422);
    }

    $stmt = $pdo->prepare('INSERT INTO import_batches (file_name, client_id, status) VALUES (:file_name, :client_id, "preview")');
    $stmt->execute([':file_name' => $fileName, ':client_id' => $clientId]);
    $batchId = (int)$pdo->lastInsertId();

    $preview = [];
    $summary = ['new' => 0, 'existing' => 0, 'conflicts' => 0, 'errors' => 0];

    foreach ($rows as $idx => $raw) {
        $supplier = normalize_import_row($raw);
        if ($supplier['name'] === '') {
            $summary['errors']++;
            continue;
        }

        // Geocoding is deferred to the browser after preview is displayed.
        // This avoids blocking the preview response for minutes with Nominatim calls.
        $supplier['geocode_mode'] = '';
        $supplier['geocode_note'] = '';

        if (trim((string)$supplier['country']) === '') {
            $supplier['country'] = 'France';
        }

        $existing = find_existing_supplier($pdo, $supplier);
        $conflicts = [];
        if ($existing) {
            $summary['existing']++;

            $existingLat = ($existing['latitude'] ?? '') !== '' ? (float)$existing['latitude'] : null;
            $existingLng = ($existing['longitude'] ?? '') !== '' ? (float)$existing['longitude'] : null;
            if ($supplier['latitude'] === null && $existingLat !== null) {
                $supplier['latitude'] = $existingLat;
            }
            if ($supplier['longitude'] === null && $existingLng !== null) {
                $supplier['longitude'] = $existingLng;
            }
            if (($existingLat !== null || $existingLng !== null) && ($supplier['latitude'] !== null || $supplier['longitude'] !== null)) {
                $supplier['geocode_mode'] = 'existing';
                $supplier['geocode_note'] = 'Coordonnées déjà connues en base';
            }

            foreach (['address', 'city', 'postal_code', 'country', 'phone', 'email', 'website', 'activity_text', 'supplier_type'] as $field) {
                $incoming = trim((string)$supplier[$field]);
                $stored = trim((string)($existing[$field] ?? ''));
                if ($incoming !== '' && $stored !== '' && $incoming !== $stored) {
                    $conflicts[] = [
                        'field' => $field,
                        'existing' => $stored,
                        'incoming' => $incoming,
                    ];

                    $pdo->prepare('INSERT INTO import_conflicts (batch_id, row_index, supplier_id, field_name, existing_value, incoming_value) VALUES (:batch_id, :row_index, :supplier_id, :field_name, :existing_value, :incoming_value)')
                        ->execute([
                            ':batch_id' => $batchId,
                            ':row_index' => $idx,
                            ':supplier_id' => (int)$existing['id'],
                            ':field_name' => $field,
                            ':existing_value' => $stored,
                            ':incoming_value' => $incoming,
                        ]);
                }
            }
        } else {
            $summary['new']++;
        }

        if ($conflicts) {
            $summary['conflicts']++;
        }

        $preview[] = [
            'row_index' => $idx,
            'name' => $supplier['name'],
            'city' => $supplier['city'],
            'existing_supplier_id' => $existing ? (int)$existing['id'] : null,
            'conflicts' => $conflicts,
            'source_values' => is_array($raw['_source_values'] ?? null) ? $raw['_source_values'] : [],
            'payload' => $supplier,
        ];
    }

    json_response([
        'ok' => true,
        'batch_id' => $batchId,
        'summary' => $summary,
        'rows' => $preview,
    ]);
}

function normalize_import_row(array $raw): array
{
    $normalized = [];
    foreach ($raw as $k => $v) {
        $normalized[normalize_text((string)$k)] = is_string($v) ? trim($v) : $v;
    }

    $name = trim((string)($normalized['nom'] ?? $normalized['name'] ?? ''));
    $activities = parse_csv_list($normalized['activite'] ?? $normalized['activites'] ?? $normalized['activity'] ?? '');
    $labels = parse_csv_list($normalized['label'] ?? $normalized['labels'] ?? '');

    return [
        'name' => $name,
        'address' => trim((string)($normalized['adresse'] ?? $normalized['address'] ?? '')),
        'city' => trim((string)($normalized['ville'] ?? $normalized['city'] ?? '')),
        'postal_code' => trim((string)($normalized['code postal'] ?? $normalized['postal code'] ?? $normalized['postal_code'] ?? '')),
        'country' => trim((string)($normalized['pays'] ?? $normalized['country'] ?? '')),
        'latitude' => ($normalized['latitude'] ?? '') !== '' ? (float)$normalized['latitude'] : null,
        'longitude' => ($normalized['longitude'] ?? '') !== '' ? (float)$normalized['longitude'] : null,
        'phone' => format_phone($normalized['telephone'] ?? $normalized['tel'] ?? $normalized['phone'] ?? ''),
        'email' => trim((string)($normalized['email'] ?? '')),
        'website' => trim((string)($normalized['website'] ?? $normalized['site web'] ?? '')),
        'supplier_type' => trim((string)($normalized['type'] ?? '')),
        'activity_text' => implode('; ', $activities),
        'activities' => $activities,
        'labels' => $labels,
        'notes' => trim((string)($normalized['notes'] ?? '')),
    ];
}

function commit_import(PDO $pdo): void
{
    $input = get_json_input();
    $batchId = (int)($input['batch_id'] ?? 0);
    $clientId = (int)($input['client_id'] ?? 0);
    $rows = (array)($input['rows'] ?? []);
    $resolutions = (array)($input['resolutions'] ?? []);
    $autoGeocode = !empty($input['auto_geocode']);

    if ($batchId <= 0 || $clientId <= 0) {
        json_response(['ok' => false, 'error' => 'batch_id et client_id requis'], 422);
    }

    $pdo->beginTransaction();
    try {
        $created = 0;
        $updated = 0;
        foreach ($rows as $row) {
            $rowIdx = (int)($row['row_index'] ?? -1);
            $payload = (array)($row['payload'] ?? []);
            if (!$payload) {
                continue;
            }

            if (
                trim((string)($payload['activity_text'] ?? $payload['activities'] ?? '')) === ''
                || trim((string)($payload['labels'] ?? '')) === ''
                || trim((string)($payload['supplier_type'] ?? '')) === ''
            ) {
                throw new RuntimeException('Validation impossible: catégorie, label et type sont requis pour la ligne ' . ($rowIdx + 1));
            }

            $rowResolution = (array)($resolutions[$rowIdx] ?? []);
            $payload['client_ids'] = [$clientId];
            if (!isset($rowResolution['activity_text'])) {
                $rowResolution['activity_text'] = 'merge_existing';
            }
            $result = persist_supplier($pdo, $payload, 'import', $rowResolution, ['auto_geocode' => $autoGeocode, 'client_link_mode' => 'merge']);
            if (!empty($result['existing'])) {
                $updated++;
            } else {
                $created++;
            }

            foreach ($rowResolution as $field => $mode) {
                $pdo->prepare('UPDATE import_conflicts SET resolution=:resolution WHERE batch_id=:batch_id AND row_index=:row_index AND field_name=:field_name')
                    ->execute([
                        ':resolution' => $mode,
                        ':batch_id' => $batchId,
                        ':row_index' => $rowIdx,
                        ':field_name' => $field,
                    ]);
            }
        }

        $pdo->prepare('UPDATE import_batches SET status="committed" WHERE id=:id')->execute([':id' => $batchId]);
        $pdo->commit();
        json_response(['ok' => true, 'created' => $created, 'updated' => $updated]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function resolve_effective_client_id(array $input = []): int
{
    start_app_session();

    if (!empty($_SESSION['is_client_user'])) {
        $sessionClientId = (int)($_SESSION['client_id'] ?? 0);
        if ($sessionClientId > 0) {
            return $sessionClientId;
        }

        $clientUserId = (int)($_SESSION['client_user_id'] ?? 0);
        if ($clientUserId > 0) {
            $stmt = get_db()->prepare('SELECT cu.client_id, cu.role, cu.username, c.name AS client_name FROM client_users cu JOIN clients c ON c.id = cu.client_id WHERE cu.id=:id AND cu.is_active=1 LIMIT 1');
            $stmt->execute([':id' => $clientUserId]);
            $row = $stmt->fetch();
            if ($row) {
                $_SESSION['client_id'] = (int)$row['client_id'];
                $_SESSION['client_role'] = (string)$row['role'];
                $_SESSION['client_username'] = (string)$row['username'];
                $_SESSION['client_name'] = (string)$row['client_name'];
                return (int)$row['client_id'];
            }
        }

        return 0;
    }

    if (!empty($_SESSION['is_admin'])) {
        $fromInput = isset($input['client_id']) ? (int)$input['client_id'] : 0;
        $fromGet = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
        return $fromInput > 0 ? $fromInput : $fromGet;
    }

    return 0;
}

function assert_client_can_write_profile(): void
{
    start_app_session();
    if (!empty($_SESSION['is_admin'])) {
        return;
    }

    $role = (string)($_SESSION['client_role'] ?? '');
    if ($role === 'client_reader' || $role === '') {
        json_response(['ok' => false, 'error' => 'Droits insuffisants'], 403);
    }
}

function assert_client_has_supplier_link(PDO $pdo, int $clientId, int $supplierId): void
{
    $stmt = $pdo->prepare('SELECT 1 FROM client_suppliers WHERE client_id=:client_id AND supplier_id=:supplier_id LIMIT 1');
    $stmt->execute([':client_id' => $clientId, ':supplier_id' => $supplierId]);
    if (!$stmt->fetch()) {
        json_response(['ok' => false, 'error' => 'Fournisseur hors périmètre client'], 403);
    }
}

function write_supplier_audit(PDO $pdo, int $supplierId, string $actionName, ?string $fieldName, $oldValue, $newValue, array $meta = []): void
{
    $actor = current_actor_context();
    $pdo->prepare('INSERT INTO supplier_audit_log (supplier_id, actor_type, actor_id, actor_name, action_name, field_name, old_value, new_value, meta_json) VALUES (:supplier_id, :actor_type, :actor_id, :actor_name, :action_name, :field_name, :old_value, :new_value, :meta_json)')
        ->execute([
            ':supplier_id' => $supplierId,
            ':actor_type' => (string)($actor['actor_type'] ?? 'unknown'),
            ':actor_id' => $actor['actor_id'] !== null ? (int)$actor['actor_id'] : null,
            ':actor_name' => (string)($actor['actor_name'] ?? ''),
            ':action_name' => $actionName,
            ':field_name' => $fieldName,
            ':old_value' => $oldValue !== null ? (string)$oldValue : null,
            ':new_value' => $newValue !== null ? (string)$newValue : null,
            ':meta_json' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]);
}

function client_bootstrap(PDO $pdo): void
{
    $clientId = resolve_effective_client_id();
    if ($clientId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id requis'], 422);
    }

    $clientStmt = $pdo->prepare('SELECT id, name, client_type, logo_url, city, address, postal_code, phone, email, website FROM clients WHERE id=:id AND is_active=1 LIMIT 1');
    $clientStmt->execute([':id' => $clientId]);
    $client = $clientStmt->fetch();
    if (!$client) {
        json_response(['ok' => false, 'error' => 'Client introuvable'], 404);
    }
    $client['phone'] = format_phone($client['phone'] ?? '');

    $sql = "SELECT
            s.id,
            s.name,
            s.address,
            s.city,
            s.postal_code,
            s.country,
            s.latitude,
            s.longitude,
            s.phone,
            s.email,
            s.website,
            s.supplier_type,
            s.activity_text AS global_activity_text,
            s.notes AS global_notes,
            (
                SELECT GROUP_CONCAT(DISTINCT l.name ORDER BY l.name SEPARATOR '; ')
                FROM supplier_labels sl
                JOIN labels l ON l.id = sl.label_id
                WHERE sl.supplier_id = s.id
            ) AS global_labels,
            csp.activity_text AS profile_activity_text,
            csp.labels_text AS profile_labels_text,
            csp.notes AS profile_notes,
            csp.relationship_status,
            csp.updated_at AS profile_updated_at
        FROM client_suppliers cs
        JOIN suppliers s ON s.id = cs.supplier_id
        LEFT JOIN client_supplier_profiles csp ON csp.client_id = cs.client_id AND csp.supplier_id = cs.supplier_id
        WHERE cs.client_id = :client_id
        ORDER BY s.name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':client_id' => $clientId]);
    $suppliers = $stmt->fetchAll();

    $activities = $pdo->query('SELECT id, name FROM activities WHERE is_active=1 ORDER BY family, name')->fetchAll();
    $labels = $pdo->query('SELECT id, name FROM labels WHERE is_active=1 ORDER BY name')->fetchAll();
    $supplierTypes = $pdo->query('SELECT id, name FROM supplier_types WHERE is_active=1 ORDER BY name')->fetchAll();

    $suppliers = array_map(function (array $row) {
        $row['phone'] = format_phone($row['phone'] ?? '');
        return $row;
    }, $suppliers);

    json_response([
        'ok' => true,
        'client' => $client,
        'suppliers' => $suppliers,
        'activities' => $activities,
        'labels' => $labels,
        'supplier_types' => $supplierTypes,
    ]);
}

function save_client_supplier_profile(PDO $pdo): void
{
    assert_client_can_write_profile();

    $input = get_json_input();
    $clientId = resolve_effective_client_id($input);
    $supplierId = isset($input['supplier_id']) ? (int)$input['supplier_id'] : 0;

    if ($clientId <= 0 || $supplierId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id et supplier_id requis'], 422);
    }

    assert_client_has_supplier_link($pdo, $clientId, $supplierId);

    $activityText = trim((string)($input['activity_text'] ?? ''));
    $labelsText = trim((string)($input['labels_text'] ?? ''));
    $notes = trim((string)($input['notes'] ?? ''));
    $relationshipStatus = trim((string)($input['relationship_status'] ?? 'active'));
    $allowedStatus = ['active', 'inactive', 'prospect', 'blocked'];
    if (!in_array($relationshipStatus, $allowedStatus, true)) {
        json_response(['ok' => false, 'error' => 'relationship_status invalide'], 422);
    }

    $existingStmt = $pdo->prepare('SELECT * FROM client_supplier_profiles WHERE client_id=:client_id AND supplier_id=:supplier_id LIMIT 1');
    $existingStmt->execute([':client_id' => $clientId, ':supplier_id' => $supplierId]);
    $existing = $existingStmt->fetch();

    $actor = current_actor_context();
    $updatedByType = (string)($actor['actor_type'] ?? 'admin');
    $updatedById = $actor['actor_id'] !== null ? (int)$actor['actor_id'] : null;

    if ($existing) {
        $pdo->prepare('UPDATE client_supplier_profiles SET activity_text=:activity_text, labels_text=:labels_text, notes=:notes, relationship_status=:relationship_status, updated_by_type=:updated_by_type, updated_by_id=:updated_by_id WHERE client_id=:client_id AND supplier_id=:supplier_id')
            ->execute([
                ':activity_text' => $activityText,
                ':labels_text' => $labelsText,
                ':notes' => $notes,
                ':relationship_status' => $relationshipStatus,
                ':updated_by_type' => $updatedByType,
                ':updated_by_id' => $updatedById,
                ':client_id' => $clientId,
                ':supplier_id' => $supplierId,
            ]);
    } else {
        $pdo->prepare('INSERT INTO client_supplier_profiles (client_id, supplier_id, activity_text, labels_text, notes, relationship_status, updated_by_type, updated_by_id) VALUES (:client_id, :supplier_id, :activity_text, :labels_text, :notes, :relationship_status, :updated_by_type, :updated_by_id)')
            ->execute([
                ':client_id' => $clientId,
                ':supplier_id' => $supplierId,
                ':activity_text' => $activityText,
                ':labels_text' => $labelsText,
                ':notes' => $notes,
                ':relationship_status' => $relationshipStatus,
                ':updated_by_type' => $updatedByType,
                ':updated_by_id' => $updatedById,
            ]);
    }

    $trackedFields = [
        'activity_text' => $activityText,
        'labels_text' => $labelsText,
        'notes' => $notes,
        'relationship_status' => $relationshipStatus,
    ];

    foreach ($trackedFields as $field => $newValue) {
        $oldValue = $existing[$field] ?? null;
        if ((string)$oldValue === (string)$newValue) {
            continue;
        }
        write_supplier_audit($pdo, $supplierId, 'client_profile_update', $field, $oldValue, $newValue, ['client_id' => $clientId]);
    }

    json_response(['ok' => true]);
}

function allowed_global_change_fields(): array
{
    return ['name', 'address', 'city', 'postal_code', 'country', 'phone', 'email', 'website', 'supplier_type', 'activity_text', 'labels'];
}

function get_supplier_labels_csv(PDO $pdo, int $supplierId): string
{
    $stmt = $pdo->prepare('SELECT GROUP_CONCAT(DISTINCT l.name ORDER BY l.name SEPARATOR "; ") AS labels_csv FROM supplier_labels sl JOIN labels l ON l.id = sl.label_id WHERE sl.supplier_id=:supplier_id');
    $stmt->execute([':supplier_id' => $supplierId]);
    $row = $stmt->fetch();
    return trim((string)($row['labels_csv'] ?? ''));
}

function get_supplier_change_request_current_value(PDO $pdo, int $supplierId, string $fieldName): string
{
    if ($fieldName === 'labels') {
        return get_supplier_labels_csv($pdo, $supplierId);
    }

    $stmt = $pdo->prepare('SELECT id, ' . $fieldName . ' AS current_value FROM suppliers WHERE id=:id LIMIT 1');
    $stmt->execute([':id' => $supplierId]);
    $supplier = $stmt->fetch();
    if (!$supplier) {
        json_response(['ok' => false, 'error' => 'Fournisseur introuvable'], 404);
    }

    return (string)($supplier['current_value'] ?? '');
}

function assert_client_can_create_change_request(): void
{
    start_app_session();
    if (!empty($_SESSION['is_admin'])) {
        json_response(['ok' => false, 'error' => 'Cette action est réservée aux comptes client'], 403);
    }
    $role = (string)($_SESSION['client_role'] ?? '');
    if ($role === 'client_reader' || $role === '') {
        json_response(['ok' => false, 'error' => 'Droits insuffisants pour proposer des modifications'], 403);
    }
}

function supplier_field_for_change_request(string $fieldName, string $value): string
{
    if ($fieldName === 'phone') {
        return format_phone($value);
    }
    if ($fieldName === 'labels' || $fieldName === 'activity_text') {
        return implode('; ', parse_csv_list($value));
    }
    return trim($value);
}

function sync_supplier_labels(PDO $pdo, int $supplierId, array $labelNames): void
{
    $pdo->prepare('DELETE FROM supplier_labels WHERE supplier_id=:supplier_id')->execute([':supplier_id' => $supplierId]);

    if (!$labelNames) {
        return;
    }

    $insertLabelStmt = $pdo->prepare('INSERT INTO labels (name, color, is_active) VALUES (:name, "", 1) ON DUPLICATE KEY UPDATE name=name');
    $selectLabelStmt = $pdo->prepare('SELECT id FROM labels WHERE name=:name LIMIT 1');
    $insertLinkStmt = $pdo->prepare('INSERT IGNORE INTO supplier_labels (supplier_id, label_id) VALUES (:supplier_id, :label_id)');

    foreach ($labelNames as $labelName) {
        $normalized = trim((string)$labelName);
        if ($normalized === '') {
            continue;
        }
        $insertLabelStmt->execute([':name' => $normalized]);
        $selectLabelStmt->execute([':name' => $normalized]);
        $labelId = (int)($selectLabelStmt->fetchColumn() ?: 0);
        if ($labelId > 0) {
            $insertLinkStmt->execute([':supplier_id' => $supplierId, ':label_id' => $labelId]);
        }
    }
}

function save_supplier_change_request(PDO $pdo): void
{
    assert_client_can_create_change_request();

    $input = get_json_input();
    $clientId = resolve_effective_client_id($input);
    $supplierId = isset($input['supplier_id']) ? (int)$input['supplier_id'] : 0;
    $fieldName = trim((string)($input['field_name'] ?? ''));
    $newValueInput = (string)($input['new_value'] ?? '');

    if ($clientId <= 0 || $supplierId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id et supplier_id requis'], 422);
    }
    if ($fieldName === '' || !in_array($fieldName, allowed_global_change_fields(), true)) {
        json_response(['ok' => false, 'error' => 'Champ global non autorisé'], 422);
    }

    assert_client_has_supplier_link($pdo, $clientId, $supplierId);

    $newValue = supplier_field_for_change_request($fieldName, $newValueInput);
    $oldValue = get_supplier_change_request_current_value($pdo, $supplierId, $fieldName);
    if (trim($oldValue) === trim($newValue)) {
        json_response(['ok' => false, 'error' => 'La nouvelle valeur est identique à la valeur actuelle'], 422);
    }

    $actor = current_actor_context();
    $requestedByUserId = $actor['actor_type'] === 'client_user' && $actor['actor_id'] !== null
        ? (int)$actor['actor_id']
        : 0;
    if ($requestedByUserId <= 0) {
        json_response(['ok' => false, 'error' => 'Session client invalide'], 401);
    }

    $pdo->prepare('INSERT INTO supplier_change_requests (supplier_id, client_id, requested_by_user_id, field_name, old_value, new_value, status) VALUES (:supplier_id, :client_id, :requested_by_user_id, :field_name, :old_value, :new_value, "pending")')
        ->execute([
            ':supplier_id' => $supplierId,
            ':client_id' => $clientId,
            ':requested_by_user_id' => $requestedByUserId,
            ':field_name' => $fieldName,
            ':old_value' => $oldValue,
            ':new_value' => $newValue,
        ]);

    write_supplier_audit($pdo, $supplierId, 'change_request_created', $fieldName, $oldValue, $newValue, ['client_id' => $clientId]);

    json_response(['ok' => true]);
}

function list_supplier_change_requests_for_client(PDO $pdo): void
{
    $inputClientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
    $supplierId = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
    $status = trim((string)($_GET['status'] ?? ''));

    $clientId = resolve_effective_client_id(['client_id' => $inputClientId]);
    if ($clientId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id requis'], 422);
    }

    $sql = 'SELECT scr.id, scr.supplier_id, scr.client_id, scr.requested_by_user_id, scr.field_name, scr.old_value, scr.new_value, scr.status, scr.review_note, scr.reviewed_by_admin, scr.reviewed_at, scr.created_at, s.name AS supplier_name, cu.username AS requested_by_username FROM supplier_change_requests scr JOIN suppliers s ON s.id = scr.supplier_id LEFT JOIN client_users cu ON cu.id = scr.requested_by_user_id WHERE scr.client_id=:client_id';
    $params = [':client_id' => $clientId];

    if ($supplierId > 0) {
        $sql .= ' AND scr.supplier_id=:supplier_id';
        $params[':supplier_id'] = $supplierId;
    }
    if ($status !== '' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $sql .= ' AND scr.status=:status';
        $params[':status'] = $status;
    }

    $sql .= ' ORDER BY scr.created_at DESC, scr.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    json_response(['ok' => true, 'requests' => $stmt->fetchAll()]);
}

function list_supplier_change_requests_for_admin(PDO $pdo): void
{
    $status = trim((string)($_GET['status'] ?? 'pending'));
    $supplierId = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
    $clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

    $sql = 'SELECT scr.id, scr.supplier_id, scr.client_id, scr.requested_by_user_id, scr.field_name, scr.old_value, scr.new_value, scr.status, scr.review_note, scr.reviewed_by_admin, scr.reviewed_at, scr.created_at, s.name AS supplier_name, c.name AS client_name, cu.username AS requested_by_username FROM supplier_change_requests scr JOIN suppliers s ON s.id = scr.supplier_id JOIN clients c ON c.id = scr.client_id LEFT JOIN client_users cu ON cu.id = scr.requested_by_user_id WHERE 1=1';
    $params = [];

    if ($status !== '' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $sql .= ' AND scr.status=:status';
        $params[':status'] = $status;
    }
    if ($supplierId > 0) {
        $sql .= ' AND scr.supplier_id=:supplier_id';
        $params[':supplier_id'] = $supplierId;
    }
    if ($clientId > 0) {
        $sql .= ' AND scr.client_id=:client_id';
        $params[':client_id'] = $clientId;
    }

    $sql .= ' ORDER BY (scr.status="pending") DESC, scr.created_at DESC, scr.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    json_response(['ok' => true, 'requests' => $stmt->fetchAll()]);
}

function review_supplier_change_request(PDO $pdo): void
{
    $input = get_json_input();
    $requestId = isset($input['id']) ? (int)$input['id'] : 0;
    $decision = trim((string)($input['decision'] ?? ''));
    $reviewNote = trim((string)($input['review_note'] ?? ''));

    if ($requestId <= 0) {
        json_response(['ok' => false, 'error' => 'Demande invalide'], 422);
    }
    if (!in_array($decision, ['approved', 'rejected'], true)) {
        json_response(['ok' => false, 'error' => 'Décision invalide'], 422);
    }

    $stmt = $pdo->prepare('SELECT * FROM supplier_change_requests WHERE id=:id LIMIT 1');
    $stmt->execute([':id' => $requestId]);
    $request = $stmt->fetch();
    if (!$request) {
        json_response(['ok' => false, 'error' => 'Demande introuvable'], 404);
    }
    if ((string)$request['status'] !== 'pending') {
        json_response(['ok' => false, 'error' => 'Cette demande est déjà traitée'], 422);
    }

    $fieldName = (string)$request['field_name'];
    if (!in_array($fieldName, allowed_global_change_fields(), true)) {
        json_response(['ok' => false, 'error' => 'Champ de demande invalide'], 422);
    }

    start_app_session();
    $reviewedBy = (string)($_SESSION['admin_username'] ?? 'admin');

    $pdo->beginTransaction();
    try {
        apply_change_request_decision($pdo, $request, $requestId, $decision, $reviewNote, $reviewedBy);
        $pdo->commit();
        json_response(['ok' => true]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function apply_change_request_decision(PDO $pdo, array $request, int $requestId, string $decision, string $reviewNote, string $reviewedBy): void
{
    $fieldName = (string)$request['field_name'];

    if ($decision === 'approved') {
        $newValue = supplier_field_for_change_request($fieldName, (string)($request['new_value'] ?? ''));
        $oldValue = (string)($request['old_value'] ?? '');

        if ($fieldName === 'labels') {
            sync_supplier_labels($pdo, (int)$request['supplier_id'], parse_csv_list($newValue));
        } else {
            $sql = 'UPDATE suppliers SET ' . $fieldName . '=:new_value WHERE id=:supplier_id';
            $pdo->prepare($sql)->execute([
                ':new_value' => $newValue,
                ':supplier_id' => (int)$request['supplier_id'],
            ]);
        }

        write_supplier_audit(
            $pdo,
            (int)$request['supplier_id'],
            'change_request_approved_apply',
            $fieldName,
            $oldValue,
            $newValue,
            ['request_id' => $requestId, 'client_id' => (int)$request['client_id']]
        );
    }

    $pdo->prepare('UPDATE supplier_change_requests SET status=:status, reviewed_by_admin=:reviewed_by_admin, reviewed_at=NOW(), review_note=:review_note WHERE id=:id')
        ->execute([
            ':status' => $decision,
            ':reviewed_by_admin' => $reviewedBy,
            ':review_note' => $reviewNote,
            ':id' => $requestId,
        ]);

    write_supplier_audit(
        $pdo,
        (int)$request['supplier_id'],
        'change_request_' . $decision,
        $fieldName,
        (string)($request['old_value'] ?? ''),
        (string)($request['new_value'] ?? ''),
        ['request_id' => $requestId, 'client_id' => (int)$request['client_id'], 'review_note' => $reviewNote]
    );
}

function review_supplier_change_requests_bulk(PDO $pdo): void
{
    $input = get_json_input();
    $ids = array_map('intval', (array)($input['ids'] ?? []));
    $ids = array_values(array_filter($ids, fn($v) => $v > 0));
    $decision = trim((string)($input['decision'] ?? 'approved'));
    $reviewNote = trim((string)($input['review_note'] ?? ''));

    if (!$ids) {
        json_response(['ok' => false, 'error' => 'Aucune demande à traiter'], 422);
    }
    if (!in_array($decision, ['approved', 'rejected'], true)) {
        json_response(['ok' => false, 'error' => 'Décision invalide'], 422);
    }

    $inClause = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare('SELECT * FROM supplier_change_requests WHERE id IN (' . $inClause . ') ORDER BY id');
    $stmt->execute($ids);
    $requests = $stmt->fetchAll();
    if (!$requests) {
        json_response(['ok' => false, 'error' => 'Demandes introuvables'], 404);
    }

    start_app_session();
    $reviewedBy = (string)($_SESSION['admin_username'] ?? 'admin');

    $processed = 0;
    $skipped = 0;
    $pdo->beginTransaction();
    try {
        foreach ($requests as $request) {
            $requestId = (int)$request['id'];
            if ((string)($request['status'] ?? '') !== 'pending') {
                $skipped++;
                continue;
            }

            $fieldName = (string)($request['field_name'] ?? '');
            if (!in_array($fieldName, allowed_global_change_fields(), true)) {
                $skipped++;
                continue;
            }

            apply_change_request_decision($pdo, $request, $requestId, $decision, $reviewNote, $reviewedBy);
            $processed++;
        }

        $pdo->commit();
        json_response(['ok' => true, 'processed' => $processed, 'skipped' => $skipped]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function map_data(PDO $pdo): void
{
    $settingsRows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    $settings = [];
    foreach ($settingsRows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $clients = $pdo->query('SELECT id, name, client_type, latitude, longitude, logo_url, city, address, postal_code, phone, email, lundi, mardi, mercredi, jeudi, vendredi, samedi, dimanche, website FROM clients WHERE is_active=1 ORDER BY name')->fetchAll();

    $activities = $pdo->query('SELECT name, family, icon_url FROM activities WHERE is_active=1')->fetchAll();
    $labels = $pdo->query('SELECT name, color FROM labels WHERE is_active=1')->fetchAll();

    $sql = "SELECT
            s.id,
            s.name,
            s.latitude,
            s.longitude,
            s.address,
            s.city,
            s.phone,
            s.email,
            s.website,
            s.supplier_type,
            s.activity_text,
            GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR '; ') AS clients,
            GROUP_CONCAT(DISTINCT l.name ORDER BY l.name SEPARATOR '; ') AS labels
        FROM suppliers s
        LEFT JOIN client_suppliers cs ON cs.supplier_id = s.id
        LEFT JOIN clients c ON c.id = cs.client_id
        LEFT JOIN supplier_labels sl ON sl.supplier_id = s.id
        LEFT JOIN labels l ON l.id = sl.label_id
        GROUP BY s.id";
    $rows = $pdo->query($sql)->fetchAll();

    $allData = array_map(function ($r) {
        return [
            'id' => (int)$r['id'],
            'nom' => $r['name'],
            'latitude' => $r['latitude'],
            'longitude' => $r['longitude'],
            'adresse' => $r['address'],
            'ville' => $r['city'],
            'telephone' => format_phone($r['phone'] ?? ''),
            'email' => $r['email'],
            'website' => $r['website'],
            'type' => $r['supplier_type'],
            'activite' => $r['activity_text'],
            'client' => $r['clients'] ?? '',
            'label' => $r['labels'] ?? '',
            '_labelsArr' => parse_csv_list($r['labels'] ?? ''),
            '_labelsNorm' => array_map(fn($x) => normalize_text($x), parse_csv_list($r['labels'] ?? '')),
        ];
    }, $rows);

    $iconMapNorm = [];
    foreach ($activities as $activity) {
        if (!empty($activity['icon_url'])) {
            $iconMapNorm[normalize_text((string)$activity['name'])] = $activity['icon_url'];
        }
    }

    $labelColorMapNorm = [];
    foreach ($labels as $label) {
        if (!empty($label['color'])) {
            $labelColorMapNorm[normalize_text((string)$label['name'])] = $label['color'];
        }
    }

    json_response([
        'ok' => true,
        'source' => 'db',
        'org_name' => $settings['org_name'] ?? '',
        'org_logo_url' => $settings['org_logo_url'] ?? '',
        'default_client_icon' => $settings['default_client_icon'] ?? '',
        'default_producer_icon' => $settings['default_producer_icon'] ?? '',
        'farm_direct_icon' => $settings['farm_direct_icon'] ?? '',
        'clients' => $clients,
        'data' => $allData,
        'icon_map_norm' => $iconMapNorm,
        'label_color_map_norm' => $labelColorMapNorm,
        'activities' => $activities,
        'labels' => $labels,
    ]);
}
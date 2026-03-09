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

        case 'admin/activity/save':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_activity($pdo);
            break;

        case 'admin/label/save':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_label($pdo);
            break;

        case 'admin/supplier/save':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_supplier($pdo, 'manual');
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

    $config = require __DIR__ . '/config.php';
    if ($username !== $config['admin']['username'] || $password !== $config['admin']['password']) {
        json_response(['ok' => false, 'error' => 'Identifiants invalides'], 401);
    }

    start_app_session();
    $_SESSION['is_admin'] = true;
    $_SESSION['admin_username'] = $username;
    json_response(['ok' => true, 'username' => $username]);
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
    ]);
}

function admin_bootstrap(PDO $pdo): void
{
    $clients = $pdo->query('SELECT * FROM clients ORDER BY name')->fetchAll();
    $activities = $pdo->query('SELECT * FROM activities ORDER BY family, name')->fetchAll();
    $labels = $pdo->query('SELECT * FROM labels ORDER BY name')->fetchAll();

    $suppliers = $pdo->query(
        "SELECT s.*, GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') AS clients
         FROM suppliers s
         LEFT JOIN client_suppliers cs ON cs.supplier_id = s.id
         LEFT JOIN clients c ON c.id = cs.client_id
         GROUP BY s.id
         ORDER BY s.name"
    )->fetchAll();

    $settingsRows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    $settings = [];
    foreach ($settingsRows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    json_response([
        'ok' => true,
        'clients' => $clients,
        'activities' => $activities,
        'labels' => $labels,
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
        ':phone' => trim((string)($input['phone'] ?? '')),
        ':email' => trim((string)($input['email'] ?? '')),
        ':website' => trim((string)($input['website'] ?? '')),
        ':logo_url' => trim((string)($input['logo_url'] ?? '')),
        ':is_active' => !empty($input['is_active']) ? 1 : 0,
    ];

    if ($id > 0) {
        $payload[':id'] = $id;
        $sql = "UPDATE clients SET
            name=:name, client_type=:client_type, address=:address, city=:city, postal_code=:postal_code,
            country=:country, latitude=:latitude, longitude=:longitude, phone=:phone, email=:email,
            website=:website, logo_url=:logo_url, is_active=:is_active
            WHERE id=:id";
        $pdo->prepare($sql)->execute($payload);
    } else {
        $sql = "INSERT INTO clients
            (name, client_type, address, city, postal_code, country, latitude, longitude, phone, email, website, logo_url, is_active)
            VALUES
            (:name, :client_type, :address, :city, :postal_code, :country, :latitude, :longitude, :phone, :email, :website, :logo_url, :is_active)";
        $pdo->prepare($sql)->execute($payload);
    }

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

    $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . rawurlencode($address);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => "User-Agent: AppCarteLimap/1.0\r\nAccept: application/json\r\n",
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        json_response(['ok' => false, 'error' => 'Service de géocodage indisponible'], 502);
    }

    $rows = json_decode($raw, true);
    if (!is_array($rows) || count($rows) === 0) {
        json_response(['ok' => true, 'found' => false]);
    }

    $first = $rows[0];
    json_response([
        'ok' => true,
        'found' => true,
        'lat' => isset($first['lat']) ? (float)$first['lat'] : null,
        'lng' => isset($first['lon']) ? (float)$first['lon'] : null,
        'display_name' => $first['display_name'] ?? '',
    ]);
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
    persist_supplier($pdo, $input, $source, []);
    json_response(['ok' => true]);
}

function persist_supplier(PDO $pdo, array $input, string $source, array $resolutions): array
{
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
        'phone' => trim((string)($input['phone'] ?? '')),
        'email' => trim((string)($input['email'] ?? '')),
        'website' => trim((string)($input['website'] ?? '')),
        'supplier_type' => trim((string)($input['supplier_type'] ?? ($input['type'] ?? ''))),
        'activity_text' => implode('; ', $activityNames),
        'notes' => trim((string)($input['notes'] ?? '')),
    ];

    if ($supplier['name'] === '') {
        throw new RuntimeException('Nom fournisseur requis');
    }

    $existing = find_existing_supplier($pdo, $supplier);
    $supplierId = null;

    if ($existing) {
        $supplierId = (int)$existing['id'];
        $fields = ['phone', 'email', 'activity_text', 'supplier_type'];
        $updatePayload = [];
        foreach ($fields as $field) {
            $incoming = (string)($supplier[$field] ?? '');
            $current = (string)($existing[$field] ?? '');
            if ($incoming === '' || $incoming === $current) {
                continue;
            }
            $mode = $resolutions[$field] ?? 'keep_existing';
            if ($mode === 'replace_existing') {
                $updatePayload[$field] = $incoming;
            }
        }

        if ($updatePayload) {
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

    foreach ($clientIds as $clientId) {
        if ($clientId <= 0) {
            continue;
        }
        $stmt = $pdo->prepare('INSERT IGNORE INTO client_suppliers (client_id, supplier_id, source) VALUES (:client_id, :supplier_id, :source)');
        $stmt->execute([':client_id' => $clientId, ':supplier_id' => $supplierId, ':source' => $source]);
    }

    sync_activity_links($pdo, $supplierId, $activityNames);
    sync_label_links($pdo, $supplierId, $labelNames);

    return ['id' => $supplierId, 'existing' => (bool)$existing];
}

function sync_activity_links(PDO $pdo, int $supplierId, array $activityNames): void
{
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

function sync_label_links(PDO $pdo, int $supplierId, array $labelNames): void
{
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

        $existing = find_existing_supplier($pdo, $supplier);
        $conflicts = [];
        if ($existing) {
            $summary['existing']++;
            foreach (['phone', 'email', 'activity_text', 'supplier_type'] as $field) {
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
        'phone' => trim((string)($normalized['telephone'] ?? $normalized['tel'] ?? $normalized['phone'] ?? '')),
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

            $rowResolution = (array)($resolutions[$rowIdx] ?? []);
            $payload['client_ids'] = [$clientId];
            $result = persist_supplier($pdo, $payload, 'import', $rowResolution);
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

function map_data(PDO $pdo): void
{
    $settingsRows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    $settings = [];
    foreach ($settingsRows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $clients = $pdo->query('SELECT id, name, client_type, latitude, longitude, logo_url, city, address, postal_code, phone, email, website FROM clients WHERE is_active=1 ORDER BY name')->fetchAll();

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
            'telephone' => $r['phone'],
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
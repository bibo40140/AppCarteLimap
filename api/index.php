<?php

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/consent.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$diagEnabled = isset($_GET['diag']) && (string)$_GET['diag'] === '1';

try {
    $pdo = get_db();
} catch (Throwable $e) {
    error_log('AppCarte API bootstrap failure: ' . $e->getMessage());
    $payload = ['ok' => false, 'error' => 'Internal server error'];
    if ($diagEnabled) {
        $payload['detail'] = $e->getMessage();
    }
    json_response($payload, 500);
}

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

        case 'auth/password-reset/request':
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Methode invalide'], 405);
            }
            request_password_reset($pdo);
            break;

        case 'auth/password-reset/confirm':
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Methode invalide'], 405);
            }
            confirm_password_reset($pdo);
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

        case 'admin/client/export':
            require_admin();
            export_clients_csv($pdo);
            break;

        case 'admin/client/delete':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            delete_client($pdo);
            break;

        case 'admin/wordpress-sync/clients-resync':
            require_admin();
            if ($method !== 'POST' && $method !== 'GET') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            resync_all_clients_to_wordpress($pdo);
            break;

        case 'admin/wordpress-sync/suppliers-resync':
            require_admin();
            if ($method !== 'POST' && $method !== 'GET') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            resync_all_suppliers_to_wordpress($pdo);
            break;

        case 'admin/producer/export':
            require_admin();
            export_producers_csv($pdo);
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

        case 'admin/client-user/delete':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            delete_client_user($pdo);
            break;

        case 'admin/client-user/send-reset-link':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Methode invalide'], 405);
            }
            admin_send_client_user_reset_link($pdo);
            break;

        case 'admin/admin-user/save':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_admin_user($pdo);
            break;

        case 'admin/admin-user/toggle-active':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            toggle_admin_user_active($pdo);
            break;

        case 'admin/admin-user/reset-password':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            reset_admin_user_password($pdo);
            break;

        case 'admin/admin-user/delete':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            delete_admin_user($pdo);
            break;

        case 'admin/admin-user/send-reset-link':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Methode invalide'], 405);
            }
            admin_send_admin_user_reset_link($pdo);
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

        case 'admin/notification/test':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            send_test_notification($pdo);
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

        case 'admin/audit/list':
            require_admin();
            list_admin_audit_logs($pdo);
            break;

        case 'audit/ui-event':
            require_client_or_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            track_ui_event($pdo);
            break;

        case 'client/bootstrap':
            require_client_or_admin();
            client_bootstrap($pdo);
            break;

        case 'client/upload/client-logo':
            require_client_or_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            upload_client_logo();
            break;

        case 'client/upload/gallery-images':
            require_client_or_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            upload_client_gallery_images();
            break;

        case 'client/geocode':
            require_client_or_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            geocode_address_client();
            break;

        case 'client/profile/save':
            require_client_or_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_client_profile($pdo);
            break;

        case 'client/supplier/profile/save':
            require_client_or_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_client_supplier_profile($pdo);
            break;

        case 'client/supplier-create-request/save':
            require_client_or_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_supplier_creation_request($pdo);
            break;

        case 'client/supplier-create-request/list':
            require_client_or_admin();
            list_supplier_creation_requests_for_client($pdo);
            break;

        case 'client/supplier-link-search':
            require_client_or_admin();
            search_supplier_link_candidates_for_client($pdo);
            break;

        case 'client/supplier-link-request/save':
            require_client_or_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            save_supplier_link_request($pdo);
            break;

        case 'client/supplier-link-request/list':
            require_client_or_admin();
            list_supplier_link_requests_for_client($pdo);
            break;

        // ==================== Phase 3: Consent Routes ====================
        case 'client/consent/confirm':
            require_client_or_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            confirm_client_consent($pdo);
            break;

        case 'client/consent/revoke':
            require_client_or_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            revoke_client_consent($pdo);
            break;

        case 'client/supplier-consent/send':
            require_client_or_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            send_supplier_consent_request($pdo);
            break;

        case 'client/supplier-consent/send-bulk':
            require_client_or_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            send_supplier_consent_requests_bulk($pdo);
            break;

        case 'client/supplier-consent/history':
            require_client_or_admin();
            get_supplier_consent_history($pdo);
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

        case 'admin/supplier-create-request/list':
            require_admin();
            list_supplier_creation_requests_for_admin($pdo);
            break;

        case 'admin/supplier-link-request/list':
            require_admin();
            list_supplier_link_requests_for_admin($pdo);
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

        case 'admin/supplier-create-request/review':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            review_supplier_creation_request($pdo);
            break;

        case 'admin/supplier-link-request/review':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            review_supplier_link_request($pdo);
            break;

        case 'map-data':
            map_data($pdo);
            break;

        // ==================== Phase 3: Public Supplier Routes ====================
        case 'supplier/consent/view':
            // GET request viewing consent from token (public, no auth)
            if ($method !== 'GET') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            view_supplier_consent_from_token($pdo);
            break;

        case 'supplier/consent/approve':
            // POST approval via token (public, no auth)
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            approve_supplier_consent_from_token($pdo);
            break;

        case 'supplier/consent/reject':
            // POST rejection via token (public, no auth)
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            reject_supplier_consent_from_token($pdo);
            break;

        // ==================== Phase 3: Admin Routes ====================
        case 'admin/consent-overview':
            require_admin();
            get_consent_overview_for_admin($pdo);
            break;

        case 'admin/supplier-consent/resend':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            resend_supplier_consent_for_admin($pdo);
            break;

        case 'admin/client-consent/revoke':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            revoke_client_consent_for_admin($pdo);
            break;

        case 'admin/supplier-consent/revoke':
            require_admin();
            if ($method !== 'POST') {
                json_response(['ok' => false, 'error' => 'Méthode invalide'], 405);
            }
            revoke_supplier_consent_for_admin($pdo);
            break;

        // ==================== Original Routes ====================
        case 'map-data':

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
        write_admin_audit($pdo, 'auth_login_failed', [
            'actor_type' => 'anonymous',
            'actor_name' => $username !== '' ? $username : 'anonymous',
            'target_type' => 'auth',
            'target_label' => 'login',
            'details' => ['reason' => 'missing_credentials'],
        ]);
        json_response(['ok' => false, 'error' => 'Identifiants invalides'], 401);
    }

    $config = require __DIR__ . '/config.php';
    $adminUsername = (string)($config['admin']['username'] ?? 'admin');
    $adminPasswordHash = (string)($config['admin']['password_hash'] ?? '');
    $adminMatches = false;

    $stmtAdmin = $pdo->prepare('SELECT id, username, password_hash, is_active FROM admin_users WHERE username=:username LIMIT 1');
    $stmtAdmin->execute([':username' => $username]);
    $adminRow = $stmtAdmin->fetch();
    if ($adminRow && (int)($adminRow['is_active'] ?? 0) === 1) {
        $adminMatches = password_verify($password, (string)($adminRow['password_hash'] ?? ''));
    }

    if ($username === $adminUsername) {
        $adminMatches = $adminMatches || ($adminPasswordHash !== '' && password_verify($password, $adminPasswordHash));
    }

    if ($adminMatches) {
        start_app_session();
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_user_id'] = $adminRow ? (int)($adminRow['id'] ?? 0) : null;
        $_SESSION['admin_username'] = $adminRow ? (string)($adminRow['username'] ?? $username) : $username;
        unset($_SESSION['is_client_user'], $_SESSION['client_user_id'], $_SESSION['client_id'], $_SESSION['client_role']);
        if ($adminRow) {
            $pdo->prepare('UPDATE admin_users SET last_login_at=NOW() WHERE id=:id')->execute([':id' => (int)$adminRow['id']]);
        }
        write_admin_audit($pdo, 'auth_login_success', [
            'actor_type' => 'admin',
            'actor_id' => $adminRow ? (int)$adminRow['id'] : null,
            'actor_name' => (string)($_SESSION['admin_username'] ?? $username),
            'target_type' => 'auth',
            'target_label' => 'login',
            'details' => ['auth_role' => 'admin'],
        ]);
        json_response(['ok' => true, 'username' => (string)($_SESSION['admin_username'] ?? $username), 'role' => 'admin']);
    }

    $stmt = $pdo->prepare('SELECT cu.id, cu.client_id, cu.username, cu.password_hash, cu.role, cu.is_active, c.name AS client_name FROM client_users cu JOIN clients c ON c.id = cu.client_id WHERE cu.username=:username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch();

    if (!$row || (int)$row['is_active'] !== 1 || !password_verify($password, (string)$row['password_hash'])) {
        write_admin_audit($pdo, 'auth_login_failed', [
            'actor_type' => 'anonymous',
            'actor_name' => $username,
            'target_type' => 'auth',
            'target_label' => 'login',
            'details' => ['reason' => 'invalid_credentials'],
        ]);
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
    unset($_SESSION['admin_username'], $_SESSION['admin_user_id']);

    write_admin_audit($pdo, 'auth_login_success', [
        'actor_type' => 'client_user',
        'actor_id' => (int)$row['id'],
        'actor_name' => (string)$row['username'],
        'target_type' => 'auth',
        'target_label' => 'login',
        'details' => ['auth_role' => (string)$row['role'], 'client_id' => (int)$row['client_id']],
    ]);

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
    try {
        $pdo = get_db();
        write_admin_audit($pdo, 'auth_logout', [
            'target_type' => 'auth',
            'target_label' => 'logout',
        ]);
    } catch (Throwable $e) {
        error_log('AppCarte: logout audit failed: ' . $e->getMessage());
    }

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
        'admin_user_id' => isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : null,
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
    ensure_admin_audit_log_table($pdo);
    $clients = $pdo->query('SELECT * FROM clients WHERE is_active=1 ORDER BY name')->fetchAll();
    $clientUsers = $pdo->query('SELECT cu.id, cu.client_id, cu.username, cu.email, cu.role, cu.is_active, cu.last_login_at, cu.created_at, c.name AS client_name FROM client_users cu JOIN clients c ON c.id = cu.client_id ORDER BY c.name, cu.username')->fetchAll();
    $adminUsers = $pdo->query('SELECT id, username, email, is_active, last_login_at, created_at FROM admin_users ORDER BY username')->fetchAll();
    $resetAudit = $pdo->query('SELECT id, user_type, user_id, username, email, status, error_message, created_at FROM password_reset_audit ORDER BY id DESC LIMIT 30')->fetchAll();
    $auditLogs = $pdo->query('SELECT id, actor_type, actor_id, actor_name, action_name, target_type, target_id, target_label, details_json, ip_address, user_agent, created_at FROM admin_audit_log ORDER BY id DESC LIMIT 150')->fetchAll();
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

    $clients = array_map(function (array $client) use ($pdo) {
        $client['phone'] = format_phone($client['phone'] ?? '');
        $client['logo_url'] = absolutize_export_url($pdo, (string)($client['logo_url'] ?? ''));
        $client['photo_cover_url'] = absolutize_export_url($pdo, (string)($client['photo_cover_url'] ?? ''));
        return $client;
    }, $clients);

    $suppliers = array_map(function (array $supplier) use ($pdo) {
        $supplier['phone'] = format_phone($supplier['phone'] ?? '');
        $supplier['logo_url'] = absolutize_export_url($pdo, (string)($supplier['logo_url'] ?? ''));
        $supplier['photo_cover_url'] = absolutize_export_url($pdo, (string)($supplier['photo_cover_url'] ?? ''));
        return $supplier;
    }, $suppliers);

    $activities = array_map(function (array $activity) use ($pdo) {
        $activity['icon_url'] = absolutize_export_url($pdo, (string)($activity['icon_url'] ?? ''));
        return $activity;
    }, $activities);

    $settingsRows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    $settings = [];
    foreach ($settingsRows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $linkRequestPending = (int)$pdo->query('SELECT COUNT(*) FROM client_supplier_link_requests WHERE status="pending"')->fetchColumn();

    json_response([
        'ok' => true,
        'clients' => $clients,
        'client_users' => $clientUsers,
        'admin_users' => $adminUsers,
        'activities' => $activities,
        'labels' => $labels,
        'supplier_types' => $supplierTypes,
        'suppliers' => $suppliers,
        'settings' => $settings,
        'password_reset_audit' => $resetAudit,
        'audit_logs' => $auditLogs,
        'supplier_link_request_pending_count' => $linkRequestPending,
    ]);
}

function ensure_admin_audit_log_table(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS admin_audit_log (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        actor_type VARCHAR(30) NOT NULL,
        actor_id INT NULL,
        actor_name VARCHAR(190) NULL,
        action_name VARCHAR(80) NOT NULL,
        target_type VARCHAR(40) NULL,
        target_id INT NULL,
        target_label VARCHAR(255) NULL,
        details_json JSON NULL,
        ip_address VARCHAR(64) NULL,
        user_agent VARCHAR(255) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin_audit_created_at (created_at),
        INDEX idx_admin_audit_actor (actor_type, actor_id),
        INDEX idx_admin_audit_action (action_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $checked = true;
}

function truncate_audit_string(?string $value, int $maxLen): ?string
{
    if ($value === null) {
        return null;
    }
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    if (mb_strlen($value, 'UTF-8') <= $maxLen) {
        return $value;
    }
    return mb_substr($value, 0, $maxLen - 1, 'UTF-8') . '…';
}

function write_admin_audit(PDO $pdo, string $actionName, array $data = []): void
{
    try {
        ensure_admin_audit_log_table($pdo);
        start_app_session();
        $actor = current_actor_context();

        $actorType = (string)($data['actor_type'] ?? ($actor['actor_type'] ?? 'unknown'));
        $actorId = $data['actor_id'] ?? ($actor['actor_id'] ?? null);
        $actorName = (string)($data['actor_name'] ?? ($actor['actor_name'] ?? 'unknown'));

        $targetType = isset($data['target_type']) ? (string)$data['target_type'] : null;
        $targetId = isset($data['target_id']) ? (int)$data['target_id'] : null;
        $targetLabel = isset($data['target_label']) ? (string)$data['target_label'] : null;
        $details = isset($data['details']) && is_array($data['details']) ? $data['details'] : [];

        $ipAddress = truncate_audit_string((string)($_SERVER['REMOTE_ADDR'] ?? ''), 64);
        $userAgent = truncate_audit_string((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 255);

        $pdo->prepare('INSERT INTO admin_audit_log (actor_type, actor_id, actor_name, action_name, target_type, target_id, target_label, details_json, ip_address, user_agent) VALUES (:actor_type, :actor_id, :actor_name, :action_name, :target_type, :target_id, :target_label, :details_json, :ip_address, :user_agent)')
            ->execute([
                ':actor_type' => truncate_audit_string($actorType, 30) ?? 'unknown',
                ':actor_id' => $actorId !== null ? (int)$actorId : null,
                ':actor_name' => truncate_audit_string($actorName, 190),
                ':action_name' => truncate_audit_string($actionName, 80) ?? 'unknown_action',
                ':target_type' => truncate_audit_string($targetType, 40),
                ':target_id' => $targetId,
                ':target_label' => truncate_audit_string($targetLabel, 255),
                ':details_json' => !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent,
            ]);
    } catch (Throwable $e) {
        error_log('AppCarte: failed to write admin_audit_log: ' . $e->getMessage());
    }
}

function list_admin_audit_logs(PDO $pdo): void
{
    ensure_admin_audit_log_table($pdo);

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 150;
    if ($limit <= 0) {
        $limit = 150;
    }
    $limit = min($limit, 500);

    $action = trim((string)($_GET['action_name'] ?? ''));
    $actorType = trim((string)($_GET['actor_type'] ?? ''));

    $sql = 'SELECT id, actor_type, actor_id, actor_name, action_name, target_type, target_id, target_label, details_json, ip_address, user_agent, created_at FROM admin_audit_log WHERE 1=1';
    $params = [];

    if ($action !== '') {
        $sql .= ' AND action_name=:action_name';
        $params[':action_name'] = $action;
    }
    if ($actorType !== '') {
        $sql .= ' AND actor_type=:actor_type';
        $params[':actor_type'] = $actorType;
    }

    $sql .= ' ORDER BY id DESC LIMIT ' . $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    json_response(['ok' => true, 'logs' => $stmt->fetchAll()]);
}

function track_ui_event(PDO $pdo): void
{
    $input = get_json_input();
    $eventName = trim((string)($input['event_name'] ?? ''));
    $eventType = trim((string)($input['event_type'] ?? 'visit'));
    $app = trim((string)($input['app'] ?? ''));
    $page = trim((string)($input['page'] ?? ''));
    $tab = trim((string)($input['tab'] ?? ''));

    if ($eventName === '') {
        json_response(['ok' => false, 'error' => 'event_name requis'], 422);
    }

    if (!preg_match('/^[a-z0-9_:\-\.]{2,80}$/i', $eventName)) {
        json_response(['ok' => false, 'error' => 'event_name invalide'], 422);
    }

    if (!in_array($eventType, ['visit', 'action'], true)) {
        $eventType = 'visit';
    }

    // Deduplicate bursts (same event in less than 2 seconds from same session).
    start_app_session();
    $signature = implode('|', [$eventType, $eventName, $app, $page, $tab]);
    $lastSig = (string)($_SESSION['last_ui_event_signature'] ?? '');
    $lastTs = (int)($_SESSION['last_ui_event_ts'] ?? 0);
    if ($signature === $lastSig && (time() - $lastTs) < 2) {
        json_response(['ok' => true, 'deduped' => true]);
    }
    $_SESSION['last_ui_event_signature'] = $signature;
    $_SESSION['last_ui_event_ts'] = time();

    $targetLabelParts = array_values(array_filter([$app, $page, $tab], static fn($v) => trim((string)$v) !== ''));
    $targetLabel = $targetLabelParts ? implode(' / ', $targetLabelParts) : null;

    $details = [];
    if ($app !== '') {
        $details['app'] = $app;
    }
    if ($page !== '') {
        $details['page'] = $page;
    }
    if ($tab !== '') {
        $details['tab'] = $tab;
    }
    if (isset($input['meta']) && is_array($input['meta'])) {
        $details['meta'] = $input['meta'];
    }

    write_admin_audit($pdo, $eventName, [
        'target_type' => $eventType,
        'target_label' => $targetLabel,
        'details' => $details,
    ]);

    json_response(['ok' => true]);
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
        ':facebook_url' => trim((string)($input['facebook_url'] ?? '')),
        ':instagram_url' => trim((string)($input['instagram_url'] ?? '')),
        ':linkedin_url' => trim((string)($input['linkedin_url'] ?? '')),
        ':logo_url' => trim((string)($input['logo_url'] ?? '')),
        ':photo_cover_url' => trim((string)($input['photo_cover_url'] ?? '')),
        ':slug' => slugify_text((string)($input['slug'] ?? $name)),
        ':description_short' => trim((string)($input['description_short'] ?? '')),
        ':description_long' => trim((string)($input['description_long'] ?? '')),
        ':is_public' => !empty($input['is_public']) ? 1 : 0,
        ':public_updated_at' => date('Y-m-d H:i:s'),
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
            website=:website, facebook_url=:facebook_url, instagram_url=:instagram_url, linkedin_url=:linkedin_url,
            logo_url=:logo_url, photo_cover_url=:photo_cover_url, slug=:slug,
            description_short=:description_short, description_long=:description_long,
            is_public=:is_public, public_updated_at=:public_updated_at, is_active=:is_active
            WHERE id=:id";
        $pdo->prepare($sql)->execute($payload);
    } else {
        $sql = "INSERT INTO clients
            (name, client_type, address, city, postal_code, country, latitude, longitude, phone, email, lundi, mardi, mercredi, jeudi, vendredi, samedi, dimanche, website, facebook_url, instagram_url, linkedin_url, logo_url, photo_cover_url, slug, description_short, description_long, is_public, public_updated_at, is_active)
            VALUES
            (:name, :client_type, :address, :city, :postal_code, :country, :latitude, :longitude, :phone, :email, :lundi, :mardi, :mercredi, :jeudi, :vendredi, :samedi, :dimanche, :website, :facebook_url, :instagram_url, :linkedin_url, :logo_url, :photo_cover_url, :slug, :description_short, :description_long, :is_public, :public_updated_at, :is_active)";
        $pdo->prepare($sql)->execute($payload);
        $id = (int)$pdo->lastInsertId();
    }

    sync_client_to_wordpress($pdo, $id);

    json_response(['ok' => true]);
}

function delete_client(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'ID client invalide'], 422);
    }
    $pdo->prepare('UPDATE clients SET is_active=0 WHERE id=:id')->execute([':id' => $id]);
    sync_client_to_wordpress($pdo, $id);
    json_response(['ok' => true]);
}

function resync_all_clients_to_wordpress(PDO $pdo): void
{
    @set_time_limit(120);

    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $limit = (int)($_GET['limit'] ?? 30);
    if ($limit <= 0 || $limit > 200) {
        $limit = 30;
    }

    $total = (int)$pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();

    $stmt = $pdo->prepare('SELECT id FROM clients ORDER BY id LIMIT :lim OFFSET :off');
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $processed = 0;
    foreach ($rows as $row) {
        $clientId = (int)($row['id'] ?? 0);
        if ($clientId <= 0) {
            continue;
        }
        sync_client_to_wordpress($pdo, $clientId);
        $processed++;
    }

    $nextOffset = $offset + count($rows);
    $done = $nextOffset >= $total;

    json_response([
        'ok' => true,
        'total' => $total,
        'processed' => $processed,
        'synced' => $processed,
        'offset' => $offset,
        'next_offset' => $nextOffset,
        'done' => $done,
    ]);
}

function sync_client_to_wordpress(PDO $pdo, int $clientId): void
{
    if ($clientId <= 0) {
        return;
    }

    try {
        $config = require __DIR__ . '/config.php';
        $sync = is_array($config['wordpress_sync'] ?? null) ? $config['wordpress_sync'] : [];
        $enabled = !empty($sync['enabled']);
        if (!$enabled) {
            return;
        }

        $endpoint = trim((string)($sync['endpoint'] ?? ''));
        $secret = (string)($sync['secret'] ?? '');
        $timeout = max(2, (int)($sync['timeout_seconds'] ?? 8));

        if ($endpoint === '' || $secret === '') {
            write_admin_audit($pdo, 'wordpress_sync_client_failed', [
                'target_type' => 'client',
                'target_id' => $clientId,
                'details' => ['reason' => 'missing_config'],
            ]);
            return;
        }

        $payload = build_client_sync_payload($pdo, $clientId);
        $variants = build_client_sync_payload_variants($payload);

        $statusCode = 0;
        $responseBody = '';
        $acceptedVariant = -1;

        foreach ($variants as $idx => $variantPayload) {
            $json = json_encode($variantPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($json)) {
                continue;
            }

            $timestamp = (string)time();
            $signature = hash_hmac('sha256', $timestamp . '.' . $json, $secret);
            [$statusCode, $responseBody] = post_json_signed($endpoint, $json, $timestamp, $signature, $timeout);

            if ($statusCode >= 200 && $statusCode < 300) {
                $acceptedVariant = $idx;
                break;
            }

            $responseText = (string)$responseBody;
            if (stripos($responseText, 'missing_id_source') === false) {
                // Do not continue fallback attempts for non-schema errors.
                break;
            }
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('HTTP ' . $statusCode . ' - ' . mb_substr((string)$responseBody, 0, 700));
        }

        write_admin_audit($pdo, 'wordpress_sync_client_ok', [
            'target_type' => 'client',
            'target_id' => $clientId,
            'details' => [
                'status_code' => $statusCode,
                'variant' => $acceptedVariant,
            ],
        ]);
    } catch (Throwable $e) {
        write_admin_audit($pdo, 'wordpress_sync_client_failed', [
            'target_type' => 'client',
            'target_id' => $clientId,
            'details' => [
                'error' => $e->getMessage(),
            ],
        ]);
        error_log('AppCarte WP sync failed for client #' . $clientId . ': ' . $e->getMessage());
    }
}

function build_client_sync_payload(PDO $pdo, int $clientId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, name, slug, client_type, address, city, postal_code, country, latitude, longitude,
                phone, email, website, facebook_url, instagram_url, linkedin_url,
            logo_url, photo_cover_url, description_short, description_long, gallery_images,
                lundi, mardi, mercredi, jeudi, vendredi, samedi, dimanche,
                is_active, is_public, updated_at
         FROM clients
         WHERE id=:id
         LIMIT 1'
    );
    $stmt->execute([':id' => $clientId]);
    $client = $stmt->fetch();

    if (!$client) {
        return [
            'id_source' => $clientId,
            'public_visible' => false,
            'deleted' => true,
        ];
    }

    $publicVisible = is_client_publicly_visible($pdo, $clientId);

    $name = (string)($client['name'] ?? '');
    $slug = (string)($client['slug'] ?? '');
    $clientType = (string)($client['client_type'] ?? '');
    $address = (string)($client['address'] ?? '');
    $city = (string)($client['city'] ?? '');
    $postalCode = (string)($client['postal_code'] ?? '');
    $country = (string)($client['country'] ?? '');
    $phone = (string)($client['phone'] ?? '');
    $email = (string)($client['email'] ?? '');
    $website = (string)($client['website'] ?? '');
    $facebookUrl = (string)($client['facebook_url'] ?? '');
    $instagramUrl = (string)($client['instagram_url'] ?? '');
    $linkedinUrl = (string)($client['linkedin_url'] ?? '');
    $logoUrl = absolutize_export_url($pdo, (string)($client['logo_url'] ?? ''));
    $coverUrl = absolutize_export_url($pdo, (string)($client['photo_cover_url'] ?? ''));
    $descriptionShort = (string)($client['description_short'] ?? '');
    $descriptionLong = (string)($client['description_long'] ?? '');
    $galleryUrls = absolutize_gallery_images_list($pdo, (string)($client['gallery_images'] ?? ''));
    $galleryImagesJson = $galleryUrls
        ? json_encode(array_map(static fn(string $url): array => ['url' => $url], $galleryUrls), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : '';
    $lundi = (string)($client['lundi'] ?? '');
    $mardi = (string)($client['mardi'] ?? '');
    $mercredi = (string)($client['mercredi'] ?? '');
    $jeudi = (string)($client['jeudi'] ?? '');
    $vendredi = (string)($client['vendredi'] ?? '');
    $samedi = (string)($client['samedi'] ?? '');
    $dimanche = (string)($client['dimanche'] ?? '');

    $operation = $publicVisible ? 'upsert' : 'delete';

    $hoursFr = [
        'lundi' => $lundi,
        'mardi' => $mardi,
        'mercredi' => $mercredi,
        'jeudi' => $jeudi,
        'vendredi' => $vendredi,
        'samedi' => $samedi,
        'dimanche' => $dimanche,
    ];
    $hoursEn = [
        'monday' => $lundi,
        'tuesday' => $mardi,
        'wednesday' => $mercredi,
        'thursday' => $jeudi,
        'friday' => $vendredi,
        'saturday' => $samedi,
        'sunday' => $dimanche,
    ];

    $descriptionLongText = trim(strip_tags(html_entity_decode($descriptionLong, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

    return [
        'id_source' => (int)$client['id'],
        'name' => $name,
        'slug' => $slug,
        'type' => $clientType,
        'client_type' => $clientType,
        'address' => $address,
        'city' => $city,
        'postal_code' => $postalCode,
        'country' => $country,
        'latitude' => ($client['latitude'] ?? null) !== null ? (float)$client['latitude'] : null,
        'longitude' => ($client['longitude'] ?? null) !== null ? (float)$client['longitude'] : null,
        'phone' => $phone,
        'email' => $email,
        'website' => $website,
        'facebook_url' => $facebookUrl,
        'facebook' => $facebookUrl,
        'instagram_url' => $instagramUrl,
        'instagram' => $instagramUrl,
        'linkedin_url' => $linkedinUrl,
        'linkedin' => $linkedinUrl,
        'logo_url' => $logoUrl,
        'photo_cover_url' => $coverUrl,
        'description_short' => $descriptionShort,
        'description_long' => $descriptionLong,
        'gallery_images' => $galleryImagesJson,
        'description_long_text' => $descriptionLongText,
        'wp_post_title' => $name,
        'wp_post_name' => $slug,
        'wp_post_excerpt' => $descriptionShort,
        'wp_post_content' => $descriptionLong,
        'wp_post_content_text' => $descriptionLongText,
        'lundi' => $lundi,
        'mardi' => $mardi,
        'mercredi' => $mercredi,
        'jeudi' => $jeudi,
        'vendredi' => $vendredi,
        'samedi' => $samedi,
        'dimanche' => $dimanche,
        'monday' => $lundi,
        'tuesday' => $mardi,
        'wednesday' => $mercredi,
        'thursday' => $jeudi,
        'friday' => $vendredi,
        'saturday' => $samedi,
        'sunday' => $dimanche,
        'contact' => [
            'phone' => $phone,
            'email' => $email,
        ],
        'schedule' => $hoursFr,
        'horaires' => $hoursFr,
        'opening_hours' => $hoursEn,
        'hours' => $hoursEn,
        'websites' => [
            'website' => $website,
            'facebook_url' => $facebookUrl,
            'instagram_url' => $instagramUrl,
            'linkedin_url' => $linkedinUrl,
            'facebook' => $facebookUrl,
            'instagram' => $instagramUrl,
            'linkedin' => $linkedinUrl,
        ],
        'social' => [
            'website' => $website,
            'facebook' => $facebookUrl,
            'instagram' => $instagramUrl,
            'linkedin' => $linkedinUrl,
        ],
        'is_active' => (int)($client['is_active'] ?? 0) === 1,
        'is_public' => (int)($client['is_public'] ?? 0) === 1,
        'operation' => $operation,
        'event' => $operation,
        'deleted' => !$publicVisible,
        'visible' => $publicVisible,
        'public_visible' => $publicVisible,
        'updated_at' => (string)($client['updated_at'] ?? ''),
        'client' => [
            'id_source' => (int)$client['id'],
            'name' => $name,
            'slug' => $slug,
            'type' => $clientType,
            'address' => $address,
            'city' => $city,
            'postal_code' => $postalCode,
            'country' => $country,
            'latitude' => ($client['latitude'] ?? null) !== null ? (float)$client['latitude'] : null,
            'longitude' => ($client['longitude'] ?? null) !== null ? (float)$client['longitude'] : null,
            'phone' => $phone,
            'email' => $email,
            'website' => $website,
            'facebook_url' => $facebookUrl,
            'facebook' => $facebookUrl,
            'instagram_url' => $instagramUrl,
            'instagram' => $instagramUrl,
            'linkedin_url' => $linkedinUrl,
            'linkedin' => $linkedinUrl,
            'logo_url' => $logoUrl,
            'photo_cover_url' => $coverUrl,
            'description_short' => $descriptionShort,
            'description_long' => $descriptionLong,
            'gallery_images' => $galleryImagesJson,
            'description_long_text' => $descriptionLongText,
            'lundi' => $lundi,
            'mardi' => $mardi,
            'mercredi' => $mercredi,
            'jeudi' => $jeudi,
            'vendredi' => $vendredi,
            'samedi' => $samedi,
            'dimanche' => $dimanche,
            'monday' => $lundi,
            'tuesday' => $mardi,
            'wednesday' => $mercredi,
            'thursday' => $jeudi,
            'friday' => $vendredi,
            'saturday' => $samedi,
            'sunday' => $dimanche,
            'schedule' => $hoursFr,
            'horaires' => $hoursFr,
            'opening_hours' => $hoursEn,
            'hours' => $hoursEn,
            'public_visible' => $publicVisible,
            'operation' => $operation,
        ],
    ];
}

function is_client_publicly_visible(PDO $pdo, int $clientId): bool
{
    if ($clientId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM clients c
         WHERE c.id = :id
           AND c.is_active = 1
           AND EXISTS (
               SELECT 1
               FROM client_consents cc
               WHERE cc.client_id = c.id
                 AND cc.status = 'approved'
                 AND cc.revoked_at IS NULL
           )"
    );
    $stmt->execute([':id' => $clientId]);
    return (int)$stmt->fetchColumn() > 0;
}

function is_supplier_publicly_visible(PDO $pdo, int $supplierId): bool
{
    if ($supplierId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM suppliers s
         WHERE s.id = :id
           AND s.is_public = 1
           AND EXISTS (
               SELECT 1
               FROM supplier_consents sc
               WHERE sc.supplier_id = s.id
                 AND sc.status = 'approved'
                 AND sc.revoked_at IS NULL
           )
           AND (
               NOT EXISTS (
                   SELECT 1
                   FROM client_suppliers cs0
                   WHERE cs0.supplier_id = s.id
               )
               OR EXISTS (
                   SELECT 1
                   FROM client_suppliers cs1
                   LEFT JOIN client_supplier_profiles csp1
                     ON csp1.client_id = cs1.client_id
                    AND csp1.supplier_id = cs1.supplier_id
                   WHERE cs1.supplier_id = s.id
                     AND COALESCE(csp1.relationship_status, 'active') <> 'inactive'
               )
           )"
    );
    $stmt->execute([':id' => $supplierId]);
    return (int)$stmt->fetchColumn() > 0;
}

function resync_all_suppliers_to_wordpress(PDO $pdo): void
{
    @set_time_limit(120);

    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $limit = (int)($_GET['limit'] ?? 30);
    if ($limit <= 0 || $limit > 200) {
        $limit = 30;
    }

    $total = (int)$pdo->query('SELECT COUNT(*) FROM suppliers')->fetchColumn();

    $stmt = $pdo->prepare('SELECT id FROM suppliers ORDER BY id LIMIT :lim OFFSET :off');
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $processed = 0;
    foreach ($rows as $row) {
        $supplierId = (int)($row['id'] ?? 0);
        if ($supplierId <= 0) {
            continue;
        }
        sync_supplier_to_wordpress($pdo, $supplierId);
        $processed++;
    }

    $nextOffset = $offset + count($rows);
    $done = $nextOffset >= $total;

    json_response([
        'ok' => true,
        'total' => $total,
        'processed' => $processed,
        'synced' => $processed,
        'offset' => $offset,
        'next_offset' => $nextOffset,
        'done' => $done,
    ]);
}

function sync_supplier_to_wordpress(PDO $pdo, int $supplierId): void
{
    if ($supplierId <= 0) {
        return;
    }

    try {
        $config = require __DIR__ . '/config.php';

        $clientSync = is_array($config['wordpress_sync'] ?? null) ? $config['wordpress_sync'] : [];
        $supplierSync = is_array($config['wordpress_sync_suppliers'] ?? null) ? $config['wordpress_sync_suppliers'] : [];

        $enabled = array_key_exists('enabled', $supplierSync)
            ? !empty($supplierSync['enabled'])
            : !empty($clientSync['enabled']);

        if (!$enabled) {
            return;
        }

        $endpoint = trim((string)($supplierSync['endpoint'] ?? ''));
        if ($endpoint === '') {
            $clientEndpoint = trim((string)($clientSync['endpoint'] ?? ''));
            if ($clientEndpoint !== '') {
                $endpoint = preg_replace('#/clients/?$#', '/suppliers', $clientEndpoint) ?? '';
            }
        }

        $secret = (string)($supplierSync['secret'] ?? '');
        if ($secret === '') {
            $secret = (string)($clientSync['secret'] ?? '');
        }

        $timeout = (int)($supplierSync['timeout_seconds'] ?? ($clientSync['timeout_seconds'] ?? 8));
        $timeout = max(2, $timeout);

        if ($endpoint === '' || $secret === '') {
            write_admin_audit($pdo, 'wordpress_sync_supplier_failed', [
                'target_type' => 'supplier',
                'target_id' => $supplierId,
                'details' => ['reason' => 'missing_config'],
            ]);
            return;
        }

        $payload = build_supplier_sync_payload($pdo, $supplierId);
        $variants = build_supplier_sync_payload_variants($payload);

        $statusCode = 0;
        $responseBody = '';
        $acceptedVariant = -1;

        foreach ($variants as $idx => $variantPayload) {
            $json = json_encode($variantPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($json)) {
                continue;
            }

            $timestamp = (string)time();
            $signature = hash_hmac('sha256', $timestamp . '.' . $json, $secret);
            [$statusCode, $responseBody] = post_json_signed($endpoint, $json, $timestamp, $signature, $timeout);

            if ($statusCode >= 200 && $statusCode < 300) {
                $acceptedVariant = $idx;
                break;
            }

            $responseText = (string)$responseBody;
            if (stripos($responseText, 'missing_id_source') === false) {
                break;
            }
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('HTTP ' . $statusCode . ' - ' . mb_substr((string)$responseBody, 0, 700));
        }

        write_admin_audit($pdo, 'wordpress_sync_supplier_ok', [
            'target_type' => 'supplier',
            'target_id' => $supplierId,
            'details' => [
                'status_code' => $statusCode,
                'variant' => $acceptedVariant,
            ],
        ]);
    } catch (Throwable $e) {
        write_admin_audit($pdo, 'wordpress_sync_supplier_failed', [
            'target_type' => 'supplier',
            'target_id' => $supplierId,
            'details' => [
                'error' => $e->getMessage(),
            ],
        ]);
        error_log('AppCarte WP sync failed for supplier #' . $supplierId . ': ' . $e->getMessage());
    }
}

function build_supplier_sync_payload(PDO $pdo, int $supplierId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, name, slug, supplier_type, activity_text, description_short, description_long,
                address, city, postal_code, country, latitude, longitude,
                phone, email, website, facebook_url, instagram_url, linkedin_url,
                logo_url, photo_cover_url, is_public, updated_at
         FROM suppliers
         WHERE id=:id
         LIMIT 1'
    );
    $stmt->execute([':id' => $supplierId]);
    $supplier = $stmt->fetch();

    if (!$supplier) {
        return [
            'event' => 'supplier_delete',
            'id_source' => $supplierId,
            'public_visible' => false,
            'deleted' => true,
        ];
    }

    $publicVisible = is_supplier_publicly_visible($pdo, $supplierId);
    $operation = $publicVisible ? 'upsert' : 'delete';

    $idSource = (int)($supplier['id'] ?? 0);
    $name = (string)($supplier['name'] ?? '');
    $slug = (string)($supplier['slug'] ?? '');
    $supplierType = (string)($supplier['supplier_type'] ?? '');
    $activityText = (string)($supplier['activity_text'] ?? '');
    $descriptionShort = (string)($supplier['description_short'] ?? '');
    $descriptionLong = (string)($supplier['description_long'] ?? '');
    $address = (string)($supplier['address'] ?? '');
    $city = (string)($supplier['city'] ?? '');
    $postalCode = (string)($supplier['postal_code'] ?? '');
    $country = (string)($supplier['country'] ?? '');
    $phone = (string)($supplier['phone'] ?? '');
    $email = (string)($supplier['email'] ?? '');
    $website = (string)($supplier['website'] ?? '');
    $facebookUrl = (string)($supplier['facebook_url'] ?? '');
    $instagramUrl = (string)($supplier['instagram_url'] ?? '');
    $linkedinUrl = (string)($supplier['linkedin_url'] ?? '');
    $logoUrl = absolutize_export_url($pdo, (string)($supplier['logo_url'] ?? ''));
    $coverUrl = absolutize_export_url($pdo, (string)($supplier['photo_cover_url'] ?? ''));

    return [
        'event' => $operation === 'delete' ? 'supplier_delete' : 'supplier_upsert',
        'operation' => $operation,
        'id_source' => $idSource,
        'name' => $name,
        'slug' => $slug,
        'supplier_type' => $supplierType,
        'activity_text' => $activityText,
        'description_short' => $descriptionShort,
        'description_long' => $descriptionLong,
        'address' => $address,
        'city' => $city,
        'postal_code' => $postalCode,
        'country' => $country,
        'latitude' => ($supplier['latitude'] ?? null) !== null ? (float)$supplier['latitude'] : null,
        'longitude' => ($supplier['longitude'] ?? null) !== null ? (float)$supplier['longitude'] : null,
        'phone' => $phone,
        'email' => $email,
        'website' => $website,
        'facebook_url' => $facebookUrl,
        'instagram_url' => $instagramUrl,
        'linkedin_url' => $linkedinUrl,
        'logo_url' => $logoUrl,
        'photo_cover_url' => $coverUrl,
        'is_public' => (int)($supplier['is_public'] ?? 0) === 1,
        'deleted' => !$publicVisible,
        'public_visible' => $publicVisible,
        'updated_at' => (string)($supplier['updated_at'] ?? ''),
        'supplier' => [
            'id_source' => $idSource,
            'name' => $name,
            'slug' => $slug,
            'supplier_type' => $supplierType,
            'activity_text' => $activityText,
            'description_short' => $descriptionShort,
            'description_long' => $descriptionLong,
            'address' => $address,
            'city' => $city,
            'postal_code' => $postalCode,
            'country' => $country,
            'latitude' => ($supplier['latitude'] ?? null) !== null ? (float)$supplier['latitude'] : null,
            'longitude' => ($supplier['longitude'] ?? null) !== null ? (float)$supplier['longitude'] : null,
            'phone' => $phone,
            'email' => $email,
            'website' => $website,
            'facebook_url' => $facebookUrl,
            'instagram_url' => $instagramUrl,
            'linkedin_url' => $linkedinUrl,
            'logo_url' => $logoUrl,
            'photo_cover_url' => $coverUrl,
            'public_visible' => $publicVisible,
            'operation' => $operation,
        ],
    ];
}

function post_json_signed(string $url, string $jsonBody, string $timestamp, string $signature, int $timeoutSeconds): array
{
    $headers = [
        'Content-Type: application/json',
        'X-Limap-Timestamp: ' . $timestamp,
        'X-Limap-Signature: ' . $signature,
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Unable to initialize cURL');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('cURL error: ' . $error);
        }

        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$statusCode, (string)$response];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $jsonBody,
            'timeout' => $timeoutSeconds,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    $statusCode = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $statusCode = (int)$m[1];
    }
    return [$statusCode, $response === false ? '' : (string)$response];
}

function export_clients_csv(PDO $pdo): void
{
    $rows = $pdo->query('SELECT id, name, slug, client_type, description_short, description_long, address, city, postal_code, country, latitude, longitude, phone, email, website, facebook_url, instagram_url, linkedin_url, logo_url, photo_cover_url, lundi, mardi, mercredi, jeudi, vendredi, samedi, dimanche, is_public, public_updated_at, updated_at FROM clients WHERE is_active=1 AND is_public=1 ORDER BY name')->fetchAll();

    $rows = array_map(function (array $row) use ($pdo) {
        $row['id_source'] = (int)$row['id'];
        unset($row['id']);
        $row['logo_url'] = absolutize_export_url($pdo, (string)($row['logo_url'] ?? ''));
        $row['photo_cover_url'] = absolutize_export_url($pdo, (string)($row['photo_cover_url'] ?? ''));
        // WordPress-ready aliases to map directly to post fields.
        $row['wp_post_title'] = (string)($row['name'] ?? '');
        $row['wp_post_name'] = (string)($row['slug'] ?? '');
        $row['wp_post_excerpt'] = (string)($row['description_short'] ?? '');
        $row['wp_post_content'] = (string)($row['description_long'] ?? '');
        return $row;
    }, $rows);

    $headers = [
        'wp_post_title', 'wp_post_name', 'wp_post_excerpt', 'wp_post_content',
        'id_source', 'name', 'slug', 'client_type', 'description_short', 'description_long',
        'address', 'city', 'postal_code', 'country', 'latitude', 'longitude',
        'phone', 'email', 'website', 'facebook_url', 'instagram_url', 'linkedin_url',
        'logo_url', 'photo_cover_url', 'lundi', 'mardi', 'mercredi', 'jeudi',
        'vendredi', 'samedi', 'dimanche', 'is_public', 'public_updated_at', 'updated_at'
    ];

    csv_response($headers, $rows, 'clients-wordpress-' . date('Ymd-His') . '.csv');
}

function export_producers_csv(PDO $pdo): void
{
    $scope = trim((string)($_GET['scope'] ?? 'all'));
    $lastExportedAt = trim((string)get_setting_value($pdo, 'producer_export_last_at', ''));
    $where = [
        's.is_public=1',
    ];
    $params = [];

    if ($scope === 'changed' && $lastExportedAt !== '') {
                $where[] = "(
                        COALESCE(s.public_updated_at, s.updated_at) >= :since
                        OR NOT EXISTS (
                                SELECT 1
                                FROM supplier_consents scn
                                WHERE scn.supplier_id = s.id
                                    AND scn.status = 'approved'
                                    AND scn.revoked_at IS NULL
                        )
                        OR EXISTS (
                                SELECT 1
                                FROM supplier_consents scu
                                WHERE scu.supplier_id = s.id
                                    AND (
                                        (scu.approved_at IS NOT NULL AND scu.approved_at >= :since)
                                        OR (scu.revoked_at IS NOT NULL AND scu.revoked_at >= :since)
                                    )
                        )
                )";
        $params[':since'] = $lastExportedAt;
    }

    $sql = "SELECT
                s.id,
                s.name,
                s.slug,
                s.supplier_type,
                s.description_short,
                s.description_long,
                s.address,
                s.city,
                s.postal_code,
                s.country,
                s.latitude,
                s.longitude,
                s.phone,
                s.email,
                s.website,
                s.facebook_url,
                s.instagram_url,
                s.linkedin_url,
                s.logo_url,
                s.photo_cover_url,
                s.activity_text,
                (
                    SELECT GROUP_CONCAT(DISTINCT a.name ORDER BY a.name SEPARATOR '; ')
                    FROM supplier_activities sa
                    JOIN activities a ON a.id = sa.activity_id
                    WHERE sa.supplier_id = s.id
                ) AS activity_names,
                (
                    SELECT GROUP_CONCAT(DISTINCT a.icon_url ORDER BY a.name SEPARATOR '; ')
                    FROM supplier_activities sa
                    JOIN activities a ON a.id = sa.activity_id
                    WHERE sa.supplier_id = s.id AND TRIM(COALESCE(a.icon_url, '')) <> ''
                ) AS activity_icons,
                (
                    SELECT GROUP_CONCAT(DISTINCT l.name ORDER BY l.name SEPARATOR '; ')
                    FROM supplier_labels sl
                    JOIN labels l ON l.id = sl.label_id
                    WHERE sl.supplier_id = s.id
                ) AS labels,
                (
                    SELECT GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR '; ')
                    FROM client_suppliers cs
                    JOIN clients c ON c.id = cs.client_id
                    WHERE cs.supplier_id = s.id
                ) AS client_names,
                                (
                                        SELECT 1
                                        FROM supplier_consents sc
                                        WHERE sc.supplier_id = s.id
                                            AND sc.status = 'approved'
                                            AND sc.revoked_at IS NULL
                                        LIMIT 1
                                ) AS consent_approved,
                s.is_public,
                s.public_updated_at,
                s.updated_at
            FROM suppliers s
            WHERE " . implode(' AND ', $where) . "
                            AND (
                                        NOT EXISTS (
                                                SELECT 1
                                                FROM client_suppliers cs0
                                                WHERE cs0.supplier_id = s.id
                                        )
                                        OR EXISTS (
                                                SELECT 1
                                                FROM client_suppliers cs1
                                                LEFT JOIN client_supplier_profiles csp1
                                                    ON csp1.client_id = cs1.client_id
                                                 AND csp1.supplier_id = cs1.supplier_id
                                                WHERE cs1.supplier_id = s.id
                                                    AND COALESCE(csp1.relationship_status, 'active') <> 'inactive'
                                        )
                            )
            ORDER BY s.name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['logo_url'] = absolutize_export_url($pdo, (string)($row['logo_url'] ?? ''));
        $row['photo_cover_url'] = absolutize_export_url($pdo, (string)($row['photo_cover_url'] ?? ''));
        $row['activity_icons'] = absolutize_export_url_list($pdo, (string)($row['activity_icons'] ?? ''));
    }
    unset($row);

    $rows = array_map(function (array $row) {
        $hasConsent = !empty($row['consent_approved']);
        $row['id_source'] = (int)$row['id'];
        unset($row['id']);
        // WordPress import can map this to post status so non-consented suppliers are hidden.
        $row['wp_post_status'] = $hasConsent ? 'publish' : 'draft';
        $row['is_public'] = $hasConsent ? 1 : 0;
        $row['consent_approved'] = $hasConsent ? 1 : 0;
        return $row;
    }, $rows);

    $headers = [
        'wp_post_status',
        'id_source', 'name', 'slug', 'supplier_type', 'description_short', 'description_long',
        'address', 'city', 'postal_code', 'country', 'latitude', 'longitude',
        'phone', 'email', 'website', 'facebook_url', 'instagram_url', 'linkedin_url',
        'logo_url', 'photo_cover_url', 'activity_text', 'activity_names', 'activity_icons', 'labels', 'client_names',
        'consent_approved', 'is_public', 'public_updated_at', 'updated_at'
    ];

    set_setting_value($pdo, 'producer_export_last_at', date('Y-m-d H:i:s'));
    $suffix = $scope === 'changed' ? 'changed' : 'all';
    csv_response($headers, $rows, 'fournisseurs-wordpress-' . $suffix . '-' . date('Ymd-His') . '.csv');
}

function save_client_user(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $clientId = isset($input['client_id']) ? (int)$input['client_id'] : 0;
    $username = trim((string)($input['username'] ?? ''));
    $email = sanitize_email_or_fail((string)($input['email'] ?? ''), 'Email utilisateur client invalide');
    $password = (string)($input['password'] ?? '');
    $role = trim((string)($input['role'] ?? 'client_manager'));
    $isActive = !empty($input['is_active']) ? 1 : 0;

    if ($clientId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id requis'], 422);
    }
    if ($username === '') {
        json_response(['ok' => false, 'error' => 'Nom utilisateur requis'], 422);
    }
    if ($email === '') {
        json_response(['ok' => false, 'error' => 'Email utilisateur client requis'], 422);
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
            ':email' => $email,
            ':role' => $role,
            ':is_active' => $isActive,
        ];

        $sql = 'UPDATE client_users SET client_id=:client_id, username=:username, email=:email, role=:role, is_active=:is_active';
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
        write_admin_audit($pdo, 'client_user_updated', [
            'target_type' => 'client_user',
            'target_id' => $id,
            'target_label' => $username,
            'details' => ['client_id' => $clientId, 'role' => $role, 'is_active' => $isActive],
        ]);
        json_response(['ok' => true]);
    }

    if ($password === '' || mb_strlen($password, 'UTF-8') < 8) {
        json_response(['ok' => false, 'error' => 'Mot de passe requis (8 caractères min)'], 422);
    }

    try {
        $pdo->prepare('INSERT INTO client_users (client_id, username, email, password_hash, role, is_active) VALUES (:client_id, :username, :email, :password_hash, :role, :is_active)')
            ->execute([
                ':client_id' => $clientId,
                ':username' => $username,
                ':email' => $email,
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

    $newId = (int)$pdo->lastInsertId();
    write_admin_audit($pdo, 'client_user_created', [
        'target_type' => 'client_user',
        'target_id' => $newId,
        'target_label' => $username,
        'details' => ['client_id' => $clientId, 'role' => $role, 'is_active' => $isActive],
    ]);

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

    write_admin_audit($pdo, 'client_user_toggled', [
        'target_type' => 'client_user',
        'target_id' => $id,
        'details' => ['is_active' => $isActive],
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

    write_admin_audit($pdo, 'client_user_password_reset', [
        'target_type' => 'client_user',
        'target_id' => $id,
    ]);

    json_response(['ok' => true]);
}

function delete_client_user(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;

    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Utilisateur invalide'], 422);
    }

    $stmt = $pdo->prepare('SELECT id, username, client_id FROM client_users WHERE id=:id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_response(['ok' => false, 'error' => 'Utilisateur introuvable'], 404);
    }

    $pdo->prepare('DELETE FROM client_users WHERE id=:id')->execute([':id' => $id]);

    write_admin_audit($pdo, 'client_user_deleted', [
        'target_type' => 'client_user',
        'target_id' => $id,
        'target_label' => (string)($row['username'] ?? ''),
        'details' => ['client_id' => (int)($row['client_id'] ?? 0)],
    ]);

    json_response(['ok' => true]);
}

function save_admin_user(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $username = trim((string)($input['username'] ?? ''));
    $email = sanitize_email_or_fail((string)($input['email'] ?? ''), 'Email admin invalide');
    $password = (string)($input['password'] ?? '');
    $isActive = !empty($input['is_active']) ? 1 : 0;

    if ($username === '') {
        json_response(['ok' => false, 'error' => 'Nom utilisateur admin requis'], 422);
    }
    if ($email === '') {
        json_response(['ok' => false, 'error' => 'Email admin requis'], 422);
    }

    if ($id > 0) {
        $payload = [
            ':id' => $id,
            ':username' => $username,
            ':email' => $email,
            ':is_active' => $isActive,
        ];

        $sql = 'UPDATE admin_users SET username=:username, email=:email, is_active=:is_active';
        if ($password !== '') {
            $error = validate_password_strength($password, 12);
            if ($error !== null) {
                json_response(['ok' => false, 'error' => $error], 422);
            }
            $sql .= ', password_hash=:password_hash';
            $payload[':password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id=:id';

        try {
            $pdo->prepare($sql)->execute($payload);
        } catch (PDOException $e) {
            if (($e->getCode() ?? '') === '23000') {
                json_response(['ok' => false, 'error' => 'Nom utilisateur admin déjà utilisé'], 422);
            }
            throw $e;
        }

        write_admin_audit($pdo, 'admin_user_updated', [
            'target_type' => 'admin_user',
            'target_id' => $id,
            'target_label' => $username,
            'details' => ['is_active' => $isActive],
        ]);

        json_response(['ok' => true]);
    }

    $error = validate_password_strength($password, 12);
    if ($error !== null) {
        json_response(['ok' => false, 'error' => $error], 422);
    }

    try {
        $pdo->prepare('INSERT INTO admin_users (username, email, password_hash, is_active) VALUES (:username, :email, :password_hash, :is_active)')
            ->execute([
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':is_active' => $isActive,
            ]);
    } catch (PDOException $e) {
        if (($e->getCode() ?? '') === '23000') {
            json_response(['ok' => false, 'error' => 'Nom utilisateur admin déjà utilisé'], 422);
        }
        throw $e;
    }

    $newId = (int)$pdo->lastInsertId();
    write_admin_audit($pdo, 'admin_user_created', [
        'target_type' => 'admin_user',
        'target_id' => $newId,
        'target_label' => $username,
        'details' => ['is_active' => $isActive],
    ]);

    json_response(['ok' => true]);
}

function toggle_admin_user_active(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $isActive = !empty($input['is_active']) ? 1 : 0;

    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Admin invalide'], 422);
    }

    start_app_session();
    $currentAdminId = isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : 0;
    if ($isActive === 0 && $currentAdminId > 0 && $currentAdminId === $id) {
        json_response(['ok' => false, 'error' => 'Impossible de désactiver ton propre compte admin'], 422);
    }

    if ($isActive === 0) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE is_active=1 AND id<>:id');
        $stmt->execute([':id' => $id]);
        $remaining = (int)$stmt->fetchColumn();
        $hasEnvAdmin = has_env_admin_hash();
        if ($remaining <= 0 && !$hasEnvAdmin) {
            json_response(['ok' => false, 'error' => 'Au moins un admin actif est requis'], 422);
        }
    }

    $pdo->prepare('UPDATE admin_users SET is_active=:is_active WHERE id=:id')->execute([
        ':is_active' => $isActive,
        ':id' => $id,
    ]);

    write_admin_audit($pdo, 'admin_user_toggled', [
        'target_type' => 'admin_user',
        'target_id' => $id,
        'details' => ['is_active' => $isActive],
    ]);

    json_response(['ok' => true]);
}

function reset_admin_user_password(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $newPassword = (string)($input['new_password'] ?? '');

    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Admin invalide'], 422);
    }

    $error = validate_password_strength($newPassword, 12);
    if ($error !== null) {
        json_response(['ok' => false, 'error' => $error], 422);
    }

    $pdo->prepare('UPDATE admin_users SET password_hash=:password_hash WHERE id=:id')->execute([
        ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ':id' => $id,
    ]);

    write_admin_audit($pdo, 'admin_user_password_reset', [
        'target_type' => 'admin_user',
        'target_id' => $id,
    ]);

    json_response(['ok' => true]);
}

function delete_admin_user(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;

    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Admin invalide'], 422);
    }

    start_app_session();
    $currentAdminId = isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : 0;
    if ($currentAdminId > 0 && $currentAdminId === $id) {
        json_response(['ok' => false, 'error' => 'Impossible de supprimer ton propre compte admin'], 422);
    }

    $stmt = $pdo->prepare('SELECT id, username, is_active FROM admin_users WHERE id=:id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_response(['ok' => false, 'error' => 'Admin introuvable'], 404);
    }

    $remainingStmt = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE is_active=1 AND id<>:id');
    $remainingStmt->execute([':id' => $id]);
    $remaining = (int)$remainingStmt->fetchColumn();
    $hasEnvAdmin = has_env_admin_hash();
    if ((int)($row['is_active'] ?? 0) === 1 && $remaining <= 0 && !$hasEnvAdmin) {
        json_response(['ok' => false, 'error' => 'Au moins un admin actif est requis'], 422);
    }

    $pdo->prepare('DELETE FROM admin_users WHERE id=:id')->execute([':id' => $id]);

    write_admin_audit($pdo, 'admin_user_deleted', [
        'target_type' => 'admin_user',
        'target_id' => $id,
        'target_label' => (string)($row['username'] ?? ''),
    ]);

    json_response(['ok' => true]);
}

function validate_password_strength(string $password, int $minLength = 12): ?string
{
    if (mb_strlen($password, 'UTF-8') < $minLength) {
        return 'Mot de passe trop court (' . $minLength . ' caractères min)';
    }

    $score = 0;
    $score += preg_match('/[a-z]/', $password) ? 1 : 0;
    $score += preg_match('/[A-Z]/', $password) ? 1 : 0;
    $score += preg_match('/\d/', $password) ? 1 : 0;
    $score += preg_match('/[^a-zA-Z\d]/', $password) ? 1 : 0;

    if ($score < 3) {
        return 'Mot de passe trop faible (utilise majuscules, minuscules, chiffres et/ou symboles)';
    }

    return null;
}

function has_env_admin_hash(): bool
{
    $config = require __DIR__ . '/config.php';
    return trim((string)($config['admin']['password_hash'] ?? '')) !== '';
}

function sanitize_email_or_fail(string $email, string $invalidMessage): string
{
    $email = trim($email);
    if ($email === '') {
        return '';
    }
    $valid = filter_var($email, FILTER_VALIDATE_EMAIL);
    if ($valid === false) {
        json_response(['ok' => false, 'error' => $invalidMessage], 422);
    }
    return mb_strtolower((string)$valid, 'UTF-8');
}

function request_password_reset(PDO $pdo): void
{
    $input = get_json_input();
    $email = sanitize_email_or_fail((string)($input['email'] ?? ''), 'Email invalide');
    if ($email === '') {
        json_response(['ok' => false, 'error' => 'Email requis'], 422);
    }

    $user = find_user_for_password_reset($pdo, $email);
    if ($user) {
        issue_password_reset_link($pdo, $user['user_type'], (int)$user['id'], (string)$user['email'], (string)$user['username']);
    }

    // Always return success to avoid account enumeration.
    json_response(['ok' => true]);
}

function confirm_password_reset(PDO $pdo): void
{
    $input = get_json_input();
    $token = trim((string)($input['token'] ?? ''));
    $newPassword = (string)($input['new_password'] ?? '');

    if ($token === '') {
        json_response(['ok' => false, 'error' => 'Token invalide'], 422);
    }

    $stmt = $pdo->prepare('SELECT id, user_type, user_id, expires_at, used_at FROM password_reset_tokens WHERE token_hash=:token_hash LIMIT 1');
    $stmt->execute([':token_hash' => hash('sha256', $token)]);
    $row = $stmt->fetch();
    if (!$row) {
        json_response(['ok' => false, 'error' => 'Lien invalide ou expire'], 422);
    }
    if (!empty($row['used_at'])) {
        json_response(['ok' => false, 'error' => 'Lien deja utilise'], 422);
    }
    if (strtotime((string)$row['expires_at']) < time()) {
        json_response(['ok' => false, 'error' => 'Lien expire'], 422);
    }

    $userType = (string)$row['user_type'];
    if ($userType === 'admin') {
        $error = validate_password_strength($newPassword, 12);
        if ($error !== null) {
            json_response(['ok' => false, 'error' => $error], 422);
        }
        $pdo->prepare('UPDATE admin_users SET password_hash=:password_hash WHERE id=:id')
            ->execute([':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT), ':id' => (int)$row['user_id']]);
    } elseif ($userType === 'client') {
        if ($newPassword === '' || mb_strlen($newPassword, 'UTF-8') < 8) {
            json_response(['ok' => false, 'error' => 'Mot de passe trop court (8 caracteres min)'], 422);
        }
        $pdo->prepare('UPDATE client_users SET password_hash=:password_hash WHERE id=:id')
            ->execute([':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT), ':id' => (int)$row['user_id']]);
    } else {
        json_response(['ok' => false, 'error' => 'Type utilisateur invalide'], 422);
    }

    $pdo->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE id=:id')->execute([':id' => (int)$row['id']]);
    json_response(['ok' => true]);
}

function admin_send_client_user_reset_link(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Utilisateur invalide'], 422);
    }

    $stmt = $pdo->prepare('SELECT id, username, email, is_active FROM client_users WHERE id=:id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row || (int)($row['is_active'] ?? 0) !== 1) {
        json_response(['ok' => false, 'error' => 'Utilisateur client introuvable ou inactif'], 422);
    }
    $email = sanitize_email_or_fail((string)($row['email'] ?? ''), 'Email utilisateur client invalide');
    if ($email === '') {
        json_response(['ok' => false, 'error' => 'Email utilisateur client requis'], 422);
    }

    issue_password_reset_link($pdo, 'client', (int)$row['id'], $email, (string)$row['username']);
    write_admin_audit($pdo, 'client_user_reset_link_sent', [
        'target_type' => 'client_user',
        'target_id' => (int)$row['id'],
        'target_label' => (string)$row['username'],
    ]);
    json_response(['ok' => true]);
}

function admin_send_admin_user_reset_link(PDO $pdo): void
{
    $input = get_json_input();
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Admin invalide'], 422);
    }

    $stmt = $pdo->prepare('SELECT id, username, email, is_active FROM admin_users WHERE id=:id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row || (int)($row['is_active'] ?? 0) !== 1) {
        json_response(['ok' => false, 'error' => 'Admin introuvable ou inactif'], 422);
    }
    $email = sanitize_email_or_fail((string)($row['email'] ?? ''), 'Email admin invalide');
    if ($email === '') {
        json_response(['ok' => false, 'error' => 'Email admin requis'], 422);
    }

    issue_password_reset_link($pdo, 'admin', (int)$row['id'], $email, (string)$row['username']);
    write_admin_audit($pdo, 'admin_user_reset_link_sent', [
        'target_type' => 'admin_user',
        'target_id' => (int)$row['id'],
        'target_label' => (string)$row['username'],
    ]);
    json_response(['ok' => true]);
}

function find_user_for_password_reset(PDO $pdo, string $email): ?array
{
    $stmtClient = $pdo->prepare('SELECT id, username, email FROM client_users WHERE is_active=1 AND LOWER(email)=:email ORDER BY id DESC LIMIT 1');
    $stmtClient->execute([':email' => mb_strtolower($email, 'UTF-8')]);
    $client = $stmtClient->fetch();
    if ($client) {
        return [
            'user_type' => 'client',
            'id' => (int)$client['id'],
            'username' => (string)$client['username'],
            'email' => (string)$client['email'],
        ];
    }

    $stmtAdmin = $pdo->prepare('SELECT id, username, email FROM admin_users WHERE is_active=1 AND LOWER(email)=:email ORDER BY id DESC LIMIT 1');
    $stmtAdmin->execute([':email' => mb_strtolower($email, 'UTF-8')]);
    $admin = $stmtAdmin->fetch();
    if ($admin) {
        return [
            'user_type' => 'admin',
            'id' => (int)$admin['id'],
            'username' => (string)$admin['username'],
            'email' => (string)$admin['email'],
        ];
    }

    return null;
}

function issue_password_reset_link(PDO $pdo, string $userType, int $userId, string $email, string $username): void
{
    $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + 2 * 3600);

    $pdo->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE user_type=:user_type AND user_id=:user_id AND used_at IS NULL')
        ->execute([':user_type' => $userType, ':user_id' => $userId]);

    $pdo->prepare('INSERT INTO password_reset_tokens (user_type, user_id, token_hash, expires_at) VALUES (:user_type, :user_id, :token_hash, :expires_at)')
        ->execute([
            ':user_type' => $userType,
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);

    $baseUrl = app_base_url($pdo);
    $resetUrl = $baseUrl . '/client/index.html?reset_token=' . rawurlencode($token);
    $subject = '[AppCarte] Reinitialisation de votre mot de passe';
    $body = "Bonjour {$username},\n\n"
        . "Un lien de reinitialisation de mot de passe vient d'etre genere pour votre compte.\n"
        . "Lien: {$resetUrl}\n\n"
        . "Ce lien expire le {$expiresAt} et ne peut etre utilise qu'une seule fois.\n"
        . "Si vous n'etes pas a l'origine de cette demande, ignorez cet email.\n";

    if (!send_plain_email([$email], $subject, $body)) {
        log_password_reset_audit($pdo, $userType, $userId, $username, $email, 'failed', 'send_plain_email returned false');
        throw new RuntimeException('Impossible d\'envoyer l\'email de reinitialisation');
    }

    log_password_reset_audit($pdo, $userType, $userId, $username, $email, 'sent', null);
}

function log_password_reset_audit(PDO $pdo, string $userType, int $userId, string $username, string $email, string $status, ?string $errorMessage): void
{
    try {
        $pdo->prepare('INSERT INTO password_reset_audit (user_type, user_id, username, email, status, error_message) VALUES (:user_type, :user_id, :username, :email, :status, :error_message)')
            ->execute([
                ':user_type' => $userType,
                ':user_id' => $userId,
                ':username' => $username,
                ':email' => $email,
                ':status' => $status,
                ':error_message' => $errorMessage,
            ]);
    } catch (Throwable $e) {
        error_log('AppCarte: failed to write password_reset_audit: ' . $e->getMessage());
    }
}

function app_base_url(PDO $pdo): string
{
    $configuredBase = get_public_assets_base_url($pdo);
    if ($configuredBase !== '') {
        return rtrim($configuredBase, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
    $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return '';
    }
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api/index.php')), '/');
    $basePath = preg_replace('#/api$#', '', $scriptDir);
    return $scheme . '://' . $host . ($basePath ?: '');
}

function save_settings(PDO $pdo): void
{
    $input = get_json_input();
    $allowedKeys = [
        'org_name',
        'org_logo_url',
        'default_client_icon',
        'default_producer_icon',
        'farm_direct_icon',
        'admin_notification_emails',
        'public_assets_base_url',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'smtp_from_email',
        'smtp_from_name',
    ];

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

    $url = '/uploads/clients/' . $filename;

    json_response(['ok' => true, 'url' => $url]);
}

function upload_client_gallery_images(): void
{
    if (empty($_FILES['images']) || !is_array($_FILES['images'])) {
        json_response(['ok' => false, 'error' => 'Aucune image fournie'], 422);
    }

    $files = $_FILES['images'];
    $names = $files['name'] ?? [];
    $tmpNames = $files['tmp_name'] ?? [];
    $errors = $files['error'] ?? [];
    $sizes = $files['size'] ?? [];

    if (!is_array($names)) {
        $names = [$names];
        $tmpNames = [$tmpNames];
        $errors = [$errors];
        $sizes = [$sizes];
    }

    if (!$names) {
        json_response(['ok' => false, 'error' => 'Aucune image fournie'], 422);
    }

    $maxSize = 10 * 1024 * 1024;
    $extByMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $rootDir = dirname(__DIR__);
    $targetDir = $rootDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'clients';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        json_response(['ok' => false, 'error' => 'Impossible de créer le dossier upload'], 500);
    }

    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api')), '/');
    $baseWebPath = preg_replace('#/api$#', '', $scriptDir);

    $urls = [];
    foreach ($names as $idx => $name) {
        $fileError = (int)($errors[$idx] ?? UPLOAD_ERR_NO_FILE);
        if ($fileError !== UPLOAD_ERR_OK) {
            continue;
        }

        $tmpPath = (string)($tmpNames[$idx] ?? '');
        $size = (int)($sizes[$idx] ?? 0);
        if ($tmpPath === '' || !is_uploaded_file($tmpPath) || $size <= 0 || $size > $maxSize) {
            continue;
        }

        $mime = mime_content_type($tmpPath) ?: '';
        if (!isset($extByMime[$mime])) {
            continue;
        }

        $basename = 'gallery_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5));
        $filename = $basename . '.' . $extByMime[$mime];
        $destPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            continue;
        }

        $urls[] = '/uploads/clients/' . $filename;
    }

    if (!$urls) {
        json_response(['ok' => false, 'error' => 'Aucune image valide téléversée'], 422);
    }

    json_response(['ok' => true, 'urls' => $urls]);
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

function store_uploaded_pdf(string $fieldName, string $subDir, string $prefix): array
{
    if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        json_response(['ok' => false, 'error' => 'Fichier PDF manquant'], 422);
    }

    $file = $_FILES[$fieldName];
    if (!empty($file['error'])) {
        json_response(['ok' => false, 'error' => 'Erreur upload (' . (int)$file['error'] . ')'], 422);
    }

    $tmpPath = $file['tmp_name'] ?? '';
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        json_response(['ok' => false, 'error' => 'Upload invalide'], 422);
    }

    $maxSize = 10 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxSize) {
        json_response(['ok' => false, 'error' => 'PDF trop volumineux (max 10MB)'], 422);
    }

    $mime = mime_content_type($tmpPath) ?: '';
    $originalName = (string)($file['name'] ?? '');
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($mime !== 'application/pdf' && $ext !== 'pdf') {
        json_response(['ok' => false, 'error' => 'Seuls les fichiers PDF sont acceptés'], 422);
    }

    $rootDir = dirname(__DIR__);
    $targetDir = $rootDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $subDir;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        json_response(['ok' => false, 'error' => 'Impossible de créer le dossier upload'], 500);
    }

    $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
    $destPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmpPath, $destPath)) {
        json_response(['ok' => false, 'error' => 'Impossible de déplacer le fichier uploadé'], 500);
    }

    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api')), '/');
    $baseWebPath = preg_replace('#/api$#', '', $scriptDir);
    $filePath = ($baseWebPath ?: '') . '/uploads/' . $subDir . '/' . $filename;

    return [
        'file_path' => $filePath,
        'original_filename' => $originalName,
        'file_hash' => hash_file('sha256', $destPath),
    ];
}

function upload_client_consent_charter(PDO $pdo): void
{
    assert_client_can_write_profile();
    $clientId = resolve_effective_client_id();
    if ($clientId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id requis'], 422);
    }

    $stored = store_uploaded_pdf('pdf', 'charteclient', 'client_charter_c' . $clientId);
    $uploadedByUserId = !empty($_SESSION['client_user_id']) ? (int)$_SESSION['client_user_id'] : null;

    $pdo->prepare('INSERT INTO client_consent_documents (client_id, consent_type, file_path, original_filename, file_hash, status, uploaded_by_user_id) VALUES (:client_id, :consent_type, :file_path, :original_filename, :file_hash, "pending", :uploaded_by_user_id)')
        ->execute([
            ':client_id' => $clientId,
            ':consent_type' => 'client_charter',
            ':file_path' => $stored['file_path'],
            ':original_filename' => $stored['original_filename'],
            ':file_hash' => $stored['file_hash'],
            ':uploaded_by_user_id' => $uploadedByUserId,
        ]);

    write_admin_audit($pdo, 'client_consent_uploaded', [
        'target_type' => 'client',
        'target_id' => $clientId,
        'details' => ['consent_type' => 'client_charter'],
    ]);

    json_response(['ok' => true]);
}

function upload_client_supplier_responsibility_consent(PDO $pdo): void
{
    assert_client_can_write_profile();
    $clientId = resolve_effective_client_id();
    if ($clientId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id requis'], 422);
    }

    $stored = store_uploaded_pdf('pdf', 'chartefournisseurclient', 'supplier_responsibility_c' . $clientId);
    $uploadedByUserId = !empty($_SESSION['client_user_id']) ? (int)$_SESSION['client_user_id'] : null;

    $pdo->prepare('INSERT INTO client_consent_documents (client_id, consent_type, file_path, original_filename, file_hash, status, uploaded_by_user_id) VALUES (:client_id, :consent_type, :file_path, :original_filename, :file_hash, "pending", :uploaded_by_user_id)')
        ->execute([
            ':client_id' => $clientId,
            ':consent_type' => 'supplier_responsibility',
            ':file_path' => $stored['file_path'],
            ':original_filename' => $stored['original_filename'],
            ':file_hash' => $stored['file_hash'],
            ':uploaded_by_user_id' => $uploadedByUserId,
        ]);

    write_admin_audit($pdo, 'client_supplier_responsibility_uploaded', [
        'target_type' => 'client',
        'target_id' => $clientId,
        'details' => ['consent_type' => 'supplier_responsibility'],
    ]);

    json_response(['ok' => true]);
}

function upload_supplier_consent_document(PDO $pdo): void
{
    assert_client_can_write_profile();
    $clientId = resolve_effective_client_id();
    $supplierId = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
    if ($clientId <= 0 || $supplierId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id et supplier_id requis'], 422);
    }

    assert_client_has_supplier_link($pdo, $clientId, $supplierId);
    $stored = store_uploaded_pdf('pdf', 'chartefournisseur', 'supplier_c' . $clientId . '_s' . $supplierId);
    $uploadedByUserId = !empty($_SESSION['client_user_id']) ? (int)$_SESSION['client_user_id'] : null;

    $pdo->prepare('INSERT INTO supplier_consent_documents (supplier_id, source_client_id, file_path, original_filename, file_hash, status, uploaded_by_user_id) VALUES (:supplier_id, :source_client_id, :file_path, :original_filename, :file_hash, "pending", :uploaded_by_user_id)')
        ->execute([
            ':supplier_id' => $supplierId,
            ':source_client_id' => $clientId,
            ':file_path' => $stored['file_path'],
            ':original_filename' => $stored['original_filename'],
            ':file_hash' => $stored['file_hash'],
            ':uploaded_by_user_id' => $uploadedByUserId,
        ]);

    write_admin_audit($pdo, 'supplier_consent_uploaded', [
        'target_type' => 'supplier',
        'target_id' => $supplierId,
        'details' => ['client_id' => $clientId],
    ]);

    json_response(['ok' => true]);
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

function geocode_address_client(): void
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
    persist_supplier($pdo, $input, $source, [], ['auto_geocode' => true]);
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
        'facebook_url' => trim((string)($input['facebook_url'] ?? '')),
        'instagram_url' => trim((string)($input['instagram_url'] ?? '')),
        'linkedin_url' => trim((string)($input['linkedin_url'] ?? '')),
        'logo_url' => trim((string)($input['logo_url'] ?? '')),
        'photo_cover_url' => trim((string)($input['photo_cover_url'] ?? '')),
        'slug' => slugify_text((string)($input['slug'] ?? ($input['name'] ?? ''))),
        'description_short' => trim((string)($input['description_short'] ?? '')),
        'description_long' => trim((string)($input['description_long'] ?? '')),
        'is_public' => array_key_exists('is_public', $input) ? (!empty($input['is_public']) ? 1 : 0) : 1,
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

    $autoGeocode = !empty($options['auto_geocode']);
    $explicitCoordinatesProvided = (($input['latitude'] ?? '') !== '' || ($input['longitude'] ?? '') !== '');
    if ($autoGeocode && !$explicitCoordinatesProvided) {
        $addressFieldsChanged = false;
        if ($existing) {
            foreach (['address', 'postal_code', 'city', 'country'] as $field) {
                if (trim((string)($supplier[$field] ?? '')) !== trim((string)($existing[$field] ?? ''))) {
                    $addressFieldsChanged = true;
                    break;
                }
            }
        }

        $needsGeocode = !$existing
            || $supplier['latitude'] === null
            || $supplier['longitude'] === null
            || $addressFieldsChanged;

        if ($needsGeocode) {
            $query = implode(', ', array_values(array_filter([
                $supplier['address'],
                $supplier['postal_code'],
                $supplier['city'],
                $supplier['country'],
            ], static fn($v) => trim((string)$v) !== '')));

            if ($query !== '') {
                $geo = geocode_address_text($query);
                if (is_array($geo) && isset($geo['lat'], $geo['lng'])) {
                    $supplier['latitude'] = (float)$geo['lat'];
                    $supplier['longitude'] = (float)$geo['lng'];
                }
            }
        }

        if ($existing) {
            $existingLat = ($existing['latitude'] ?? '') !== '' ? (float)$existing['latitude'] : null;
            $existingLng = ($existing['longitude'] ?? '') !== '' ? (float)$existing['longitude'] : null;
            if ($supplier['latitude'] === null && $existingLat !== null) {
                $supplier['latitude'] = $existingLat;
            }
            if ($supplier['longitude'] === null && $existingLng !== null) {
                $supplier['longitude'] = $existingLng;
            }
        }
    }

    $supplierId = null;

    if ($existing) {
        $supplierId = (int)$existing['id'];
        $fields = ['name', 'address', 'city', 'postal_code', 'country', 'latitude', 'longitude', 'phone', 'email', 'website', 'facebook_url', 'instagram_url', 'linkedin_url', 'logo_url', 'photo_cover_url', 'slug', 'description_short', 'description_long', 'is_public', 'activity_text', 'supplier_type', 'notes'];
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
            $updatePayload['public_updated_at'] = date('Y-m-d H:i:s');
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
            (name, normalized_name, address, city, postal_code, country, latitude, longitude, phone, email, website, facebook_url, instagram_url, linkedin_url, logo_url, photo_cover_url, slug, description_short, description_long, is_public, public_updated_at, supplier_type, activity_text, notes)
            VALUES
            (:name, :normalized_name, :address, :city, :postal_code, :country, :latitude, :longitude, :phone, :email, :website, :facebook_url, :instagram_url, :linkedin_url, :logo_url, :photo_cover_url, :slug, :description_short, :description_long, :is_public, :public_updated_at, :supplier_type, :activity_text, :notes)";
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
            ':facebook_url' => $supplier['facebook_url'],
            ':instagram_url' => $supplier['instagram_url'],
            ':linkedin_url' => $supplier['linkedin_url'],
            ':logo_url' => $supplier['logo_url'],
            ':photo_cover_url' => $supplier['photo_cover_url'],
            ':slug' => $supplier['slug'],
            ':description_short' => $supplier['description_short'],
            ':description_long' => $supplier['description_long'],
            ':is_public' => $supplier['is_public'],
            ':public_updated_at' => date('Y-m-d H:i:s'),
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

    sync_supplier_to_wordpress($pdo, $supplierId);

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
    sync_supplier_to_wordpress($pdo, $id);
    json_response(['ok' => true]);
}

function get_setting_value(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=:key LIMIT 1');
    $stmt->execute([':key' => $key]);
    $value = $stmt->fetchColumn();
    return $value !== false ? (string)$value : $default;
}

function set_setting_value(PDO $pdo, string $key, string $value): void
{
    $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')
        ->execute([':key' => $key, ':value' => $value]);
}

function get_public_assets_base_url(PDO $pdo): string
{
    $configured = trim(get_setting_value($pdo, 'public_assets_base_url', ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if ($host !== '' && !preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $host)) {
        $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
        $scheme = $isHttps ? 'https' : 'http';
        return $scheme . '://' . $host;
    }

    return '';
}

function absolutize_export_url(PDO $pdo, string $url): string
{
    $url = trim($url);
    if ($url === '' || preg_match('/^https?:\/\//i', $url)) {
        return $url;
    }
    if (strpos($url, '/') !== 0) {
        return $url;
    }

    $base = get_public_assets_base_url($pdo);
    return $base === '' ? $url : $base . $url;
}

function absolutize_export_url_list(PDO $pdo, string $listValue): string
{
    $parts = preg_split('/\s*;\s*/', trim($listValue)) ?: [];
    $parts = array_values(array_filter($parts, static fn($v) => $v !== ''));
    if (!$parts) {
        return '';
    }

    $parts = array_map(static fn($v) => absolutize_export_url($pdo, (string)$v), $parts);
    return implode('; ', $parts);
}

function parse_email_list(string $value): array
{
    $parts = preg_split('/[;,\s]+/', trim($value)) ?: [];
    $emails = [];
    foreach ($parts as $item) {
        $email = filter_var(trim((string)$item), FILTER_VALIDATE_EMAIL);
        if ($email !== false) {
            $emails[] = (string)$email;
        }
    }
    return array_values(array_unique($emails));
}

function get_notification_mail_config(PDO $pdo): array
{
    $config = require __DIR__ . '/config.php';
    $fallback = (array)($config['notifications'] ?? []);

    $result = [
        'host' => trim(get_setting_value($pdo, 'smtp_host', (string)($fallback['smtp_host'] ?? ''))),
        'port' => (int)trim(get_setting_value($pdo, 'smtp_port', (string)($fallback['smtp_port'] ?? ''))),
        'encryption' => strtolower(trim(get_setting_value($pdo, 'smtp_encryption', (string)($fallback['smtp_encryption'] ?? '')))),
        'username' => trim(get_setting_value($pdo, 'smtp_username', (string)($fallback['smtp_username'] ?? ''))),
        'password' => (string)get_setting_value($pdo, 'smtp_password', (string)($fallback['smtp_password'] ?? '')),
        'from_email' => trim(get_setting_value($pdo, 'smtp_from_email', (string)($fallback['from_email'] ?? ''))),
        'from_name' => trim(get_setting_value($pdo, 'smtp_from_name', (string)($fallback['from_name'] ?? 'AppCarte Limap'))),
    ];

    if (!in_array($result['encryption'], ['', 'tls', 'ssl'], true)) {
        $result['encryption'] = 'tls';
    }
    if ($result['port'] <= 0) {
        $result['port'] = $result['encryption'] === 'ssl' ? 465 : 587;
    }
    return $result;
}

function smtp_expect($socket, array $validCodes, ?string &$fullResponse = null): bool
{
    $response = '';
    while (!feof($socket)) {
        $line = fgets($socket, 1024);
        if ($line === false) {
            break;
        }
        $response .= $line;
        if (preg_match('/^\d{3}\s/', $line)) {
            break;
        }
    }

    $fullResponse = trim($response);
    if (!preg_match('/^(\d{3})/', $response, $m)) {
        return false;
    }
    $code = (int)$m[1];
    return in_array($code, $validCodes, true);
}

function smtp_command($socket, string $command, array $validCodes, ?string &$response = null): bool
{
    fwrite($socket, $command . "\r\n");
    return smtp_expect($socket, $validCodes, $response);
}

function smtp_send_plain_email(array $smtp, array $to, string $subject, string $body, ?string &$error = null): bool
{
    $error = null;
    $host = trim((string)($smtp['host'] ?? ''));
    if ($host === '') {
        $error = 'SMTP host manquant';
        return false;
    }

    $encryption = strtolower(trim((string)($smtp['encryption'] ?? '')));
    $port = (int)($smtp['port'] ?? ($encryption === 'ssl' ? 465 : 587));
    $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;

    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client($remote, $errno, $errstr, 20);
    if (!$socket) {
        $error = 'Connexion SMTP impossible: ' . $errstr;
        return false;
    }

    stream_set_timeout($socket, 20);

    $resp = '';
    if (!smtp_expect($socket, [220], $resp)) {
        fclose($socket);
        $error = 'SMTP greeting invalide: ' . $resp;
        return false;
    }

    $helloHost = gethostname() ?: 'localhost';
    if (!smtp_command($socket, 'EHLO ' . $helloHost, [250], $resp)) {
        if (!smtp_command($socket, 'HELO ' . $helloHost, [250], $resp)) {
            fclose($socket);
            $error = 'EHLO/HELO refusé: ' . $resp;
            return false;
        }
    }

    if ($encryption === 'tls') {
        if (!smtp_command($socket, 'STARTTLS', [220], $resp)) {
            fclose($socket);
            $error = 'STARTTLS refusé: ' . $resp;
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            $error = 'Activation TLS impossible';
            return false;
        }
        if (!smtp_command($socket, 'EHLO ' . $helloHost, [250], $resp)) {
            fclose($socket);
            $error = 'EHLO après STARTTLS refusé: ' . $resp;
            return false;
        }
    }

    $username = (string)($smtp['username'] ?? '');
    $password = (string)($smtp['password'] ?? '');
    if ($username !== '') {
        if (!smtp_command($socket, 'AUTH LOGIN', [334], $resp)) {
            fclose($socket);
            $error = 'AUTH LOGIN refusé: ' . $resp;
            return false;
        }
        if (!smtp_command($socket, base64_encode($username), [334], $resp)) {
            fclose($socket);
            $error = 'SMTP username refusé: ' . $resp;
            return false;
        }
        if (!smtp_command($socket, base64_encode($password), [235], $resp)) {
            fclose($socket);
            $error = 'SMTP password refusé: ' . $resp;
            return false;
        }
    }

    $fromEmail = trim((string)($smtp['from_email'] ?? ''));
    if ($fromEmail === '' || filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false) {
        $fromEmail = ($username !== '' && filter_var($username, FILTER_VALIDATE_EMAIL)) ? $username : ('noreply@' . (gethostname() ?: 'localhost'));
    }
    $fromName = trim((string)($smtp['from_name'] ?? 'AppCarte Limap'));

    if (!smtp_command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250], $resp)) {
        fclose($socket);
        $error = 'MAIL FROM refusé: ' . $resp;
        return false;
    }

    foreach ($to as $recipient) {
        if (!smtp_command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251], $resp)) {
            fclose($socket);
            $error = 'RCPT TO refusé pour ' . $recipient . ': ' . $resp;
            return false;
        }
    }

    if (!smtp_command($socket, 'DATA', [354], $resp)) {
        fclose($socket);
        $error = 'DATA refusé: ' . $resp;
        return false;
    }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = [
        'From: ' . ($fromName !== '' ? '"' . addslashes($fromName) . '" ' : '') . '<' . $fromEmail . '>',
        'To: ' . implode(', ', $to),
        'Subject: ' . $encodedSubject,
        'Date: ' . date(DATE_RFC2822),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];

    $normalizedBody = str_replace(["\r\n", "\r"], "\n", $body);
    $normalizedBody = preg_replace('/^\./m', '..', $normalizedBody);
    $data = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", $normalizedBody) . "\r\n.\r\n";
    fwrite($socket, $data);

    if (!smtp_expect($socket, [250], $resp)) {
        fclose($socket);
        $error = 'Validation DATA refusée: ' . $resp;
        return false;
    }

    smtp_command($socket, 'QUIT', [221], $resp);
    fclose($socket);
    return true;
}

function admin_notification_recipients(PDO $pdo): array
{
    $settingEmails = get_setting_value($pdo, 'admin_notification_emails', '');
    if ($settingEmails !== '') {
        return parse_email_list($settingEmails);
    }

    $config = require __DIR__ . '/config.php';
    $fallback = (string)($config['notifications']['admin_emails'] ?? '');
    return parse_email_list($fallback);
}

function send_plain_email(array $to, string $subject, string $body): bool
{
    if (!$to) {
        return false;
    }

    $pdo = get_db();
    $smtp = get_notification_mail_config($pdo);
    if (trim((string)($smtp['host'] ?? '')) !== '') {
        $smtpError = null;
        if (smtp_send_plain_email($smtp, $to, $subject, $body, $smtpError)) {
            return true;
        }
        error_log('AppCarte SMTP send failed: ' . (string)$smtpError);
        return false;
    }

    $config = require __DIR__ . '/config.php';
    $from = trim((string)($smtp['from_email'] ?? ($config['notifications']['from_email'] ?? '')));

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ];
    if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'From: ' . $from;
    }

    return @mail(implode(',', $to), $subject, $body, implode("\r\n", $headers));
}

function send_test_notification(PDO $pdo): void
{
    $input = get_json_input();
    $manualTo = parse_email_list((string)($input['to'] ?? ''));
    $to = $manualTo ?: admin_notification_recipients($pdo);
    if (!$to) {
        json_response(['ok' => false, 'error' => 'Aucun destinataire configuré'], 422);
    }

    $subject = '[AppCarte] Test notification SMTP';
    $body = "Ceci est un email de test depuis AppCarte Limap.\n\n"
        . 'Date: ' . date('Y-m-d H:i:s') . "\n"
        . 'Serveur: ' . (gethostname() ?: 'localhost') . "\n";

    if (!send_plain_email($to, $subject, $body)) {
        json_response(['ok' => false, 'error' => 'Échec de l\'envoi du mail de test (vérifie la config SMTP).'], 500);
    }

    json_response(['ok' => true]);
}

function notify_admin_new_change_request(PDO $pdo, int $requestId, int $supplierId, int $clientId, string $fieldName, string $oldValue, string $newValue): void
{
    $to = admin_notification_recipients($pdo);
    if (!$to) {
        return;
    }

    $supplierStmt = $pdo->prepare('SELECT name FROM suppliers WHERE id=:id LIMIT 1');
    $supplierStmt->execute([':id' => $supplierId]);
    $supplierName = (string)($supplierStmt->fetchColumn() ?: 'Fournisseur #' . $supplierId);

    $clientStmt = $pdo->prepare('SELECT name FROM clients WHERE id=:id LIMIT 1');
    $clientStmt->execute([':id' => $clientId]);
    $clientName = (string)($clientStmt->fetchColumn() ?: 'Client #' . $clientId);

    $subject = '[AppCarte] Nouvelle demande de modification';
    $body = "Une nouvelle demande de modification a été soumise.\n\n"
        . "Demande ID: {$requestId}\n"
        . "Client: {$clientName}\n"
        . "Fournisseur: {$supplierName}\n"
        . "Champ: {$fieldName}\n"
        . "Ancienne valeur: {$oldValue}\n"
        . "Nouvelle valeur: {$newValue}\n\n"
        . "Ouvre l'admin pour la traiter dans l'onglet Demandes.";

    if (!send_plain_email($to, $subject, $body)) {
        error_log('AppCarte: notification email failed for request #' . $requestId);
    }
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

    $clientStmt = $pdo->prepare('SELECT id, name, client_type, logo_url, city, address, postal_code, country, latitude, longitude, phone, email, website, facebook_url, instagram_url, lundi, mardi, mercredi, jeudi, vendredi, samedi, dimanche, description_short, description_long, gallery_images FROM clients WHERE id=:id AND is_active=1 LIMIT 1');
    $clientStmt->execute([':id' => $clientId]);
    $client = $clientStmt->fetch();
    if (!$client) {
        json_response(['ok' => false, 'error' => 'Client introuvable'], 404);
    }
    $client['phone'] = format_phone($client['phone'] ?? '');
    $client['logo_url'] = absolutize_export_url($pdo, (string)($client['logo_url'] ?? ''));
    $galleryUrls = absolutize_gallery_images_list($pdo, (string)($client['gallery_images'] ?? ''));
    $client['gallery_images'] = $galleryUrls
        ? json_encode(array_map(static fn(string $url): array => ['url' => $url], $galleryUrls), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : '';

    $clientConsentStmt = $pdo->prepare('SELECT status, consent_text_version, accepted_at
        FROM client_consents
        WHERE client_id=:client_id
        ORDER BY accepted_at DESC, id DESC
        LIMIT 1');
    $clientConsentStmt->execute([':client_id' => $clientId]);
    $clientConsent = $clientConsentStmt->fetch() ?: null;

    $consents = [
        'client_consent' => [
            'status' => $clientConsent ? (string)($clientConsent['status'] ?? 'none') : 'none',
            'accepted_at' => $clientConsent ? (string)($clientConsent['accepted_at'] ?? '') : '',
            'version' => $clientConsent ? (string)($clientConsent['consent_text_version'] ?? '') : '',
        ],
    ];

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
            s.facebook_url,
            s.instagram_url,
            s.linkedin_url,
            s.logo_url,
            s.photo_cover_url,
            s.slug,
            s.description_short,
            s.description_long,
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
            csp.updated_at AS profile_updated_at,
            (
                SELECT GROUP_CONCAT(csp2.activity_text SEPARATOR '; ')
                FROM client_supplier_profiles csp2
                WHERE csp2.supplier_id = s.id AND csp2.client_id != :client_id2
            ) AS other_clients_activity_text,
            (
                SELECT GROUP_CONCAT(csp2.labels_text SEPARATOR '; ')
                FROM client_supplier_profiles csp2
                WHERE csp2.supplier_id = s.id AND csp2.client_id != :client_id3
            ) AS other_clients_labels_text,
            (
                SELECT CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM supplier_consents sc
                        WHERE sc.supplier_id = s.id
                          AND sc.status = 'approved'
                          AND sc.revoked_at IS NULL
                    ) THEN 'approved'
                    ELSE COALESCE((
                        SELECT scr.status
                        FROM supplier_consent_requests scr
                        WHERE scr.supplier_id = s.id
                        ORDER BY scr.requested_at DESC, scr.id DESC
                        LIMIT 1
                    ), 'none')
                END
            ) AS supplier_consent_status,
            (
                SELECT c.name
                FROM supplier_consent_requests scr
                JOIN clients c ON c.id = scr.source_client_id
                WHERE scr.supplier_id = s.id
                ORDER BY scr.requested_at DESC, scr.id DESC
                LIMIT 1
            ) AS supplier_consent_source_client_name,
            (
                SELECT scr.source_client_id
                FROM supplier_consent_requests scr
                WHERE scr.supplier_id = s.id
                ORDER BY scr.requested_at DESC, scr.id DESC
                LIMIT 1
            ) AS supplier_consent_source_client_id,
            (
                SELECT scr.requested_at
                FROM supplier_consent_requests scr
                WHERE scr.supplier_id = s.id
                ORDER BY scr.requested_at DESC, scr.id DESC
                LIMIT 1
            ) AS supplier_consent_requested_at,
            (
                SELECT scr.answered_at
                FROM supplier_consent_requests scr
                WHERE scr.supplier_id = s.id
                ORDER BY scr.requested_at DESC, scr.id DESC
                LIMIT 1
            ) AS supplier_consent_answered_at
        FROM client_suppliers cs
        JOIN suppliers s ON s.id = cs.supplier_id
        LEFT JOIN client_supplier_profiles csp ON csp.client_id = cs.client_id AND csp.supplier_id = cs.supplier_id
        WHERE cs.client_id = :client_id
        ORDER BY s.name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':client_id' => $clientId, ':client_id2' => $clientId, ':client_id3' => $clientId]);
    $suppliers = $stmt->fetchAll();

    $activities = $pdo->query('SELECT id, name FROM activities WHERE is_active=1 ORDER BY family, name')->fetchAll();
    $labels = $pdo->query('SELECT id, name FROM labels WHERE is_active=1 ORDER BY name')->fetchAll();
    $supplierTypes = $pdo->query('SELECT id, name FROM supplier_types WHERE is_active=1 ORDER BY name')->fetchAll();

    $suppliers = array_map(function (array $row) use ($pdo, $clientId) {
        $row['phone'] = format_phone($row['phone'] ?? '');
        $row['logo_url'] = absolutize_export_url($pdo, (string)($row['logo_url'] ?? ''));
        $row['photo_cover_url'] = absolutize_export_url($pdo, (string)($row['photo_cover_url'] ?? ''));
        $status = (string)($row['supplier_consent_status'] ?? 'none');
        $row['supplier_consent_can_send'] = ($status === 'none');
        $row['supplier_consent_can_resend'] = in_array($status, ['sent', 'opened', 'expired', 'rejected'], true);
        $row['supplier_consent_source_client_name'] = ($row['supplier_consent_source_client_name'] ?? null);
        $row['supplier_consent_source_client_id'] = ($row['supplier_consent_source_client_id'] ?? null) !== null ? (int)$row['supplier_consent_source_client_id'] : null;
        if ($row['supplier_consent_source_client_id'] !== null && (int)$row['supplier_consent_source_client_id'] === $clientId) {
            $row['supplier_consent_source_client_name'] = (string)($client['name'] ?? '');
        }
        return $row;
    }, $suppliers);

    json_response([
        'ok' => true,
        'client' => $client,
        'consents' => $consents,
        'client_consent' => $consents['client_consent'],
        'suppliers' => $suppliers,
        'activities' => $activities,
        'labels' => $labels,
        'supplier_types' => $supplierTypes,
    ]);
}

function save_client_profile(PDO $pdo): void
{
    assert_client_can_write_profile();

    $input = get_json_input();
    $clientId = resolve_effective_client_id($input);
    if ($clientId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id requis'], 422);
    }

    $name = trim((string)($input['name'] ?? ''));
    if ($name === '') {
        json_response(['ok' => false, 'error' => 'Nom client requis'], 422);
    }

    $stmt = $pdo->prepare('SELECT id, name, client_type, email, phone, website, facebook_url, instagram_url, logo_url, address, city, postal_code, country, latitude, longitude, lundi, mardi, mercredi, jeudi, vendredi, samedi, dimanche, description_short, description_long, gallery_images FROM clients WHERE id=:id AND is_active=1 LIMIT 1');
    $stmt->execute([':id' => $clientId]);
    $existingClient = $stmt->fetch();
    if (!$existingClient) {
        json_response(['ok' => false, 'error' => 'Client introuvable'], 404);
    }

    $payload = [
        ':id' => $clientId,
        ':name' => $name,
        ':client_type' => trim((string)($input['client_type'] ?? '')),
        ':email' => trim((string)($input['email'] ?? '')),
        ':phone' => format_phone((string)($input['phone'] ?? '')),
        ':website' => trim((string)($input['website'] ?? '')),
        ':facebook_url' => trim((string)($input['facebook_url'] ?? '')),
        ':instagram_url' => trim((string)($input['instagram_url'] ?? '')),
        ':logo_url' => trim((string)($input['logo_url'] ?? '')),
        ':address' => trim((string)($input['address'] ?? '')),
        ':city' => trim((string)($input['city'] ?? '')),
        ':postal_code' => trim((string)($input['postal_code'] ?? '')),
        ':country' => trim((string)($input['country'] ?? '')),
        ':latitude' => ($input['latitude'] ?? '') !== '' ? (float)$input['latitude'] : null,
        ':longitude' => ($input['longitude'] ?? '') !== '' ? (float)$input['longitude'] : null,
        ':lundi' => trim((string)($input['lundi'] ?? '')),
        ':mardi' => trim((string)($input['mardi'] ?? '')),
        ':mercredi' => trim((string)($input['mercredi'] ?? '')),
        ':jeudi' => trim((string)($input['jeudi'] ?? '')),
        ':vendredi' => trim((string)($input['vendredi'] ?? '')),
        ':samedi' => trim((string)($input['samedi'] ?? '')),
        ':dimanche' => trim((string)($input['dimanche'] ?? '')),
        ':description_short' => trim((string)($input['description_short'] ?? '')),
        ':description_long' => trim((string)($input['description_long'] ?? '')),
        ':gallery_images' => trim((string)($input['gallery_images'] ?? '')),
    ];

    $pdo->prepare('UPDATE clients SET
        name=:name,
        client_type=:client_type,
        email=:email,
        phone=:phone,
        website=:website,
        facebook_url=:facebook_url,
        instagram_url=:instagram_url,
        logo_url=:logo_url,
        address=:address,
        city=:city,
        postal_code=:postal_code,
        country=:country,
        latitude=:latitude,
        longitude=:longitude,
        lundi=:lundi,
        mardi=:mardi,
        mercredi=:mercredi,
        jeudi=:jeudi,
        vendredi=:vendredi,
        samedi=:samedi,
        dimanche=:dimanche,
        description_short=:description_short,
        description_long=:description_long,
        gallery_images=:gallery_images,
        public_updated_at=NOW()
        WHERE id=:id')
        ->execute($payload);

    $changedFields = [];
    foreach ([
        'name', 'client_type', 'email', 'phone', 'website', 'facebook_url', 'instagram_url', 'logo_url',
        'address', 'city', 'postal_code', 'country', 'latitude', 'longitude',
        'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche',
        'description_short', 'description_long', 'gallery_images'
    ] as $field) {
        $oldValue = (string)($existingClient[$field] ?? '');
        $newValue = (string)($payload[':' . $field] ?? '');
        if ($oldValue !== $newValue) {
            $changedFields[] = $field;
        }
    }

    if ($changedFields) {
        write_admin_audit($pdo, 'client_account_updated', [
            'target_type' => 'action',
            'target_id' => $clientId,
            'target_label' => (string)($name !== '' ? $name : ('client#' . $clientId)),
            'details' => [
                'client_id' => $clientId,
                'changed_fields' => $changedFields,
            ],
        ]);

        if (function_exists('sync_client_to_wordpress')) {
            sync_client_to_wordpress($pdo, $clientId);
        }
    }

    json_response(['ok' => true]);
}

function merge_client_changes_into_global(array $globalItems, array $prevClientItems, array $newClientItems): array
{
    $norm = fn($v) => mb_strtolower(trim((string)$v), 'UTF-8');
    $prevNormed = array_map($norm, $prevClientItems);
    $newNormed  = array_map($norm, $newClientItems);
    // Items this client explicitly removed (were in their prev profile, no longer in new)
    $removedNorm = array_values(array_diff($prevNormed, $newNormed));

    $result   = [];
    $seenNorm = [];
    // Keep all global items except those removed by this client
    foreach ($globalItems as $item) {
        $n = $norm($item);
        if (in_array($n, $removedNorm, true) || in_array($n, $seenNorm, true)) {
            continue;
        }
        $result[]   = $item;
        $seenNorm[] = $n;
    }
    // Add newly submitted items from this client
    foreach ($newClientItems as $item) {
        $n = $norm($item);
        if (in_array($n, $seenNorm, true)) {
            continue;
        }
        $result[]   = $item;
        $seenNorm[] = $n;
    }
    return $result;
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

    $changedFields = [];

    foreach ($trackedFields as $field => $newValue) {
        $oldValue = $existing[$field] ?? null;
        if ((string)$oldValue === (string)$newValue) {
            continue;
        }
        write_supplier_audit($pdo, $supplierId, 'client_profile_update', $field, $oldValue, $newValue, ['client_id' => $clientId]);
        $changedFields[] = $field;
    }

    // Merge this client's changes into the global supplier record, preserving
    // contributions from other clients.
    $gSupStmt = $pdo->prepare('SELECT activity_text FROM suppliers WHERE id=:id LIMIT 1');
    $gSupStmt->execute([':id' => $supplierId]);
    $gSup = $gSupStmt->fetch();
    $globalActivities    = parse_csv_list($gSup['activity_text'] ?? '');
    $prevClientActivities = parse_csv_list($existing['activity_text'] ?? '');
    $newClientActivities  = parse_csv_list($activityText);
    $mergedActivities = merge_client_changes_into_global($globalActivities, $prevClientActivities, $newClientActivities);

    $globalLabelsStr  = get_supplier_labels_csv($pdo, $supplierId);
    $globalLabels     = parse_csv_list($globalLabelsStr);
    $prevClientLabels = parse_csv_list($existing['labels_text'] ?? '');
    $newClientLabels  = parse_csv_list($labelsText);
    $mergedLabels = merge_client_changes_into_global($globalLabels, $prevClientLabels, $newClientLabels);

    $pdo->prepare('UPDATE suppliers SET activity_text=:activity_text WHERE id=:id')
        ->execute([':activity_text' => implode('; ', $mergedActivities), ':id' => $supplierId]);
    sync_supplier_labels($pdo, $supplierId, $mergedLabels);

    if ($changedFields) {
        $metaStmt = $pdo->prepare('SELECT c.name AS client_name, s.name AS supplier_name FROM clients c JOIN suppliers s ON s.id=:supplier_id WHERE c.id=:client_id LIMIT 1');
        $metaStmt->execute([':client_id' => $clientId, ':supplier_id' => $supplierId]);
        $meta = $metaStmt->fetch() ?: [];

        $clientName = trim((string)($meta['client_name'] ?? ''));
        $supplierName = trim((string)($meta['supplier_name'] ?? ''));
        $targetLabel = trim(($clientName !== '' ? $clientName : ('client#' . $clientId)) . ' / ' . ($supplierName !== '' ? $supplierName : ('supplier#' . $supplierId)));

        write_admin_audit($pdo, 'client_profile_saved', [
            'target_type' => 'action',
            'target_id' => $supplierId,
            'target_label' => $targetLabel,
            'details' => [
                'client_id' => $clientId,
                'supplier_id' => $supplierId,
                'changed_fields' => $changedFields,
                'relationship_status' => $relationshipStatus,
            ],
        ]);
    }

    json_response(['ok' => true]);
}

function allowed_global_change_fields(): array
{
    return [
        'name',
        'address',
        'city',
        'postal_code',
        'country',
        'phone',
        'email',
        'website',
        'facebook_url',
        'instagram_url',
        'linkedin_url',
        'logo_url',
        'photo_cover_url',
        'slug',
        'description_short',
        'description_long',
        'supplier_type',
        'activity_text',
        'labels',
    ];
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
        return;
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

function resolve_change_request_user_id(PDO $pdo, int $clientId): int
{
    $actor = current_actor_context();
    if ($actor['actor_type'] === 'client_user' && $actor['actor_id'] !== null) {
        return (int)$actor['actor_id'];
    }
    if ($actor['actor_type'] === 'admin') {
        $proxyStmt = $pdo->prepare('SELECT id FROM client_users WHERE client_id=:client_id AND is_active=1 ORDER BY id LIMIT 1');
        $proxyStmt->execute([':client_id' => $clientId]);
        return (int)($proxyStmt->fetchColumn() ?: 0);
    }
    return 0;
}

function save_supplier_creation_request(PDO $pdo): void
{
    assert_client_can_create_change_request();

    $input = get_json_input();
    $clientId = resolve_effective_client_id($input);
    if ($clientId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id requis'], 422);
    }

    $name = trim((string)($input['name'] ?? ''));
    if ($name === '') {
        json_response(['ok' => false, 'error' => 'Nom fournisseur requis'], 422);
    }

    $requestedByUserId = resolve_change_request_user_id($pdo, $clientId);
    if ($requestedByUserId <= 0) {
        json_response(['ok' => false, 'error' => 'Aucun utilisateur client actif trouvé pour ce client (crée un compte client actif).'], 422);
    }

    $activityText = supplier_field_for_change_request('activity_text', (string)($input['activity_text'] ?? ''));
    $labelsText = supplier_field_for_change_request('labels', (string)($input['labels_text'] ?? ''));

    $pdo->prepare('INSERT INTO client_supplier_creation_requests
        (client_id, requested_by_user_id, name, supplier_type, activity_text, labels_text, address, city, postal_code, country, phone, email, website, notes, status)
        VALUES
        (:client_id, :requested_by_user_id, :name, :supplier_type, :activity_text, :labels_text, :address, :city, :postal_code, :country, :phone, :email, :website, :notes, "pending")')
        ->execute([
            ':client_id' => $clientId,
            ':requested_by_user_id' => $requestedByUserId,
            ':name' => $name,
            ':supplier_type' => trim((string)($input['supplier_type'] ?? '')),
            ':activity_text' => $activityText,
            ':labels_text' => $labelsText,
            ':address' => trim((string)($input['address'] ?? '')),
            ':city' => trim((string)($input['city'] ?? '')),
            ':postal_code' => trim((string)($input['postal_code'] ?? '')),
            ':country' => trim((string)($input['country'] ?? 'France')),
            ':phone' => format_phone((string)($input['phone'] ?? '')),
            ':email' => trim((string)($input['email'] ?? '')),
            ':website' => trim((string)($input['website'] ?? '')),
            ':notes' => trim((string)($input['notes'] ?? '')),
        ]);

    $requestId = (int)$pdo->lastInsertId();
    $clientNameStmt = $pdo->prepare('SELECT name FROM clients WHERE id=:id LIMIT 1');
    $clientNameStmt->execute([':id' => $clientId]);
    $clientName = (string)($clientNameStmt->fetchColumn() ?: ('client#' . $clientId));

    write_admin_audit($pdo, 'supplier_creation_request_submitted', [
        'target_type' => 'action',
        'target_id' => $requestId,
        'target_label' => $clientName . ' / ' . $name,
        'details' => [
            'request_id' => $requestId,
            'client_id' => $clientId,
            'requested_by_user_id' => $requestedByUserId,
            'supplier_name' => $name,
        ],
    ]);

    json_response(['ok' => true]);
}

function list_supplier_creation_requests_for_client(PDO $pdo): void
{
    $inputClientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
    $status = trim((string)($_GET['status'] ?? ''));

    $clientId = resolve_effective_client_id(['client_id' => $inputClientId]);
    if ($clientId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id requis'], 422);
    }

    $sql = 'SELECT csr.id, csr.client_id, csr.requested_by_user_id, csr.name, csr.supplier_type, csr.activity_text, csr.labels_text, csr.address, csr.city, csr.postal_code, csr.country, csr.phone, csr.email, csr.website, csr.notes, csr.status, csr.approved_supplier_id, csr.reviewed_by_admin, csr.reviewed_at, csr.review_note, csr.created_at, cu.username AS requested_by_username, s.name AS approved_supplier_name
            FROM client_supplier_creation_requests csr
            LEFT JOIN client_users cu ON cu.id = csr.requested_by_user_id
            LEFT JOIN suppliers s ON s.id = csr.approved_supplier_id
            WHERE csr.client_id=:client_id';
    $params = [':client_id' => $clientId];

    if ($status !== '' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $sql .= ' AND csr.status=:status';
        $params[':status'] = $status;
    }

    $sql .= ' ORDER BY csr.created_at DESC, csr.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    json_response(['ok' => true, 'requests' => $stmt->fetchAll()]);
}

function search_supplier_link_candidates_for_client(PDO $pdo): void
{
    $inputClientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
    $query = trim((string)($_GET['q'] ?? ''));

    $clientId = resolve_effective_client_id(['client_id' => $inputClientId]);
    if ($clientId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id requis'], 422);
    }

    if (mb_strlen($query, 'UTF-8') < 2) {
        json_response(['ok' => true, 'suppliers' => []]);
    }

    $like = '%' . $query . '%';
    $sql = 'SELECT s.id, s.name, s.city, s.postal_code, s.supplier_type
            FROM suppliers s
            LEFT JOIN client_suppliers cs ON cs.supplier_id = s.id AND cs.client_id = :client_id
            WHERE cs.id IS NULL
              AND (
                s.name LIKE :q
                OR s.city LIKE :q
                OR s.postal_code LIKE :q
                OR s.email LIKE :q
              )
            ORDER BY s.name ASC
            LIMIT 30';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':client_id' => $clientId,
        ':q' => $like,
    ]);

    json_response(['ok' => true, 'suppliers' => $stmt->fetchAll()]);
}

function save_supplier_link_request(PDO $pdo): void
{
    assert_client_can_create_change_request();

    $input = get_json_input();
    $clientId = resolve_effective_client_id($input);
    $supplierId = isset($input['supplier_id']) ? (int)$input['supplier_id'] : 0;
    $note = trim((string)($input['note'] ?? ''));

    if ($clientId <= 0 || $supplierId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id et supplier_id requis'], 422);
    }

    $existsStmt = $pdo->prepare('SELECT id, name FROM suppliers WHERE id=:id LIMIT 1');
    $existsStmt->execute([':id' => $supplierId]);
    $supplier = $existsStmt->fetch();
    if (!$supplier) {
        json_response(['ok' => false, 'error' => 'Fournisseur introuvable'], 404);
    }

    $linkStmt = $pdo->prepare('SELECT 1 FROM client_suppliers WHERE client_id=:client_id AND supplier_id=:supplier_id LIMIT 1');
    $linkStmt->execute([
        ':client_id' => $clientId,
        ':supplier_id' => $supplierId,
    ]);
    if ($linkStmt->fetch()) {
        json_response(['ok' => false, 'error' => 'Ce fournisseur est déjà lié à votre client'], 422);
    }

    $dupStmt = $pdo->prepare('SELECT id FROM client_supplier_link_requests WHERE client_id=:client_id AND supplier_id=:supplier_id AND status="pending" LIMIT 1');
    $dupStmt->execute([
        ':client_id' => $clientId,
        ':supplier_id' => $supplierId,
    ]);
    if ($dupStmt->fetch()) {
        json_response(['ok' => false, 'error' => 'Une demande de rattachement est déjà en attente pour ce fournisseur'], 422);
    }

    $requestedByUserId = resolve_change_request_user_id($pdo, $clientId);
    if ($requestedByUserId <= 0) {
        json_response(['ok' => false, 'error' => 'Aucun utilisateur client actif trouvé pour ce client (crée un compte client actif).'], 422);
    }

    $pdo->prepare('INSERT INTO client_supplier_link_requests (client_id, requested_by_user_id, supplier_id, note, status)
        VALUES (:client_id, :requested_by_user_id, :supplier_id, :note, "pending")')
        ->execute([
            ':client_id' => $clientId,
            ':requested_by_user_id' => $requestedByUserId,
            ':supplier_id' => $supplierId,
            ':note' => $note,
        ]);

    $requestId = (int)$pdo->lastInsertId();
    notify_admin_new_supplier_link_request($pdo, $requestId, $supplierId, $clientId, $note);

    $clientNameStmt = $pdo->prepare('SELECT name FROM clients WHERE id=:id LIMIT 1');
    $clientNameStmt->execute([':id' => $clientId]);
    $clientName = (string)($clientNameStmt->fetchColumn() ?: ('client#' . $clientId));

    write_admin_audit($pdo, 'supplier_link_request_submitted', [
        'target_type' => 'action',
        'target_id' => $requestId,
        'target_label' => $clientName . ' / ' . (string)($supplier['name'] ?? ('supplier#' . $supplierId)),
        'details' => [
            'request_id' => $requestId,
            'client_id' => $clientId,
            'supplier_id' => $supplierId,
            'requested_by_user_id' => $requestedByUserId,
        ],
    ]);

    json_response(['ok' => true]);
}

function notify_admin_new_supplier_link_request(PDO $pdo, int $requestId, int $supplierId, int $clientId, string $note): void
{
    $to = admin_notification_recipients($pdo);
    if (!$to) {
        return;
    }

    $supplierStmt = $pdo->prepare('SELECT name FROM suppliers WHERE id=:id LIMIT 1');
    $supplierStmt->execute([':id' => $supplierId]);
    $supplierName = (string)($supplierStmt->fetchColumn() ?: 'Fournisseur #' . $supplierId);

    $clientStmt = $pdo->prepare('SELECT name FROM clients WHERE id=:id LIMIT 1');
    $clientStmt->execute([':id' => $clientId]);
    $clientName = (string)($clientStmt->fetchColumn() ?: 'Client #' . $clientId);

    $noteText = $note !== '' ? "\nNote client : {$note}" : '';
    $subject = '[AppCarte] Demande de rattachement fournisseur';
    $body = "Un client demande le rattachement d'un fournisseur existant.\n\n"
        . "Demande ID : {$requestId}\n"
        . "Client : {$clientName}\n"
        . "Fournisseur : {$supplierName}"
        . $noteText . "\n\n"
        . "Ouvre l'admin dans l'onglet Demandes pour traiter cette demande.";

    if (!send_plain_email($to, $subject, $body)) {
        error_log('AppCarte: notification email failed for link request #' . $requestId);
    }
}

function list_supplier_link_requests_for_client(PDO $pdo): void
{
    $inputClientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
    $status = trim((string)($_GET['status'] ?? ''));

    $clientId = resolve_effective_client_id(['client_id' => $inputClientId]);
    if ($clientId <= 0) {
        json_response(['ok' => false, 'error' => 'client_id requis'], 422);
    }

    $sql = 'SELECT lr.id, lr.client_id, lr.requested_by_user_id, lr.supplier_id, lr.note, lr.status, lr.reviewed_by_admin, lr.reviewed_at, lr.review_note, lr.created_at,
                   cu.username AS requested_by_username, s.name AS supplier_name, s.city AS supplier_city
            FROM client_supplier_link_requests lr
            LEFT JOIN client_users cu ON cu.id = lr.requested_by_user_id
            JOIN suppliers s ON s.id = lr.supplier_id
            WHERE lr.client_id=:client_id';
    $params = [':client_id' => $clientId];

    if ($status !== '' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $sql .= ' AND lr.status=:status';
        $params[':status'] = $status;
    }

    $sql .= ' ORDER BY lr.created_at DESC, lr.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    json_response(['ok' => true, 'requests' => $stmt->fetchAll()]);
}

function list_supplier_creation_requests_for_admin(PDO $pdo): void
{
    $status = trim((string)($_GET['status'] ?? 'all'));
    $clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

    $sql = 'SELECT csr.id, csr.client_id, csr.requested_by_user_id, csr.name, csr.supplier_type, csr.activity_text, csr.labels_text, csr.address, csr.city, csr.postal_code, csr.country, csr.phone, csr.email, csr.website, csr.notes, csr.status, csr.approved_supplier_id, csr.reviewed_by_admin, csr.reviewed_at, csr.review_note, csr.created_at, c.name AS client_name, cu.username AS requested_by_username, s.name AS approved_supplier_name
            FROM client_supplier_creation_requests csr
            JOIN clients c ON c.id = csr.client_id
            LEFT JOIN client_users cu ON cu.id = csr.requested_by_user_id
            LEFT JOIN suppliers s ON s.id = csr.approved_supplier_id
            WHERE 1=1';
    $params = [];

    if ($status !== '' && $status !== 'all' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $sql .= ' AND csr.status=:status';
        $params[':status'] = $status;
    }

    if ($clientId > 0) {
        $sql .= ' AND csr.client_id=:client_id';
        $params[':client_id'] = $clientId;
    }

    $sql .= ' ORDER BY (csr.status="pending") DESC, csr.created_at DESC, csr.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    json_response(['ok' => true, 'requests' => $stmt->fetchAll()]);
}

function list_supplier_link_requests_for_admin(PDO $pdo): void
{
    $status = trim((string)($_GET['status'] ?? 'all'));
    $clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

    $sql = 'SELECT lr.id, lr.client_id, lr.requested_by_user_id, lr.supplier_id, lr.note, lr.status, lr.reviewed_by_admin, lr.reviewed_at, lr.review_note, lr.created_at,
                   c.name AS client_name, cu.username AS requested_by_username, s.name AS supplier_name, s.city AS supplier_city
            FROM client_supplier_link_requests lr
            JOIN clients c ON c.id = lr.client_id
            LEFT JOIN client_users cu ON cu.id = lr.requested_by_user_id
            JOIN suppliers s ON s.id = lr.supplier_id
            WHERE 1=1';
    $params = [];

    if ($status !== '' && $status !== 'all' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $sql .= ' AND lr.status=:status';
        $params[':status'] = $status;
    }

    if ($clientId > 0) {
        $sql .= ' AND lr.client_id=:client_id';
        $params[':client_id'] = $clientId;
    }

    $sql .= ' ORDER BY (lr.status="pending") DESC, lr.created_at DESC, lr.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    json_response(['ok' => true, 'requests' => $stmt->fetchAll()]);
}

function review_supplier_creation_request(PDO $pdo): void
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

    $stmt = $pdo->prepare('SELECT * FROM client_supplier_creation_requests WHERE id=:id LIMIT 1');
    $stmt->execute([':id' => $requestId]);
    $request = $stmt->fetch();
    if (!$request) {
        json_response(['ok' => false, 'error' => 'Demande introuvable'], 404);
    }
    if ((string)$request['status'] !== 'pending') {
        json_response(['ok' => false, 'error' => 'Cette demande est déjà traitée'], 422);
    }

    start_app_session();
    $reviewedBy = (string)($_SESSION['admin_username'] ?? 'admin');

    $approvedSupplierId = null;

    $pdo->beginTransaction();
    try {
        if ($decision === 'approved') {
            $name = trim((string)($request['name'] ?? ''));
            if ($name === '') {
                throw new RuntimeException('Nom fournisseur requis pour valider la demande');
            }

            $address = trim((string)($request['address'] ?? ''));
            $postalCode = trim((string)($request['postal_code'] ?? ''));
            $city = trim((string)($request['city'] ?? ''));
            $country = trim((string)($request['country'] ?? ''));
            $geoQuery = implode(', ', array_values(array_filter([
                $address,
                $postalCode,
                $city,
                $country,
            ], static fn($v) => trim((string)$v) !== '')));
            $geo = $geoQuery !== '' ? geocode_address_text($geoQuery) : null;
            $latitude = (is_array($geo) && isset($geo['lat'])) ? (float)$geo['lat'] : null;
            $longitude = (is_array($geo) && isset($geo['lng'])) ? (float)$geo['lng'] : null;

            $insertSupplierSql = 'INSERT INTO suppliers
                (name, normalized_name, address, city, postal_code, country, latitude, longitude, phone, email, website, supplier_type, activity_text, notes, slug, is_public, public_updated_at)
                VALUES
                (:name, :normalized_name, :address, :city, :postal_code, :country, :latitude, :longitude, :phone, :email, :website, :supplier_type, :activity_text, :notes, :slug, 1, NOW())';

            $pdo->prepare($insertSupplierSql)->execute([
                ':name' => $name,
                ':normalized_name' => normalize_text($name),
                ':address' => $address,
                ':city' => $city,
                ':postal_code' => $postalCode,
                ':country' => $country,
                ':latitude' => $latitude,
                ':longitude' => $longitude,
                ':phone' => format_phone((string)($request['phone'] ?? '')),
                ':email' => trim((string)($request['email'] ?? '')),
                ':website' => trim((string)($request['website'] ?? '')),
                ':supplier_type' => trim((string)($request['supplier_type'] ?? '')),
                ':activity_text' => supplier_field_for_change_request('activity_text', (string)($request['activity_text'] ?? '')),
                ':notes' => trim((string)($request['notes'] ?? '')),
                ':slug' => slugify_text($name),
            ]);

            $approvedSupplierId = (int)$pdo->lastInsertId();

            $pdo->prepare('INSERT IGNORE INTO client_suppliers (client_id, supplier_id) VALUES (:client_id, :supplier_id)')
                ->execute([
                    ':client_id' => (int)$request['client_id'],
                    ':supplier_id' => $approvedSupplierId,
                ]);

            $pdo->prepare('INSERT INTO client_supplier_profiles (client_id, supplier_id, activity_text, labels_text, notes, relationship_status, updated_by_type, updated_by_id)
                VALUES (:client_id, :supplier_id, :activity_text, :labels_text, :notes, "active", "admin", NULL)
                ON DUPLICATE KEY UPDATE activity_text=VALUES(activity_text), labels_text=VALUES(labels_text), notes=VALUES(notes), relationship_status="active", updated_by_type="admin", updated_by_id=NULL')
                ->execute([
                    ':client_id' => (int)$request['client_id'],
                    ':supplier_id' => $approvedSupplierId,
                    ':activity_text' => supplier_field_for_change_request('activity_text', (string)($request['activity_text'] ?? '')),
                    ':labels_text' => supplier_field_for_change_request('labels', (string)($request['labels_text'] ?? '')),
                    ':notes' => trim((string)($request['notes'] ?? '')),
                ]);

            sync_supplier_labels($pdo, $approvedSupplierId, parse_csv_list((string)($request['labels_text'] ?? '')));

            write_supplier_audit(
                $pdo,
                $approvedSupplierId,
                'creation_request_approved_create_supplier',
                null,
                null,
                $name,
                ['request_id' => $requestId, 'client_id' => (int)$request['client_id']]
            );
        }

        $pdo->prepare('UPDATE client_supplier_creation_requests
            SET status=:status,
                approved_supplier_id=:approved_supplier_id,
                reviewed_by_admin=:reviewed_by_admin,
                reviewed_at=NOW(),
                review_note=:review_note
            WHERE id=:id')
            ->execute([
                ':status' => $decision,
                ':approved_supplier_id' => $approvedSupplierId,
                ':reviewed_by_admin' => $reviewedBy,
                ':review_note' => $reviewNote,
                ':id' => $requestId,
            ]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    json_response(['ok' => true]);
}

function review_supplier_link_request(PDO $pdo): void
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

    $stmt = $pdo->prepare('SELECT * FROM client_supplier_link_requests WHERE id=:id LIMIT 1');
    $stmt->execute([':id' => $requestId]);
    $request = $stmt->fetch();
    if (!$request) {
        json_response(['ok' => false, 'error' => 'Demande introuvable'], 404);
    }
    if ((string)$request['status'] !== 'pending') {
        json_response(['ok' => false, 'error' => 'Cette demande est déjà traitée'], 422);
    }

    start_app_session();
    $reviewedBy = (string)($_SESSION['admin_username'] ?? 'admin');

    $pdo->beginTransaction();
    try {
        if ($decision === 'approved') {
            $pdo->prepare('INSERT IGNORE INTO client_suppliers (client_id, supplier_id, source) VALUES (:client_id, :supplier_id, "link_request")')
                ->execute([
                    ':client_id' => (int)$request['client_id'],
                    ':supplier_id' => (int)$request['supplier_id'],
                ]);

            $pdo->prepare('INSERT INTO client_supplier_profiles (client_id, supplier_id, relationship_status, updated_by_type, updated_by_id)
                VALUES (:client_id, :supplier_id, "active", "admin", NULL)
                ON DUPLICATE KEY UPDATE updated_by_type="admin", updated_by_id=NULL')
                ->execute([
                    ':client_id' => (int)$request['client_id'],
                    ':supplier_id' => (int)$request['supplier_id'],
                ]);

            write_supplier_audit(
                $pdo,
                (int)$request['supplier_id'],
                'link_request_approved_attach_client',
                null,
                null,
                (string)$request['client_id'],
                ['request_id' => $requestId, 'client_id' => (int)$request['client_id']]
            );
        }

        $pdo->prepare('UPDATE client_supplier_link_requests
            SET status=:status,
                reviewed_by_admin=:reviewed_by_admin,
                reviewed_at=NOW(),
                review_note=:review_note
            WHERE id=:id')
            ->execute([
                ':status' => $decision,
                ':reviewed_by_admin' => $reviewedBy,
                ':review_note' => $reviewNote,
                ':id' => $requestId,
            ]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    json_response(['ok' => true]);
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

    $requestedByUserId = resolve_change_request_user_id($pdo, $clientId);

    if ($requestedByUserId <= 0) {
        json_response(['ok' => false, 'error' => 'Aucun utilisateur client actif trouvé pour ce client (crée un compte client actif).'], 422);
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

    $requestId = (int)$pdo->lastInsertId();
    notify_admin_new_change_request($pdo, $requestId, $supplierId, $clientId, $fieldName, $oldValue, $newValue);

    write_supplier_audit($pdo, $supplierId, 'change_request_created', $fieldName, $oldValue, $newValue, ['client_id' => $clientId]);

    $metaStmt = $pdo->prepare('SELECT c.name AS client_name, s.name AS supplier_name FROM clients c JOIN suppliers s ON s.id=:supplier_id WHERE c.id=:client_id LIMIT 1');
    $metaStmt->execute([':client_id' => $clientId, ':supplier_id' => $supplierId]);
    $meta = $metaStmt->fetch() ?: [];
    $clientName = trim((string)($meta['client_name'] ?? ''));
    $supplierName = trim((string)($meta['supplier_name'] ?? ''));

    write_admin_audit($pdo, 'supplier_change_request_submitted', [
        'target_type' => 'action',
        'target_id' => $requestId,
        'target_label' => ($clientName !== '' ? $clientName : ('client#' . $clientId)) . ' / ' . ($supplierName !== '' ? $supplierName : ('supplier#' . $supplierId)),
        'details' => [
            'request_id' => $requestId,
            'client_id' => $clientId,
            'supplier_id' => $supplierId,
            'field_name' => $fieldName,
            'requested_by_user_id' => $requestedByUserId,
        ],
    ]);

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
            $pdo->prepare('UPDATE suppliers SET public_updated_at=NOW() WHERE id=:supplier_id')
                ->execute([':supplier_id' => (int)$request['supplier_id']]);
        } else {
            $sql = 'UPDATE suppliers SET ' . $fieldName . '=:new_value, public_updated_at=NOW() WHERE id=:supplier_id';
            $pdo->prepare($sql)->execute([
                ':new_value' => $newValue,
                ':supplier_id' => (int)$request['supplier_id'],
            ]);

            if (in_array($fieldName, ['address', 'postal_code', 'city', 'country'], true)) {
                $supplierStmt = $pdo->prepare('SELECT address, postal_code, city, country FROM suppliers WHERE id=:id LIMIT 1');
                $supplierStmt->execute([':id' => (int)$request['supplier_id']]);
                $supplierRow = $supplierStmt->fetch() ?: null;
                if ($supplierRow) {
                    $query = implode(', ', array_values(array_filter([
                        trim((string)($supplierRow['address'] ?? '')),
                        trim((string)($supplierRow['postal_code'] ?? '')),
                        trim((string)($supplierRow['city'] ?? '')),
                        trim((string)($supplierRow['country'] ?? '')),
                    ], static fn($v) => trim((string)$v) !== '')));
                    if ($query !== '') {
                        $geo = geocode_address_text($query);
                        if (is_array($geo) && isset($geo['lat'], $geo['lng'])) {
                            $pdo->prepare('UPDATE suppliers SET latitude=:latitude, longitude=:longitude, public_updated_at=NOW() WHERE id=:supplier_id')
                                ->execute([
                                    ':latitude' => (float)$geo['lat'],
                                    ':longitude' => (float)$geo['lng'],
                                    ':supplier_id' => (int)$request['supplier_id'],
                                ]);
                        }
                    }
                }
            }
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
    start_app_session();
    $hasPrivilegedSession = !empty($_SESSION['is_admin']) || !empty($_SESSION['is_client_user']);
    $privateModeRequested = isset($_GET['private']) && (string)$_GET['private'] === '1';
    $isPrivileged = $hasPrivilegedSession && $privateModeRequested;

    $settingsRows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    $settings = [];
    foreach ($settingsRows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

        $clientWhere = 'c.is_active=1';
    if (!$isPrivileged) {
        $clientWhere .= " AND EXISTS (
                        SELECT 1
                        FROM client_consents cc
                        WHERE cc.client_id = c.id
                            AND cc.status = 'approved'
                            AND cc.revoked_at IS NULL
        )";
    }
    $clients = $pdo->query('SELECT c.id, c.name, c.client_type, c.latitude, c.longitude, c.logo_url, c.city, c.address, c.postal_code, c.phone, c.email, c.lundi, c.mardi, c.mercredi, c.jeudi, c.vendredi, c.samedi, c.dimanche, c.website, c.facebook_url, c.instagram_url, c.linkedin_url FROM clients c WHERE ' . $clientWhere . ' ORDER BY c.name')->fetchAll();
    $clients = array_map(function (array $client) use ($pdo): array {
        $client['logo_url'] = absolutize_export_url($pdo, (string)($client['logo_url'] ?? ''));
        return $client;
    }, $clients);

    $activities = $pdo->query('SELECT name, family, icon_url FROM activities WHERE is_active=1')->fetchAll();
    $activities = array_map(function (array $activity) use ($pdo): array {
        $activity['icon_url'] = absolutize_export_url($pdo, (string)($activity['icon_url'] ?? ''));
        return $activity;
    }, $activities);
    $labels = $pdo->query('SELECT name, color FROM labels WHERE is_active=1')->fetchAll();

    $publicSupplierFilter = '';
    if (!$isPrivileged) {
        $publicSupplierFilter = "
        AND EXISTS (
            SELECT 1
            FROM supplier_consents sc
            WHERE sc.supplier_id = s.id
              AND sc.status = 'approved'
              AND sc.revoked_at IS NULL
        )";
    }

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
        WHERE (
            NOT EXISTS (
                SELECT 1
                FROM client_suppliers cs0
                WHERE cs0.supplier_id = s.id
            )
            OR EXISTS (
                SELECT 1
                FROM client_suppliers cs1
                LEFT JOIN client_supplier_profiles csp1
                  ON csp1.client_id = cs1.client_id
                 AND csp1.supplier_id = cs1.supplier_id
                WHERE cs1.supplier_id = s.id
                  AND COALESCE(csp1.relationship_status, 'active') <> 'inactive'
            )
        )
            {$publicSupplierFilter}
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
        'org_logo_url' => absolutize_export_url($pdo, (string)($settings['org_logo_url'] ?? '')),
        'default_client_icon' => absolutize_export_url($pdo, (string)($settings['default_client_icon'] ?? '')),
        'default_producer_icon' => absolutize_export_url($pdo, (string)($settings['default_producer_icon'] ?? '')),
        'farm_direct_icon' => absolutize_export_url($pdo, (string)($settings['farm_direct_icon'] ?? '')),
        'clients' => $clients,
        'data' => $allData,
        'icon_map_norm' => $iconMapNorm,
        'label_color_map_norm' => $labelColorMapNorm,
        'activities' => $activities,
        'labels' => $labels,
    ]);
}

// REMOVED: PDF consent request functions - Phase 1 cleanup
// See SPEC_CONSENTEMENTS.md section 16 for new implementation

function review_consent_request_DISABLED(PDO $pdo): void
{
    $input = get_json_input();
    $scope = trim((string)($input['request_scope'] ?? ''));
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $decision = trim((string)($input['decision'] ?? ''));
    $reviewNote = trim((string)($input['review_note'] ?? ''));

    if (!in_array($scope, ['client', 'supplier'], true) || $id <= 0) {
        json_response(['ok' => false, 'error' => 'Demande invalide'], 422);
    }
    if (!in_array($decision, ['approved', 'rejected'], true)) {
        json_response(['ok' => false, 'error' => 'Décision invalide'], 422);
    }

    start_app_session();
    $adminId = !empty($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : null;
    $adminName = (string)($_SESSION['admin_username'] ?? 'admin');

    if ($scope === 'client') {
        $stmt = $pdo->prepare('UPDATE client_consent_documents SET status=:status, reviewed_by_admin_id=:reviewed_by_admin_id, reviewed_by_admin_name=:reviewed_by_admin_name, reviewed_at=NOW(), review_note=:review_note WHERE id=:id');
    } else {
        $stmt = $pdo->prepare('UPDATE supplier_consent_documents SET status=:status, reviewed_by_admin_id=:reviewed_by_admin_id, reviewed_by_admin_name=:reviewed_by_admin_name, reviewed_at=NOW(), review_note=:review_note WHERE id=:id');
    }

    $stmt->execute([
        ':status' => $decision,
        ':reviewed_by_admin_id' => $adminId,
        ':reviewed_by_admin_name' => $adminName,
        ':review_note' => $reviewNote,
        ':id' => $id,
    ]);

}

function build_client_sync_payload_variants(array $payload): array
{
    $variants = [];

    // Variant 0: flat payload (current format).
    $variants[] = $payload;

    // Variant 1: nested under client key.
    $variants[] = ['client' => $payload];

    // Variant 2: nested under payload key.
    $variants[] = ['payload' => $payload];

    // Variant 3: explicit id_source + nested client body.
    $variants[] = [
        'id_source' => (int)($payload['id_source'] ?? 0),
        'client' => $payload,
    ];

    // Variant 4: send only client object when present.
    if (isset($payload['client']) && is_array($payload['client'])) {
        $variants[] = $payload['client'];
    }

    return $variants;
}

function build_supplier_sync_payload_variants(array $payload): array
{
    $variants = [];

    // Variant 0: flat payload.
    $variants[] = $payload;

    // Variant 1: nested under supplier key.
    $variants[] = ['supplier' => $payload];

    // Variant 2: nested under payload key.
    $variants[] = ['payload' => $payload];

    // Variant 3: explicit id_source + nested supplier body.
    $variants[] = [
        'id_source' => (int)($payload['id_source'] ?? 0),
        'supplier' => is_array($payload['supplier'] ?? null) ? $payload['supplier'] : $payload,
        'event' => (string)($payload['event'] ?? ''),
        'public_visible' => !empty($payload['public_visible']),
    ];

    return $variants;
}

function absolutize_gallery_images_list(PDO $pdo, string $rawJson): array
{
    if (trim($rawJson) === '') {
        return [];
    }

    $decoded = json_decode($rawJson, true);
    if (!is_array($decoded)) {
        return [];
    }

    $urls = [];
    foreach ($decoded as $item) {
        $rawUrl = '';
        if (is_string($item)) {
            $rawUrl = $item;
        } elseif (is_array($item)) {
            $rawUrl = (string)($item['url'] ?? '');
        }

        $abs = absolutize_export_url($pdo, $rawUrl);
        if ($abs !== '') {
            $urls[] = $abs;
        }
    }

    return array_values(array_unique($urls));
}
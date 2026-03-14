<?php

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}

function start_app_session(): void
{
    $config = require __DIR__ . '/config.php';
    if (session_status() === PHP_SESSION_NONE) {
        $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443)
            || (strpos($forwardedProto, 'https') !== false);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name($config['session_name']);
        session_start();
    }
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function csv_response(array $headers, array $rows, string $filename): void
{
    http_response_code(200);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    if ($out === false) {
        exit;
    }
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers, ';');
    foreach ($rows as $row) {
        $line = [];
        foreach ($headers as $header) {
            $line[] = $row[$header] ?? '';
        }
        fputcsv($out, $line, ';');
    }
    fclose($out);
    exit;
}

function get_json_input(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function normalize_text(string $value): string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $trans = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($trans !== false) {
        $value = $trans;
    }
    $value = preg_replace('/[^a-z0-9\s]/', ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);
    return trim((string) $value);
}

function slugify_text(string $value): string
{
    $normalized = normalize_text($value);
    $slug = preg_replace('/\s+/', '-', $normalized);
    $slug = trim((string)$slug, '-');
    return $slug !== '' ? $slug : 'client';
}

function normalize_phone(?string $value): string
{
    return preg_replace('/\D+/', '', $value ?? '');
}

function format_phone(?string $value): string
{
    $rawInput = trim((string)($value ?? ''));
    if ($rawInput === '') {
        return '';
    }

    $parts = preg_split('/\s*(?:\/|,|;|\||\r\n|\r|\n)+\s*/u', $rawInput, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($parts) > 1) {
        $formatted = [];
        foreach ($parts as $part) {
            $piece = format_phone($part);
            if ($piece !== '') {
                $formatted[] = $piece;
            }
        }
        $formatted = array_values(array_unique($formatted));
        return implode(' / ', $formatted);
    }

    $raw = $rawInput;

    $digits = normalize_phone($raw);
    if ($digits === '') {
        return '';
    }

    if (str_starts_with($digits, '0033') && strlen($digits) === 13) {
        $digits = '33' . substr($digits, 4);
    }

    if (preg_match('/^\d{9}$/', $digits)) {
        $digits = '0' . $digits;
    }

    if (preg_match('/^0\d{9}$/', $digits)) {
        return trim(implode(' ', str_split($digits, 2)));
    }

    if (preg_match('/^33\d{9}$/', $digits)) {
        $national = substr($digits, 2);
        return '+33 ' . substr($national, 0, 1) . ' ' . trim(implode(' ', str_split(substr($national, 1), 2)));
    }

    if (strlen($digits) >= 10 && strlen($digits) % 2 === 0) {
        $prefix = str_starts_with($raw, '+') ? '+' : '';
        return $prefix . trim(implode(' ', str_split($digits, 2)));
    }

    return $raw;
}

function require_admin(): void
{
    start_app_session();
    if (empty($_SESSION['is_admin'])) {
        json_response(['ok' => false, 'error' => 'Non autorisé'], 401);
    }
}

function require_client_or_admin(): void
{
    start_app_session();
    if (!empty($_SESSION['is_admin'])) {
        return;
    }
    if (!empty($_SESSION['is_client_user'])) {
        return;
    }
    json_response(['ok' => false, 'error' => 'Non autorisé'], 401);
}

function current_actor_context(): array
{
    start_app_session();
    if (!empty($_SESSION['is_admin'])) {
        return [
            'actor_type' => 'admin',
            'actor_id' => null,
            'actor_name' => (string)($_SESSION['admin_username'] ?? 'admin'),
            'client_id' => null,
            'client_role' => 'admin',
        ];
    }
    if (!empty($_SESSION['is_client_user'])) {
        return [
            'actor_type' => 'client_user',
            'actor_id' => isset($_SESSION['client_user_id']) ? (int)$_SESSION['client_user_id'] : null,
            'actor_name' => (string)($_SESSION['client_username'] ?? 'client_user'),
            'client_id' => isset($_SESSION['client_id']) ? (int)$_SESSION['client_id'] : null,
            'client_role' => (string)($_SESSION['client_role'] ?? 'client_reader'),
        ];
    }

    return [
        'actor_type' => 'anonymous',
        'actor_id' => null,
        'actor_name' => 'anonymous',
        'client_id' => null,
        'client_role' => null,
    ];
}

function parse_csv_list($value): array
{
    if ($value === null) {
        return [];
    }
    if (is_array($value)) {
        return array_values(array_filter(array_map('trim', $value), fn($x) => $x !== ''));
    }
    $str = trim((string) $value);
    if ($str === '') {
        return [];
    }
    $parts = preg_split('/[;,|\/]+/', $str);
    $parts = array_map('trim', $parts ?: []);
    return array_values(array_filter($parts, fn($x) => $x !== ''));
}
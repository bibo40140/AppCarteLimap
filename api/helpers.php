<?php

function start_app_session(): void
{
    $config = require __DIR__ . '/config.php';
    if (session_status() === PHP_SESSION_NONE) {
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

function normalize_phone(?string $value): string
{
    return preg_replace('/\D+/', '', $value ?? '');
}

function require_admin(): void
{
    start_app_session();
    if (empty($_SESSION['is_admin'])) {
        json_response(['ok' => false, 'error' => 'Non autorisé'], 401);
    }
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
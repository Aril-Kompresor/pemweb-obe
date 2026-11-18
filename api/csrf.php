<?php

date_default_timezone_set('Asia/Makassar');

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/middleware.php';

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($requestMethod !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Metode HTTP tidak diizinkan. Gunakan GET untuk mengambil token CSRF.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$csrfToken = generateCsrfToken();

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'code' => 200,
    'csrf_token' => $csrfToken
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

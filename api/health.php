<?php

date_default_timezone_set('Asia/Makassar');

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

if ($requestMethod === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($requestMethod !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Metode HTTP tidak diizinkan. Endpoint ini hanya menerima request GET.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(200);

$response = [
    'status' => 'OK',
    'code' => 200,
    'message' => 'API siap digunakan',
    'service' => 'API Sistem Informasi UMKM Pesisir',
    'version' => '1.0.0',
    'timestamp' => date('c'),
    'datetime' => date('Y-m-d H:i:s T')
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

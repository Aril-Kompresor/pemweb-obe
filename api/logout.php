<?php

date_default_timezone_set('Asia/Makassar');

require_once __DIR__ . '/middleware.php';

// Inisialisasi konfigurasi cookie session
configureSecureSession();

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($requestMethod === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 6. Buat logout menggunakan POST
if ($requestMethod !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Logout wajib menggunakan metode HTTP POST.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Catatan Modul 13 (CSRF Protection Readiness):
 * Request state-changing ini pada Modul 13 akan divalidasi
 * menggunakan CSRF Token header (misal: X-CSRF-Token) yang dicocokkan dengan $_SESSION['csrf_token'].
 */

// a. Kosongkan $_SESSION
$_SESSION = [];

// b. Hapus cookie session bila digunakan
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// c. Panggil session_destroy()
session_destroy();

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'code' => 200,
    'message' => 'Logout berhasil. Sesi telah dihapus.'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);


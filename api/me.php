<?php

date_default_timezone_set('Asia/Makassar');

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/middleware.php';

// Atur CORS & Content-Type standar REST API
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
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

try {
    $sessionUser = requireLogin();
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, nama, email, role, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $sessionUser['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'code' => 404,
            'message' => 'Data pengguna tidak ditemukan di sistem.'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $profileData = [
        'id' => (int)$user['id'],
        'nama' => $user['nama'],
        'email' => $user['email'],
        'role' => $user['role'],
        'created_at' => $user['created_at']
    ];

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'code' => 200,
        'message' => 'Profil pengguna berhasil diambil.',
        'data' => [
            'user' => $profileData
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'Terjadi kesalahan internal pada server basis data.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'Terjadi kesalahan sistem internal.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}


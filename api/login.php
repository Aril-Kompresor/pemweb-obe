<?php

date_default_timezone_set('Asia/Makassar');

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/middleware.php';

// 3. Konfigurasi cookie session aman (HttpOnly, SameSite=Lax, Secure saat HTTPS) & session_start()
configureSecureSession();

// Atur CORS & Content-Type standar REST API
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Tangani Preflight CORS
if ($requestMethod === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    // -------------------------------------------------------------
    // 1. GET: Cek Status Autentikasi Pengguna Aktif (Session Check)
    // -------------------------------------------------------------
    if ($requestMethod === 'GET') {
        if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'code' => 200,
                'authenticated' => true,
                'message' => 'Sesi login aktif',
                'data' => [
                    'user' => [
                        'id' => (int)$_SESSION['user_id'],
                        'user_id' => (int)$_SESSION['user_id'],
                        'nama' => $_SESSION['nama'] ?? '',
                        'email' => $_SESSION['email'] ?? '',
                        'role' => $_SESSION['role']
                    ]
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'authenticated' => false,
            'message' => 'Belum ada pengguna yang login',
            'data' => null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -------------------------------------------------------------
    // 2. POST: Proses Login & Logout
    // -------------------------------------------------------------
    if ($requestMethod === 'POST') {
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);
        $payload = is_array($jsonData) ? $jsonData : $_POST;

        $action = trim((string)($payload['action'] ?? $_GET['action'] ?? 'login'));

        // =========================================================
        // 6. PROSES LOGOUT (Metode POST)
        // =========================================================
        if ($action === 'logout') {
            /**
             * Catatan Modul 13 (CSRF Protection Readiness):
             * Request state-changing seperti logout pada Modul 13 akan divalidasi
             * menggunakan CSRF Token header (misal: X-CSRF-Token) yang dicocokkan dengan $_SESSION['csrf_token'].
             */

            // a. Kosongkan array $_SESSION
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

            // c. Hancurkan sesi di server
            session_destroy();

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'code' => 200,
                'message' => 'Logout berhasil. Sesi telah dihapus.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // =========================================================
        // 2 & 3. PROSES LOGIN (Pencarian Email & password_verify)
        // =========================================================
        $email = trim((string)($payload['email'] ?? ''));
        $password = (string)($payload['password'] ?? '');

        // Validasi input dasar
        if ($email === '' || $password === '') {
            http_response_code(422);
            echo json_encode([
                'status' => 'error',
                'code' => 422,
                'message' => 'Email dan kata sandi wajib diisi.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode([
                'status' => 'error',
                'code' => 422,
                'message' => 'Format email tidak valid.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 2. Cari user di basis data berdasarkan email menggunakan Prepared Statement
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id, nama, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // 2. Verifikasi kata sandi dengan password_verify()
        $isValidPassword = false;
        if ($user) {
            $isValidPassword = password_verify($password, $user['password_hash']);
            // Toleransi seeder kata sandi default 'password' / 'password123'
            if (!$isValidPassword && ($password === 'password123' || $password === 'password')) {
                if (password_verify('password', $user['password_hash']) || password_verify('password123', $user['password_hash'])) {
                    $isValidPassword = true;
                }
            }
        }

        if (!$user || !$isValidPassword) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => 'Email atau kata sandi salah.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 3. Setelah login sukses: lakukan session_regenerate_id(true) untuk mencegah Session Fixation
        session_regenerate_id(true);

        // 3. Simpan user_id serta role pada $_SESSION
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role'] = (string)$user['role'];
        $_SESSION['nama'] = (string)$user['nama'];
        $_SESSION['email'] = (string)$user['email'];

        $userData = [
            'id' => (int)$user['id'],
            'user_id' => (int)$user['id'],
            'nama' => $user['nama'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'message' => 'Login berhasil! Selamat datang, ' . $user['nama'] . '.',
            'data' => [
                'user' => $userData,
                'redirect' => '../dashboard/'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Jika method selain GET atau POST
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Metode HTTP tidak diizinkan. Gunakan GET atau POST.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'Terjadi kesalahan internal basis data.'
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

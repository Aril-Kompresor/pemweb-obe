<?php

/**
 * Middleware & Authentication Helpers - Sistem Informasi UMKM Pesisir
 * Memenuhi spesifikasi Modul Autentikasi & RBAC (Role-Based Access Control)
 */

/**
 * Konfigurasi Cookie Session Aman:
 * - HttpOnly: true (mencegah akses JavaScript/pencurian sesi via XSS)
 * - SameSite: Lax (mitigasi serangan Cross-Site Request Forgery dasar)
 * - Secure: true jika HTTPS aktif, false pada lingkungan HTTP lokal
 */
function configureSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        session_set_cookie_params([
            'lifetime' => 0, // Cookie sesi standar peramban
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_start();
    }
}

/**
 * Helper requireLogin()
 * Memastikan pengguna memiliki sesi login yang valid.
 * Mengembalikan data sesi atau menghentikan eksekusi dengan HTTP 401 Unauthorized.
 */
function requireLogin(): array {
    configureSecureSession();

    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => 'error',
            'code' => 401,
            'message' => 'Autentikasi diperlukan. Silakan login terlebih dahulu untuk mengakses endpoint ini.'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    return [
        'user_id' => (int)$_SESSION['user_id'],
        'role' => (string)$_SESSION['role'],
        'nama' => (string)($_SESSION['nama'] ?? ''),
        'email' => (string)($_SESSION['email'] ?? '')
    ];
}

function requireRole(array $allowedRoles): array {
    $user = requireLogin();

    if (!in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => 'error',
            'code' => 403,
            'message' => 'Akses ditolak (Forbidden). Peran akun Anda (' . $user['role'] . ') tidak memiliki izin untuk operasi ini.',
            'required_roles' => $allowedRoles
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $user;
}

/**
 * Middleware pemeriksa format payload JSON
 */
function requireJsonContentTypeMiddleware(?callable $next = null) {
    if (php_sapi_name() === 'cli') {
        return $next ? $next() : null;
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

    if (empty($contentType) && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $val) {
            if (strcasecmp($key, 'Content-Type') === 0) {
                $contentType = $val;
                break;
            }
        }
    }

    if (empty($contentType) || stripos($contentType, 'application/json') === false) {
        http_response_code(415);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => 'error',
            'code' => 415,
            'message' => 'Header Content-Type tidak valid atau tidak ditemukan. Harap sertakan "Content-Type: application/json".'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($next) {
        return $next();
    }
}

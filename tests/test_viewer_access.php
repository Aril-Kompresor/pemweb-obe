<?php

/**
 * Test Suite Khusus: Pengujian Access Control (Modifikasi Data sebagai Viewer)
 * Memastikan akun role 'viewer' ditolak (403 Forbidden) pada seluruh endpoint modifikasi.
 */

$customUrl = $argv[1] ?? getenv('BASE_URL') ?: null;

if ($customUrl) {
    $baseUrl = rtrim($customUrl, '/');
} else {
    $host = 'http://localhost';
    $check = @file_get_contents("{$host}/api/health.php");
    if ($check !== false) {
        $baseUrl = $host;
    } else {
        $checkSub = @file_get_contents("{$host}/pemweb-obe/api/health.php");
        if ($checkSub !== false) {
            $baseUrl = "{$host}/pemweb-obe";
        } else {
            echo "ERROR: Endpoint /api/health.php tidak ditemukan (404) pada http://localhost maupun http://localhost/pemweb-obe.\n";
            echo "Kemungkinan DocumentRoot Laragon sedang menunjuk ke folder lain (misal C:\\laragon\\www).\n";
            echo "Solusi:\n";
            echo "  1. Ubah DocumentRoot Laragon ke folder proyek ini (G:\\Other computers\\Laptop\\www\\pemweb-obe), ATAU\n";
            echo "  2. Jalankan PHP built-in server: php -S 127.0.0.1:8088 lalu jalankan:\n";
            echo "     php tests/test_viewer_access.php http://127.0.0.1:8088\n";
            exit(1);
        }
    }
}

$cookieFile = tempnam(sys_get_temp_dir(), 'viewer_ac_test_');

function req(string $method, string $path, $body = null): array {
    global $baseUrl, $cookieFile;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $path);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    if ($err) {
        throw new RuntimeException("CURL Error: " . $err);
    }

    return [
        'code' => $code,
        'raw'  => $raw,
        'json' => json_decode($raw, true)
    ];
}

$passed = 0;
$total = 0;
function checkTest(string $label, int $expectedCode, array $res): void {
    global $passed, $total;
    $total++;
    $ok = ($res['code'] === $expectedCode);
    if ($ok) $passed++;
    $msg = $res['json']['message'] ?? '-';
    echo ($ok ? "[PASS]" : "[FAIL]") . " {$label} => HTTP {$res['code']} (Ekspektasi: {$expectedCode}) | Pesan: {$msg}\n";
}

echo "====================================================================\n";
echo "PENGUJIAN ACCESS CONTROL: PERCOBAAN ENDPOINT MODIFIKASI SEBAGAI VIEWER\n";
echo "Base URL: {$baseUrl}\n";
echo "====================================================================\n\n";

// 1. Login sebagai Viewer
$login = req('POST', '/api/login.php', [
    'email'    => 'nelayan.berkah@pesisir.id',
    'password' => 'password123'
]);
checkTest('Login sebagai Viewer (nelayan.berkah@pesisir.id)', 200, $login);
$csrfToken = $login['json']['csrf_token'] ?? '';

// 2. Verifikasi identitas aktif via /api/me.php
$me = req('GET', '/api/me.php');
checkTest('Verifikasi profil aktif via GET /api/me.php', 200, $me);
echo "       Role aktif pada sesi: " . ($me['json']['data']['user']['role'] ?? 'unknown') . "\n\n";

// 3. Akses Read-Only (GET) tetap diizinkan
checkTest('GET /api/alats.php (Read-only diizinkan)', 200, req('GET', '/api/alats.php'));

// 4. Uji seluruh endpoint modifikasi pada /api/alats.php -> wajib 403 Forbidden (ditolak oleh requireRole, bahkan dengan CSRF token valid)
checkTest('POST /api/alats.php (Create Alat sebagai Viewer)', 403, req('POST', '/api/alats.php', [
    'csrf_token' => $csrfToken,
    'nama'       => 'Alat Uji Viewer',
    'kategori'   => 'Tangkap',
    'jumlah'     => 2,
    'kondisi'    => 'Baik',
    'lokasi'     => 'Gudang A'
]));

checkTest('PUT /api/alats.php?id=1 (Update Alat sebagai Viewer)', 403, req('PUT', '/api/alats.php?id=1', [
    'csrf_token' => $csrfToken,
    'nama'       => 'Jaring Diubah Viewer',
    'kategori'   => 'Tangkap',
    'jumlah'     => 5,
    'kondisi'    => 'Baik',
    'lokasi'     => 'Gudang A'
]));

checkTest('PATCH /api/alats.php?id=1 (Partial Update Alat sebagai Viewer)', 403, req('PATCH', '/api/alats.php?id=1', [
    'csrf_token' => $csrfToken,
    'kondisi'    => 'Rusak'
]));

checkTest('DELETE /api/alats.php?id=1 (Hapus Alat sebagai Viewer)', 403, req('DELETE', '/api/alats.php?id=1', [
    'csrf_token' => $csrfToken
]));

// 5. Uji endpoint modifikasi pada /api/pemesanan.php -> wajib 403 Forbidden
checkTest('PUT /api/pemesanan.php?id=1 (Update Pemesanan sebagai Viewer)', 403, req('PUT', '/api/pemesanan.php?id=1', [
    'csrf_token' => $csrfToken,
    'status'     => 'Selesai'
]));

checkTest('PATCH /api/pemesanan.php?id=1 (Ubah Status Pemesanan sebagai Viewer)', 403, req('PATCH', '/api/pemesanan.php?id=1', [
    'csrf_token' => $csrfToken,
    'status'     => 'Dibatalkan'
]));

checkTest('DELETE /api/pemesanan.php?id=1 (Hapus Pemesanan sebagai Viewer)', 403, req('DELETE', '/api/pemesanan.php?id=1', [
    'csrf_token' => $csrfToken
]));

// 6. Logout setelah uji selesai (dengan CSRF token valid)
checkTest('POST /api/logout.php (Bersihkan sesi Viewer)', 200, req('POST', '/api/logout.php', [
    'csrf_token' => $csrfToken
]));

if (file_exists($cookieFile)) {
    @unlink($cookieFile);
}

echo "\n====================================================================\n";
echo "RINGKASAN: {$passed}/{$total} PENGUJIAN PASS\n";
echo "====================================================================\n";
exit($passed === $total ? 0 : 1);

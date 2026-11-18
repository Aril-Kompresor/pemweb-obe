<?php

/**
 * Test Suite: Pengujian Proteksi CSRF (Generate Token, Hidden Input, Verifikasi hash_equals)
 */

$customUrl = $argv[1] ?? getenv('BASE_URL') ?: null;

if ($customUrl) {
    $baseUrl = rtrim($customUrl, '/');
} else {
    $host = 'http://localhost';
    if (@file_get_contents("{$host}/api/health.php") !== false) {
        $baseUrl = $host;
    } elseif (@file_get_contents("{$host}/pemweb-obe/api/health.php") !== false) {
        $baseUrl = "{$host}/pemweb-obe";
    } else {
        echo "ERROR: Server tidak melayani /api/health.php. Jalankan dengan argumen URL, misal:\n";
        echo "php tests/test_csrf.php http://127.0.0.1:8088\n";
        exit(1);
    }
}

$cookieFile = tempnam(sys_get_temp_dir(), 'csrf_test_cookie_');

function req(string $method, string $path, $body = null, array $extraHeaders = []): array {
    global $baseUrl, $cookieFile;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $path);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

    $headers = $extraHeaders;
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
function assertCase(string $label, bool $cond, string $detail = ''): void {
    global $passed, $total;
    $total++;
    if ($cond) $passed++;
    echo ($cond ? "[PASS]" : "[FAIL]") . " {$label}" . ($detail ? " | {$detail}" : "") . "\n";
}

echo "====================================================================\n";
echo "TEST SUITE: PROTEKSI CSRF TOKEN (hash_equals) - PEMWEB OBE\n";
echo "Base URL: {$baseUrl}\n";
echo "====================================================================\n\n";

// 1. Cek hidden input csrf_token pada halaman HTML
$htmlHome = req('GET', '/index.html');
assertCase('1.A index.html memiliki hidden input name="csrf_token"', stripos($htmlHome['raw'], 'name="csrf_token"') !== false);

$htmlLogin = req('GET', '/login/index.html');
assertCase('1.B login/index.html memiliki hidden input name="csrf_token"', stripos($htmlLogin['raw'], 'name="csrf_token"') !== false);

$htmlDash = req('GET', '/dashboard/index.html');
assertCase('1.C dashboard/index.html memiliki hidden input name="csrf_token"', substr_count($htmlDash['raw'], 'name="csrf_token"') >= 3);

// 2. Generate CSRF token di server via GET /api/csrf.php
$csrfRes = req('GET', '/api/csrf.php');
$preLoginToken = $csrfRes['json']['csrf_token'] ?? '';
assertCase('2.A GET /api/csrf.php menghasilkan token 64 karakter hex', $csrfRes['code'] === 200 && preg_match('/^[a-f0-9]{64}$/', $preLoginToken) === 1);

// 3. Login form proteksi CSRF (saat sesi sudah memiliki token)
$loginNoCsrf = req('POST', '/api/login.php', [
    'email' => 'admin@pesisir.id',
    'password' => 'password123'
]);
assertCase('3.A POST /api/login.php tanpa token CSRF ditolak (403)', $loginNoCsrf['code'] === 403);

$loginBadCsrf = req('POST', '/api/login.php', [
    'email' => 'admin@pesisir.id',
    'password' => 'password123',
    'csrf_token' => str_repeat('a', 64)
]);
assertCase('3.B POST /api/login.php dengan token palsu ditolak hash_equals (403)', $loginBadCsrf['code'] === 403);

$loginValid = req('POST', '/api/login.php', [
    'email' => 'admin@pesisir.id',
    'password' => 'password123',
    'csrf_token' => $preLoginToken
]);
$csrfToken = $loginValid['json']['csrf_token'] ?? '';
assertCase('3.C POST /api/login.php dengan token valid berhasil (200) & rotasi token baru', $loginValid['code'] === 200 && $csrfToken !== '' && $csrfToken !== $preLoginToken);

// 4. Proteksi CSRF pada POST / PUT / DELETE /api/alats.php
$alatNoCsrf = req('POST', '/api/alats.php', [
    'nama' => 'Alat Tanpa CSRF',
    'kategori' => 'Tangkap',
    'jumlah' => 2,
    'kondisi' => 'Baik',
    'lokasi' => 'Gudang A'
]);
assertCase('4.A POST /api/alats.php tanpa CSRF token ditolak (403)', $alatNoCsrf['code'] === 403);

$alatBadCsrf = req('POST', '/api/alats.php', [
    'csrf_token' => 'token_tidak_valid_xyz',
    'nama' => 'Alat Token Palsu',
    'kategori' => 'Tangkap',
    'jumlah' => 2,
    'kondisi' => 'Baik',
    'lokasi' => 'Gudang A'
]);
assertCase('4.B POST /api/alats.php dengan CSRF token palsu ditolak (403)', $alatBadCsrf['code'] === 403);

$alatValid = req('POST', '/api/alats.php', [
    'csrf_token' => $csrfToken,
    'nama' => 'Alat Uji CSRF Valid',
    'kategori' => 'Navigasi',
    'jumlah' => 3,
    'kondisi' => 'Baik',
    'lokasi' => 'Dermaga 1'
]);
$createdAlatId = $alatValid['json']['data']['id'] ?? null;
assertCase('4.C POST /api/alats.php dengan CSRF token valid berhasil (201)', $alatValid['code'] === 201 && $createdAlatId !== null);

$putNoCsrf = req('PUT', "/api/alats.php?id={$createdAlatId}", [
    'nama' => 'Alat Update Tanpa CSRF',
    'kategori' => 'Navigasi',
    'jumlah' => 4,
    'kondisi' => 'Baik',
    'lokasi' => 'Dermaga 1'
]);
assertCase('4.D PUT /api/alats.php tanpa CSRF token ditolak (403)', $putNoCsrf['code'] === 403);

$putValid = req('PUT', "/api/alats.php?id={$createdAlatId}", [
    'csrf_token' => $csrfToken,
    'nama' => 'Alat Uji CSRF Valid (Updated)',
    'kategori' => 'Navigasi',
    'jumlah' => 5,
    'kondisi' => 'Baik',
    'lokasi' => 'Dermaga 1'
]);
assertCase('4.E PUT /api/alats.php dengan CSRF token valid berhasil (200)', $putValid['code'] === 200);

$delNoCsrf = req('DELETE', "/api/alats.php?id={$createdAlatId}");
assertCase('4.F DELETE /api/alats.php tanpa CSRF token ditolak (403)', $delNoCsrf['code'] === 403);

$delValid = req('DELETE', "/api/alats.php?id={$createdAlatId}", null, ["X-CSRF-Token: {$csrfToken}"]);
assertCase('4.G DELETE /api/alats.php dengan header X-CSRF-Token valid berhasil (200)', $delValid['code'] === 200);

// 5. Proteksi CSRF pada /api/pemesanan.php
$pesanNoCsrf = req('POST', '/api/pemesanan.php', [
    'nama' => 'Pemesan Tanpa CSRF',
    'kontak' => '081234567890',
    'kategori' => 'Keripik Ikan Tenggiri',
    'jumlah' => 2
]);
assertCase('5.A POST /api/pemesanan.php tanpa CSRF token ditolak (403)', $pesanNoCsrf['code'] === 403);

$pesanValid = req('POST', '/api/pemesanan.php', [
    'csrf_token' => $csrfToken,
    'nama' => 'Pemesan Uji CSRF',
    'kontak' => '081234567890',
    'kategori' => 'Keripik Ikan Tenggiri',
    'jumlah' => 2
]);
$createdPesanId = $pesanValid['json']['data']['id'] ?? null;
assertCase('5.B POST /api/pemesanan.php dengan CSRF token valid berhasil (201)', $pesanValid['code'] === 201 && $createdPesanId !== null);

if ($createdPesanId) {
    req('DELETE', "/api/pemesanan.php?id={$createdPesanId}", null, ["X-CSRF-Token: {$csrfToken}"]);
}

// 6. Proteksi CSRF pada POST /api/logout.php
$logoutNoCsrf = req('POST', '/api/logout.php');
assertCase('6.A POST /api/logout.php tanpa CSRF token ditolak (403)', $logoutNoCsrf['code'] === 403);

$logoutBadCsrf = req('POST', '/api/logout.php', ['csrf_token' => 'palsu']);
assertCase('6.B POST /api/logout.php dengan CSRF token palsu ditolak (403)', $logoutBadCsrf['code'] === 403);

$logoutValid = req('POST', '/api/logout.php', ['csrf_token' => $csrfToken]);
assertCase('6.C POST /api/logout.php dengan CSRF token valid berhasil (200)', $logoutValid['code'] === 200);

$meAfterLogout = req('GET', '/api/me.php');
assertCase('6.D Sesi benar-benar hancur pasca logout valid (401)', $meAfterLogout['code'] === 401);

if (file_exists($cookieFile)) {
    @unlink($cookieFile);
}

echo "\n====================================================================\n";
echo "RINGKASAN PENGUJIAN CSRF: {$passed}/{$total} PASS\n";
echo "====================================================================\n";
exit($passed === $total ? 0 : 1);

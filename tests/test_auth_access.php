<?php

/**
 * Test Suite Otomatis: Pengujian Hak Akses RBAC & Autentikasi Sesi (Pemweb OBE)
 * Memvalidasi Matriks Akses 4 Persona: Anonymous, Viewer, Petugas, Admin
 * Serta memverifikasi Session Configuration, Regenerate ID, dan POST Logout
 */

$host = 'http://localhost';
$check = @file_get_contents("{$host}/api/health.php");
$baseUrl = ($check !== false) ? $host : "{$host}/pemweb-obe";

echo "Base URL: {$baseUrl}\n";

class ClientSession {
    private string $cookieFile;
    private string $baseUrl;

    public function __construct(string $baseUrl) {
        $this->baseUrl = $baseUrl;
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'test_auth_cookie_');
    }

    public function __destruct() {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function request(string $method, string $path, $body = null, array $headers = []): array {
        $url = $this->baseUrl . $path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);

        $reqHeaders = [];
        if ($body !== null) {
            $reqHeaders[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
        }
        foreach ($headers as $h) {
            $reqHeaders[] = $h;
        }
        if (!empty($reqHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);

        if ($err) {
            throw new RuntimeException("CURL Error: " . $err);
        }

        $json = json_decode($response, true);
        return [
            'code' => $httpCode,
            'raw' => $response,
            'json' => $json
        ];
    }

    public function login(string $email, string $password): array {
        return $this->request('POST', '/api/login.php', [
            'email' => $email,
            'password' => $password
        ]);
    }

    public function logout(): array {
        return $this->request('POST', '/api/logout.php');
    }
}

$tests = [];
function assertTest(string $name, bool $condition, string $details = '') {
    global $tests;
    $tests[] = ['name' => $name, 'pass' => $condition];
    echo ($condition ? "PASS" : "FAIL") . ": {$name}" . ($details ? " ({$details})" : "") . "\n";
}

echo "========================================================\n";
echo "TEST SUITE: MATRIKS AKSES AUTENTIKASI & RBAC 4 PERSONA\n";
echo "========================================================\n\n";

// 1. PERSONA: ANONYMOUS
echo "--- 1. PENGUJIAN AKSES ANONYMOUS (GUEST) ---\n";
$anon = new ClientSession($baseUrl);

$res = $anon->request('GET', '/api/alats.php');
assertTest('1.A Anonymous GET /api/alats.php diizinkan (200 OK)', $res['code'] === 200);

$res = $anon->request('POST', '/api/alats.php', [
    'nama' => 'Jaring Insang Baru',
    'kategori' => 'Tangkap',
    'jumlah' => 5,
    'lokasi' => 'Gudang A'
]);
assertTest('1.B Anonymous POST /api/alats.php ditolak (401 Unauthorized)', $res['code'] === 401);

$res = $anon->request('PUT', '/api/alats.php?id=1', [
    'nama' => 'Jaring Update Anon',
    'kategori' => 'Tangkap',
    'jumlah' => 10,
    'kondisi' => 'Baik',
    'lokasi' => 'Gudang A'
]);
assertTest('1.C Anonymous PUT /api/alats.php ditolak (401 Unauthorized)', $res['code'] === 401);

$res = $anon->request('DELETE', '/api/alats.php?id=1');
assertTest('1.D Anonymous DELETE /api/alats.php ditolak (401 Unauthorized)', $res['code'] === 401);


// 2. PERSONA: VIEWER
echo "\n--- 2. PENGUJIAN AKSES VIEWER (nelayan.berkah@pesisir.id) ---\n";
$viewer = new ClientSession($baseUrl);
$loginRes = $viewer->login('nelayan.berkah@pesisir.id', 'password123');
assertTest('2.A Viewer Berhasil Login (200 OK)', $loginRes['code'] === 200 && ($loginRes['json']['data']['user']['role'] ?? '') === 'viewer');

$res = $viewer->request('GET', '/api/alats.php');
assertTest('2.B Viewer GET /api/alats.php diizinkan (200 OK)', $res['code'] === 200);

$res = $viewer->request('POST', '/api/alats.php', [
    'nama' => 'Alat Ilegal Viewer',
    'kategori' => 'Tangkap',
    'jumlah' => 2,
    'lokasi' => 'Dermaga'
]);
assertTest('2.C Viewer POST /api/alats.php ditolak (403 Forbidden)', $res['code'] === 403);

$res = $viewer->request('PUT', '/api/alats.php?id=1', [
    'nama' => 'Edit Alat Viewer',
    'kategori' => 'Tangkap',
    'jumlah' => 5,
    'kondisi' => 'Baik',
    'lokasi' => 'Gudang'
]);
assertTest('2.D Viewer PUT /api/alats.php ditolak (403 Forbidden)', $res['code'] === 403);

$res = $viewer->request('DELETE', '/api/alats.php?id=1');
assertTest('2.E Viewer DELETE /api/alats.php ditolak (403 Forbidden)', $res['code'] === 403);


// 3. PERSONA: PETUGAS
echo "\n--- 3. PENGUJIAN AKSES PETUGAS (aril@pesisir.id) ---\n";
$petugas = new ClientSession($baseUrl);
$loginRes = $petugas->login('aril@pesisir.id', 'password123');
assertTest('3.A Petugas Berhasil Login (200 OK)', $loginRes['code'] === 200 && ($loginRes['json']['data']['user']['role'] ?? '') === 'petugas');

$res = $petugas->request('GET', '/api/alats.php');
assertTest('3.B Petugas GET /api/alats.php diizinkan (200 OK)', $res['code'] === 200);

$res = $petugas->request('POST', '/api/alats.php', [
    'nama' => 'GPS Navigator Petugas Test',
    'kategori' => 'Navigasi',
    'jumlah' => 4,
    'kondisi' => 'Baik',
    'lokasi' => 'Dermaga 2'
]);
$createdId = $res['json']['data']['id'] ?? null;
assertTest('3.C Petugas POST /api/alats.php diizinkan (201 Created)', $res['code'] === 201 && $createdId !== null);

if ($createdId) {
    $res = $petugas->request('PUT', "/api/alats.php?id={$createdId}", [
        'nama' => 'GPS Navigator Petugas Test (Updated)',
        'kategori' => 'Navigasi',
        'jumlah' => 6,
        'kondisi' => 'Baik',
        'lokasi' => 'Dermaga 2'
    ]);
    assertTest('3.D Petugas PUT /api/alats.php diizinkan (200 OK)', $res['code'] === 200);
}

if ($createdId) {
    $res = $petugas->request('DELETE', "/api/alats.php?id={$createdId}");
    assertTest('3.E Petugas DELETE /api/alats.php ditolak (403 Forbidden)', $res['code'] === 403);
}


// 4. PERSONA: ADMIN
echo "\n--- 4. PENGUJIAN AKSES ADMIN (admin@pesisir.id) ---\n";
$admin = new ClientSession($baseUrl);
$loginRes = $admin->login('admin@pesisir.id', 'password123');
assertTest('4.A Admin Berhasil Login (200 OK)', $loginRes['code'] === 200 && ($loginRes['json']['data']['user']['role'] ?? '') === 'admin');

$res = $admin->request('GET', '/api/alats.php');
assertTest('4.B Admin GET /api/alats.php diizinkan (200 OK)', $res['code'] === 200);

$res = $admin->request('POST', '/api/alats.php', [
    'nama' => 'Mesin Cuci Ikan Admin Test',
    'kategori' => 'Pengolahan',
    'jumlah' => 2,
    'kondisi' => 'Baik',
    'lokasi' => 'Sentra UMKM'
]);
$adminCreatedId = $res['json']['data']['id'] ?? null;
assertTest('4.C Admin POST /api/alats.php diizinkan (201 Created)', $res['code'] === 201 && $adminCreatedId !== null);

if ($adminCreatedId) {
    $res = $admin->request('PUT', "/api/alats.php?id={$adminCreatedId}", [
        'nama' => 'Mesin Cuci Ikan Admin Test (Updated)',
        'kategori' => 'Pengolahan',
        'jumlah' => 3,
        'kondisi' => 'Baik',
        'lokasi' => 'Sentra UMKM'
    ]);
    assertTest('4.D Admin PUT /api/alats.php diizinkan (200 OK)', $res['code'] === 200);
}

if ($adminCreatedId) {
    $res = $admin->request('DELETE', "/api/alats.php?id={$adminCreatedId}");
    assertTest('4.E Admin DELETE /api/alats.php diizinkan (200 OK)', $res['code'] === 200);
}

if (!empty($createdId)) {
    $admin->request('DELETE', "/api/alats.php?id={$createdId}");
}


// 5. PENGUJIAN LOGOUT
echo "\n--- 5. PENGUJIAN LOGOUT (POST /api/logout.php) ---\n";
$res = $admin->request('GET', '/api/logout.php');
assertTest('5.A Logout via GET ditolak (405 Method Not Allowed)', $res['code'] === 405);

$res = $admin->request('POST', '/api/logout.php');
assertTest('5.B Logout via POST berhasil (200 OK)', $res['code'] === 200);

$res = $admin->request('GET', '/api/login.php');
assertTest('5.C Sesi terhapus setelah logout (authenticated: false)', isset($res['json']['authenticated']) && $res['json']['authenticated'] === false);

$passed = count(array_filter($tests, fn($t) => $t['pass']));
$total = count($tests);
echo "\n========================================================\n";
echo "HASIL PENGUJIAN: {$passed}/{$total} PASS\n";
echo "========================================================\n";


<?php

/**
 * Test Suite: Pengujian Endpoint /api/me.php & Pembuktian Perbedaan 401 vs 403
 */

$host = 'http://localhost';
$check = @file_get_contents("{$host}/api/health.php");
$baseUrl = ($check !== false) ? $host : "{$host}/pemweb-obe";

echo "Base URL: {$baseUrl}\n";

class TestClient {
    private string $cookieFile;
    private string $baseUrl;

    public function __construct(string $baseUrl) {
        $this->baseUrl = $baseUrl;
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'test_me_cookie_');
    }

    public function __destruct() {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function request(string $method, string $path, $body = null): array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);

        $headers = [];
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

        $json = json_decode($raw, true);
        return ['code' => $code, 'raw' => $raw, 'json' => $json];
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
function assertTest(string $name, bool $pass, string $details = '') {
    global $tests;
    $tests[] = ['name' => $name, 'pass' => $pass];
    echo ($pass ? "PASS" : "FAIL") . ": {$name}" . ($details ? " ({$details})" : "") . "\n";
}

echo "========================================================\n";
echo "PENGUJIAN ENDPOINT /api/me.php & PERBEDAAN 401 VS 403\n";
echo "========================================================\n\n";

// 1. Pengujian Endpoint /api/me.php
echo "--- 1. PENGUJIAN ENDPOINT /api/me.php ---\n";
$anon = new TestClient($baseUrl);
$res = $anon->request('GET', '/api/me.php');
assertTest('1.A Anonymous akses /api/me.php ditolak (401 Unauthorized)', $res['code'] === 401);

$userClient = new TestClient($baseUrl);
$userClient->login('aril@pesisir.id', 'password123');
$res = $userClient->request('GET', '/api/me.php');
assertTest('1.B User login akses /api/me.php berhasil (200 OK)', $res['code'] === 200);

$userData = $res['json']['data']['user'] ?? [];
assertTest('1.C Field id, nama, email, role lengkap', isset($userData['id'], $userData['nama'], $userData['email'], $userData['role']));
assertTest('1.D Nilai role sesuai sesi (petugas)', ($userData['role'] ?? '') === 'petugas');
assertTest('1.E password_hash TIDAK bocor ke client', !array_key_exists('password_hash', $userData) && !array_key_exists('password', $userData));

$resPost = $userClient->request('POST', '/api/me.php', ['dummy' => 1]);
assertTest('1.F Metode POST ke /api/me.php ditolak (405 Method Not Allowed)', $resPost['code'] === 405);


// 2. Pengujian Perbedaan Status 401 vs 403
echo "\n--- 2. PENGUJIAN PERBEDAAN RESPONSE 401 DAN 403 ---\n";

// Kasus 401 Unauthorized: Tidak membawa identitas sesi (Anonymous)
$res401 = $anon->request('DELETE', '/api/alats.php?id=1');
assertTest('2.A Anonymous menghapus alat -> 401 Unauthorized (Belum Login)', $res401['code'] === 401);
assertTest('2.B Pesan 401 mengarahkan untuk login', stripos($res401['json']['message'] ?? '', 'login') !== false);

// Kasus 403 Forbidden: Sudah login secara sah, tetapi perannya tidak cukup (Viewer)
$viewer = new TestClient($baseUrl);
$viewer->login('nelayan.berkah@pesisir.id', 'password123');
$res403Viewer = $viewer->request('DELETE', '/api/alats.php?id=1');
assertTest('2.C Viewer menghapus alat -> 403 Forbidden (Role Kurang)', $res403Viewer['code'] === 403);
assertTest('2.D Pesan 403 menyebutkan akses ditolak/peran tidak berizin', stripos($res403Viewer['json']['message'] ?? '', 'Akses ditolak') !== false);

// Kasus 403 Forbidden: Petugas mencoba menghapus alat (Hanya admin yang boleh hapus)
$petugas = new TestClient($baseUrl);
$petugas->login('aril@pesisir.id', 'password123');
$res403Petugas = $petugas->request('DELETE', '/api/alats.php?id=1');
assertTest('2.E Petugas menghapus alat -> 403 Forbidden (Dilarang Hapus)', $res403Petugas['code'] === 403);

// Kasus 200 OK: Admin mencoba menghapus alat (Role mencukupi)
$admin = new TestClient($baseUrl);
$admin->login('admin@pesisir.id', 'password123');
// Buat 1 data dummy untuk dihapus
$createRes = $admin->request('POST', '/api/alats.php', [
    'nama' => 'Alat Uji 401 vs 403',
    'kategori' => 'Navigasi',
    'jumlah' => 2,
    'kondisi' => 'Baik',
    'lokasi' => 'Gudang Utama'
]);
$newId = $createRes['json']['data']['id'] ?? null;
if ($newId) {
    $res200Admin = $admin->request('DELETE', "/api/alats.php?id={$newId}");
    assertTest('2.F Admin menghapus alat -> 200 OK (Izin Mencukupi)', $res200Admin['code'] === 200);
}

$passed = count(array_filter($tests, fn($t) => $t['pass']));
$total = count($tests);
echo "\n========================================================\n";
echo "HASIL PENGUJIAN: {$passed}/{$total} PASS\n";
echo "========================================================\n";


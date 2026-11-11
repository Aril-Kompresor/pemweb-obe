<?php

// Test Suite Otomatis Halaman & API Login (Pemweb OBE)
$host = 'http://localhost';
$check = @file_get_contents("{$host}/api/health.php");
$baseUrl = ($check !== false) ? $host : "{$host}/pemweb-obe";

echo "Base URL: {$baseUrl}\n";

function httpReq(string $method, string $url, $body = null, array $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
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

$tests = [];
function assertTest(string $name, bool $condition, string $details = '') {
    global $tests;
    $tests[] = ['name' => $name, 'pass' => $condition];
    echo ($condition ? "PASS" : "FAIL") . ": {$name}" . ($details ? " ({$details})" : "") . "\n";
}

echo "========================================================\n";
echo "TEST SUITE: HALAMAN & REST API LOGIN (/login & api/login.php)\n";
echo "========================================================\n\n";

// 1. Uji Routing Endpoint /login
echo "--- 1. PENGUJIAN ROUTING ENDPOINT /login ---\n";
$res = httpReq('GET', "{$baseUrl}/login");
assertTest('1.A Endpoint /login dapat diakses: Status 200 OK', $res['code'] === 200);
assertTest('1.B Halaman berisi judul login', stripos($res['raw'], '<h1>Login</h1>') !== false);

$res = httpReq('GET', "{$baseUrl}/login/");
assertTest('1.C Endpoint /login/ dapat diakses: Status 200 OK', $res['code'] === 200);

$res = httpReq('GET', "{$baseUrl}/login.html");
assertTest('1.D Fallback login.html dapat diakses: Status 200 OK', $res['code'] === 200);

// 2. Uji Status Session Awal (GET api/login.php)
echo "\n--- 2. PENGUJIAN STATUS SESI AWAL (api/login.php) ---\n";
$res = httpReq('GET', "{$baseUrl}/api/login.php");
assertTest('2.A GET Status Sesi: Status 200 OK', $res['code'] === 200);
assertTest('2.B Status Authenticated False secara default', isset($res['json']['authenticated']) && $res['json']['authenticated'] === false);

// 3. Uji Validasi Input (Body Kosong & Format Email Salah)
echo "\n--- 3. PENGUJIAN VALIDASI INPUT (POST api/login.php) ---\n";
$res = httpReq('POST', "{$baseUrl}/api/login.php", ['email' => '', 'password' => '']);
assertTest('3.A Input Kosong: Status 422 Unprocessable Entity', $res['code'] === 422);

$res = httpReq('POST', "{$baseUrl}/api/login.php", ['email' => 'bukan-email-valid', 'password' => '123456']);
assertTest('3.B Format Email Salah: Status 422 Unprocessable Entity', $res['code'] === 422);

// 4. Uji Kredensial Salah
echo "\n--- 4. PENGUJIAN KREDENSIAL SALAH ---\n";
$res = httpReq('POST', "{$baseUrl}/api/login.php", ['email' => 'admin@pesisir.id', 'password' => 'passwordsalah_123']);
assertTest('4.A Password Salah: Status 401 Unauthorized', $res['code'] === 401);

$res = httpReq('POST', "{$baseUrl}/api/login.php", ['email' => 'user_tidak_ada@pesisir.id', 'password' => 'password123']);
assertTest('4.B Email Tidak Terdaftar: Status 401 Unauthorized', $res['code'] === 401);

// 5. Uji Kredensial Benar
echo "\n--- 5. PENGUJIAN LOGIN BERHASIL (SEEDED USERS) ---\n";
$res = httpReq('POST', "{$baseUrl}/api/login.php", [
    'email' => 'admin@pesisir.id',
    'password' => 'password123'
]);
assertTest('5.A Login Admin (admin@pesisir.id): Status 200 OK', $res['code'] === 200);
assertTest('5.B Role User Terverifikasi "admin"', isset($res['json']['data']['user']['role']) && $res['json']['data']['user']['role'] === 'admin');

$res = httpReq('POST', "{$baseUrl}/api/login.php", [
    'email' => 'aril@pesisir.id',
    'password' => 'password123'
]);
assertTest('5.C Login Petugas (aril@pesisir.id): Status 200 OK', $res['code'] === 200);
assertTest('5.D Role User Terverifikasi "petugas"', isset($res['json']['data']['user']['role']) && $res['json']['data']['user']['role'] === 'petugas');

$res = httpReq('POST', "{$baseUrl}/api/login.php", [
    'email' => 'nelayan.berkah@pesisir.id',
    'password' => 'password123'
]);
assertTest('5.E Login Viewer (nelayan.berkah@pesisir.id): Status 200 OK', $res['code'] === 200);
assertTest('5.F Role User Terverifikasi "viewer"', isset($res['json']['data']['user']['role']) && $res['json']['data']['user']['role'] === 'viewer');

$passed = count(array_filter($tests, fn($t) => $t['pass']));
$total = count($tests);
echo "\n========================================================\n";
echo "HASIL PENGUJIAN: {$passed}/{$total} PASS\n";
echo "========================================================\n";


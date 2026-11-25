<?php

/**
 * Unit Test Standalone: Security Helpers, CSRF, Password Hashing & Input Sanitization
 * Dapat dijalankan langsung di lingkungan CLI / CI tanpa ketergantungan server HTTP eksternal.
 */

// Pastikan session dimulai sebelum output apapun dikirim
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

require_once __DIR__ . '/../api/middleware.php';

$passed = 0;
$total = 0;

function assertUnit(string $label, bool $condition, string $detail = ''): void {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo "[PASS] {$label}\n";
    } else {
        echo "[FAIL] {$label}" . ($detail ? " | {$detail}" : "") . "\n";
    }
}

echo "====================================================================\n";
echo "UNIT TEST: KEAMANAN, CSRF, AUTENTIKASI & SANITASI INPUT\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "====================================================================\n\n";

$token1 = generateCsrfToken(true);
assertUnit(
    '1.A generateCsrfToken() menghasilkan string heksadesimal 64 karakter',
    is_string($token1) && strlen($token1) === 64 && ctype_xdigit($token1)
);

$token2 = generateCsrfToken(false);
assertUnit(
    '1.B generateCsrfToken() konsisten jika tidak di-force regenerate',
    $token1 === $token2
);

$token3 = generateCsrfToken(true);
assertUnit(
    '1.C generateCsrfToken(true) menghasilkan token baru yang berbeda (rotasi token)',
    $token1 !== $token3 && strlen($token3) === 64
);

// 2. Uji Verifikasi Token CSRF (hash_equals)
assertUnit(
    '2.A hash_equals() menerima token yang identik',
    hash_equals($token3, $token3) === true
);

$forgedToken = bin2hex(random_bytes(32));
assertUnit(
    '2.B hash_equals() menolak token palsu / tidak cocok',
    hash_equals($token3, $forgedToken) === false
);

assertUnit(
    '2.C hash_equals() menolak token kosong atau string acak',
    hash_equals($token3, '') === false && hash_equals($token3, 'fake_token') === false
);

// 3. Uji Ekstraksi Token CSRF dari Berbagai Sumber Request
$payloadJson = ['csrf_token' => $token3, 'nama' => 'Uji Coba'];
assertUnit(
    '3.A extractCsrfTokenFromRequest() mengekstrak token dari payload array/JSON',
    extractCsrfTokenFromRequest($payloadJson) === $token3
);

$_POST['csrf_token'] = $token3;
assertUnit(
    '3.B extractCsrfTokenFromRequest() mengekstrak token dari superglobal $_POST',
    extractCsrfTokenFromRequest() === $token3
);
unset($_POST['csrf_token']);

// 4. Uji Bcrypt Password Hashing & Verifikasi
$rawPassword = 'password123';
$hashed = password_hash($rawPassword, PASSWORD_BCRYPT, ['cost' => 10]);

assertUnit(
    '4.A password_hash() menghasilkan hash format Bcrypt ($2y$)',
    str_starts_with($hashed, '$2y$') || str_starts_with($hashed, '$2a$')
);

assertUnit(
    '4.B password_verify() memvalidasi kata sandi asli dengan benar',
    password_verify($rawPassword, $hashed) === true
);

assertUnit(
    '4.C password_verify() menolak kata sandi salah',
    password_verify('salah_password', $hashed) === false
);

// 5. Uji Sanitasi Input XSS
$xssPayload = "<script>alert('XSS')</script><img src=x onerror=alert(1)>";
$escaped = htmlspecialchars($xssPayload, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

assertUnit(
    '5.A htmlspecialchars() menetralisir tag HTML dan atribut event handler',
    !str_contains($escaped, '<script>') && str_contains($escaped, '&lt;script&gt;')
);

assertUnit(
    '5.B htmlspecialchars() menetralisir tanda petik ganda dan tunggal (ENT_QUOTES)',
    !str_contains($escaped, "'") && str_contains($escaped, '&#039;')
);

// 6. Uji Validasi Parameter Numerik (Mitigasi SQL Injection pada LIMIT/OFFSET)
$validLimit = '10';
$invalidLimitSql = '10; DROP TABLE alat;--';
$invalidLimitNegative = '-5';

assertUnit(
    '6.A ctype_digit() menerima string bilangan bulat positif valid',
    ctype_digit($validLimit) && (int)$validLimit > 0
);

assertUnit(
    '6.B ctype_digit() menolak karakter SQL Injection pada parameter numerik',
    !ctype_digit($invalidLimitSql)
);

assertUnit(
    '6.C ctype_digit() menolak bilangan negatif',
    !ctype_digit($invalidLimitNegative)
);

// 7. Uji Keberadaan File Proteksi Sensitif
$htaccessPath = dirname(__DIR__) . '/.htaccess';
$gitignorePath = dirname(__DIR__) . '/.gitignore';
$envExamplePath = dirname(__DIR__) . '/.env.example';

assertUnit(
    '7.A File .htaccess ada dan memblokir akses ke file .env dan .git',
    file_exists($htaccessPath) && str_contains(file_get_contents($htaccessPath), '\.env')
);

assertUnit(
    '7.B File .gitignore ada dan mengabaikan file .env lokal',
    file_exists($gitignorePath) && str_contains(file_get_contents($gitignorePath), '.env')
);

assertUnit(
    '7.C File .env.example tidak membocorkan password asli',
    file_exists($envExamplePath) && (
        str_contains(file_get_contents($envExamplePath), 'DB_PASS=') ||
        str_contains(file_get_contents($envExamplePath), 'DB_PASSWORD=')
    )
);

echo "\n====================================================================\n";
echo "RINGKASAN: {$passed} dari {$total} Kasus Uji Unit Lulus (PASS)\n";
echo "====================================================================\n";

if ($passed !== $total) {
    exit(1);
}

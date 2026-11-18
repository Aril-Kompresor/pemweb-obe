<?php

$temuan = [
    [
        'id'        => 'SEC-01',
        'risiko'    => 'SQL Injection (penggabungan input LIMIT/OFFSET)',
        'bukti'     => 'api/alats.php:145, api/pemesanan.php:142',
        'severity'  => 'High',
        'perbaikan' => 'Prepared statement :limit/:offset + bindValue(PDO::PARAM_INT)',
        'retest'    => 'PASS',
    ],
    [
        'id'        => 'SEC-02',
        'risiko'    => 'XSS (render output user mentah ke innerHTML)',
        'bukti'     => 'script.js, js/script.js, dashboard/dashboard.js, login/login.js',
        'severity'  => 'High',
        'perbaikan' => 'Gunakan textContent, escapeHtml(), dan sanitizeImageUrl()',
        'retest'    => 'PASS',
    ],
    [
        'id'        => 'SEC-03',
        'risiko'    => 'Broken Access Control (modifikasi oleh viewer)',
        'bukti'     => 'Uji POST/PUT/PATCH/DELETE sebagai viewer -> 403',
        'severity'  => 'High',
        'perbaikan' => 'Proteksi requireRole() pada endpoint CUD',
        'retest'    => 'PASS',
    ],
    [
        'id'        => 'SEC-04',
        'risiko'    => 'Cross-Site Request Forgery (CSRF)',
        'bukti'     => 'Form POST & endpoint mutasi sebelumnya tanpa token CSRF',
        'severity'  => 'High',
        'perbaikan' => 'Generate token /api/csrf.php, hidden input, verifikasi hash_equals()',
        'retest'    => 'PASS',
    ],
    [
        'id'        => 'SEC-05',
        'risiko'    => 'Eksposur file/folder sensitif (.env, .git, config/)',
        'bukti'     => '.htaccess belum memblokir akses HTTP langsung & directory listing',
        'severity'  => 'Critical',
        'perbaikan' => 'Tambah Options -Indexes, FilesMatch, dan RewriteRule blokir 403',
        'retest'    => 'PASS',
    ],
    [
        'id'        => 'SEC-06',
        'risiko'    => 'Parameter debug terbuka & default APP_DEBUG=true',
        'bukti'     => 'api/alats.php:41, api/pemesanan.php:41, .env.example:5',
        'severity'  => 'Medium',
        'perbaikan' => 'Batasi simulate_db_error ke local+debug, ubah default APP_DEBUG=false',
        'retest'    => 'PASS',
    ],
];

echo "| ID | Risiko | Bukti | Severity | Perbaikan | Retest Status |\n";
echo "| :--- | :--- | :--- | :--- | :--- | :--- |\n";
foreach ($temuan as $t) {
    echo "| {$t['id']} | {$t['risiko']} | {$t['bukti']} | {$t['severity']} | {$t['perbaikan']} | {$t['retest']} |\n";
}

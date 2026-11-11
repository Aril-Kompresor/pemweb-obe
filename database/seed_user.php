<?php

/**
 * Script Seeder Pengguna (User Seeder) - Pemweb OBE
 * Menyimpan data akun pengguna dengan kata sandi ter-hash (password_hash) menggunakan algoritma Bcrypt.
 */

require_once dirname(__DIR__) . '/config/database.php';

echo "========================================================\n";
echo "SEEDER PENGGUNA BASIS DATA (password_hash Bcrypt)\n";
echo "========================================================\n";

try {
    $pdo = db();

    // Data akun yang akan di-seed
    $users = [
        [
            'nama' => 'Admin Pesisir',
            'email' => 'admin@pesisir.id',
            'password' => 'password123',
            'role' => 'admin'
        ],
        [
            'nama' => 'Muhammad Aril',
            'email' => 'aril@pesisir.id',
            'password' => 'password123',
            'role' => 'petugas'
        ],
        [
            'nama' => 'Kelompok Nelayan Berkah',
            'email' => 'nelayan.berkah@pesisir.id',
            'password' => 'password123',
            'role' => 'viewer'
        ],
        [
            'nama' => 'Budi Nelayan Pesisir',
            'email' => 'budi.nelayan@pesisir.id',
            'password' => 'password123',
            'role' => 'viewer'
        ]
    ];

    $sql = "INSERT INTO users (nama, email, password_hash, role)
            VALUES (:nama, :email, :password_hash, :role)
            ON DUPLICATE KEY UPDATE 
                nama = VALUES(nama),
                password_hash = VALUES(password_hash),
                role = VALUES(role)";

    $stmt = $pdo->prepare($sql);

    foreach ($users as $u) {
        // Enkripsi kata sandi menggunakan fungsi native PHP password_hash (Bcrypt)
        $passwordHash = password_hash($u['password'], PASSWORD_BCRYPT);

        $stmt->execute([
            ':nama' => $u['nama'],
            ':email' => $u['email'],
            ':password_hash' => $passwordHash,
            ':role' => $u['role']
        ]);

        echo "Berhasil memproses user: {$u['nama']} ({$u['email']}) [Role: {$u['role']}]\n";
    }

    echo "\nVerifikasi seluruh user di tabel 'users':\n";
    $query = $pdo->query("SELECT id, nama, email, role, LEFT(password_hash, 15) AS hash_preview FROM users ORDER BY id ASC");
    $allUsers = $query->fetchAll();

    foreach ($allUsers as $row) {
        echo "- ID {$row['id']}: {$row['nama']} | Email: {$row['email']} | Role: {$row['role']} | Hash: {$row['hash_preview']}...\n";
    }

    echo "\nSelesai! Seluruh akun berhasil disimpan dengan password_hash.\n";
    echo "========================================================\n";

} catch (Throwable $e) {
    echo "Terjadi kesalahan saat seeding user: " . $e->getMessage() . "\n";
    exit(1);
}


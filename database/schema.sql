-- ========================================================
-- SKEMA BASIS DATA PEMWEB OBE: SISTEM UMKM PESISIR
-- Modul 10: Integrasi Basis Data, Data Modeling, PDO, ERD
-- ========================================================

CREATE DATABASE IF NOT EXISTS pemweb_obe 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE pemweb_obe;

-- 1. TABEL PENGGUNA (users)
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'petugas', 'viewer') NOT NULL DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABEL PERALATAN PESISIR (alat)
CREATE TABLE IF NOT EXISTS alat (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(120) NOT NULL,
    kategori VARCHAR(80) NOT NULL,
    jumlah INT UNSIGNED NOT NULL DEFAULT 0,
    kondisi ENUM('Baik', 'Perlu Cek', 'Rusak') NOT NULL DEFAULT 'Baik',
    lokasi VARCHAR(100) NOT NULL DEFAULT 'Gudang Utama',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_alat_kategori (kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABEL PEMESANAN & LAYANAN UMKM (pemesanan)
-- Digunakan untuk form Kontak & Pemesanan Layanan UMKM
CREATE TABLE IF NOT EXISTS pemesanan (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    alat_id BIGINT UNSIGNED NULL,
    nama VARCHAR(100) NOT NULL,
    kontak VARCHAR(100) NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    jumlah INT UNSIGNED NOT NULL DEFAULT 1,
    catatan TEXT NULL,
    status ENUM('Menunggu', 'Diproses', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Menunggu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Key Constraints
    CONSTRAINT fk_pemesanan_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_pemesanan_alat FOREIGN KEY (alat_id) REFERENCES alat(id) ON DELETE SET NULL ON UPDATE CASCADE,

    -- Constraint Validasi Kuantitas
    CONSTRAINT chk_pemesanan_jumlah CHECK (jumlah >= 1),

    -- Indexing untuk mempercepat query pencarian & filter
    INDEX idx_pemesanan_kontak (kontak),
    INDEX idx_pemesanan_kategori (kategori),
    INDEX idx_pemesanan_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- DATA AWAL (SEEDER) UNTUK PENGUJIAN
-- ========================================================

-- Seeder Users (Password default: 'password123')
INSERT INTO users (id, nama, email, password_hash, role) VALUES
(1, 'Admin Pesisir', 'admin@pesisir.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Muhammad Aril', 'aril@pesisir.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'petugas'),
(3, 'Kelompok Nelayan Berkah', 'nelayan.berkah@pesisir.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer')
ON DUPLICATE KEY UPDATE nama = VALUES(nama);

-- Seeder Alat
INSERT INTO alat (id, nama, kategori, jumlah, kondisi, lokasi) VALUES
(1, 'Jaring Insang', 'Tangkap', 10, 'Baik', 'Gudang A'),
(2, 'Coolbox', 'Penyimpanan', 5, 'Baik', 'Dermaga 1'),
(3, 'GPS Tracker', 'Navigasi', 3, 'Baik', 'Kapal 01'),
(4, 'Timbangan Digital', 'Pengolahan', 7, 'Baik', 'Gudang A'),
(5, 'Mesin Vacuum Sealer', 'Pengemasan', 2, 'Baik', 'Sentra UMKM')
ON DUPLICATE KEY UPDATE nama = VALUES(nama);

-- Seeder Pemesanan Awal
INSERT INTO pemesanan (id, user_id, alat_id, nama, kontak, kategori, jumlah, catatan, status) VALUES
(1, 3, NULL, 'Pak Budi Santoso', '081234567890', 'Keripik Ikan Tenggiri', 10, 'Kirim ke Juata Laut, tolong bungkus rapi.', 'Diproses'),
(2, 3, 2, 'Kelompok Nelayan Berkah', '082198765432', 'Peminjaman Coolbox', 2, 'Peminjaman untuk melaut akhir pekan.', 'Selesai')
ON DUPLICATE KEY UPDATE nama = VALUES(nama);

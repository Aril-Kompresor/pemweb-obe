<?php

// Seeder Data Banyak untuk Modul Pagination & Inventaris UMKM Pesisir
require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    echo "Terhubung ke database pemweb_obe...\n";

    // -------------------------------------------------------------
    // 1. DATA SEEDER PERALATAN PESISIR (alat)
    // -------------------------------------------------------------
    $daftarAlat = [
        ['nama' => 'Jaring Insang Monofilamen', 'kategori' => 'Tangkap', 'jumlah' => 12, 'kondisi' => 'Baik', 'lokasi' => 'Gudang A'],
        ['nama' => 'Coolbox Polyurethane 100L', 'kategori' => 'Penyimpanan', 'jumlah' => 8, 'kondisi' => 'Baik', 'lokasi' => 'Dermaga 1'],
        ['nama' => 'GPS Marine Navigator G-200', 'kategori' => 'Navigasi', 'jumlah' => 4, 'kondisi' => 'Baik', 'lokasi' => 'Kapal 01'],
        ['nama' => 'Timbangan Digital Lantai 150kg', 'kategori' => 'Pengolahan', 'jumlah' => 6, 'kondisi' => 'Baik', 'lokasi' => 'Sentra UMKM'],
        ['nama' => 'Mesin Vacuum Sealer Otomatis', 'kategori' => 'Pengemasan', 'jumlah' => 3, 'kondisi' => 'Baik', 'lokasi' => 'Sentra UMKM'],
        ['nama' => 'Pukat Cincin Mini (Purse Seine)', 'kategori' => 'Tangkap', 'jumlah' => 5, 'kondisi' => 'Perlu Cek', 'lokasi' => 'Gudang A'],
        ['nama' => 'Fish Finder Sonar Dual-Beam', 'kategori' => 'Navigasi', 'jumlah' => 3, 'kondisi' => 'Baik', 'lokasi' => 'Kapal 02'],
        ['nama' => 'Keranjang Ikan Plastik Higienis 50kg', 'kategori' => 'Penyimpanan', 'jumlah' => 35, 'kondisi' => 'Baik', 'lokasi' => 'Dermaga 1'],
        ['nama' => 'Life Jacket Standar SOLAS Maritim', 'kategori' => 'Keselamatan', 'jumlah' => 24, 'kondisi' => 'Baik', 'lokasi' => 'Dermaga 2'],
        ['nama' => 'Lampu Badai Nelayan LED 100W Solar', 'kategori' => 'Navigasi', 'jumlah' => 15, 'kondisi' => 'Baik', 'lokasi' => 'Kapal 01'],
        ['nama' => 'Bubu Jebakan Kepiting Bakau Stainless', 'kategori' => 'Tangkap', 'jumlah' => 20, 'kondisi' => 'Baik', 'lokasi' => 'Gudang A'],
        ['nama' => 'Mesin Spinner Peniris Minyak Keripik', 'kategori' => 'Pengolahan', 'jumlah' => 2, 'kondisi' => 'Baik', 'lokasi' => 'Sentra UMKM'],
        ['nama' => 'Perahu Karet Inflatable Patroli 3M', 'kategori' => 'Keselamatan', 'jumlah' => 2, 'kondisi' => 'Baik', 'lokasi' => 'Dermaga 1'],
        ['nama' => 'Solar Panel Kit Portabel Nelayan 200W', 'kategori' => 'Pengolahan', 'jumlah' => 5, 'kondisi' => 'Baik', 'lokasi' => 'Sentra UMKM'],
        ['nama' => 'Mesin Penggiling Daging Ikan Kamaboko', 'kategori' => 'Pengolahan', 'jumlah' => 2, 'kondisi' => 'Perlu Cek', 'lokasi' => 'Sentra UMKM'],
        ['nama' => 'Radio Komunikasi VHF Marine HT-550', 'kategori' => 'Navigasi', 'jumlah' => 7, 'kondisi' => 'Baik', 'lokasi' => 'Kapal 01'],
        ['nama' => 'Coolbox Ultra Freezer 220 Liter', 'kategori' => 'Penyimpanan', 'jumlah' => 4, 'kondisi' => 'Baik', 'lokasi' => 'Dermaga 2'],
        ['nama' => 'Jaring Rajungan / Kepiting Tancap', 'kategori' => 'Tangkap', 'jumlah' => 14, 'kondisi' => 'Baik', 'lokasi' => 'Gudang A'],
        ['nama' => 'Mesin Continuous Continuous Band Sealer', 'kategori' => 'Pengemasan', 'jumlah' => 2, 'kondisi' => 'Baik', 'lokasi' => 'Sentra UMKM'],
        ['nama' => 'Pelampung Tanda Jaring Malam Hari (Solar)', 'kategori' => 'Navigasi', 'jumlah' => 18, 'kondisi' => 'Baik', 'lokasi' => 'Kapal 02'],
        ['nama' => 'Termometer Digital Daging Ikan & Udang', 'kategori' => 'Pengolahan', 'jumlah' => 9, 'kondisi' => 'Baik', 'lokasi' => 'Sentra UMKM'],
        ['nama' => 'Ring Buoy Pelampung Lempar Orange', 'kategori' => 'Keselamatan', 'jumlah' => 10, 'kondisi' => 'Baik', 'lokasi' => 'Dermaga 1'],
        ['nama' => 'Pancing Ulur Multi-Hook Cumi & Tuna', 'kategori' => 'Tangkap', 'jumlah' => 16, 'kondisi' => 'Baik', 'lokasi' => 'Gudang A'],
        ['nama' => 'Kotak P3K First Aid Kit Maritim Kedap Air', 'kategori' => 'Keselamatan', 'jumlah' => 8, 'kondisi' => 'Baik', 'lokasi' => 'Kapal 01'],
        ['nama' => 'Mesin Cetak Kode Kedaluwarsa Inkjet', 'kategori' => 'Pengemasan', 'jumlah' => 2, 'kondisi' => 'Baik', 'lokasi' => 'Sentra UMKM'],
        ['nama' => 'Serok Jaring Halus Benur Udang Rebon', 'kategori' => 'Tangkap', 'jumlah' => 12, 'kondisi' => 'Baik', 'lokasi' => 'Gudang A'],
        ['nama' => 'Timbangan Gantung Pegas Stainless 50kg', 'kategori' => 'Pengolahan', 'jumlah' => 8, 'kondisi' => 'Perlu Cek', 'lokasi' => 'Dermaga 2'],
        ['nama' => 'Genset Inverter Nelayan Silenced 2500W', 'kategori' => 'Pengolahan', 'jumlah' => 3, 'kondisi' => 'Perlu Cek', 'lokasi' => 'Kapal 02'],
        ['nama' => 'Kompas Magnetik Kapal Anti Guncang', 'kategori' => 'Navigasi', 'jumlah' => 5, 'kondisi' => 'Baik', 'lokasi' => 'Kapal 01'],
        ['nama' => 'Mesin Es Flake Portabel Nelayan Pesisir', 'kategori' => 'Penyimpanan', 'jumlah' => 2, 'kondisi' => 'Rusak', 'lokasi' => 'Dermaga 1']
    ];

    $insertAlatStmt = $pdo->prepare(
        "INSERT INTO alat (nama, kategori, jumlah, kondisi, lokasi)
         VALUES (:nama, :kategori, :jumlah, :kondisi, :lokasi)
         ON DUPLICATE KEY UPDATE 
            kategori = VALUES(kategori), 
            jumlah = VALUES(jumlah), 
            kondisi = VALUES(kondisi), 
            lokasi = VALUES(lokasi)"
    );

    $alatInserted = 0;
    foreach ($daftarAlat as $alat) {
        // Cek jika nama alat sudah ada
        $checkStmt = $pdo->prepare("SELECT id FROM alat WHERE nama = :nama LIMIT 1");
        $checkStmt->execute(['nama' => $alat['nama']]);
        if (!$checkStmt->fetch()) {
            $insertAlatStmt->execute($alat);
            $alatInserted++;
        }
    }
    echo "Berhasil menambahkan {$alatInserted} data alat baru ke database!\n";

    // -------------------------------------------------------------
    // 2. DATA SEEDER PEMESANAN LAYANAN & PRODUK (pemesanan)
    // -------------------------------------------------------------
    $daftarPemesanan = [
        ['nama' => 'Restoran Sari Laut Bahari', 'kontak' => '081344556677', 'kategori' => 'Keripik Ikan Tenggiri', 'jumlah' => 25, 'status' => 'Diproses', 'catatan' => 'Kemasan toples 500g untuk display resto.'],
        ['nama' => 'Koperasi Bahari Sejahtera', 'kontak' => '082155667788', 'kategori' => 'Terasi Udang Rebon Asli', 'jumlah' => 50, 'status' => 'Selesai', 'catatan' => 'Pengiriman ke dermaga kargo kontainer.'],
        ['nama' => 'Warung Makan Ibu Juata', 'kontak' => '085277889900', 'kategori' => 'Abon Ikan Cakalang', 'jumlah' => 15, 'status' => 'Menunggu', 'catatan' => 'Minta varian rasa ekstra pedas.'],
        ['nama' => 'Kelompok Nelayan Samudera', 'kontak' => '081299001122', 'kategori' => 'Peminjaman Coolbox', 'jumlah' => 4, 'status' => 'Selesai', 'catatan' => 'Sewa coolbox melaut 4 hari.'],
        ['nama' => 'CV Mitra Mandiri Pesisir', 'kontak' => '081122334455', 'kategori' => 'Paket Kombo Hasil Laut', 'jumlah' => 30, 'status' => 'Diproses', 'catatan' => 'Paket souvenir kunjungan tamu dinas kelautan.'],
        ['nama' => 'Pak Hendra Pratama', 'kontak' => '081933445566', 'kategori' => 'Peminjaman Jaring Insang', 'jumlah' => 2, 'status' => 'Menunggu', 'catatan' => 'Kebutuhan mendesak musim ikan tembang.'],
        ['nama' => 'Sentra Keripik Ibu Darmi', 'kontak' => '085366778899', 'kategori' => 'Sewa Mesin Vacuum Sealer', 'jumlah' => 1, 'status' => 'Selesai', 'catatan' => 'Sewa mingguan pengemasan keripik.'],
        ['nama' => 'Keluarga Besar Nelayan Berau', 'kontak' => '081266554433', 'kategori' => 'Paket Kombo Hasil Laut', 'jumlah' => 20, 'status' => 'Menunggu', 'catatan' => 'Dikirim ke pelabuhan penyeberangan.'],
        ['nama' => 'Toko Oleh-Oleh Bandara Utama', 'kontak' => '082211447788', 'kategori' => 'Keripik Ikan Tenggiri', 'jumlah' => 40, 'status' => 'Diproses', 'catatan' => 'Lengkap sertifikat halal & tanggal kedaluwarsa.'],
        ['nama' => 'UD Berkah Laut Lestari', 'kontak' => '081388990011', 'kategori' => 'Terasi Udang Rebon Asli', 'jumlah' => 35, 'status' => 'Selesai', 'catatan' => 'Kemasan vakum kedap udara 250 gram.'],
        ['nama' => 'Hj. Rohani Tarakan Barat', 'kontak' => '085211993344', 'kategori' => 'Abon Ikan Cakalang', 'jumlah' => 12, 'status' => 'Menunggu', 'catatan' => 'Kirim via kurir lokal alamat Mamburungan.'],
        ['nama' => 'Bapak Joko Waluyo', 'kontak' => '081399887766', 'kategori' => 'Peminjaman Coolbox', 'jumlah' => 3, 'status' => 'Dibatalkan', 'catatan' => 'Cuaca gelombang tinggi, sewa dibatalkan.'],
        ['nama' => 'Asosiasi Pedagang Ikan Segar', 'kontak' => '081155443322', 'kategori' => 'Peminjaman Coolbox', 'jumlah' => 6, 'status' => 'Diproses', 'catatan' => 'Distribusi ikan layang segar ke pasar induk.'],
        ['nama' => 'Kedai Kopi Pesisir Selat', 'kontak' => '082188776655', 'kategori' => 'Keripik Ikan Tenggiri', 'jumlah' => 15, 'status' => 'Selesai', 'catatan' => 'Camilan ringan menu kopi sore.'],
        ['nama' => 'Kelompok Pengolah Ikan Mawar', 'kontak' => '085344332211', 'kategori' => 'Sewa Mesin Vacuum Sealer', 'jumlah' => 1, 'status' => 'Menunggu', 'catatan' => 'Pengemasan perdana produk nugget cakalang.']
    ];

    $insertPesanStmt = $pdo->prepare(
        "INSERT INTO pemesanan (nama, kontak, kategori, jumlah, status, catatan)
         VALUES (:nama, :kontak, :kategori, :jumlah, :status, :catatan)"
    );

    $pesanInserted = 0;
    foreach ($daftarPemesanan as $pesan) {
        $checkStmt = $pdo->prepare("SELECT id FROM pemesanan WHERE nama = :nama AND kategori = :kategori LIMIT 1");
        $checkStmt->execute(['nama' => $pesan['nama'], 'kategori' => $pesan['kategori']]);
        if (!$checkStmt->fetch()) {
            $insertPesanStmt->execute($pesan);
            $pesanInserted++;
        }
    }
    echo "Berhasil menambahkan {$pesanInserted} data pemesanan baru ke database!\n";

    // Tampilkan total row saat ini
    $totalAlat = $pdo->query("SELECT COUNT(*) FROM alat")->fetchColumn();
    $totalPesan = $pdo->query("SELECT COUNT(*) FROM pemesanan")->fetchColumn();
    echo "\n=== RINGKASAN DATABASE ===\n";
    echo "Total Data Alat Sekarang      : {$totalAlat} baris\n";
    echo "Total Data Pemesanan Sekarang : {$totalPesan} baris\n";

} catch (Throwable $e) {
    echo "Error Seeder: " . $e->getMessage() . "\n";
    exit(1);
}


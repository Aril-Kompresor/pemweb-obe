<?php

date_default_timezone_set('Asia/Makassar');

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/middleware.php';

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

if ($requestMethod === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// -------------------------------------------------------------
// GET: Mengambil riwayat / daftar pemesanan layanan (Prepared Statement)
// -------------------------------------------------------------
if ($requestMethod === 'GET') {
    try {
        $pdo = db();
        $status = $_GET['status'] ?? null;
        $kategori = $_GET['kategori'] ?? null;

        $sql = "SELECT id, user_id, alat_id, nama, kontak, kategori, jumlah, catatan, status, created_at FROM pemesanan WHERE 1=1";
        $params = [];

        if ($status !== null && trim($status) !== '') {
            $sql .= " AND status = :status";
            $params['status'] = trim($status);
        }

        if ($kategori !== null && trim($kategori) !== '') {
            $sql .= " AND kategori LIKE :kategori";
            $params['kategori'] = '%' . trim($kategori) . '%';
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'total' => count($data),
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'code' => 500,
            'message' => 'Gagal mengambil data pemesanan: ' . $e->getMessage()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// -------------------------------------------------------------
// POST: Menyimpan pemesanan layanan baru dari form kontak
// -------------------------------------------------------------
if ($requestMethod === 'POST') {
    requireJsonContentTypeMiddleware();

    $rawInput = file_get_contents('php://input');

    if (empty(trim($rawInput))) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'code' => 400,
            'message' => 'Request body kosong. Harap kirim payload berformat JSON.'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'code' => 400,
            'message' => 'Format JSON tidak valid: ' . json_last_error_msg()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $errors = [];

    // Validasi Nama
    $nama = trim((string)($input['nama'] ?? ''));
    if ($nama === '') {
        $errors['nama'] = 'Field nama pemesan wajib diisi.';
    } elseif (mb_strlen($nama) < 3) {
        $errors['nama'] = 'Nama pemesan minimal 3 karakter.';
    } elseif (mb_strlen($nama) > 100) {
        $errors['nama'] = 'Nama pemesan maksimal 100 karakter.';
    }

    // Validasi Kontak
    $kontak = trim((string)($input['kontak'] ?? ''));
    if ($kontak === '') {
        $errors['kontak'] = 'Nomor WhatsApp atau kontak aktif wajib diisi.';
    } elseif (mb_strlen($kontak) < 6 || mb_strlen($kontak) > 50) {
        $errors['kontak'] = 'Kontak harus memiliki panjang antara 6 hingga 50 karakter.';
    }

    // Validasi Kategori Layanan
    $kategori = trim((string)($input['kategori'] ?? ''));
    if ($kategori === '') {
        $errors['kategori'] = 'Pilihan produk atau alat wajib dipilih.';
    }

    // Validasi Kuantitas / Jumlah
    $jumlah = $input['jumlah'] ?? 1;
    if (!is_numeric($jumlah) || (int)$jumlah < 1) {
        $errors['jumlah'] = 'Jumlah minimal adalah 1 unit/paket.';
    } else {
        $jumlah = (int)$jumlah;
    }

    $catatan = isset($input['catatan']) ? trim((string)$input['catatan']) : null;
    if ($catatan === '') {
        $catatan = null;
    }

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode([
            'status' => 'error',
            'code' => 422,
            'message' => 'Validasi data pemesanan gagal.',
            'errors' => $errors
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $pdo = db();

        // Cari tahu apakah kategori merujuk ke alat tertentu untuk relasi alat_id
        $alatId = null;
        $checkAlatStmt = $pdo->prepare("SELECT id FROM alat WHERE :kategori LIKE CONCAT('%', nama, '%') LIMIT 1");
        $checkAlatStmt->execute(['kategori' => $kategori]);
        $foundAlat = $checkAlatStmt->fetch();
        if ($foundAlat) {
            $alatId = $foundAlat['id'];
        }

        // Query terparameterisasi (Prepared Statement) untuk insert aman dari SQL injection
        $insertSql = "INSERT INTO pemesanan (user_id, alat_id, nama, kontak, kategori, jumlah, catatan, status) 
                      VALUES (:user_id, :alat_id, :nama, :kontak, :kategori, :jumlah, :catatan, 'Menunggu')";
        
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([
            'user_id'  => null,
            'alat_id'  => $alatId,
            'nama'     => $nama,
            'kontak'   => $kontak,
            'kategori' => $kategori,
            'jumlah'   => $jumlah,
            'catatan'  => $catatan
        ]);

        $newId = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'code' => 201,
            'message' => 'Pemesanan layanan berhasil disimpan ke database.',
            'data' => [
                'id' => (int)$newId,
                'nama' => $nama,
                'kontak' => $kontak,
                'kategori' => $kategori,
                'jumlah' => $jumlah,
                'catatan' => $catatan,
                'status' => 'Menunggu'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'code' => 500,
            'message' => 'Gagal menyimpan pemesanan ke database: ' . $e->getMessage()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

http_response_code(405);
echo json_encode([
    'status' => 'error',
    'code' => 405,
    'message' => 'Metode HTTP tidak diizinkan. Hanya GET dan POST yang diperbolehkan.'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

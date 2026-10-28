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
// GET: Mengambil data alat dengan filter kategori (Prepared Statement)
// Sesuai standar Modul 10 Praktikum Pemrograman Web OBE
// -------------------------------------------------------------
if ($requestMethod === 'GET') {
    try {
        $pdo = db();
        $kategori = $_GET['kategori'] ?? $_GET['jenis'] ?? null;

        if ($kategori !== null && trim($kategori) !== '') {
            $stmt = $pdo->prepare('SELECT id, nama, kategori, kategori AS jenis, jumlah, kondisi, lokasi, created_at FROM alat WHERE kategori = :kategori ORDER BY id ASC');
            $stmt->execute(['kategori' => trim($kategori)]);
        } else {
            $stmt = $pdo->query('SELECT id, nama, kategori, kategori AS jenis, jumlah, kondisi, lokasi, created_at FROM alat ORDER BY id ASC');
        }

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
            'message' => 'Gagal mengambil data alat dari basis data: ' . $e->getMessage()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// -------------------------------------------------------------
// POST: Menambahkan data alat baru ke database (Prepared Statement)
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
            'message' => 'Format JSON rusak atau tidak valid: ' . json_last_error_msg()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $errors = [];

    if (!isset($input['nama']) || trim((string)$input['nama']) === '') {
        $errors['nama'] = 'Field nama wajib diisi.';
    } elseif (mb_strlen(trim((string)$input['nama'])) < 3) {
        $errors['nama'] = 'Nama alat minimal harus 3 karakter.';
    } elseif (mb_strlen(trim((string)$input['nama'])) > 100) {
        $errors['nama'] = 'Nama alat maksimal 100 karakter.';
    }

    $kategori = $input['kategori'] ?? $input['jenis'] ?? '';
    if (trim((string)$kategori) === '') {
        $errors['jenis'] = 'Field jenis / kategori wajib diisi.';
    }

    if (!isset($input['jumlah']) || $input['jumlah'] === '') {
        $errors['jumlah'] = 'Field jumlah wajib diisi.';
    } elseif (!is_numeric($input['jumlah'])) {
        $errors['jumlah'] = 'Field jumlah harus berupa angka numerik.';
    } elseif ((int)$input['jumlah'] < 1) {
        $errors['jumlah'] = 'Jumlah alat minimal 1 unit (aturan bisnis: tidak boleh kurang dari 1).';
    }

    if (!isset($input['lokasi']) || trim((string)$input['lokasi']) === '') {
        $errors['lokasi'] = 'Field lokasi wajib diisi.';
    }

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode([
            'status' => 'error',
            'code' => 422,
            'message' => 'Data tidak memenuhi aturan bisnis.',
            'errors' => $errors
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $pdo = db();
        $insertStmt = $pdo->prepare(
            'INSERT INTO alat (nama, kategori, jumlah, lokasi, kondisi) 
             VALUES (:nama, :kategori, :jumlah, :lokasi, :kondisi)'
        );

        $kondisi = $input['kondisi'] ?? 'Baik';
        $insertStmt->execute([
            'nama'     => trim((string)$input['nama']),
            'kategori' => trim((string)$kategori),
            'jumlah'   => (int)$input['jumlah'],
            'lokasi'   => trim((string)$input['lokasi']),
            'kondisi'  => in_array($kondisi, ['Baik', 'Perlu Cek', 'Rusak']) ? $kondisi : 'Baik'
        ]);

        $newId = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'code' => 201,
            'message' => 'Data alat berhasil ditambahkan ke basis data.',
            'data' => [
                'id'       => (int)$newId,
                'nama'     => trim((string)$input['nama']),
                'kategori' => trim((string)$kategori),
                'jenis'    => trim((string)$kategori),
                'jumlah'   => (int)$input['jumlah'],
                'lokasi'   => trim((string)$input['lokasi']),
                'kondisi'  => $kondisi
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'code' => 500,
            'message' => 'Gagal menyimpan ke basis data: ' . $e->getMessage()
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

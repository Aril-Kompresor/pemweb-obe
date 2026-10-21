<?php

date_default_timezone_set('Asia/Makassar');

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

$daftarAlat = [
    ['id' => 1, 'nama' => 'Jaring Insang', 'jenis' => 'Tangkap', 'jumlah' => 10, 'lokasi' => 'Gudang A'],
    ['id' => 2, 'nama' => 'Coolbox', 'jenis' => 'Penyimpanan', 'jumlah' => 5, 'lokasi' => 'Dermaga 1'],
    ['id' => 3, 'nama' => 'GPS Tracker', 'jenis' => 'Navigasi', 'jumlah' => 3, 'lokasi' => 'Kapal 01'],
    ['id' => 4, 'nama' => 'Timbangan Digital', 'jenis' => 'Pengolahan', 'jumlah' => 7, 'lokasi' => 'Gudang A'],
    ['id' => 5, 'nama' => 'Mesin Vacuum Sealer', 'jenis' => 'Pengemasan', 'jumlah' => 2, 'lokasi' => 'Sentra UMKM']
];

if ($requestMethod === 'GET') {
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'code' => 200,
        'total' => count($daftarAlat),
        'data' => $daftarAlat
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($requestMethod === 'POST') {
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
    }

    if (!isset($input['jenis']) || trim((string)$input['jenis']) === '') {
        $errors['jenis'] = 'Field jenis wajib diisi.';
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

    $newItem = [
        'id' => count($daftarAlat) + 1,
        'nama' => trim((string)$input['nama']),
        'jenis' => trim((string)$input['jenis']),
        'jumlah' => (int)$input['jumlah'],
        'lokasi' => trim((string)$input['lokasi'])
    ];

    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'code' => 201,
        'message' => 'Data alat berhasil ditambahkan.',
        'data' => $newItem
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode([
    'status' => 'error',
    'code' => 405,
    'message' => 'Metode HTTP tidak diizinkan. Hanya GET dan POST yang diperbolehkan.'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

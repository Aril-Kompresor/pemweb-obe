<?php

date_default_timezone_set('Asia/Makassar');

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/middleware.php';

// Atur CORS & Content-Type standar REST API
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-HTTP-Method-Override');
}

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Dukungan method override
if ($requestMethod === 'POST') {
    if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
        $requestMethod = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
    } elseif (isset($_GET['_method'])) {
        $requestMethod = strtoupper($_GET['_method']);
    }
}

// Tangani Preflight CORS
if ($requestMethod === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// =============================================================
// TRY/CATCH BOUNDARY API: Menjamin response 500 generik tanpa leak
// =============================================================
try {
    $pdo = db();

    // ---------------------------------------------------------
    // 1. GET: Collection & By ID (Prepared Statements)
    // ---------------------------------------------------------
    if ($requestMethod === 'GET') {
        // --- 1.A. GET by ID ---
        if (isset($_GET['id']) && trim((string)$_GET['id']) !== '') {
            $idRaw = trim((string)$_GET['id']);

            // Validasi format ID
            if (!ctype_digit($idRaw) || (int)$idRaw <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'ID pemesanan tidak valid. ID harus berupa bilangan bulat positif.'
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $id = (int)$idRaw;
            $stmt = $pdo->prepare(
                'SELECT p.id, p.user_id, p.alat_id, p.nama, p.kontak, p.kategori, p.jumlah, p.catatan, p.status, p.created_at, p.updated_at,
                        a.nama AS nama_alat
                 FROM pemesanan p
                 LEFT JOIN alat a ON p.alat_id = a.id
                 WHERE p.id = :id
                 LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $pesanan = $stmt->fetch();

            // Pemeriksaan keberadaan (existence check)
            if (!$pesanan) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'code' => 404,
                    'message' => "Data pemesanan dengan ID {$id} tidak ditemukan."
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'code' => 200,
                'data' => $pesanan
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // --- 1.B. GET Collection ---
        $status = $_GET['status'] ?? null;
        $kategori = $_GET['kategori'] ?? null;
        $search = $_GET['search'] ?? null;

        $sql = "SELECT p.id, p.user_id, p.alat_id, p.nama, p.kontak, p.kategori, p.jumlah, p.catatan, p.status, p.created_at, p.updated_at,
                       a.nama AS nama_alat
                FROM pemesanan p
                LEFT JOIN alat a ON p.alat_id = a.id
                WHERE 1=1";
        $params = [];

        if ($status !== null && trim($status) !== '' && trim($status) !== 'Semua') {
            $sql .= " AND p.status = :status";
            $params['status'] = trim($status);
        }

        if ($kategori !== null && trim($kategori) !== '' && trim($kategori) !== 'Semua') {
            $sql .= " AND p.kategori LIKE :kategori";
            $params['kategori'] = '%' . trim($kategori) . '%';
        }

        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (p.nama LIKE :search_nama OR p.kontak LIKE :search_kontak OR p.kategori LIKE :search_kat)";
            $keyword = '%' . trim($search) . '%';
            $params['search_nama'] = $keyword;
            $params['search_kontak'] = $keyword;
            $params['search_kat'] = $keyword;
        }

        $sql .= " ORDER BY p.id DESC";

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
    }

    // ---------------------------------------------------------
    // 2. POST: Create Pemesanan Baru
    // ---------------------------------------------------------
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

        // Validasi Kategori
        $kategori = trim((string)($input['kategori'] ?? ''));
        if ($kategori === '') {
            $errors['kategori'] = 'Pilihan produk atau layanan wajib dipilih.';
        } elseif (mb_strlen($kategori) > 100) {
            $errors['kategori'] = 'Kategori layanan maksimal 100 karakter.';
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

        $status = trim((string)($input['status'] ?? 'Menunggu'));
        $validStatuses = ['Menunggu', 'Diproses', 'Selesai', 'Dibatalkan'];
        if (!in_array($status, $validStatuses, true)) {
            $status = 'Menunggu';
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

        // Deteksi relasi alat_id otomatis jika ada kesamaan nama alat
        $alatId = isset($input['alat_id']) && is_numeric($input['alat_id']) ? (int)$input['alat_id'] : null;
        if ($alatId === null) {
            $checkAlatStmt = $pdo->prepare("SELECT id FROM alat WHERE :kategori LIKE CONCAT('%', nama, '%') LIMIT 1");
            $checkAlatStmt->execute(['kategori' => $kategori]);
            $foundAlat = $checkAlatStmt->fetch();
            if ($foundAlat) {
                $alatId = $foundAlat['id'];
            }
        }

        // Insert aman dengan Prepared Statement
        $insertSql = "INSERT INTO pemesanan (user_id, alat_id, nama, kontak, kategori, jumlah, catatan, status) 
                      VALUES (:user_id, :alat_id, :nama, :kontak, :kategori, :jumlah, :catatan, :status)";
        
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([
            'user_id'  => null,
            'alat_id'  => $alatId,
            'nama'     => $nama,
            'kontak'   => $kontak,
            'kategori' => $kategori,
            'jumlah'   => $jumlah,
            'catatan'  => $catatan,
            'status'   => $status
        ]);

        $newId = (int)$pdo->lastInsertId();

        $fetchStmt = $pdo->prepare('SELECT id, user_id, alat_id, nama, kontak, kategori, jumlah, catatan, status, created_at, updated_at FROM pemesanan WHERE id = :id');
        $fetchStmt->execute(['id' => $newId]);
        $newData = $fetchStmt->fetch();

        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'code' => 201,
            'message' => 'Pemesanan layanan berhasil disimpan ke basis data.',
            'data' => $newData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---------------------------------------------------------
    // 3. PUT / PATCH: Update Pemesanan Menggunakan ID Tervalidasi
    // ---------------------------------------------------------
    if ($requestMethod === 'PUT' || $requestMethod === 'PATCH') {
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

        $idRaw = $_GET['id'] ?? $input['id'] ?? null;
        if ($idRaw === null || trim((string)$idRaw) === '') {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'code' => 400,
                'message' => 'ID pemesanan tidak disertakan. Sertakan id pada query parameter atau JSON body.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!ctype_digit((string)$idRaw) || (int)$idRaw <= 0) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'code' => 400,
                'message' => 'ID pemesanan tidak valid. ID harus berupa bilangan bulat positif.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = (int)$idRaw;

        // Pemeriksaan keberadaan (existence check)
        $checkStmt = $pdo->prepare('SELECT id, user_id, alat_id, nama, kontak, kategori, jumlah, catatan, status FROM pemesanan WHERE id = :id LIMIT 1');
        $checkStmt->execute(['id' => $id]);
        $existing = $checkStmt->fetch();

        if (!$existing) {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'code' => 404,
                'message' => "Data pemesanan dengan ID {$id} tidak ditemukan untuk diperbarui."
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $errors = [];

        // Validasi Nama jika ada
        $nama = $existing['nama'];
        if (array_key_exists('nama', $input)) {
            $namaVal = trim((string)$input['nama']);
            if ($namaVal === '') {
                $errors['nama'] = 'Nama pemesan tidak boleh kosong.';
            } elseif (mb_strlen($namaVal) < 3 || mb_strlen($namaVal) > 100) {
                $errors['nama'] = 'Nama pemesan harus antara 3 hingga 100 karakter.';
            } else {
                $nama = $namaVal;
            }
        }

        // Validasi Kontak jika ada
        $kontak = $existing['kontak'];
        if (array_key_exists('kontak', $input)) {
            $kontakVal = trim((string)$input['kontak']);
            if ($kontakVal === '') {
                $errors['kontak'] = 'Kontak pemesan tidak boleh kosong.';
            } elseif (mb_strlen($kontakVal) < 6 || mb_strlen($kontakVal) > 50) {
                $errors['kontak'] = 'Kontak harus antara 6 hingga 50 karakter.';
            } else {
                $kontak = $kontakVal;
            }
        }

        // Validasi Kategori jika ada
        $kategori = $existing['kategori'];
        if (array_key_exists('kategori', $input)) {
            $katVal = trim((string)$input['kategori']);
            if ($katVal === '') {
                $errors['kategori'] = 'Kategori layanan tidak boleh kosong.';
            } else {
                $kategori = $katVal;
            }
        }

        // Validasi Jumlah jika ada
        $jumlah = (int)$existing['jumlah'];
        if (array_key_exists('jumlah', $input)) {
            if (!is_numeric($input['jumlah']) || (int)$input['jumlah'] < 1) {
                $errors['jumlah'] = 'Jumlah pesanan minimal 1.';
            } else {
                $jumlah = (int)$input['jumlah'];
            }
        }

        // Validasi Status jika ada
        $status = $existing['status'];
        $validStatuses = ['Menunggu', 'Diproses', 'Selesai', 'Dibatalkan'];
        if (array_key_exists('status', $input)) {
            $statusVal = trim((string)$input['status']);
            if (!in_array($statusVal, $validStatuses, true)) {
                $errors['status'] = 'Status harus salah satu dari: ' . implode(', ', $validStatuses) . '.';
            } else {
                $status = $statusVal;
            }
        }

        // Catatan
        $catatan = $existing['catatan'];
        if (array_key_exists('catatan', $input)) {
            $catatanVal = trim((string)$input['catatan']);
            $catatan = ($catatanVal === '') ? null : $catatanVal;
        }

        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validasi data pembaruan pemesanan gagal.',
                'errors' => $errors
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $updateStmt = $pdo->prepare(
            'UPDATE pemesanan 
             SET nama = :nama, kontak = :kontak, kategori = :kategori, jumlah = :jumlah, catatan = :catatan, status = :status 
             WHERE id = :id'
        );
        $updateStmt->execute([
            'nama'     => $nama,
            'kontak'   => $kontak,
            'kategori' => $kategori,
            'jumlah'   => $jumlah,
            'catatan'  => $catatan,
            'status'   => $status,
            'id'       => $id
        ]);

        $fetchUpdated = $pdo->prepare('SELECT id, user_id, alat_id, nama, kontak, kategori, jumlah, catatan, status, created_at, updated_at FROM pemesanan WHERE id = :id');
        $fetchUpdated->execute(['id' => $id]);
        $updatedData = $fetchUpdated->fetch();

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'message' => "Data pemesanan dengan ID {$id} berhasil diperbarui.",
            'data' => $updatedData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---------------------------------------------------------
    // 4. DELETE: Hapus Pemesanan dengan Pemeriksaan Keberadaan
    // ---------------------------------------------------------
    if ($requestMethod === 'DELETE') {
        $idRaw = $_GET['id'] ?? null;
        if ($idRaw === null || trim((string)$idRaw) === '') {
            $rawInput = file_get_contents('php://input');
            if (!empty(trim($rawInput))) {
                $input = json_decode($rawInput, true);
                if (is_array($input) && isset($input['id'])) {
                    $idRaw = $input['id'];
                }
            }
        }

        if ($idRaw === null || trim((string)$idRaw) === '') {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'code' => 400,
                'message' => 'ID pemesanan wajib disertakan untuk operasi hapus. Sertakan parameter ?id={id} atau payload JSON.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!ctype_digit((string)$idRaw) || (int)$idRaw <= 0) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'code' => 400,
                'message' => 'ID pemesanan tidak valid. ID harus berupa bilangan bulat positif.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = (int)$idRaw;

        // Pemeriksaan keberadaan (existence check)
        $findStmt = $pdo->prepare('SELECT id, nama, kategori FROM pemesanan WHERE id = :id LIMIT 1');
        $findStmt->execute(['id' => $id]);
        $existing = $findStmt->fetch();

        if (!$existing) {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'code' => 404,
                'message' => "Data pemesanan dengan ID {$id} tidak ditemukan. Operasi hapus dibatalkan."
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $deleteStmt = $pdo->prepare('DELETE FROM pemesanan WHERE id = :id');
        $deleteStmt->execute(['id' => $id]);

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'message' => "Data pemesanan dengan ID {$id} ('{$existing['nama']}') berhasil dihapus dari basis data.",
            'deleted_id' => $id
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---------------------------------------------------------
    // Method Tidak Diizinkan (405)
    // ---------------------------------------------------------
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Metode HTTP tidak diizinkan. Hanya GET, POST, PUT, PATCH, dan DELETE yang diperbolehkan.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    // ---------------------------------------------------------
    // 5. TRY/CATCH BOUNDARY API: Log Internal & Response 500 Generik
    // ---------------------------------------------------------
    error_log(sprintf(
        '[API ERROR 500 pemesanan.php] %s in %s:%d | Trace: %s',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    ));

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'Terjadi kesalahan internal pada server. Silakan coba beberapa saat lagi.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

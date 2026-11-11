<?php

date_default_timezone_set('Asia/Makassar');

ini_set('display_errors', '0');
error_reporting(E_ALL);

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

// Dukungan method override (misal: client lama atau gateway proxy)
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
    // Simulasi kegagalan database untuk pengujian keamanan (Memastikan tidak ada leak password / stack trace ke client)
    if (isset($_GET['simulate_db_error']) && $_GET['simulate_db_error'] === '1') {
        throw new PDOException("SQLSTATE[HY000] [1045] Access denied for user 'root'@'127.0.0.1' (using password: YES) with secret db_password_rahasia_123");
    }

    $pdo = db();

    // ---------------------------------------------------------
    // 1. GET: Collection & By ID (Prepared Statements & Pagination)
    // ---------------------------------------------------------
    if ($requestMethod === 'GET') {
        // --- 1.A. GET by ID ---
        if (isset($_GET['id']) && trim((string)$_GET['id']) !== '') {
            $idRaw = trim((string)$_GET['id']);

            // Validasi format ID: harus bilangan bulat positif
            if (!ctype_digit($idRaw) || (int)$idRaw <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'ID alat tidak valid. ID harus berupa bilangan bulat positif.'
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $id = (int)$idRaw;
            $stmt = $pdo->prepare(
                'SELECT id, nama, kategori, kategori AS jenis, jumlah, kondisi, lokasi, created_at, updated_at 
                 FROM alat 
                 WHERE id = :id 
                 LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $alat = $stmt->fetch();

            // Pemeriksaan keberadaan (existence check)
            if (!$alat) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'code' => 404,
                    'message' => "Data alat dengan ID {$id} tidak ditemukan."
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'code' => 200,
                'data' => $alat
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // --- 1.B. GET Collection (dengan Filter & Pagination) ---
        $kategori = $_GET['kategori'] ?? $_GET['jenis'] ?? null;
        $kondisi = $_GET['kondisi'] ?? null;
        $lokasi = $_GET['lokasi'] ?? null;
        $search = $_GET['search'] ?? null;

        $whereSql = " FROM alat WHERE 1=1";
        $params = [];

        if ($kategori !== null && trim($kategori) !== '' && trim($kategori) !== 'Semua') {
            $whereSql .= " AND kategori = :kategori";
            $params['kategori'] = trim($kategori);
        }

        if ($kondisi !== null && trim($kondisi) !== '' && trim($kondisi) !== 'Semua') {
            $whereSql .= " AND kondisi = :kondisi";
            $params['kondisi'] = trim($kondisi);
        }

        if ($lokasi !== null && trim($lokasi) !== '' && trim($lokasi) !== 'Semua') {
            $whereSql .= " AND lokasi = :lokasi";
            $params['lokasi'] = trim($lokasi);
        }

        if ($search !== null && trim($search) !== '') {
            $whereSql .= " AND (nama LIKE :search_nama OR lokasi LIKE :search_lokasi OR kategori LIKE :search_kat)";
            $keyword = '%' . trim($search) . '%';
            $params['search_nama'] = $keyword;
            $params['search_lokasi'] = $keyword;
            $params['search_kat'] = $keyword;
        }

        // Hitung total baris data yang cocok dengan filter untuk informasi paginasi
        $countStmt = $pdo->prepare("SELECT COUNT(*)" . $whereSql);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        // Parameter Paginasi
        $isPaginated = isset($_GET['page']) || isset($_GET['limit']) || isset($_GET['per_page']);
        $limitParam = $_GET['limit'] ?? $_GET['per_page'] ?? null;

        $sql = "SELECT id, nama, kategori, kategori AS jenis, jumlah, kondisi, lokasi, created_at, updated_at" . $whereSql . " ORDER BY id DESC";

        if ($isPaginated && $limitParam !== 'all') {
            $limit = is_numeric($limitParam) ? max(1, min(100, (int)$limitParam)) : 10;
            $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;
            $offset = ($page - 1) * $limit;

            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'code' => 200,
                'pagination' => [
                    'total' => $totalRecords,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ],
                'total' => $totalRecords,
                'count' => count($data),
                'data' => $data
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Jika tidak meminta paginasi, kembalikan semua data
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'pagination' => [
                'total' => $totalRecords,
                'per_page' => $totalRecords,
                'current_page' => 1,
                'total_pages' => 1,
                'has_next' => false,
                'has_prev' => false
            ],
            'total' => $totalRecords,
            'count' => count($data),
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---------------------------------------------------------
    // 2. POST: Create Baru dengan Validasi & Prepared Statement
    // ---------------------------------------------------------
    if ($requestMethod === 'POST') {
        requireRole(['admin', 'petugas']);
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
            $errors['nama'] = 'Field nama alat wajib diisi.';
        } elseif (mb_strlen($nama) < 3) {
            $errors['nama'] = 'Nama alat minimal 3 karakter.';
        } elseif (mb_strlen($nama) > 120) {
            $errors['nama'] = 'Nama alat maksimal 120 karakter.';
        }

        // Validasi Kategori
        $kategori = trim((string)($input['kategori'] ?? $input['jenis'] ?? ''));
        if ($kategori === '') {
            $errors['kategori'] = 'Field kategori/jenis alat wajib diisi.';
        } elseif (mb_strlen($kategori) > 80) {
            $errors['kategori'] = 'Kategori alat maksimal 80 karakter.';
        }

        // Validasi Jumlah
        if (!isset($input['jumlah']) || $input['jumlah'] === '') {
            $errors['jumlah'] = 'Field jumlah wajib diisi.';
        } elseif (!is_numeric($input['jumlah'])) {
            $errors['jumlah'] = 'Field jumlah harus berupa angka numerik.';
        } elseif ((int)$input['jumlah'] < 1) {
            $errors['jumlah'] = 'Jumlah alat minimal 1 unit (aturan bisnis: tidak boleh kurang dari 1).';
        }

        // Validasi Kondisi
        $kondisi = trim((string)($input['kondisi'] ?? 'Baik'));
        $validKondisi = ['Baik', 'Perlu Cek', 'Rusak'];
        if (!in_array($kondisi, $validKondisi, true)) {
            $errors['kondisi'] = 'Kondisi alat harus salah satu dari: ' . implode(', ', $validKondisi) . '.';
        }

        // Validasi Lokasi
        $lokasi = trim((string)($input['lokasi'] ?? ''));
        if ($lokasi === '') {
            $errors['lokasi'] = 'Field lokasi penyimpanan wajib diisi.';
        } elseif (mb_strlen($lokasi) > 100) {
            $errors['lokasi'] = 'Lokasi maksimal 100 karakter.';
        }

        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validasi data alat baru gagal.',
                'errors' => $errors
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Query aman dengan Prepared Statement
        $insertStmt = $pdo->prepare(
            'INSERT INTO alat (nama, kategori, jumlah, kondisi, lokasi) 
             VALUES (:nama, :kategori, :jumlah, :kondisi, :lokasi)'
        );
        $insertStmt->execute([
            'nama'     => $nama,
            'kategori' => $kategori,
            'jumlah'   => (int)$input['jumlah'],
            'kondisi'  => $kondisi,
            'lokasi'   => $lokasi
        ]);

        $newId = (int)$pdo->lastInsertId();

        // Ambil data yang baru dibuat
        $fetchStmt = $pdo->prepare('SELECT id, nama, kategori, kategori AS jenis, jumlah, kondisi, lokasi, created_at, updated_at FROM alat WHERE id = :id');
        $fetchStmt->execute(['id' => $newId]);
        $newData = $fetchStmt->fetch();

        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'code' => 201,
            'message' => 'Data alat berhasil ditambahkan ke basis data.',
            'data' => $newData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---------------------------------------------------------
    // 3. PUT / PATCH: Update Menggunakan ID yang Tervalidasi
    // ---------------------------------------------------------
    if ($requestMethod === 'PUT' || $requestMethod === 'PATCH') {
        requireRole(['admin', 'petugas']);
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

        // Ambil ID dari URL query param atau payload body
        $idRaw = $_GET['id'] ?? $input['id'] ?? null;
        if ($idRaw === null || trim((string)$idRaw) === '') {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'code' => 400,
                'message' => 'ID alat tidak disertakan. Sertakan id pada query parameter atau JSON body.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validasi format ID tervalidasi: harus bilangan bulat positif
        if (!ctype_digit((string)$idRaw) || (int)$idRaw <= 0) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'code' => 400,
                'message' => 'ID alat tidak valid. ID harus berupa bilangan bulat positif.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = (int)$idRaw;

        // Pemeriksaan keberadaan data sebelum update (Existence check)
        $checkStmt = $pdo->prepare('SELECT id, nama, kategori, jumlah, kondisi, lokasi FROM alat WHERE id = :id LIMIT 1');
        $checkStmt->execute(['id' => $id]);
        $existing = $checkStmt->fetch();

        if (!$existing) {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'code' => 404,
                'message' => "Data alat dengan ID {$id} tidak ditemukan untuk diperbarui."
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $errors = [];

        // Validasi Nama jika disertakan (atau wajib untuk PUT)
        $nama = $existing['nama'];
        if (array_key_exists('nama', $input)) {
            $namaVal = trim((string)$input['nama']);
            if ($namaVal === '') {
                $errors['nama'] = 'Field nama alat tidak boleh kosong.';
            } elseif (mb_strlen($namaVal) < 3) {
                $errors['nama'] = 'Nama alat minimal 3 karakter.';
            } elseif (mb_strlen($namaVal) > 120) {
                $errors['nama'] = 'Nama alat maksimal 120 karakter.';
            } else {
                $nama = $namaVal;
            }
        } elseif ($requestMethod === 'PUT') {
            $errors['nama'] = 'Field nama alat wajib disertakan untuk metode PUT.';
        }

        // Validasi Kategori jika disertakan
        $kategori = $existing['kategori'];
        if (array_key_exists('kategori', $input) || array_key_exists('jenis', $input)) {
            $katVal = trim((string)($input['kategori'] ?? $input['jenis'] ?? ''));
            if ($katVal === '') {
                $errors['kategori'] = 'Field kategori alat tidak boleh kosong.';
            } elseif (mb_strlen($katVal) > 80) {
                $errors['kategori'] = 'Kategori alat maksimal 80 karakter.';
            } else {
                $kategori = $katVal;
            }
        } elseif ($requestMethod === 'PUT') {
            $errors['kategori'] = 'Field kategori/jenis alat wajib disertakan untuk metode PUT.';
        }

        // Validasi Jumlah jika disertakan
        $jumlah = (int)$existing['jumlah'];
        if (array_key_exists('jumlah', $input)) {
            if ($input['jumlah'] === '' || $input['jumlah'] === null) {
                $errors['jumlah'] = 'Field jumlah tidak boleh kosong.';
            } elseif (!is_numeric($input['jumlah'])) {
                $errors['jumlah'] = 'Field jumlah harus berupa angka numerik.';
            } elseif ((int)$input['jumlah'] < 1) {
                $errors['jumlah'] = 'Jumlah alat minimal 1 unit (aturan bisnis: tidak boleh kurang dari 1).';
            } else {
                $jumlah = (int)$input['jumlah'];
            }
        } elseif ($requestMethod === 'PUT') {
            $errors['jumlah'] = 'Field jumlah wajib disertakan untuk metode PUT.';
        }

        // Validasi Kondisi jika disertakan
        $kondisi = $existing['kondisi'];
        $validKondisi = ['Baik', 'Perlu Cek', 'Rusak'];
        if (array_key_exists('kondisi', $input)) {
            $kondisiVal = trim((string)$input['kondisi']);
            if (!in_array($kondisiVal, $validKondisi, true)) {
                $errors['kondisi'] = 'Kondisi alat harus salah satu dari: ' . implode(', ', $validKondisi) . '.';
            } else {
                $kondisi = $kondisiVal;
            }
        }

        // Validasi Lokasi jika disertakan
        $lokasi = $existing['lokasi'];
        if (array_key_exists('lokasi', $input)) {
            $lokasiVal = trim((string)$input['lokasi']);
            if ($lokasiVal === '') {
                $errors['lokasi'] = 'Field lokasi tidak boleh kosong.';
            } elseif (mb_strlen($lokasiVal) > 100) {
                $errors['lokasi'] = 'Lokasi maksimal 100 karakter.';
            } else {
                $lokasi = $lokasiVal;
            }
        } elseif ($requestMethod === 'PUT') {
            $errors['lokasi'] = 'Field lokasi wajib disertakan untuk metode PUT.';
        }

        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validasi data pembaruan alat gagal.',
                'errors' => $errors
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Query Update dengan Prepared Statement
        $updateStmt = $pdo->prepare(
            'UPDATE alat 
             SET nama = :nama, kategori = :kategori, jumlah = :jumlah, kondisi = :kondisi, lokasi = :lokasi 
             WHERE id = :id'
        );
        $updateStmt->execute([
            'nama'     => $nama,
            'kategori' => $kategori,
            'jumlah'   => $jumlah,
            'kondisi'  => $kondisi,
            'lokasi'   => $lokasi,
            'id'       => $id
        ]);

        // Ambil data terbaru setelah pembaruan
        $fetchUpdated = $pdo->prepare('SELECT id, nama, kategori, kategori AS jenis, jumlah, kondisi, lokasi, created_at, updated_at FROM alat WHERE id = :id');
        $fetchUpdated->execute(['id' => $id]);
        $updatedData = $fetchUpdated->fetch();

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'message' => "Data alat dengan ID {$id} berhasil diperbarui.",
            'data' => $updatedData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---------------------------------------------------------
    // 4. DELETE: Hapus Data dengan Pemeriksaan Keberadaan (Existence)
    // ---------------------------------------------------------
    if ($requestMethod === 'DELETE') {
        requireRole(['admin']);

        $idRaw = $_GET['id'] ?? null;

        // Jika ID tidak ada di query parameter, coba baca dari JSON body
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
                'message' => 'ID alat wajib disertakan untuk operasi hapus. Sertakan parameter ?id={id} atau payload JSON.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validasi format ID: harus berupa bilangan bulat positif
        if (!ctype_digit((string)$idRaw) || (int)$idRaw <= 0) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'code' => 400,
                'message' => 'ID alat tidak valid. ID harus berupa bilangan bulat positif.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = (int)$idRaw;

        // Pemeriksaan keberadaan data sebelum dihapus (Existence check via Prepared Statement)
        $findStmt = $pdo->prepare('SELECT id, nama FROM alat WHERE id = :id LIMIT 1');
        $findStmt->execute(['id' => $id]);
        $existing = $findStmt->fetch();

        if (!$existing) {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'code' => 404,
                'message' => "Data alat dengan ID {$id} tidak ditemukan. Operasi hapus dibatalkan."
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Eksekusi penghapusan menggunakan Prepared Statement
        $deleteStmt = $pdo->prepare('DELETE FROM alat WHERE id = :id');
        $deleteStmt->execute(['id' => $id]);

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'message' => "Data alat dengan ID {$id} ('{$existing['nama']}') berhasil dihapus dari basis data.",
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
        '[API ERROR 500] %s in %s:%d | Trace: %s',
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

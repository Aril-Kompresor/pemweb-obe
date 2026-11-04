<?php

date_default_timezone_set('Asia/Makassar');

require_once dirname(__DIR__) . '/config/database.php';

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($requestMethod === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($requestMethod !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Metode HTTP tidak diizinkan. Hanya GET yang diperbolehkan.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = db();

    // Statistik Peralatan
    $alatStmt = $pdo->query('SELECT COUNT(*) AS total_alat, COALESCE(SUM(jumlah), 0) AS total_unit FROM alat');
    $alatStats = $alatStmt->fetch();

    // Statistik Pemesanan
    $pesanStmt = $pdo->query(
        "SELECT 
            COUNT(*) AS total_pesanan,
            SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END) AS status_menunggu,
            SUM(CASE WHEN status = 'Diproses' THEN 1 ELSE 0 END) AS status_diproses,
            SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) AS status_selesai,
            SUM(CASE WHEN status = 'Dibatalkan' THEN 1 ELSE 0 END) AS status_dibatalkan
         FROM pemesanan"
    );
    $pesanStats = $pesanStmt->fetch();

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'code' => 200,
        'data' => [
            'alat' => [
                'total_ragam' => (int)($alatStats['total_alat'] ?? 0),
                'total_unit'  => (int)($alatStats['total_unit'] ?? 0)
            ],
            'pemesanan' => [
                'total_pesanan'    => (int)($pesanStats['total_pesanan'] ?? 0),
                'status_menunggu'  => (int)($pesanStats['status_menunggu'] ?? 0),
                'status_diproses'  => (int)($pesanStats['status_diproses'] ?? 0),
                'status_selesai'   => (int)($pesanStats['status_selesai'] ?? 0),
                'status_dibatalkan'=> (int)($pesanStats['status_dibatalkan'] ?? 0)
            ],
            'timestamp' => date('Y-m-d H:i:s T')
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log(sprintf('[API ERROR 500 stats.php] %s', $e->getMessage()));
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'Terjadi kesalahan internal pada server. Silakan coba beberapa saat lagi.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}


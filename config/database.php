<?php

require dirname(__DIR__) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

function envRequired(string $key): string {
    $value = $_ENV[$key] ?? '';
    if ($value === '') {
        throw new RuntimeException("Konfigurasi {$key} belum diisi pada .env");
    }
    return $value;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = $_ENV['DB_HOST'] ?? envRequired('DB_HOST');
    $name = $_ENV['DB_NAME'] ?? $_ENV['DB_DATABASE'] ?? envRequired('DB_NAME');
    $user = $_ENV['DB_USER'] ?? $_ENV['DB_USERNAME'] ?? envRequired('DB_USER');

    if (!array_key_exists('DB_PASS', $_ENV) && !array_key_exists('DB_PASSWORD', $_ENV)) {
        throw new RuntimeException('Konfigurasi DB_PASS belum didefinisikan pada .env');
    }
    $pass = (string) ($_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? '');
    $port = (string) ($_ENV['DB_PORT'] ?? '3306');

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}

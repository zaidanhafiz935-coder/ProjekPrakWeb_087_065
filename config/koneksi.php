<?php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'siaga_masyarakat');

// Base URL
define('BASE_URL', 'http://localhost/PROJECT/ProjekPrakWeb_087_065');

// Upload configuration
define('UPLOAD_REPORTS', __DIR__ . '/../uploads/reports/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024);

define('ALLOWED_IMG', [
    'image/jpeg',
    'image/png',
    'image/webp'
]);

define('ALLOWED_EXT', [
    'jpg',
    'jpeg',
    'png',
    'webp'
]);

// Database connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME
    );

    $koneksi->set_charset('utf8mb4');

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    die('Terjadi masalah koneksi database.');
}
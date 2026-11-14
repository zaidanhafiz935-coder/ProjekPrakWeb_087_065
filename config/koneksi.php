<?php

// ======================================
// DATABASE CONFIGURATION
// ======================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'siaga_masyarakat');

// ======================================
// DATABASE CONNECTION
// ======================================

$koneksi = new mysqli(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME
);

if ($koneksi->connect_error) {
    // Pada produksi, jangan tampilkan detail error ke pengguna
    error_log('Koneksi database gagal: ' . $koneksi->connect_error);
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
            <h2 style="color:#dc3545;">Koneksi Database Gagal</h2>
            <p>Sistem tidak dapat terhubung ke database. Silakan hubungi administrator.</p>
         </div>');
}

$conn = $koneksi;
$koneksi->set_charset('utf8mb4');

define('BASE_URL',        'http://localhost/PROJECT/ProjekPrakWeb_087_065');
define('UPLOAD_REPORTS',  __DIR__ . '/../uploads/reports/');
define('MAX_FILE_SIZE',   2 * 1024 * 1024); // 2 MB
define('ALLOWED_IMG',     ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);
define('ALLOWED_EXT',     ['jpg', 'jpeg', 'png', 'webp']);


?>
<?php

// ======================================
// AUTHENTICATION CONFIGURATION
// ======================================

// Memanggil konfigurasi database dan BASE_URL
require_once __DIR__ . '/../config/koneksi.php';

// Memulai session jika belum berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Batas waktu session: 30 menit
define('SESSION_TIMEOUT', 1800);


// ======================================
// SESSION TIMEOUT CHECK
// ======================================

function cekSession()
{
    // Jika belum login, tidak perlu mengecek waktu aktivitas
    if (!isset($_SESSION['user_id'])) {
        return;
    }

    // Jika waktu aktivitas belum tersedia
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return;
    }

    // Cek apakah session sudah melewati batas waktu
    if (time() - $_SESSION['last_activity'] >= SESSION_TIMEOUT) {

        // Hapus seluruh data session
        $_SESSION = [];

        // Hapus cookie session
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Hancurkan session
        session_destroy();

        // Arahkan ke login
        header('Location: ' . BASE_URL . '/login.php?pesan=session_expired');
        exit;
    }

    // Perbarui waktu aktivitas terakhir
    $_SESSION['last_activity'] = time();
}


// Jalankan pengecekan session
cekSession();


// ======================================
// LOGIN CHECK
// ======================================

function requireLogin()
{
    // Jika pengguna belum login
    if (!isset($_SESSION['user_id'])) {

        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}


// ======================================
// ROLE CHECK
// ======================================

function requireRole($role)
{
    // Pastikan pengguna sudah login
    requireLogin();

    // Pastikan role sesuai
    if ($_SESSION['role'] !== $role) {

        http_response_code(403);
        exit('403 Forbidden - Anda tidak memiliki akses ke halaman ini.');
    }
}


// ======================================
// HELPER FUNCTION
// ======================================

function sudahLogin()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isWarga()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'warga';
}

?>
<?php
/**
 * logout.php
 * Menghancurkan session dan mengarahkan pengguna ke halaman login.
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Mulai session agar bisa di-destroy
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hapus semua variabel session
session_unset();

// Hancurkan session
session_destroy();

// Hapus cookie session
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'],   $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Redirect ke halaman login
header('Location: ' . BASE_URL . '/login.php');
exit;

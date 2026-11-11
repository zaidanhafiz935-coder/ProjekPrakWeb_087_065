<?php

session_start();
require_once __DIR__ . '/../config/koneksi.php';

// Dashboard hanya dapat diakses oleh admin yang sudah login.
if (($_SESSION['role'] ?? null) !== 'admin') {
    header('Location: ' . BASE_URL . '/logout.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>

    <h1>Selamat Datang, <?= htmlspecialchars($_SESSION['username']); ?>!</h1>

    <p>Login berhasil!</p>

    <a href="<?= BASE_URL ?>/logout.php">Logout</a>

</body>
</html>
<?php

session_start();


echo "Session ID: " . session_id();

// Proteksi halaman dashboard
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
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

    <a href="logout.php">Logout</a>

</body>
</html>
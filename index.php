<?php
// 1. Hubungkan ke database
require_once "config/koneksi.php";

// 2. Query ke tabel laporan dan amankan dari error
$query = mysqli_query($conn, "SELECT * FROM laporan ORDER BY id DESC");

if (!$query) {
    die("Query Error: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaporPak</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body>
  <nav class="navbar">
    <div class="brand">
        <img src="assets/images/logo.png" alt="logo" class="logo">
        <span class="brand-text">LaporPak</span>
    </div>

    <div class="nav-actions">
        <!-- Tombol login yang berfungsi -->
        <a href="login.php" class="tombol-login" style="text-decoration:none; display:inline-block;">Login</a>
        
        <button class="hamburger-menu" id="tombolHamburger">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
    </div>
    <ul class="isi-menu" id="menuLapor">
        <li><a href="index.php">Beranda</a></li>
        <li><a href="#laporan">Daftar Laporan</a></li>
        <li><a href="#tentang">Tentang Kami</a></li>
    </ul>
</nav>

<!-- Tempat menampilkan data laporan secara berulang -->
<main style="padding: 20px;">
    <h2>Daftar Laporan</h2>
    <?php 
    if (mysqli_num_rows($query) > 0) {
        // Looping data dilakukan di sini
        while($data = mysqli_fetch_array($query)) { 
    ?>
            <div class="card-laporan" style="border: 1px solid #ccc; padding: 15px; margin-bottom: 10px; border-radius: 6px;">
                <h3><?= htmlspecialchars($data['judul'] ?? 'Tanpa Judul'); ?></h3>
                <p><?= htmlspecialchars($data['isi_laporan'] ?? ''); ?></p>
                <small>Status: <?= htmlspecialchars($data['status'] ?? 'pending'); ?></small>
            </div>
    <?php 
        } 
    } else {
        echo "<p>Belum ada data laporan.</p>";
    }
    ?>
</main>

</body>
</html>
<?php
require_once "config/koneksi.php";

$query = mysqli_query($conn, "SELECT * FROM reports ORDER BY id DESC");
if (!$query) {
    die("Query Error: " . mysqli_error($conn));
}

// Peta status -> kelas badge
$badgeStatus = [
    'menunggu'     => 'badge-menunggu',
    'pending'      => 'badge-menunggu',
    'diverifikasi' => 'badge-diverifikasi',
    'diproses'     => 'badge-diproses',
    'selesai'      => 'badge-selesai',
    'ditolak'      => 'badge-ditolak',
];
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
        <img src="assets/images/logo.png" alt="Logo LaporPak" class="logo">
        <span class="brand-text">LaporPak</span>
    </div>

    <div class="nav-actions">
        <a href="login.php" class="tombol-login">Login</a>
        <button class="hamburger-menu" id="tombolHamburger" aria-label="Buka menu" aria-expanded="false">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
    </div>

    <ul class="isi-menu" id="menuLapor">
        <li><a href="index.php" class="aktif">Beranda</a></li>
        <li><a href="#laporan">Daftar Laporan</a></li>
        <li><a href="#tentang">Tentang Kami</a></li>
    </ul>
</nav>

<main class="konten">
    <section id="laporan" class="bagian">
        <h2 class="judul-bagian">Daftar Laporan</h2>

        <?php if (mysqli_num_rows($query) > 0): ?>
            <div class="grid-laporan">
                <?php while ($data = mysqli_fetch_assoc($query)):
                    $status = strtolower($data['status'] ?? 'pending');
                    $kelas  = $badgeStatus[$status] ?? 'badge-menunggu';
                ?>
                    <article class="card-laporan">
                        <div class="laporan-header">
                            <h3 class="laporan-judul"><?= htmlspecialchars($data['judul'] ?? 'Tanpa Judul'); ?></h3>
                            <span class="badge-status <?= $kelas; ?>"><?= htmlspecialchars(ucfirst($status)); ?></span>
                        </div>
                        <p class="laporan-isi"><?= nl2br(htmlspecialchars($data['isi_laporan'] ?? '')); ?></p>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="teks-kosong">Belum ada data laporan.</p>
        <?php endif; ?>
    </section>

    <section id="tentang" class="bagian">
        <h2 class="judul-bagian">Tentang Kami</h2>
        <p>Isi deskripsi singkat tentang LaporPak di sini.</p>
    </section>
</main>

<footer class="footer">
    <small>&copy; <?= date('Y'); ?> LaporPak</small>
</footer>

</body>
</html>
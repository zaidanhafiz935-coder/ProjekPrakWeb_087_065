<!-- admin/partials/admin_sidebar.php -->
<?php
// Deteksi halaman aktif dari URL
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
function sidebarActive(string $file, string $current): string {
    return $current === $file ? 'active' : '';
}
?>
<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <i class="bi bi-speedometer2"></i> Panel Admin
  </div>
  <nav class="nav flex-column pt-2">
    <a href="<?= BASE_URL ?>/admin/dashboard.php"  class="nav-link <?= sidebarActive('dashboard.php', $currentPage) ?>">
      <i class="bi bi-house-door"></i> Dashboard
    </a>
    <a href="<?= BASE_URL ?>/admin/laporan.php"     class="nav-link <?= sidebarActive('laporan.php', $currentPage) ?>">
      <i class="bi bi-file-earmark-text"></i> Data Laporan
    </a>
    <a href="<?= BASE_URL ?>/admin/kategori.php"    class="nav-link <?= sidebarActive('kategori.php', $currentPage) ?>">
      <i class="bi bi-tags"></i> Kategori Laporan
    </a>
    <a href="<?= BASE_URL ?>/admin/peta.php"        class="nav-link <?= sidebarActive('peta.php', $currentPage) ?>">
      <i class="bi bi-map"></i> Peta Laporan
    </a>
    <a href="<?= BASE_URL ?>/admin/kegiatan.php"    class="nav-link <?= sidebarActive('kegiatan.php', $currentPage) ?>">
      <i class="bi bi-calendar-event"></i> Kegiatan Sosial
    </a>
    <a href="<?= BASE_URL ?>/admin/peserta.php"     class="nav-link <?= sidebarActive('peserta.php', $currentPage) ?>">
      <i class="bi bi-person-badge"></i> Data Relawan
    </a>
    <a href="<?= BASE_URL ?>/admin/pengguna.php"    class="nav-link <?= sidebarActive('pengguna.php', $currentPage) ?>">
      <i class="bi bi-people"></i> Data Pengguna
    </a>
    <a href="<?= BASE_URL ?>/admin/statistik.php"   class="nav-link <?= sidebarActive('statistik.php', $currentPage) ?>">
      <i class="bi bi-bar-chart-line"></i> Statistik
    </a>
    <hr class="border-secondary mx-3 my-2">
    <a href="<?= BASE_URL ?>/logout.php" class="nav-link" style="color:rgba(255,100,100,.8);">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </nav>
</div>

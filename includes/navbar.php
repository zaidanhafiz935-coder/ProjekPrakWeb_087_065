<!-- includes/navbar.php -->
<!-- Navbar utama untuk halaman publik -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom shadow-sm sticky-top">
  <div class="container">
    <!-- Logo & Brand -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/">
      <i class="bi bi-shield-check-fill fs-4 text-accent"></i>
      <span class="fw-bold">Siaga Masyarakat</span>
    </a>

    <!-- Toggle mobile -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarMain" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Menu -->
    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/"><i class="bi bi-house-door me-1"></i>Beranda</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/warga/tambah_laporan.php"><i class="bi bi-plus-circle me-1"></i>Lapor Masalah</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/warga/peta.php"><i class="bi bi-map me-1"></i>Peta Laporan</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/warga/kegiatan.php"><i class="bi bi-people me-1"></i>Kegiatan Sosial</a>
        </li>
      </ul>

      <!-- Auth buttons -->
      <div class="d-flex gap-2 align-items-center">
        <?php if (sudahLogin()): ?>
          <?php $user = penggunaLogin(); ?>
          <div class="dropdown">
            <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button"
                    data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i><?= e($user['nama']) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php if (isAdmin()): ?>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php">
                  <i class="bi bi-speedometer2 me-2"></i>Dashboard Admin</a></li>
              <?php else: ?>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/warga/dashboard.php">
                  <i class="bi bi-speedometer2 me-2"></i>Dashboard Saya</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/warga/laporan.php">
                  <i class="bi bi-file-text me-2"></i>Laporan Saya</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/warga/kegiatan_saya.php">
                  <i class="bi bi-calendar-check me-2"></i>Kegiatan Saya</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                <i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-in-right me-1"></i>Login
          </a>
          <a href="<?= BASE_URL ?>/register.php" class="btn btn-accent btn-sm">
            <i class="bi bi-person-plus me-1"></i>Daftar
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

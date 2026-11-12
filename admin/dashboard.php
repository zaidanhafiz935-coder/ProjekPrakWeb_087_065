<?php

session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/nav/navbar_admin.php';
require_once __DIR__ . '/nav/sidebar_admin.php';
require_once __DIR__ . '/../includes/auth.php';


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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin | Siaga Masyarakat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navbar Admin -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom shadow-sm sticky-top">
  <div class="container-fluid px-3">
    <button class="btn btn-link text-white me-2 d-lg-none p-0" id="btn-sidebar-toggle">
      <i class="bi bi-list fs-4"></i>
    </button>
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/admin/dashboard.php">
      <i class="bi bi-shield-check-fill text-accent"></i>
      <span class="fw-bold">Siaga Masyarakat</span>
      <span class="badge bg-accent ms-1 small">Admin</span>
    </a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <div class="dropdown">
        <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle me-1"></i><?= e($user['nama']) ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= BASE_URL ?>/"><i class="bi bi-house me-2"></i>Lihat Website</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
            <i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="dashboard-wrapper">

  <!-- SIDEBAR -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <i class="bi bi-speedometer2"></i> Panel Admin
    </div>
    <nav class="nav flex-column pt-2">
      <a href="<?= BASE_URL ?>/admin/dashboard.php"  class="nav-link active"><i class="bi bi-house-door"></i> Dashboard</a>
      <a href="<?= BASE_URL ?>/admin/laporan.php"     class="nav-link"><i class="bi bi-file-earmark-text"></i> Data Laporan</a>
      <a href="<?= BASE_URL ?>/admin/kategori.php"    class="nav-link"><i class="bi bi-tags"></i> Kategori Laporan</a>
      <a href="<?= BASE_URL ?>/admin/laporan.php?filter=riwayat" class="nav-link"><i class="bi bi-clock-history"></i> Tracking & Riwayat</a>
      <a href="<?= BASE_URL ?>/admin/peta.php"        class="nav-link"><i class="bi bi-map"></i> Peta Laporan</a>
      <a href="<?= BASE_URL ?>/admin/kegiatan.php"    class="nav-link"><i class="bi bi-calendar-event"></i> Kegiatan Sosial</a>
      <a href="<?= BASE_URL ?>/admin/peserta.php"     class="nav-link"><i class="bi bi-person-badge"></i> Data Relawan</a>
      <a href="<?= BASE_URL ?>/admin/pengguna.php"    class="nav-link"><i class="bi bi-people"></i> Data Pengguna</a>
      <a href="<?= BASE_URL ?>/admin/statistik.php"   class="nav-link"><i class="bi bi-bar-chart-line"></i> Laporan Statistik</a>
      <hr class="border-secondary mx-3 my-2">
      <a href="<?= BASE_URL ?>/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
  </div>

  <!-- KONTEN -->
  <div class="dashboard-content">
    <div id="alert-container"><?= tampilkanFlash() ?></div>

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h4 class="fw-bold mb-0">Dashboard Admin</h4>
        <small class="text-muted">Selamat datang, <?= e($user['nama']) ?> — <?= date('l, d F Y') ?></small>
      </div>
      <a href="<?= BASE_URL ?>/admin/laporan.php" class="btn btn-primary btn-sm">
        <i class="bi bi-file-earmark-text me-1"></i>Kelola Laporan
      </a>
    </div>

    <!-- Statistik Laporan -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-2">
        <div class="stat-card">
          <div class="stat-icon" style="background:#e8f5ee;"><i class="bi bi-files text-primary-custom"></i></div>
          <div class="stat-number"><?= $stat['total'] ?></div>
          <div class="stat-label">Total</div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="stat-card">
          <div class="stat-icon" style="background:#fff3cd;"><i class="bi bi-hourglass" style="color:#856404;"></i></div>
          <div class="stat-number" style="color:#856404;"><?= $stat['menunggu'] ?></div>
          <div class="stat-label">Menunggu</div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="stat-card">
          <div class="stat-icon" style="background:#cfe2ff;"><i class="bi bi-patch-check" style="color:#0a58ca;"></i></div>
          <div class="stat-number" style="color:#0a58ca;"><?= $stat['diverifikasi'] ?? 0 ?></div>
          <div class="stat-label">Diverifikasi</div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="stat-card">
          <div class="stat-icon" style="background:#fff0d3;"><i class="bi bi-gear" style="color:#fd7e14;"></i></div>
          <div class="stat-number" style="color:#fd7e14;"><?= $stat['diproses'] ?></div>
          <div class="stat-label">Diproses</div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="stat-card">
          <div class="stat-icon" style="background:#d1e7dd;"><i class="bi bi-check-circle text-success"></i></div>
          <div class="stat-number text-success"><?= $stat['selesai'] ?></div>
          <div class="stat-label">Selesai</div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="stat-card">
          <div class="stat-icon" style="background:#f8d7da;"><i class="bi bi-x-circle text-danger"></i></div>
          <div class="stat-number text-danger"><?= $stat['ditolak'] ?></div>
          <div class="stat-label">Ditolak</div>
        </div>
      </div>
    </div>

    <!-- Statistik Pengguna & Kegiatan -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon mx-auto" style="background:#e8f5ee;">
            <i class="bi bi-people-fill text-primary-custom"></i>
          </div>
          <div class="stat-number"><?= $stat['total_warga'] ?></div>
          <div class="stat-label">Total Warga Terdaftar</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon mx-auto" style="background:#e8f5ee;">
            <i class="bi bi-calendar-event-fill text-primary-custom"></i>
          </div>
          <div class="stat-number"><?= $stat['total_kegiatan'] ?></div>
          <div class="stat-label">Total Kegiatan Sosial</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon mx-auto" style="background:#e8f5ee;">
            <i class="bi bi-person-check-fill text-primary-custom"></i>
          </div>
          <div class="stat-number"><?= $stat['total_peserta'] ?></div>
          <div class="stat-label">Total Peserta Kegiatan</div>
        </div>
      </div>
    </div>

    <!-- Laporan Terbaru -->
    <div class="card card-custom">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2"></i>Laporan Masuk Terbaru</span>
        <a href="<?= BASE_URL ?>/admin/laporan.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-custom table-hover mb-0">
            <thead>
              <tr>
                <th>No. Laporan</th>
                <th>Judul</th>
                <th>Kategori</th>
                <th>Pelapor</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($laporanBaru as $lap): ?>
              <tr>
                <td><code class="small"><?= e($lap['nomor_laporan']) ?></code></td>
                <td style="max-width:180px;">
                  <span class="d-block text-truncate"><?= e($lap['judul']) ?></span>
                </td>
                <td><span class="badge bg-light text-dark border small"><?= e($lap['nama_kategori']) ?></span></td>
                <td class="small"><?= e($lap['pelapor']) ?></td>
                <td class="small text-muted"><?= formatTanggal($lap['created_at']) ?></td>
                <td>
                  <span class="badge-status <?= badgeStatus($lap['status']) ?>">
                    <?= e($lap['status']) ?>
                  </span>
                </td>
                <td>
                  <a href="<?= BASE_URL ?>/admin/detail_laporan.php?id=<?= (int)$lap['id'] ?>"
                     class="btn btn-sm btn-primary" title="Detail">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div><!-- /.dashboard-content -->
</div><!-- /.dashboard-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
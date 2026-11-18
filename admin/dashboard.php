<?php

session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';


// Dashboard hanya dapat diakses oleh admin yang sudah login.
requireRole('admin');

// Ambil data admin
$user = [
    'nama' => $_SESSION['username'] ?? 'Administrator'
];

// Ambil statistik dashboard
$stat = getStatistikLaporan($koneksi);

// Ambil 5 laporan terbaru
$queryLaporan = "
    SELECT
        r.id,
        r.nomor_laporan,
        r.judul,
        r.status,
        r.created_at,
        c.nama_kategori,
        u.nama AS pelapor

    FROM reports r

    INNER JOIN categories c
        ON r.category_id = c.id

    INNER JOIN users u
        ON r.user_id = u.id

    ORDER BY r.created_at DESC

    LIMIT 5
";

$resultLaporan = $koneksi->query($queryLaporan);

$laporanBaru = $resultLaporan->fetch_all(MYSQLI_ASSOC);

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
  <?php include __DIR__ . '/nav/navbar_admin.php'; ?>

<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="dashboard-wrapper">
   <?php include __DIR__ . '/nav/sidebar_admin.php'; ?>

  <!-- KONTEN -->
  <div class="dashboard-content">
    <div id="alert-container"><?= tampilkanFlash() ?></div>

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h4 class="fw-bold mb-0">Dashboard Admin</h4>
        <small class="text-muted">Selamat datang, <?= e($_SESSION['username'] ?? 'Admin') ?> — <?= date('l, d F Y') ?></small>
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
<!-- Script Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script Project -->
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
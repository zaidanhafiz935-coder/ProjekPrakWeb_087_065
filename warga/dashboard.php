<?php
/**
 * warga/dashboard.php
 * Dashboard utama warga — menampilkan statistik dan daftar laporan milik warga.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
paksakanRole('warga');

$user   = penggunaLogin();
$userId = $user['id'];

// Statistik laporan milik warga ini
$stmtStat = $koneksi->prepare(
    "SELECT
        COUNT(*)                           AS total,
        SUM(status = 'Menunggu')           AS menunggu,
        SUM(status IN ('Diverifikasi','Diproses')) AS diproses,
        SUM(status = 'Selesai')            AS selesai
     FROM reports WHERE user_id = ?"
);
$stmtStat->bind_param('i', $userId);
$stmtStat->execute();
$stat = $stmtStat->get_result()->fetch_assoc();
$stmtStat->close();

// Kegiatan yang diikuti warga
$stmtKeg = $koneksi->prepare(
    "SELECT COUNT(*) AS total
     FROM activity_participants
     WHERE user_id = ? AND status_pendaftaran = 'Terdaftar'"
);
$stmtKeg->bind_param('i', $userId);
$stmtKeg->execute();
$totalKegiatan = $stmtKeg->get_result()->fetch_assoc()['total'] ?? 0;
$stmtKeg->close();

// Laporan terbaru milik warga (5 terakhir)
$stmtLap = $koneksi->prepare(
    "SELECT r.id, r.nomor_laporan, r.judul, r.status, r.created_at,
            c.nama_kategori
     FROM reports r
     JOIN categories c ON r.category_id = c.id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC LIMIT 5"
);
$stmtLap->bind_param('i', $userId);
$stmtLap->execute();
$laporan = $stmtLap->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtLap->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Siaga Masyarakat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container py-4">
  <div id="alert-container"><?= tampilkanFlash() ?></div>

  <!-- Page header -->
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="fw-bold mb-0">Selamat Datang, <?= e($user['nama']) ?>!</h4>
      <p class="text-muted small mb-0">Berikut ringkasan laporan dan kegiatan Anda</p>
    </div>
    <a href="<?= BASE_URL ?>/warga/tambah_laporan.php" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i>Buat Laporan
    </a>
  </div>

  <!-- Statistik Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5ee;">
          <i class="bi bi-file-earmark-text-fill text-primary-custom"></i>
        </div>
        <div class="stat-number"><?= $stat['total'] ?? 0 ?></div>
        <div class="stat-label">Total Laporan</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon" style="background:#fff3cd;">
          <i class="bi bi-hourglass-split" style="color:#856404;"></i>
        </div>
        <div class="stat-number" style="color:#856404;"><?= $stat['menunggu'] ?? 0 ?></div>
        <div class="stat-label">Menunggu</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon" style="background:#fff0d3;">
          <i class="bi bi-gear-fill" style="color:#fd7e14;"></i>
        </div>
        <div class="stat-number" style="color:#fd7e14;"><?= $stat['diproses'] ?? 0 ?></div>
        <div class="stat-label">Diproses</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon" style="background:#d1e7dd;">
          <i class="bi bi-check-circle-fill text-success"></i>
        </div>
        <div class="stat-number text-success"><?= $stat['selesai'] ?? 0 ?></div>
        <div class="stat-label">Selesai</div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Laporan Terbaru -->
    <div class="col-lg-8">
      <div class="card card-custom">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-file-earmark-text me-2"></i>Laporan Terbaru Saya</span>
          <a href="<?= BASE_URL ?>/warga/laporan_saya.php" class="btn btn-sm btn-outline-primary">
            Lihat Semua
          </a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($laporan)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-1 d-block mb-2"></i>
              Anda belum membuat laporan.
              <div class="mt-3">
                <a href="<?= BASE_URL ?>/warga/tambah_laporan.php" class="btn btn-primary btn-sm">
                  <i class="bi bi-plus me-1"></i>Buat Laporan Pertama
                </a>
              </div>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-custom table-hover mb-0">
                <thead>
                  <tr>
                    <th>No. Laporan</th>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($laporan as $lap): ?>
                  <tr>
                    <td><code class="small"><?= e($lap['nomor_laporan']) ?></code></td>
                    <td style="max-width:160px;">
                      <span class="d-block text-truncate"><?= e($lap['judul']) ?></span>
                    </td>
                    <td><span class="badge bg-light text-dark border small"><?= e($lap['nama_kategori']) ?></span></td>
                    <td class="small text-muted"><?= formatTanggal($lap['created_at']) ?></td>
                    <td>
                      <span class="badge-status <?= badgeStatus($lap['status']) ?>">
                        <?= e($lap['status']) ?>
                      </span>
                    </td>
                    <td>
                      <a href="<?= BASE_URL ?>/warga/detail_laporan.php?id=<?= (int)$lap['id'] ?>"
                         class="btn btn-sm btn-outline-primary" title="Detail">
                        <i class="bi bi-eye"></i>
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Panel Kanan -->
    <div class="col-lg-4">
      <!-- Profil Singkat -->
      <div class="card card-custom mb-3">
        <div class="card-header"><i class="bi bi-person-circle me-2"></i>Profil Saya</div>
        <div class="card-body">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center"
                 style="width:52px;height:52px;background:var(--color-primary-l);color:var(--color-primary);font-size:1.4rem;">
              <i class="bi bi-person-fill"></i>
            </div>
            <div>
              <div class="fw-semibold"><?= e($user['nama']) ?></div>
              <div class="text-muted small"><?= e($user['email']) ?></div>
            </div>
          </div>
          <div class="d-grid">
            <a href="<?= BASE_URL ?>/warga/laporan_saya.php" class="btn btn-outline-primary btn-sm mb-2">
              <i class="bi bi-list-ul me-1"></i>Semua Laporan Saya
            </a>
            <a href="<?= BASE_URL ?>/warga/kegiatan_saya.php" class="btn btn-outline-primary btn-sm">
              <i class="bi bi-calendar-check me-1"></i>Kegiatan Saya
            </a>
          </div>
        </div>
      </div>

      <!-- Kegiatan Info -->
      <div class="card card-custom">
        <div class="card-header"><i class="bi bi-people me-2"></i>Kegiatan Diikuti</div>
        <div class="card-body text-center">
          <div class="stat-number text-primary-custom"><?= $totalKegiatan ?></div>
          <div class="stat-label mb-3">Kegiatan Terdaftar</div>
          <a href="<?= BASE_URL ?>/warga/kegiatan.php" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-search me-1"></i>Cari Kegiatan
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Aksi Cepat -->
  <div class="row g-3 mt-2">
    <div class="col-12">
      <h6 class="fw-semibold text-muted mb-3">Akses Cepat</h6>
    </div>
    <div class="col-6 col-md-3">
      <a href="<?= BASE_URL ?>/warga/tambah_laporan.php"
         class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-1">
        <i class="bi bi-plus-circle fs-4"></i>
        <span class="small">Buat Laporan</span>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="<?= BASE_URL ?>/warga/tracking.php"
         class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-1">
        <i class="bi bi-search fs-4"></i>
        <span class="small">Tracking Laporan</span>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="<?= BASE_URL ?>/warga/peta.php"
         class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-1">
        <i class="bi bi-map fs-4"></i>
        <span class="small">Peta Laporan</span>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="<?= BASE_URL ?>/warga/kegiatan.php"
         class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-1">
        <i class="bi bi-people fs-4"></i>
        <span class="small">Kegiatan Sosial</span>
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>

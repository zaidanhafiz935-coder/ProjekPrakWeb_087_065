<?php
/**
 * index.php — Landing Page Siaga Masyarakat
 * Menampilkan hero, statistik, layanan, laporan terbaru, kegiatan sosial, footer.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Ambil 6 laporan terbaru
$laporanTerbaru = [];

$res = $koneksi->query(
    "SELECT r.nomor_laporan, r.judul, r.alamat_lokasi, r.status, r.created_at,
            c.nama_kategori
     FROM reports r
     JOIN categories c ON r.category_id = c.id
     ORDER BY r.created_at DESC LIMIT 6"
);

if ($res) {
    $laporanTerbaru = $res->fetch_all(MYSQLI_ASSOC);
}

// Ambil 3 kegiatan terbaru
$kegiatanTerbaru = [];

$res2 = $koneksi->query(
    "SELECT id, nama_kegiatan, deskripsi, lokasi, tanggal, waktu, kuota, status,
            (SELECT COUNT(*) FROM activity_participants ap
             WHERE ap.activity_id = a.id
             AND ap.status_pendaftaran = 'Terdaftar') AS jml_peserta
     FROM activities a
     ORDER BY tanggal ASC LIMIT 3"
);

if ($res2) {
    $kegiatanTerbaru = $res2->fetch_all(MYSQLI_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Siaga Masyarakat — Peduli Lingkungan, Bersama Membangun Masyarakat</title>
  <meta name="description" content="Platform digital pelaporan masalah lingkungan dan fasilitas umum.">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ======================================================
     HERO SECTION
     ====================================================== -->
<section class="hero-section">
  <div class="container">
    <div class="row align-items-center gy-4">
      <div class="col-lg-7">
        <span class="badge bg-accent mb-3 px-3 py-2 rounded-pill">
          <i class="bi bi-shield-check me-1"></i>Platform Layanan Publik Digital
        </span>
        <h1 class="hero-title mb-3">
          Laporkan Masalah,<br>Wujudkan Lingkungan Lebih Baik
        </h1>
        <p class="hero-subtitle mb-4">
          Siaga Masyarakat memudahkan Anda melaporkan masalah lingkungan dan fasilitas umum,
          memantau proses penanganannya secara transparan, serta berpartisipasi dalam kegiatan sosial
          untuk membangun lingkungan yang lebih bersih dan aman.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?= BASE_URL ?>/warga/tambah_laporan.php" class="btn btn-accent btn-lg px-4 fw-semibold">
            <i class="bi bi-plus-circle-fill me-2"></i>Buat Laporan
          </a>
          <a href="<?= BASE_URL ?>/warga/peta.php" class="btn btn-outline-light btn-lg px-4 fw-semibold">
            <i class="bi bi-map-fill me-2"></i>Lihat Peta Laporan
          </a>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-flex justify-content-center">
        <!-- Ilustrasi SVG ringan -->
        <svg width="340" height="280" viewBox="0 0 340 280" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <circle cx="170" cy="140" r="120" fill="rgba(255,255,255,0.08)"/>
          <circle cx="170" cy="140" r="85"  fill="rgba(255,255,255,0.10)"/>
          <!-- Map icon -->
          <path d="M120 100 L155 90 L185 105 L220 95 L220 175 L185 185 L155 170 L120 180 Z"
                fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>
          <!-- Pin markers -->
          <circle cx="148" cy="128" r="10" fill="#f97316"/>
          <path d="M148 118 Q148 108 148 128" stroke="#f97316" stroke-width="2"/>
          <circle cx="185" cy="145" r="8"  fill="#198754"/>
          <circle cx="162" cy="160" r="9"  fill="#ffc107"/>
          <!-- People icons -->
          <circle cx="255" cy="180" r="16" fill="rgba(255,255,255,0.15)"/>
          <text x="255" y="186" text-anchor="middle" fill="white" font-size="14">👥</text>
          <circle cx="80"  cy="170" r="16" fill="rgba(255,255,255,0.15)"/>
          <text x="80"  y="176" text-anchor="middle" fill="white" font-size="14">📋</text>
        </svg>
      </div>
    </div>
  </div>
</section>

<!-- ======================================================
     STATISTIK LAPORAN
     ====================================================== -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="row g-3 justify-content-center">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#e8f5ee;">
            <i class="bi bi-file-earmark-text-fill text-primary-custom"></i>
          </div>
          <div class="stat-number"><?= number_format($stat['total'] ?? 0) ?></div>
          <div class="stat-label">Total Laporan</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#fff3cd;">
            <i class="bi bi-hourglass-split" style="color:#856404;"></i>
          </div>
          <div class="stat-number" style="color:#856404;"><?= number_format($stat['menunggu'] ?? 0) ?></div>
          <div class="stat-label">Laporan Baru</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#fff0d3;">
            <i class="bi bi-gear-fill" style="color:#fd7e14;"></i>
          </div>
          <div class="stat-number" style="color:#fd7e14;"><?= number_format($stat['diproses'] ?? 0) ?></div>
          <div class="stat-label">Sedang Diproses</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#d1e7dd;">
            <i class="bi bi-check-circle-fill text-success"></i>
          </div>
          <div class="stat-number text-success"><?= number_format($stat['selesai'] ?? 0) ?></div>
          <div class="stat-label">Laporan Selesai</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ======================================================
     INFORMASI LAYANAN
     ====================================================== -->
<section class="py-5" style="background: var(--color-bg);">
  <div class="container">
    <h2 class="section-title text-center mb-2">Layanan Kami</h2>
    <p class="text-center text-muted mb-5">Solusi digital untuk partisipasi masyarakat yang lebih baik</p>

    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="service-card">
          <div class="service-icon"><i class="bi bi-clipboard2-plus-fill"></i></div>
          <h6 class="fw-bold mb-2">Pelaporan Masalah</h6>
          <p class="text-muted small mb-0">
            Laporkan masalah lingkungan lengkap dengan foto, lokasi GPS, dan deskripsi detail hanya dalam beberapa langkah.
          </p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="service-card">
          <div class="service-icon"><i class="bi bi-search-heart"></i></div>
          <h6 class="fw-bold mb-2">Tracking Laporan</h6>
          <p class="text-muted small mb-0">
            Pantau status penanganan laporan Anda secara real-time dari "Menunggu" hingga "Selesai" dengan riwayat lengkap.
          </p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="service-card">
          <div class="service-icon"><i class="bi bi-map-fill"></i></div>
          <h6 class="fw-bold mb-2">Peta Laporan</h6>
          <p class="text-muted small mb-0">
            Lihat seluruh laporan masyarakat di peta interaktif. Identifikasi area bermasalah di sekitar Anda.
          </p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="service-card">
          <div class="service-icon"><i class="bi bi-people-fill"></i></div>
          <h6 class="fw-bold mb-2">Kegiatan Sosial</h6>
          <p class="text-muted small mb-0">
            Ikuti kegiatan kerja bakti, penghijauan, dan aksi sosial lainnya untuk membangun lingkungan bersama.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ======================================================
     LAPORAN TERBARU
     ====================================================== -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <h2 class="section-title mb-0">Laporan Terbaru</h2>
      <a href="<?= BASE_URL ?>/warga/peta.php" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-map me-1"></i>Lihat Peta
      </a>
    </div>

    <?php if (empty($laporanTerbaru)): ?>
      <div class="text-center py-4 text-muted">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>Belum ada laporan.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-custom table-hover">
          <thead>
            <tr>
              <th>No. Laporan</th>
              <th>Judul</th>
              <th>Kategori</th>
              <th>Lokasi</th>
              <th>Tanggal</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($laporanTerbaru as $lap): ?>
            <tr>
              <td><code class="small"><?= e($lap['nomor_laporan']) ?></code></td>
              <td class="fw-medium" style="max-width:200px;">
                <span class="text-truncate d-block" title="<?= e($lap['judul']) ?>"><?= e($lap['judul']) ?></span>
              </td>
              <td><span class="badge bg-light text-dark border"><?= e($lap['nama_kategori']) ?></span></td>
              <td class="text-muted small" style="max-width:150px;">
                <span class="text-truncate d-block"><?= e($lap['alamat_lokasi'] ?: '-') ?></span>
              </td>
              <td class="small text-muted"><?= formatTanggal($lap['created_at']) ?></td>
              <td>
                <span class="badge-status <?= badgeStatus($lap['status']) ?>">
                  <?= e($lap['status']) ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="text-center mt-3">
      <a href="<?= BASE_URL ?>/warga/tambah_laporan.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Buat Laporan Baru
      </a>
    </div>
  </div>
</section>

<!-- ======================================================
     KEGIATAN SOSIAL
     ====================================================== -->
<section class="py-5" style="background: var(--color-bg);">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <h2 class="section-title mb-0">Kegiatan Sosial</h2>
      <a href="<?= BASE_URL ?>/warga/kegiatan.php" class="btn btn-outline-primary btn-sm">
        Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

    <?php if (empty($kegiatanTerbaru)): ?>
      <div class="text-center py-4 text-muted">
        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>Belum ada kegiatan.
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($kegiatanTerbaru as $keg): ?>
        <div class="col-md-4">
          <div class="activity-card h-100">
            <div class="card-img-placeholder">
              <i class="bi bi-people-fill"></i>
            </div>
            <div class="p-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge <?= badgeKegiatan($keg['status']) ?> badge-status">
                  <?= e($keg['status']) ?>
                </span>
                <small class="text-muted"><?= formatTanggal($keg['tanggal']) ?></small>
              </div>
              <h6 class="fw-bold mb-1"><?= e($keg['nama_kegiatan']) ?></h6>
              <p class="text-muted small mb-2">
                <?= e(mb_substr($keg['deskripsi'] ?? '', 0, 100)) ?>...
              </p>
              <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                  <i class="bi bi-geo-alt me-1"></i><?= e(mb_substr($keg['lokasi'] ?? '-', 0, 35)) ?>
                </small>
                <small class="text-muted">
                  <i class="bi bi-people me-1"></i><?= e($keg['jml_peserta']) ?>/<?= e($keg['kuota']) ?>
                </small>
              </div>
              <a href="<?= BASE_URL ?>/warga/detail_kegiatan.php?id=<?= (int)$keg['id'] ?>"
                 class="btn btn-outline-primary btn-sm mt-3 w-100">
                <i class="bi bi-eye me-1"></i>Lihat Detail
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ======================================================
     CTA SECTION
     ====================================================== -->
<section class="py-5 bg-primary-custom text-white">
  <div class="container text-center">
    <h3 class="fw-bold mb-3">Siap Melaporkan Masalah di Sekitar Anda?</h3>
    <p class="mb-4 opacity-75">
      Bergabunglah bersama ribuan warga yang telah menggunakan Siaga Masyarakat untuk membangun lingkungan yang lebih baik.
    </p>
    <div class="d-flex flex-wrap justify-content-center gap-3">
      <a href="<?= BASE_URL ?>/register.php" class="btn btn-accent btn-lg px-5 fw-semibold">
        <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
      </a>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-light btn-lg px-5 fw-semibold">
        <i class="bi bi-box-arrow-in-right me-2"></i>Login
      </a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>

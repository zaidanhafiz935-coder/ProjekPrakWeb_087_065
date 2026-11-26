<?php
/**
 * warga/kegiatan_saya.php
 * Halaman daftar kegiatan sosial yang diikuti oleh warga.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
paksakanRole('warga');

$user   = penggunaLogin();
$userId = $user['id'];

// Ambil daftar kegiatan yang diikuti oleh user
$stmt = $koneksi->prepare(
    "SELECT ap.id AS participant_id, ap.status_pendaftaran, ap.created_at AS tgl_daftar,
            a.id AS activity_id, a.nama_kegiatan, a.deskripsi, a.foto, a.lokasi, a.tanggal, a.waktu, a.kuota,
            a.status AS status_kegiatan,
            (SELECT COUNT(*) FROM activity_participants p 
             WHERE p.activity_id = a.id AND p.status_pendaftaran = 'Terdaftar') AS jml_peserta
     FROM activity_participants ap
     JOIN activities a ON ap.activity_id = a.id
     WHERE ap.user_id = ?
     ORDER BY a.tanggal DESC"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$kegiatan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kegiatan Saya | Siaga Masyarakat</title>
  <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container py-4">
  <div id="alert-container"><?= tampilkanFlash() ?></div>

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb breadcrumb-custom">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/warga/dashboard.php">Dashboard</a></li>
      <li class="breadcrumb-item active">Kegiatan Saya</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="fw-bold mb-0">Kegiatan Sosial Saya</h4>
      <p class="text-muted small mb-0">Daftar kegiatan sosial yang pernah atau sedang Anda ikuti.</p>
    </div>
    <a href="<?= BASE_URL ?>/warga/kegiatan.php" class="btn btn-primary">
      <i class="bi bi-search me-1"></i>Cari Kegiatan Baru
    </a>
  </div>

  <?php if (empty($kegiatan)): ?>
    <div class="card card-custom">
      <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-calendar-x fs-1 d-block mb-3 text-secondary"></i>
        <h5 class="fw-semibold">Belum Ada Kegiatan</h5>
        <p class="small mb-3">Anda belum mendaftar kegiatan sosial apapun saat ini.</p>
        <a href="<?= BASE_URL ?>/warga/kegiatan.php" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-arrow-right me-1"></i>Jelajahi Kegiatan Sosial
        </a>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($kegiatan as $keg): ?>
      <div class="col-md-6 col-lg-4">
        <div class="activity-card h-100 d-flex flex-column">
          <?php if (!empty($keg['foto'])): ?>
            <img src="<?= BASE_URL ?>/uploads/activities/<?= e($keg['foto']) ?>"
                 alt="<?= e($keg['nama_kegiatan']) ?>" class="card-img-top">
          <?php else: ?>
            <div class="card-img-placeholder">
              <i class="bi bi-people-fill"></i>
            </div>
          <?php endif; ?>
          <div class="p-3 d-flex flex-column flex-grow-1">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="badge <?= badgeKegiatan($keg['status_kegiatan']) ?> badge-status">
                <?= e($keg['status_kegiatan']) ?>
              </span>
              <?php if ($keg['status_pendaftaran'] === 'Terdaftar'): ?>
                <span class="badge bg-success badge-status"><i class="bi bi-check-circle me-1"></i>Terdaftar</span>
              <?php else: ?>
                <span class="badge bg-secondary badge-status"><i class="bi bi-x-circle me-1"></i>Dibatalkan</span>
              <?php endif; ?>
            </div>

            <h6 class="fw-bold mb-2"><?= e($keg['nama_kegiatan']) ?></h6>
            <p class="text-muted small mb-3 flex-grow-1">
              <?= e(mb_substr($keg['deskripsi'] ?? '', 0, 100)) ?>...
            </p>

            <div class="small text-muted mb-1">
              <i class="bi bi-calendar3 me-1"></i><?= formatTanggal($keg['tanggal']) ?>
              &nbsp;|&nbsp;
              <i class="bi bi-clock me-1"></i><?= substr($keg['waktu'], 0, 5) ?> WIB
            </div>
            <div class="small text-muted mb-2">
              <i class="bi bi-geo-alt me-1"></i><?= e(mb_substr($keg['lokasi'] ?? '-', 0, 40)) ?>
            </div>
            <div class="small text-muted mb-3">
              <i class="bi bi-clock-history me-1"></i>Terdaftar: <?= formatTanggal($keg['tgl_daftar']) ?>
            </div>

            <div class="d-flex gap-2 mt-auto">
              <a href="<?= BASE_URL ?>/warga/detail_kegiatan.php?id=<?= (int)$keg['activity_id'] ?>"
                 class="btn btn-outline-primary btn-sm flex-fill">
                <i class="bi bi-eye me-1"></i>Detail
              </a>
              <?php if ($keg['status_pendaftaran'] === 'Terdaftar' && $keg['status_kegiatan'] === 'Akan Datang'): ?>
                <form method="POST" action="<?= BASE_URL ?>/warga/batal_kegiatan.php"
                      class="form-hapus flex-fill" data-pesan="Batalkan pendaftaran kegiatan ini?">
                  <input type="hidden" name="csrf_token" value="<?= e(buatCsrfToken()) ?>">
                  <input type="hidden" name="activity_id" value="<?= (int)$keg['activity_id'] ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                    <i class="bi bi-x-circle me-1"></i>Batal
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>

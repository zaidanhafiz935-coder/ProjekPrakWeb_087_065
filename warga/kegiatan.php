<?php
/**
 * warga/kegiatan.php
 * Daftar kegiatan sosial — publik, dapat dilihat tanpa login.
 * Warga yang login dapat mendaftar kegiatan.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();

// Ambil kegiatan
$result = $koneksi->query(
    "SELECT a.*,
            (SELECT COUNT(*) FROM activity_participants ap
             WHERE ap.activity_id = a.id AND ap.status_pendaftaran='Terdaftar') AS jml_peserta
     FROM activities a
     ORDER BY a.tanggal ASC"
);
$kegiatan = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Jika sudah login, ambil ID kegiatan yang sudah didaftar warga
$terdaftar = [];
if (sudahLogin() && isWarga()) {
    $userId = penggunaLogin()['id'];
    $stmtT  = $koneksi->prepare(
        "SELECT activity_id FROM activity_participants WHERE user_id = ? AND status_pendaftaran='Terdaftar'"
    );
    $stmtT->bind_param('i', $userId);
    $stmtT->execute();
    foreach ($stmtT->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $terdaftar[$row['activity_id']] = true;
    }
    $stmtT->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kegiatan Sosial | Siaga Masyarakat</title>
  <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container py-4">
  <div id="alert-container"><?= tampilkanFlash() ?></div>

  <h4 class="fw-bold mb-1">Kegiatan Sosial</h4>
  <p class="text-muted mb-4">Bergabunglah dalam kegiatan bersama untuk membangun lingkungan yang lebih baik.</p>

  <?php if (empty($kegiatan)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>Belum ada kegiatan sosial tersedia.
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($kegiatan as $keg): ?>
      <?php
        $penuh     = $keg['jml_peserta'] >= $keg['kuota'];
        $sudahDaftar = isset($terdaftar[$keg['id']]);
        $bisaDaftar  = $keg['status'] === 'Akan Datang' && !$penuh && !$sudahDaftar && isWarga();
      ?>
      <div class="col-md-6 col-lg-4">
        <div class="activity-card h-100">
          <?php if ($keg['foto']): ?>
            <img src="<?= BASE_URL ?>/uploads/activities/<?= e($keg['foto']) ?>"
                 alt="<?= e($keg['nama_kegiatan']) ?>" class="card-img-top">
          <?php else: ?>
            <div class="card-img-placeholder">
              <i class="bi bi-people-fill"></i>
            </div>
          <?php endif; ?>
          <div class="p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="badge <?= badgeKegiatan($keg['status']) ?> badge-status">
                <?= e($keg['status']) ?>
              </span>
              <?php if ($penuh): ?>
                <span class="badge bg-danger badge-status">Kuota Penuh</span>
              <?php endif; ?>
            </div>
            <h6 class="fw-bold mb-2"><?= e($keg['nama_kegiatan']) ?></h6>
            <p class="text-muted small mb-2">
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

            <!-- Progress kuota -->
            <div class="mb-3">
              <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Peserta</span>
                <span class="fw-semibold"><?= $keg['jml_peserta'] ?>/<?= $keg['kuota'] ?></span>
              </div>
              <div class="progress" style="height:6px;">
                <div class="progress-bar bg-success" role="progressbar"
                     style="width:<?= min(100, ($keg['kuota'] > 0 ? round($keg['jml_peserta']/$keg['kuota']*100) : 0)) ?>%"></div>
              </div>
            </div>

            <div class="d-flex gap-2">
              <a href="<?= BASE_URL ?>/warga/detail_kegiatan.php?id=<?= (int)$keg['id'] ?>"
                 class="btn btn-outline-primary btn-sm flex-fill">
                <i class="bi bi-eye me-1"></i>Detail
              </a>
              <?php if ($sudahDaftar): ?>
                <span class="btn btn-success btn-sm flex-fill disabled">
                  <i class="bi bi-check2-circle me-1"></i>Terdaftar
                </span>
              <?php elseif ($bisaDaftar): ?>
                <form method="POST" action="<?= BASE_URL ?>/warga/daftar_kegiatan.php">
                  <input type="hidden" name="csrf_token" value="<?= e(buatCsrfToken()) ?>">
                  <input type="hidden" name="activity_id" value="<?= (int)$keg['id'] ?>">
                  <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-person-plus me-1"></i>Daftar
                  </button>
                </form>
              <?php elseif (!sudahLogin()): ?>
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-secondary btn-sm flex-fill">
                  Login untuk Daftar
                </a>
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

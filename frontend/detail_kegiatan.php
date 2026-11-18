<?php
/**
 * warga/detail_kegiatan.php
 * Detail kegiatan sosial, daftar peserta (anonim), dan tombol daftar/batal.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();

$id = inputInt($_GET['id'] ?? 0);
if (!$id) { redirect(BASE_URL . '/warga/kegiatan.php'); }

$stmt = $koneksi->prepare(
    "SELECT a.*,
            (SELECT COUNT(*) FROM activity_participants ap
             WHERE ap.activity_id = a.id AND ap.status_pendaftaran='Terdaftar') AS jml_peserta
     FROM activities a WHERE a.id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$keg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$keg) { setFlash('gagal', 'Kegiatan tidak ditemukan.'); redirect(BASE_URL . '/warga/kegiatan.php'); }

$sudahDaftar = false;
$userId = null;
if (sudahLogin() && isWarga()) {
    $userId = penggunaLogin()['id'];
    $stmtC  = $koneksi->prepare(
        "SELECT id FROM activity_participants WHERE activity_id = ? AND user_id = ? AND status_pendaftaran = 'Terdaftar'"
    );
    $stmtC->bind_param('ii', $id, $userId);
    $stmtC->execute();
    $sudahDaftar = $stmtC->get_result()->num_rows > 0;
    $stmtC->close();
}

$penuh = $keg['jml_peserta'] >= $keg['kuota'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($keg['nama_kegiatan']) ?> | Siaga Masyarakat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container py-4">
  <div id="alert-container"><?= tampilkanFlash() ?></div>

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb breadcrumb-custom">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/warga/kegiatan.php">Kegiatan Sosial</a></li>
      <li class="breadcrumb-item active"><?= e($keg['nama_kegiatan']) ?></li>
    </ol>
  </nav>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card card-custom">
        <?php if ($keg['foto']): ?>
          <img src="<?= BASE_URL ?>/uploads/activities/<?= e($keg['foto']) ?>"
               alt="<?= e($keg['nama_kegiatan']) ?>" class="card-img-top" style="max-height:320px;object-fit:cover;">
        <?php endif; ?>
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between mb-3">
            <h4 class="fw-bold mb-0"><?= e($keg['nama_kegiatan']) ?></h4>
            <span class="badge <?= badgeKegiatan($keg['status']) ?> ms-2"><?= e($keg['status']) ?></span>
          </div>

          <div class="row g-2 mb-4 small">
            <div class="col-sm-6 text-muted">
              <i class="bi bi-calendar3 me-1"></i><strong>Tanggal:</strong> <?= formatTanggal($keg['tanggal']) ?>
            </div>
            <div class="col-sm-6 text-muted">
              <i class="bi bi-clock me-1"></i><strong>Waktu:</strong> <?= substr($keg['waktu'],0,5) ?> WIB
            </div>
            <div class="col-sm-6 text-muted">
              <i class="bi bi-geo-alt me-1"></i><strong>Lokasi:</strong> <?= e($keg['lokasi'] ?: '-') ?>
            </div>
            <div class="col-sm-6 text-muted">
              <i class="bi bi-people me-1"></i><strong>Peserta:</strong>
              <?= $keg['jml_peserta'] ?>/<?= $keg['kuota'] ?>
              <?php if ($penuh): ?><span class="badge bg-danger ms-1 small">Penuh</span><?php endif; ?>
            </div>
          </div>

          <div class="mb-4">
            <strong>Deskripsi Kegiatan:</strong>
            <p class="mt-2"><?= nl2br(e($keg['deskripsi'])) ?></p>
          </div>

          <!-- Peta Kegiatan -->
          <?php if ($keg['latitude'] && $keg['longitude']): ?>
          <div>
            <strong class="d-block mb-2">Lokasi di Peta:</strong>
            <div id="peta-kegiatan" style="height:220px;" class="rounded border"></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
      <div class="card card-custom mb-3">
        <div class="card-header"><i class="bi bi-person-plus me-2"></i>Pendaftaran</div>
        <div class="card-body text-center">
          <div class="mb-3">
            <div class="progress mb-2" style="height:10px;">
              <div class="progress-bar bg-success"
                   style="width:<?= $keg['kuota'] > 0 ? round($keg['jml_peserta']/$keg['kuota']*100) : 0 ?>%"></div>
            </div>
            <small class="text-muted"><?= $keg['jml_peserta'] ?> dari <?= $keg['kuota'] ?> kuota terisi</small>
          </div>

          <?php if (!sudahLogin()): ?>
            <p class="text-muted small">Login untuk mendaftar kegiatan ini.</p>
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary w-100">
              <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </a>
          <?php elseif (!isWarga()): ?>
            <p class="text-muted small">Hanya warga yang dapat mendaftar kegiatan.</p>
          <?php elseif ($sudahDaftar): ?>
            <div class="alert alert-success py-2 small">
              <i class="bi bi-check-circle me-1"></i>Anda sudah terdaftar!
            </div>
            <?php if ($keg['status'] === 'Akan Datang'): ?>
              <form method="POST" action="<?= BASE_URL ?>/warga/batal_kegiatan.php"
                    class="form-hapus" data-pesan="Batalkan pendaftaran kegiatan ini?">
                <input type="hidden" name="csrf_token" value="<?= e(buatCsrfToken()) ?>">
                <input type="hidden" name="activity_id" value="<?= (int)$keg['id'] ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                  <i class="bi bi-x-circle me-1"></i>Batalkan Pendaftaran
                </button>
              </form>
            <?php endif; ?>
          <?php elseif ($keg['status'] !== 'Akan Datang'): ?>
            <p class="text-muted small">Kegiatan sudah <?= strtolower(e($keg['status'])) ?>. Pendaftaran ditutup.</p>
          <?php elseif ($penuh): ?>
            <p class="text-muted small">Kuota peserta sudah penuh.</p>
          <?php else: ?>
            <form method="POST" action="<?= BASE_URL ?>/warga/daftar_kegiatan.php">
              <input type="hidden" name="csrf_token" value="<?= e(buatCsrfToken()) ?>">
              <input type="hidden" name="activity_id" value="<?= (int)$keg['id'] ?>">
              <button type="submit" class="btn btn-primary w-100 fw-semibold">
                <i class="bi bi-person-check me-2"></i>Daftar Sekarang
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <div class="card card-custom">
        <div class="card-header"><i class="bi bi-info-circle me-2"></i>Ringkasan</div>
        <div class="card-body small">
          <dl class="row mb-0">
            <dt class="col-5 text-muted">Tanggal</dt>
            <dd class="col-7"><?= formatTanggal($keg['tanggal']) ?></dd>
            <dt class="col-5 text-muted">Waktu</dt>
            <dd class="col-7"><?= substr($keg['waktu'],0,5) ?> WIB</dd>
            <dt class="col-5 text-muted">Kuota</dt>
            <dd class="col-7"><?= $keg['kuota'] ?> peserta</dd>
            <dt class="col-5 text-muted">Terdaftar</dt>
            <dd class="col-7"><?= $keg['jml_peserta'] ?> peserta</dd>
            <dt class="col-5 text-muted">Status</dt>
            <dd class="col-7">
              <span class="badge <?= badgeKegiatan($keg['status']) ?>"><?= e($keg['status']) ?></span>
            </dd>
          </dl>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<?php if ($keg['latitude'] && $keg['longitude']): ?>
<script>
(function(){
  const map = L.map('peta-kegiatan').setView([<?= (float)$keg['latitude'] ?>, <?= (float)$keg['longitude'] ?>], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap',maxZoom:19}).addTo(map);
  L.marker([<?= (float)$keg['latitude'] ?>, <?= (float)$keg['longitude'] ?>])
   .addTo(map).bindPopup('<?= addslashes(e($keg['nama_kegiatan'])) ?>').openPopup();
})();
</script>
<?php endif; ?>
</body>
</html>

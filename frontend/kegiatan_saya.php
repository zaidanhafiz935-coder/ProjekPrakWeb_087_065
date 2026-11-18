<?php
/**
 * warga/kegiatan_saya.php
 * Daftar kegiatan yang diikuti oleh warga yang sedang login.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
paksakanRole('warga');

$userId = penggunaLogin()['id'];

$stmt = $koneksi->prepare(
    "SELECT a.id, a.nama_kegiatan, a.lokasi, a.tanggal, a.waktu, a.status AS status_keg,
            ap.status_pendaftaran, ap.created_at AS tgl_daftar
     FROM activity_participants ap
     JOIN activities a ON ap.activity_id = a.id
     WHERE ap.user_id = ?
     ORDER BY a.tanggal ASC"
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container py-4">
  <div id="alert-container"><?= tampilkanFlash() ?></div>
  <h4 class="fw-bold mb-4">Kegiatan Saya</h4>

  <?php if (empty($kegiatan)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
      Anda belum terdaftar di kegiatan manapun.
      <div class="mt-3">
        <a href="<?= BASE_URL ?>/warga/kegiatan.php" class="btn btn-primary btn-sm">
          <i class="bi bi-search me-1"></i>Cari Kegiatan
        </a>
      </div>
    </div>
  <?php else: ?>
    <div class="card card-custom">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-custom table-hover mb-0">
            <thead>
              <tr>
                <th>Kegiatan</th>
                <th>Tanggal</th>
                <th>Lokasi</th>
                <th>Status Kegiatan</th>
                <th>Status Daftar</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($kegiatan as $keg): ?>
              <tr>
                <td class="fw-medium"><?= e($keg['nama_kegiatan']) ?></td>
                <td class="small text-muted">
                  <?= formatTanggal($keg['tanggal']) ?><br>
                  <span class="text-muted"><?= substr($keg['waktu'],0,5) ?> WIB</span>
                </td>
                <td class="small text-muted"><?= e(mb_substr($keg['lokasi'] ?? '-', 0, 40)) ?></td>
                <td>
                  <span class="badge <?= badgeKegiatan($keg['status_keg']) ?> badge-status">
                    <?= e($keg['status_keg']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($keg['status_pendaftaran'] === 'Terdaftar'): ?>
                    <span class="badge bg-success badge-status">Terdaftar</span>
                  <?php else: ?>
                    <span class="badge bg-secondary badge-status">Dibatalkan</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?= BASE_URL ?>/warga/detail_kegiatan.php?id=<?= (int)$keg['id'] ?>"
                     class="btn btn-sm btn-outline-primary">
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
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>

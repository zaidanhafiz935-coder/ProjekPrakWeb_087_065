<?php
/**
 * warga/tracking.php
 * Tracking laporan by nomor laporan — dapat digunakan tanpa login (publik).
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();

$laporan = null;
$riwayat = [];
$nomor   = trim($_GET['nomor'] ?? '');

if ($nomor) {
    // Bersihkan input
    $nomor = preg_replace('/[^A-Za-z0-9\-]/', '', $nomor);

    $stmt = $koneksi->prepare(
        "SELECT r.*, c.nama_kategori
         FROM reports r
         JOIN categories c ON r.category_id = c.id
         WHERE r.nomor_laporan = ?"
    );
    $stmt->bind_param('s', $nomor);
    $stmt->execute();
    $laporan = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($laporan) {
        $stmtH = $koneksi->prepare(
            "SELECT rh.*, u.nama AS petugas FROM report_histories rh
             LEFT JOIN users u ON rh.admin_id = u.id
             WHERE rh.report_id = ? ORDER BY rh.created_at ASC"
        );
        $stmtH->bind_param('i', $laporan['id']);
        $stmtH->execute();
        $riwayat = $stmtH->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtH->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tracking Laporan | Siaga Masyarakat</title>
  <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container py-4" style="max-width:780px;">
  <h4 class="fw-bold mb-2">Tracking Laporan</h4>
  <p class="text-muted mb-4">Masukkan nomor laporan untuk melihat status dan riwayat penanganan.</p>

  <!-- Form Tracking -->
  <div class="card card-custom mb-4">
    <div class="card-body">
      <form method="GET" class="d-flex gap-2">
        <input type="text" name="nomor" class="form-control"
               placeholder="Contoh: SM-2026-00001"
               value="<?= e($nomor) ?>" required>
        <button type="submit" class="btn btn-primary px-4">
          <i class="bi bi-search me-1"></i>Cari
        </button>
      </form>
    </div>
  </div>

  <!-- Hasil -->
  <?php if ($nomor && !$laporan): ?>
    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle me-2"></i>
      Laporan dengan nomor <strong><?= e($nomor) ?></strong> tidak ditemukan.
    </div>
  <?php endif; ?>

  <?php if ($laporan): ?>
  <div class="card card-custom mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-file-earmark-check me-2"></i><?= e($laporan['nomor_laporan']) ?></span>
      <span class="badge-status <?= badgeStatus($laporan['status']) ?>"><?= e($laporan['status']) ?></span>
    </div>
    <div class="card-body">
      <h5 class="fw-bold mb-3"><?= e($laporan['judul']) ?></h5>

      <div class="row g-2 small text-muted mb-3">
        <div class="col-sm-6">
          <i class="bi bi-tag me-1"></i><?= e($laporan['nama_kategori']) ?>
        </div>
        <div class="col-sm-6">
          <i class="bi bi-calendar me-1"></i><?= formatTanggal($laporan['created_at']) ?>
        </div>
        <?php if ($laporan['alamat_lokasi']): ?>
        <div class="col-12">
          <i class="bi bi-geo-alt me-1"></i><?= e($laporan['alamat_lokasi']) ?>
        </div>
        <?php endif; ?>
      </div>

      <p class="mb-3"><?= nl2br(e($laporan['deskripsi'])) ?></p>

      <?php if ($laporan['foto']): ?>
      <div class="mb-3">
        <img src="<?= BASE_URL ?>/uploads/reports/<?= e($laporan['foto']) ?>"
             alt="Foto Laporan" class="img-fluid rounded shadow-sm" style="max-height:200px;">
      </div>
      <?php endif; ?>

      <!-- Progress Bar Status -->
      <?php
      $urutan = ['Menunggu'=>0,'Diverifikasi'=>1,'Diproses'=>2,'Selesai'=>3,'Ditolak'=>3];
      $langkah = $urutan[$laporan['status']] ?? 0;
      $persen  = $laporan['status'] === 'Ditolak' ? 100 : ($langkah * 33);
      $colorBar = $laporan['status'] === 'Ditolak' ? 'bg-danger' : ($laporan['status'] === 'Selesai' ? 'bg-success' : 'bg-warning');
      ?>
      <div class="mt-3">
        <div class="d-flex justify-content-between small text-muted mb-1">
          <span>Menunggu</span>
          <span>Diverifikasi</span>
          <span>Diproses</span>
          <span>Selesai</span>
        </div>
        <div class="progress" style="height:10px;">
          <div class="progress-bar <?= $colorBar ?>" role="progressbar"
               style="width:<?= $persen ?>%"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Riwayat Timeline -->
  <div class="card card-custom">
    <div class="card-header"><i class="bi bi-clock-history me-2"></i>Riwayat Penanganan</div>
    <div class="card-body">
      <?php if (empty($riwayat)): ?>
        <p class="text-muted small">Belum ada riwayat penanganan.</p>
      <?php else: ?>
        <div class="timeline">
          <?php foreach ($riwayat as $i => $rw): ?>
          <div class="timeline-item <?= $i === array_key_last($riwayat) ? 'aktif' : '' ?>">
            <div class="timeline-date"><?= formatWaktu($rw['created_at']) ?></div>
            <div class="timeline-content mt-1">
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge-status <?= badgeStatus($rw['status']) ?>"><?= e($rw['status']) ?></span>
                <?php if ($rw['petugas']): ?>
                  <small class="text-muted">oleh <?= e($rw['petugas']) ?></small>
                <?php endif; ?>
              </div>
              <?php if ($rw['catatan']): ?>
                <p class="mb-0 small"><?= nl2br(e($rw['catatan'])) ?></p>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>

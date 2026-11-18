<?php
/**
 * warga/detail_laporan.php
 * Detail laporan warga dengan timeline riwayat penanganan.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
requireRole('warga');

$user   = penggunaLogin();
$userId = $user['id'];
$id     = inputInt($_GET['id'] ?? 0);

if (!$id) { redirect(BASE_URL . '/warga/laporan_saya.php'); }

// Admin boleh lihat semua; warga hanya miliknya
if (isAdmin()) {
    $stmt = $koneksi->prepare(
        "SELECT r.*, c.nama_kategori, u.nama AS pelapor
         FROM reports r
         JOIN categories c ON r.category_id = c.id
         JOIN users u ON r.user_id = u.id
         WHERE r.id = ?"
    );
    $stmt->bind_param('i', $id);
} else {
    $stmt = $koneksi->prepare(
        "SELECT r.*, c.nama_kategori, u.nama AS pelapor
         FROM reports r
         JOIN categories c ON r.category_id = c.id
         JOIN users u ON r.user_id = u.id
         WHERE r.id = ? AND r.user_id = ?"
    );
    $stmt->bind_param('ii', $id, $userId);
}
$stmt->execute();
$laporan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$laporan) {
    setFlash('gagal', 'Laporan tidak ditemukan.');
    redirect(isAdmin() ? BASE_URL . '/admin/laporan.php' : BASE_URL . '/warga/laporan_saya.php');
}

// Riwayat penanganan
$stmtH = $koneksi->prepare(
    "SELECT rh.*, u.nama AS petugas
     FROM report_histories rh
     LEFT JOIN users u ON rh.admin_id = u.id
     WHERE rh.report_id = ?
     ORDER BY rh.created_at ASC"
);
$stmtH->bind_param('i', $id);
$stmtH->execute();
$riwayat = $stmtH->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtH->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Laporan <?= e($laporan['nomor_laporan']) ?> | Siaga Masyarakat</title>
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
      <?php if (isAdmin()): ?>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/laporan.php">Data Laporan</a></li>
      <?php else: ?>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/warga/laporan_saya.php">Laporan Saya</a></li>
      <?php endif; ?>
      <li class="breadcrumb-item active"><?= e($laporan['nomor_laporan']) ?></li>
    </ol>
  </nav>

  <div class="row g-4">
    <!-- Detail Laporan -->
    <div class="col-lg-8">
      <div class="card card-custom mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-file-earmark-text me-2"></i><?= e($laporan['nomor_laporan']) ?></span>
          <span class="badge-status <?= badgeStatus($laporan['status']) ?>">
            <?= e($laporan['status']) ?>
          </span>
        </div>
        <div class="card-body">
          <h5 class="fw-bold mb-3"><?= e($laporan['judul']) ?></h5>

          <div class="row g-2 mb-3 small text-muted">
            <div class="col-sm-6">
              <i class="bi bi-tag me-1"></i><strong>Kategori:</strong> <?= e($laporan['nama_kategori']) ?>
            </div>
            <div class="col-sm-6">
              <i class="bi bi-calendar me-1"></i><strong>Tanggal:</strong> <?= formatWaktu($laporan['created_at']) ?>
            </div>
            <div class="col-sm-6">
              <i class="bi bi-person me-1"></i><strong>Pelapor:</strong> <?= e($laporan['pelapor']) ?>
            </div>
            <?php if ($laporan['alamat_lokasi']): ?>
            <div class="col-12">
              <i class="bi bi-geo-alt me-1"></i><strong>Lokasi:</strong> <?= e($laporan['alamat_lokasi']) ?>
            </div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <strong class="small">Deskripsi Masalah:</strong>
            <p class="mt-1 mb-0"><?= nl2br(e($laporan['deskripsi'])) ?></p>
          </div>

          <!-- Foto -->
          <?php if ($laporan['foto']): ?>
          <div class="mb-3">
            <strong class="small d-block mb-2">Foto Bukti:</strong>
            <a href="<?= BASE_URL ?>/uploads/reports/<?= e($laporan['foto']) ?>"
               target="_blank" title="Klik untuk perbesar">
              <img src="<?= BASE_URL ?>/uploads/reports/<?= e($laporan['foto']) ?>"
                   alt="Foto laporan" class="img-fluid rounded shadow-sm"
                   style="max-height:300px;object-fit:cover;">
            </a>
          </div>
          <?php endif; ?>

          <!-- Peta -->
          <?php if ($laporan['latitude'] && $laporan['longitude']): ?>
          <div class="mb-3">
            <strong class="small d-block mb-2">Lokasi di Peta:</strong>
            <div id="peta-detail" style="height:250px;" class="rounded border"></div>
          </div>
          <?php endif; ?>

          <!-- Tombol Aksi Warga -->
          <?php if (isWarga() && $laporan['status'] === 'Menunggu'): ?>
          <div class="d-flex gap-2 mt-3">
            <a href="<?= BASE_URL ?>/warga/edit_laporan.php?id=<?= (int)$laporan['id'] ?>"
               class="btn btn-warning btn-sm">
              <i class="bi bi-pencil me-1"></i>Edit Laporan
            </a>
            <form method="POST" action="<?= BASE_URL ?>/warga/hapus_laporan.php"
                  class="form-hapus"
                  data-pesan="Hapus laporan ini? Tindakan tidak dapat dibatalkan.">
              <input type="hidden" name="csrf_token" value="<?= e(buatCsrfToken()) ?>">
              <input type="hidden" name="id" value="<?= (int)$laporan['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">
                <i class="bi bi-trash me-1"></i>Hapus
              </button>
            </form>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Timeline Riwayat -->
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
                    <span class="badge-status <?= badgeStatus($rw['status']) ?>">
                      <?= e($rw['status']) ?>
                    </span>
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
    </div>

    <!-- Sidebar Info -->
    <div class="col-lg-4">
      <div class="card card-custom">
        <div class="card-header"><i class="bi bi-info-circle me-2"></i>Informasi Laporan</div>
        <div class="card-body small">
          <dl class="row mb-0">
            <dt class="col-5 text-muted">No. Laporan</dt>
            <dd class="col-7"><code><?= e($laporan['nomor_laporan']) ?></code></dd>
            <dt class="col-5 text-muted">Status</dt>
            <dd class="col-7">
              <span class="badge-status <?= badgeStatus($laporan['status']) ?>">
                <?= e($laporan['status']) ?>
              </span>
            </dd>
            <dt class="col-5 text-muted">Kategori</dt>
            <dd class="col-7"><?= e($laporan['nama_kategori']) ?></dd>
            <dt class="col-5 text-muted">Dibuat</dt>
            <dd class="col-7"><?= formatTanggal($laporan['created_at']) ?></dd>
            <dt class="col-5 text-muted">Diperbarui</dt>
            <dd class="col-7"><?= formatTanggal($laporan['updated_at']) ?></dd>
            <?php if ($laporan['latitude']): ?>
            <dt class="col-5 text-muted">Koordinat</dt>
            <dd class="col-7">
              <?= number_format((float)$laporan['latitude'],6) ?>,
              <?= number_format((float)$laporan['longitude'],6) ?>
            </dd>
            <?php endif; ?>
          </dl>
        </div>
      </div>

      <div class="mt-3">
        <?php if (isAdmin()): ?>
          <a href="<?= BASE_URL ?>/admin/laporan.php" class="btn btn-outline-secondary w-100">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
          </a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/warga/laporan_saya.php" class="btn btn-outline-secondary w-100">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Laporan Saya
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<?php if ($laporan['latitude'] && $laporan['longitude']): ?>
<script>
(function() {
  const lat = <?= (float)$laporan['latitude'] ?>;
  const lng = <?= (float)$laporan['longitude'] ?>;
  const map = L.map('peta-detail').setView([lat, lng], 16);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap',
    maxZoom: 19
  }).addTo(map);
  L.marker([lat, lng])
    .addTo(map)
    .bindPopup('<?= addslashes(e($laporan['judul'])) ?>');
})();
</script>
<?php endif; ?>
</body>
</html>

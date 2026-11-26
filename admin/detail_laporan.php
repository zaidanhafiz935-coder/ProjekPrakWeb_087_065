<?php
/**
 * admin/detail_laporan.php
 * Detail laporan untuk admin — menampilkan data, foto, peta, riwayat, dan form ubah status.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
paksakanRole('admin');

$user   = penggunaLogin();
$adminId = $user['id'];
$id      = inputInt($_GET['id'] ?? 0);

if (!$id) { setFlash('gagal', 'ID tidak valid.'); redirect(BASE_URL . '/admin/laporan.php'); }

// Ambil laporan
$stmt = $koneksi->prepare(
    "SELECT r.*, c.nama_kategori, u.nama AS pelapor, u.email AS email_pelapor, u.no_telepon
     FROM reports r
     JOIN categories c ON r.category_id = c.id
     JOIN users u ON r.user_id = u.id
     WHERE r.id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$laporan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$laporan) { setFlash('gagal', 'Laporan tidak ditemukan.'); redirect(BASE_URL . '/admin/laporan.php'); }

// Riwayat penanganan
$stmtH = $koneksi->prepare(
    "SELECT rh.*, u.nama AS petugas FROM report_histories rh
     LEFT JOIN users u ON rh.admin_id = u.id
     WHERE rh.report_id = ? ORDER BY rh.created_at ASC"
);
$stmtH->bind_param('i', $id);
$stmtH->execute();
$riwayat = $stmtH->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtH->close();

// Proses form ubah status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();

    $statusBaru = trim($_POST['status_baru'] ?? '');
    $catatan    = trim($_POST['catatan']     ?? '');

    $statusValid = ['Menunggu','Diverifikasi','Diproses','Selesai','Ditolak'];
    if (!in_array($statusBaru, $statusValid, true)) {
        setFlash('gagal', 'Status tidak valid.');
        redirect(BASE_URL . '/admin/detail_laporan.php?id=' . $id);
    }

    // Update status laporan
    $stmtU = $koneksi->prepare("UPDATE reports SET status = ? WHERE id = ?");
    $stmtU->bind_param('si', $statusBaru, $id);
    $stmtU->execute();
    $stmtU->close();

    // Simpan riwayat
    $stmtRH = $koneksi->prepare(
        "INSERT INTO report_histories (report_id, admin_id, status, catatan) VALUES (?,?,?,?)"
    );
    $catatanFinal = $catatan ?: "Status diubah menjadi $statusBaru.";
    $stmtRH->bind_param('iiss', $id, $adminId, $statusBaru, $catatanFinal);
    $stmtRH->execute();
    $stmtRH->close();

    setFlash('sukses', "Status laporan berhasil diubah menjadi \"$statusBaru\".");
    redirect(BASE_URL . '/admin/detail_laporan.php?id=' . $id);
}

$csrfToken = buatCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Laporan <?= e($laporan['nomor_laporan']) ?> | Admin</title>
  <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/nav/navbar_admin.php'; ?>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="dashboard-wrapper">
  <?php include __DIR__ . '/nav/sidebar_admin.php'; ?>
  <div class="dashboard-content">
    <div id="alert-container"><?= tampilkanFlash() ?></div>

    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/laporan.php">Data Laporan</a></li>
        <li class="breadcrumb-item active"><?= e($laporan['nomor_laporan']) ?></li>
      </ol>
    </nav>

    <div class="row g-4">
      <!-- Kolom Kiri: Detail + Riwayat -->
      <div class="col-lg-8">
        <!-- Detail Laporan -->
        <div class="card card-custom mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-file-earmark-text me-2"></i><?= e($laporan['nomor_laporan']) ?></span>
            <span class="badge-status <?= badgeStatus($laporan['status']) ?>"><?= e($laporan['status']) ?></span>
          </div>
          <div class="card-body">
            <h5 class="fw-bold mb-3"><?= e($laporan['judul']) ?></h5>

            <div class="row g-2 mb-3 small">
              <div class="col-sm-6 text-muted">
                <i class="bi bi-tag me-1"></i><strong>Kategori:</strong> <?= e($laporan['nama_kategori']) ?>
              </div>
              <div class="col-sm-6 text-muted">
                <i class="bi bi-calendar me-1"></i><strong>Dibuat:</strong> <?= formatWaktu($laporan['created_at']) ?>
              </div>
              <div class="col-sm-6 text-muted">
                <i class="bi bi-person me-1"></i><strong>Pelapor:</strong> <?= e($laporan['pelapor']) ?>
              </div>
              <div class="col-sm-6 text-muted">
                <i class="bi bi-telephone me-1"></i><strong>Telepon:</strong> <?= e($laporan['no_telepon'] ?: '-') ?>
              </div>
              <?php if ($laporan['alamat_lokasi']): ?>
              <div class="col-12 text-muted">
                <i class="bi bi-geo-alt me-1"></i><strong>Lokasi:</strong> <?= e($laporan['alamat_lokasi']) ?>
              </div>
              <?php endif; ?>
            </div>

            <div class="mb-3">
              <strong class="small">Deskripsi:</strong>
              <p class="mt-1 mb-0"><?= nl2br(e($laporan['deskripsi'])) ?></p>
            </div>

            <?php if ($laporan['foto']): ?>
            <div class="mb-3">
              <strong class="small d-block mb-2">Foto Bukti:</strong>
              <a href="<?= BASE_URL ?>/uploads/reports/<?= e($laporan['foto']) ?>" target="_blank">
                <img src="<?= BASE_URL ?>/uploads/reports/<?= e($laporan['foto']) ?>"
                     alt="Foto" class="img-fluid rounded shadow-sm" style="max-height:280px;">
              </a>
            </div>
            <?php endif; ?>

            <?php if ($laporan['latitude'] && $laporan['longitude']): ?>
            <div>
              <strong class="small d-block mb-2">Lokasi di Peta:</strong>
              <div id="peta-admin" style="height:250px;" class="rounded border"></div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Riwayat Timeline -->
        <div class="card card-custom">
          <div class="card-header"><i class="bi bi-clock-history me-2"></i>Riwayat Penanganan</div>
          <div class="card-body">
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
          </div>
        </div>
      </div>

      <!-- Kolom Kanan: Form Ubah Status + Info -->
      <div class="col-lg-4">

        <!-- Form Ubah Status -->
        <div class="card card-custom mb-3">
          <div class="card-header bg-warning text-dark">
            <i class="bi bi-pencil-square me-2"></i>Perbarui Status Laporan
          </div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

              <div class="mb-3">
                <label class="form-label">Status Saat Ini</label>
                <div>
                  <span class="badge-status <?= badgeStatus($laporan['status']) ?>">
                    <?= e($laporan['status']) ?>
                  </span>
                </div>
              </div>

              <div class="mb-3">
                <label for="status_baru" class="form-label">Status Baru <span class="text-danger">*</span></label>
                <select id="status_baru" name="status_baru" class="form-select" required>
                  <option value="">-- Pilih Status --</option>
                  <?php foreach (['Menunggu','Diverifikasi','Diproses','Selesai','Ditolak'] as $s): ?>
                    <option value="<?= $s ?>" <?= $laporan['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-3">
                <label for="catatan" class="form-label">Catatan Penanganan</label>
                <textarea id="catatan" name="catatan" rows="3" class="form-control"
                          placeholder="Tulis catatan atau informasi penanganan..."></textarea>
              </div>

              <button type="submit" class="btn btn-warning w-100 fw-semibold">
                <i class="bi bi-save me-2"></i>Simpan Perubahan
              </button>
            </form>
          </div>
        </div>

        <!-- Info Singkat -->
        <div class="card card-custom">
          <div class="card-header"><i class="bi bi-info-circle me-2"></i>Info Laporan</div>
          <div class="card-body small">
            <dl class="row mb-0">
              <dt class="col-5 text-muted">No. Laporan</dt>
              <dd class="col-7"><code><?= e($laporan['nomor_laporan']) ?></code></dd>
              <dt class="col-5 text-muted">Pelapor</dt>
              <dd class="col-7"><?= e($laporan['pelapor']) ?></dd>
              <dt class="col-5 text-muted">Email</dt>
              <dd class="col-7"><?= e($laporan['email_pelapor']) ?></dd>
              <dt class="col-5 text-muted">Dibuat</dt>
              <dd class="col-7"><?= formatTanggal($laporan['created_at']) ?></dd>
              <dt class="col-5 text-muted">Diperbarui</dt>
              <dd class="col-7"><?= formatTanggal($laporan['updated_at']) ?></dd>
            </dl>
          </div>
        </div>

        <div class="mt-3">
          <a href="<?= BASE_URL ?>/admin/laporan.php" class="btn btn-outline-secondary w-100">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<?php if ($laporan['latitude'] && $laporan['longitude']): ?>
<script>
(function(){
  const map = L.map('peta-admin').setView([<?= (float)$laporan['latitude'] ?>, <?= (float)$laporan['longitude'] ?>], 16);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {attribution:'© OpenStreetMap', maxZoom:19}).addTo(map);
  L.marker([<?= (float)$laporan['latitude'] ?>, <?= (float)$laporan['longitude'] ?>])
   .addTo(map).bindPopup('<?= addslashes(e($laporan['judul'])) ?>').openPopup();
})();
</script>
<?php endif; ?>
</body>
</html>

<?php
/**
 * admin/peserta.php
 * Daftar peserta kegiatan sosial.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
paksakanRole('admin');

$id = inputInt($_GET['id'] ?? 0);
$keg = null;

if ($id) {
    $stmt = $koneksi->prepare("SELECT * FROM activities WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $keg = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Jika ada ID kegiatan, ambil peserta kegiatan tersebut
// Jika tidak ada, tampilkan semua relawan yang pernah mendaftar
if ($keg) {
    $stmtP = $koneksi->prepare(
        "SELECT u.nama, u.email, u.no_telepon, ap.status_pendaftaran, ap.created_at,
                a.nama_kegiatan
         FROM activity_participants ap
         JOIN users u ON ap.user_id = u.id
         JOIN activities a ON ap.activity_id = a.id
         WHERE ap.activity_id = ?
         ORDER BY ap.created_at ASC"
    );
    $stmtP->bind_param('i', $id);
} else {
    $stmtP = $koneksi->prepare(
        "SELECT u.nama, u.email, u.no_telepon, ap.status_pendaftaran, ap.created_at,
                a.nama_kegiatan
         FROM activity_participants ap
         JOIN users u ON ap.user_id = u.id
         JOIN activities a ON ap.activity_id = a.id
         ORDER BY ap.created_at DESC"
    );
}
$stmtP->execute();
$peserta = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtP->close();

$total   = count($peserta);
$terdaftar = count(array_filter($peserta, fn($p) => $p['status_pendaftaran'] === 'Terdaftar'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Relawan | Admin Siaga Masyarakat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/nav/navbar_admin.php'; ?>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="dashboard-wrapper">
  <?php include __DIR__ . '/nav/sidebar_admin.php'; ?>
  <div class="dashboard-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-0">
          <?= $keg ? 'Peserta: ' . e($keg['nama_kegiatan']) : 'Data Relawan' ?>
        </h4>
        <small class="text-muted"><?= $terdaftar ?> aktif dari <?= $total ?> total pendaftaran</small>
      </div>
      <a href="<?= BASE_URL ?>/admin/kegiatan.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kegiatan
      </a>
    </div>

    <div class="card card-custom">
      <div class="card-body p-0">
        <?php if (empty($peserta)): ?>
          <div class="text-center py-5 text-muted"><i class="bi bi-person-x fs-1 d-block mb-2"></i>Belum ada peserta.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-custom table-hover mb-0">
            <thead><tr>
              <th>#</th>
              <th>Nama</th>
              <th>Email</th>
              <th>No. Telepon</th>
              <?php if (!$keg): ?><th>Kegiatan</th><?php endif; ?>
              <th>Tgl. Daftar</th>
              <th>Status</th>
            </tr></thead>
            <tbody>
              <?php foreach ($peserta as $i => $p): ?>
              <tr>
                <td class="text-muted small"><?= $i + 1 ?></td>
                <td class="fw-medium"><?= e($p['nama']) ?></td>
                <td class="small"><?= e($p['email']) ?></td>
                <td class="small"><?= e($p['no_telepon'] ?: '-') ?></td>
                <?php if (!$keg): ?><td class="small"><?= e($p['nama_kegiatan']) ?></td><?php endif; ?>
                <td class="small text-muted"><?= formatTanggal($p['created_at']) ?></td>
                <td>
                  <?php if ($p['status_pendaftaran'] === 'Terdaftar'): ?>
                    <span class="badge bg-success badge-status">Terdaftar</span>
                  <?php else: ?>
                    <span class="badge bg-secondary badge-status">Dibatalkan</span>
                  <?php endif; ?>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>

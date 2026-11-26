<?php
/**
 * warga/laporan.php
 * Daftar laporan milik warga — dengan paginasi, filter status, dan tombol aksi.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
paksakanRole('warga');

$user   = penggunaLogin();
$userId = $user['id'];

// Filter & paginasi
$filterStatus = $_GET['status'] ?? '';
$halaman      = max(1, inputInt($_GET['hal'] ?? 1));
$perHalaman   = 10;
$offset       = ($halaman - 1) * $perHalaman;

// Validasi filter status
$statusValid = ['','Menunggu','Diverifikasi','Diproses','Selesai','Ditolak'];
if (!in_array($filterStatus, $statusValid, true)) $filterStatus = '';

// Hitung total
$whereStatus = $filterStatus ? " AND r.status = '$filterStatus'" : '';
$stmtTotal   = $koneksi->prepare(
    "SELECT COUNT(*) FROM reports r WHERE r.user_id = ? $whereStatus"
);
$stmtTotal->bind_param('i', $userId);
$stmtTotal->execute();
$total       = $stmtTotal->get_result()->fetch_row()[0] ?? 0;
$stmtTotal->close();
$totalHalaman = max(1, (int)ceil($total / $perHalaman));

// Query laporan
$sql = "SELECT r.id, r.nomor_laporan, r.judul, r.status, r.created_at, c.nama_kategori
        FROM reports r
        JOIN categories c ON r.category_id = c.id
        WHERE r.user_id = ? $whereStatus
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?";
$stmtLap = $koneksi->prepare($sql);
$stmtLap->bind_param('iii', $userId, $perHalaman, $offset);
$stmtLap->execute();
$laporan = $stmtLap->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtLap->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan Saya | Siaga Masyarakat</title>
  <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container py-4">
  <div id="alert-container"><?= tampilkanFlash() ?></div>

  <div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">Laporan Saya</h4>
    <a href="<?= BASE_URL ?>/warga/tambah_laporan.php" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i>Buat Laporan
    </a>
  </div>

  <!-- Filter -->
  <div class="card card-custom mb-4">
    <div class="card-body py-3">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label small">Filter Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">Semua Status</option>
            <?php foreach (['Menunggu','Diverifikasi','Diproses','Selesai','Ditolak'] as $s): ?>
              <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-filter me-1"></i>Filter
          </button>
          <a href="<?= BASE_URL ?>/warga/laporan_saya.php" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel -->
  <div class="card card-custom">
    <div class="card-header">
      <i class="bi bi-file-earmark-text me-2"></i>
      Daftar Laporan
      <span class="badge bg-secondary ms-1"><?= $total ?></span>
    </div>
    <div class="card-body p-0">
      <?php if (empty($laporan)): ?>
        <div class="text-center py-5 text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-2"></i>
          <?= $filterStatus ? "Tidak ada laporan dengan status \"$filterStatus\"." : 'Anda belum memiliki laporan.' ?>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-custom table-hover mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>No. Laporan</th>
                <th>Judul</th>
                <th>Kategori</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($laporan as $i => $lap): ?>
              <tr>
                <td class="text-muted small"><?= $offset + $i + 1 ?></td>
                <td><code class="small"><?= e($lap['nomor_laporan']) ?></code></td>
                <td style="max-width:200px;">
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
                  <div class="btn-group btn-group-sm">
                    <a href="<?= BASE_URL ?>/warga/detail_laporan.php?id=<?= (int)$lap['id'] ?>"
                       class="btn btn-outline-primary" title="Detail"><i class="bi bi-eye"></i></a>
                    <?php if ($lap['status'] === 'Menunggu'): ?>
                      <a href="<?= BASE_URL ?>/warga/edit_laporan.php?id=<?= (int)$lap['id'] ?>"
                         class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                      <form method="POST" action="<?= BASE_URL ?>/warga/hapus_laporan.php"
                            class="form-hapus d-inline"
                            data-pesan="Hapus laporan ini? Tindakan tidak dapat dibatalkan.">
                        <input type="hidden" name="csrf_token" value="<?= e(buatCsrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$lap['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger" title="Hapus">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Paginasi -->
        <?php if ($totalHalaman > 1): ?>
        <div class="d-flex justify-content-center py-3">
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <?php for ($p = 1; $p <= $totalHalaman; $p++): ?>
                <li class="page-item <?= $p === $halaman ? 'active' : '' ?>">
                  <a class="page-link"
                     href="?status=<?= urlencode($filterStatus) ?>&hal=<?= $p ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>

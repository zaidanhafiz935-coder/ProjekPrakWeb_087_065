<?php
/**
 * admin/laporan.php
 * Manajemen laporan — daftar semua laporan, filter, pencarian, paginasi.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
paksakanRole('admin');

$user = penggunaLogin();

// Filter
$filterStatus   = $_GET['status']   ?? '';
$filterKategori = inputInt($_GET['kategori'] ?? 0);
$cari           = trim($_GET['cari'] ?? '');
$halaman        = max(1, inputInt($_GET['hal'] ?? 1));
$perHalaman     = 15;
$offset         = ($halaman - 1) * $perHalaman;

// Validasi
$statusValid = ['','Menunggu','Diverifikasi','Diproses','Selesai','Ditolak'];
if (!in_array($filterStatus, $statusValid, true)) $filterStatus = '';

// Bangun WHERE clause
$where  = "WHERE 1=1";
$params = [];
$types  = '';

if ($filterStatus)   { $where .= " AND r.status = ?";          $params[] = $filterStatus;   $types .= 's'; }
if ($filterKategori) { $where .= " AND r.category_id = ?";     $params[] = $filterKategori; $types .= 'i'; }
if ($cari)           { $where .= " AND (r.judul LIKE ? OR r.nomor_laporan LIKE ? OR u.nama LIKE ?)";
                       $l = '%'.$cari.'%'; $params[] = $l; $params[] = $l; $params[] = $l; $types .= 'sss'; }

// Total
$stmtT = $koneksi->prepare(
    "SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id = u.id $where"
);
if ($types) { $stmtT->bind_param($types, ...$params); }
$stmtT->execute();
$total        = $stmtT->get_result()->fetch_row()[0] ?? 0;
$stmtT->close();
$totalHalaman = max(1, (int)ceil($total / $perHalaman));

// Query
$paramsPage = array_merge($params, [$perHalaman, $offset]);
$typesPage  = $types . 'ii';
$stmtL = $koneksi->prepare(
    "SELECT r.id, r.nomor_laporan, r.judul, r.status, r.created_at,
            u.nama AS pelapor, c.nama_kategori
     FROM reports r
     JOIN users u ON r.user_id = u.id
     JOIN categories c ON r.category_id = c.id
     $where
     ORDER BY r.created_at DESC LIMIT ? OFFSET ?"
);
$stmtL->bind_param($typesPage, ...$paramsPage);
$stmtL->execute();
$laporan = $stmtL->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtL->close();

// Kategori untuk filter
$kategoriList = $koneksi->query("SELECT id, nama_kategori FROM categories ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Laporan | Admin Siaga Masyarakat</title>
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
    <div id="alert-container"><?= tampilkanFlash() ?></div>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold mb-0">Data Laporan</h4>
    </div>

    <!-- Filter -->
    <div class="card card-custom mb-4">
      <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
          <div class="col-md-3">
            <input type="text" name="cari" class="form-control form-control-sm"
                   placeholder="Cari judul/nomor/pelapor..." value="<?= e($cari) ?>">
          </div>
          <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
              <option value="">Semua Status</option>
              <?php foreach (['Menunggu','Diverifikasi','Diproses','Selesai','Ditolak'] as $s): ?>
                <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <select name="kategori" class="form-select form-select-sm">
              <option value="0">Semua Kategori</option>
              <?php foreach ($kategoriList as $kat): ?>
                <option value="<?= (int)$kat['id'] ?>"
                        <?= $filterKategori === (int)$kat['id'] ? 'selected' : '' ?>>
                  <?= e($kat['nama_kategori']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="bi bi-search me-1"></i>Cari
            </button>
            <a href="<?= BASE_URL ?>/admin/laporan.php" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel -->
    <div class="card card-custom">
      <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-file-earmark-text me-2"></i>Daftar Laporan
          <span class="badge bg-secondary ms-1"><?= $total ?></span>
        </span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($laporan)): ?>
          <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>Tidak ada laporan ditemukan.
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
                  <th>Pelapor</th>
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
                  <td style="max-width:180px;">
                    <span class="d-block text-truncate" title="<?= e($lap['judul']) ?>"><?= e($lap['judul']) ?></span>
                  </td>
                  <td><span class="badge bg-light text-dark border small"><?= e($lap['nama_kategori']) ?></span></td>
                  <td class="small"><?= e($lap['pelapor']) ?></td>
                  <td class="small text-muted"><?= formatTanggal($lap['created_at']) ?></td>
                  <td>
                    <span class="badge-status <?= badgeStatus($lap['status']) ?>">
                      <?= e($lap['status']) ?>
                    </span>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <a href="<?= BASE_URL ?>/admin/detail_laporan.php?id=<?= (int)$lap['id'] ?>"
                         class="btn btn-primary" title="Detail & Ubah Status">
                        <i class="bi bi-eye"></i>
                      </a>
                      <form method="POST" action="<?= BASE_URL ?>/admin/hapus_laporan.php"
                            class="form-hapus d-inline"
                            data-pesan="Hapus laporan ini secara permanen?">
                        <input type="hidden" name="csrf_token" value="<?= e(buatCsrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$lap['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger" title="Hapus">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <?php if ($totalHalaman > 1): ?>
          <div class="d-flex justify-content-center py-3">
            <nav>
              <ul class="pagination pagination-sm mb-0">
                <?php for ($p = 1; $p <= min($totalHalaman, 10); $p++): ?>
                  <li class="page-item <?= $p === $halaman ? 'active' : '' ?>">
                    <a class="page-link"
                       href="?status=<?= urlencode($filterStatus) ?>&kategori=<?= $filterKategori ?>&cari=<?= urlencode($cari) ?>&hal=<?= $p ?>"><?= $p ?></a>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>

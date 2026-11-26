<?php
/**
 * admin/pengguna.php
 * Manajemen data pengguna — lihat, aktifkan/nonaktifkan, hapus.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
paksakanRole('admin');

$adminId = penggunaLogin()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();
    $action = $_POST['action'] ?? '';
    $uid    = inputInt($_POST['user_id'] ?? 0);

    if ($uid === $adminId) {
        setFlash('gagal', 'Anda tidak dapat mengubah akun sendiri dari halaman ini.');
        redirect(BASE_URL . '/admin/pengguna.php');
    }

    if ($action === 'toggle_status' && $uid) {
        $stmtS = $koneksi->prepare("SELECT status_akun FROM users WHERE id = ?");
        $stmtS->bind_param('i', $uid);
        $stmtS->execute();
        $current = $stmtS->get_result()->fetch_assoc()['status_akun'] ?? '';
        $stmtS->close();
        $baru = $current === 'aktif' ? 'nonaktif' : 'aktif';
        $stmtU = $koneksi->prepare("UPDATE users SET status_akun = ? WHERE id = ?");
        $stmtU->bind_param('si', $baru, $uid);
        if ($stmtU->execute()) setFlash('sukses', "Status akun berhasil diubah menjadi \"$baru\".");
        else setFlash('gagal', 'Gagal mengubah status akun.');
        $stmtU->close();

    } elseif ($action === 'hapus' && $uid) {
        $stmtD = $koneksi->prepare("DELETE FROM users WHERE id = ? AND role = 'warga'");
        $stmtD->bind_param('i', $uid);
        if ($stmtD->execute() && $stmtD->affected_rows > 0) setFlash('sukses', 'Akun warga berhasil dihapus.');
        else setFlash('gagal', 'Gagal menghapus. Hanya akun warga yang dapat dihapus.');
        $stmtD->close();
    }

    redirect(BASE_URL . '/admin/pengguna.php');
}

// Filter
$cari = trim($_GET['cari'] ?? '');
$filterRole = $_GET['role'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$where  = "WHERE 1=1";
$params = [];
$types  = '';
if ($cari)         { $where .= " AND (nama LIKE ? OR email LIKE ?)"; $l = '%'.$cari.'%'; $params[] = $l; $params[] = $l; $types .= 'ss'; }
if ($filterRole)   { $where .= " AND role = ?";        $params[] = $filterRole;   $types .= 's'; }
if ($filterStatus) { $where .= " AND status_akun = ?"; $params[] = $filterStatus; $types .= 's'; }

$sql   = "SELECT id, nama, email, no_telepon, alamat, role, status_akun, created_at FROM users $where ORDER BY created_at DESC";
$stmtU = $koneksi->prepare($sql);
if ($types) $stmtU->bind_param($types, ...$params);
$stmtU->execute();
$pengguna = $stmtU->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtU->close();

$csrfToken = buatCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Pengguna | Admin Siaga Masyarakat</title>
  <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.ico">
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
    <h4 class="fw-bold mb-4">Data Pengguna</h4>

    <!-- Filter -->
    <div class="card card-custom mb-4">
      <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
          <div class="col-md-4">
            <input type="text" name="cari" class="form-control form-control-sm"
                   placeholder="Cari nama atau email..." value="<?= e($cari) ?>">
          </div>
          <div class="col-md-2">
            <select name="role" class="form-select form-select-sm">
              <option value="">Semua Role</option>
              <option value="warga" <?= $filterRole === 'warga' ? 'selected' : '' ?>>Warga</option>
              <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
              <option value="">Semua Status</option>
              <option value="aktif"    <?= $filterStatus === 'aktif'    ? 'selected' : '' ?>>Aktif</option>
              <option value="nonaktif" <?= $filterStatus === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Cari</button>
            <a href="<?= BASE_URL ?>/admin/pengguna.php" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel -->
    <div class="card card-custom">
      <div class="card-header">
        <i class="bi bi-people me-2"></i>Daftar Pengguna
        <span class="badge bg-secondary ms-1"><?= count($pengguna) ?></span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-custom table-hover mb-0">
            <thead><tr>
              <th>#</th>
              <th>Nama</th>
              <th>Email</th>
              <th>No. Telepon</th>
              <th>Role</th>
              <th>Status Akun</th>
              <th>Tgl. Daftar</th>
              <th>Aksi</th>
            </tr></thead>
            <tbody>
              <?php foreach ($pengguna as $i => $pg): ?>
              <tr>
                <td class="text-muted small"><?= $i + 1 ?></td>
                <td class="fw-medium"><?= e($pg['nama']) ?></td>
                <td class="small"><?= e($pg['email']) ?></td>
                <td class="small"><?= e($pg['no_telepon'] ?: '-') ?></td>
                <td>
                  <span class="badge <?= $pg['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                    <?= e($pg['role']) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= $pg['status_akun'] === 'aktif' ? 'bg-success' : 'bg-secondary' ?>">
                    <?= e($pg['status_akun']) ?>
                  </span>
                </td>
                <td class="small text-muted"><?= formatTanggal($pg['created_at']) ?></td>
                <td>
                  <?php if ((int)$pg['id'] !== $adminId): ?>
                  <div class="btn-group btn-group-sm">
                    <!-- Toggle Status -->
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                      <input type="hidden" name="action"  value="toggle_status">
                      <input type="hidden" name="user_id" value="<?= (int)$pg['id'] ?>">
                      <button type="submit" class="btn <?= $pg['status_akun'] === 'aktif' ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                              title="<?= $pg['status_akun'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                        <i class="bi bi-<?= $pg['status_akun'] === 'aktif' ? 'person-dash' : 'person-check' ?>"></i>
                      </button>
                    </form>
                    <?php if ($pg['role'] === 'warga'): ?>
                    <form method="POST" class="form-hapus d-inline"
                          data-pesan="Hapus akun <?= addslashes($pg['nama']) ?>? Seluruh laporan akan terhapus.">
                      <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                      <input type="hidden" name="action"  value="hapus">
                      <input type="hidden" name="user_id" value="<?= (int)$pg['id'] ?>">
                      <button type="submit" class="btn btn-outline-danger" title="Hapus">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                    <?php endif; ?>
                  </div>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>

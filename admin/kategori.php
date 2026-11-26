<?php
/**
 * admin/kategori.php
 * CRUD Kategori Laporan untuk admin.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
paksakanRole('admin');

$errors = [];

// Proses POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah') {
        $nama = trim($_POST['nama_kategori'] ?? '');
        $desk = trim($_POST['deskripsi']     ?? '');
        if (kosong($nama)) { $errors['nama'] = 'Nama kategori wajib diisi.'; }
        else {
            $stmtI = $koneksi->prepare("INSERT INTO categories (nama_kategori, deskripsi) VALUES (?,?)");
            $stmtI->bind_param('ss', $nama, $desk);
            if ($stmtI->execute()) { setFlash('sukses', 'Kategori ditambahkan.'); redirect(BASE_URL . '/admin/kategori.php'); }
            else $errors['umum'] = 'Gagal. Kemungkinan nama sudah ada.';
            $stmtI->close();
        }
    } elseif ($action === 'edit') {
        $editId = inputInt($_POST['edit_id'] ?? 0);
        $nama   = trim($_POST['nama_kategori'] ?? '');
        $desk   = trim($_POST['deskripsi']     ?? '');
        if (kosong($nama)) { $errors['nama_edit'] = 'Nama kategori wajib diisi.'; }
        elseif ($editId) {
            $stmtU = $koneksi->prepare("UPDATE categories SET nama_kategori=?, deskripsi=? WHERE id=?");
            $stmtU->bind_param('ssi', $nama, $desk, $editId);
            if ($stmtU->execute()) { setFlash('sukses', 'Kategori diperbarui.'); redirect(BASE_URL . '/admin/kategori.php'); }
            else $errors['umum'] = 'Gagal memperbarui kategori.';
            $stmtU->close();
        }
    } elseif ($action === 'hapus') {
        $hapusId = inputInt($_POST['hapus_id'] ?? 0);
        if ($hapusId) {
            // Cek apakah masih digunakan
            $stmtC = $koneksi->prepare("SELECT COUNT(*) FROM reports WHERE category_id = ?");
            $stmtC->bind_param('i', $hapusId);
            $stmtC->execute();
            $jml = $stmtC->get_result()->fetch_row()[0] ?? 0;
            $stmtC->close();
            if ($jml > 0) {
                setFlash('gagal', "Kategori ini masih digunakan oleh $jml laporan dan tidak dapat dihapus.");
            } else {
                $stmtD = $koneksi->prepare("DELETE FROM categories WHERE id = ?");
                $stmtD->bind_param('i', $hapusId);
                if ($stmtD->execute()) setFlash('sukses', 'Kategori dihapus.');
                else setFlash('gagal', 'Gagal menghapus kategori.');
                $stmtD->close();
            }
            redirect(BASE_URL . '/admin/kategori.php');
        }
    }
}

$kategori = $koneksi->query(
    "SELECT c.*, (SELECT COUNT(*) FROM reports r WHERE r.category_id = c.id) AS jml_laporan
     FROM categories c ORDER BY c.nama_kategori"
)->fetch_all(MYSQLI_ASSOC);

$csrfToken = buatCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kategori Laporan | Admin Siaga Masyarakat</title>
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
    <h4 class="fw-bold mb-4">Kategori Laporan</h4>

    <div class="row g-4">
      <!-- Form Tambah -->
      <div class="col-lg-4">
        <div class="card card-custom">
          <div class="card-header"><i class="bi bi-tag-fill me-2"></i>Tambah Kategori</div>
          <div class="card-body">
            <?php if (!empty($errors['umum'])): ?><div class="alert alert-danger"><?= e($errors['umum']) ?></div><?php endif; ?>
            <form method="POST" novalidate>
              <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
              <input type="hidden" name="action" value="tambah">
              <div class="mb-3">
                <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                <input type="text" name="nama_kategori"
                       class="form-control <?= isset($errors['nama']) ? 'is-invalid' : '' ?>"
                       placeholder="Contoh: Jalan Rusak" required>
                <?php if (isset($errors['nama'])): ?><div class="invalid-feedback"><?= e($errors['nama']) ?></div><?php endif; ?>
              </div>
              <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="deskripsi" rows="2" class="form-control"
                          placeholder="Deskripsi singkat kategori ini..."></textarea>
              </div>
              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-plus-circle me-1"></i>Tambah Kategori
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Daftar Kategori -->
      <div class="col-lg-8">
        <div class="card card-custom">
          <div class="card-header">
            <i class="bi bi-tags me-2"></i>Daftar Kategori
            <span class="badge bg-secondary ms-1"><?= count($kategori) ?></span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-custom table-hover mb-0">
                <thead><tr>
                  <th>#</th>
                  <th>Nama Kategori</th>
                  <th>Deskripsi</th>
                  <th>Laporan</th>
                  <th>Aksi</th>
                </tr></thead>
                <tbody>
                  <?php foreach ($kategori as $i => $cat): ?>
                  <tr>
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td class="fw-medium"><?= e($cat['nama_kategori']) ?></td>
                    <td class="small text-muted"><?= e(mb_substr($cat['deskripsi'] ?? '', 0, 60)) ?></td>
                    <td class="text-center">
                      <span class="badge bg-light text-dark border"><?= $cat['jml_laporan'] ?></span>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning btn-edit-kategori"
                                data-id="<?= (int)$cat['id'] ?>"
                                data-nama="<?= e($cat['nama_kategori']) ?>"
                                data-deskripsi="<?= e($cat['deskripsi'] ?? '') ?>"
                                title="Edit">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" class="form-hapus d-inline"
                              data-pesan="Hapus kategori '<?= addslashes(e($cat['nama_kategori'])) ?>'?">
                          <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                          <input type="hidden" name="action"   value="hapus">
                          <input type="hidden" name="hapus_id" value="<?= (int)$cat['id'] ?>">
                          <button type="submit" class="btn btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                      </div>
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

  </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action"  value="edit">
        <input type="hidden" name="edit_id" id="edit_id">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Edit Kategori</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nama Kategori</label>
            <input type="text" name="nama_kategori" id="edit_nama" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" id="edit_desk" rows="2" class="form-control"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<script>
document.querySelectorAll('.btn-edit-kategori').forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById('edit_id').value   = this.dataset.id;
    document.getElementById('edit_nama').value = this.dataset.nama;
    document.getElementById('edit_desk').value = this.dataset.deskripsi;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
  });
});
</script>
</body>
</html>

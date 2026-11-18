<?php
/**
 * admin/kegiatan.php
 * CRUD kegiatan sosial untuk admin.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Pastikan hanya admin yang bisa mengakses halaman ini
requireRole('admin');

$errors = [];
$mode   = $_GET['mode'] ?? 'list';
$id     = inputInt($_GET['id'] ?? 0);
$keg    = null;

// Load data untuk mode edit
if ($mode === 'edit' && $id) {
    $stmt = $koneksi->prepare("SELECT * FROM activities WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $keg = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$keg) { setFlash('gagal', 'Kegiatan tidak ditemukan.'); redirect(BASE_URL . '/admin/kegiatan.php'); }
}

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();

    $action       = $_POST['action']        ?? '';
    $namaKeg      = trim($_POST['nama_kegiatan'] ?? '');
    $deskripsi    = trim($_POST['deskripsi']     ?? '');
    $lokasi       = trim($_POST['lokasi']        ?? '');
    $tanggal      = trim($_POST['tanggal']       ?? '');
    $waktu        = trim($_POST['waktu']         ?? '');
    $kuota        = inputInt($_POST['kuota']     ?? 0);
    $status       = trim($_POST['status']        ?? 'Akan Datang');
    $lat          = trim($_POST['latitude']      ?? '');
    $lng          = trim($_POST['longitude']     ?? '');
    $editId       = inputInt($_POST['edit_id']   ?? 0);

    // Validasi
    if (kosong($namaKeg))  $errors['nama']    = 'Nama kegiatan wajib diisi.';
    if (kosong($tanggal))  $errors['tanggal'] = 'Tanggal wajib diisi.';
    if (kosong($waktu))    $errors['waktu']   = 'Waktu wajib diisi.';
    if ($kuota < 1)        $errors['kuota']   = 'Kuota minimal 1.';

    $statusValid = ['Akan Datang','Berlangsung','Selesai','Dibatalkan'];
    if (!in_array($status, $statusValid, true)) $errors['status'] = 'Status tidak valid.';

    $namaFoto = $editId ? ($keg['foto'] ?? '') : '';
    if (!empty($_FILES['foto']['name'])) {
        $upload = uploadFoto($_FILES['foto'], UPLOAD_ACTS);
        if (!$upload['ok']) $errors['foto'] = $upload['pesan'];
        else                $namaFoto = $upload['nama'];
    }

    if (empty($errors)) {
        $latVal = $lat !== '' ? (float)$lat : null;
        $lngVal = $lng !== '' ? (float)$lng : null;

        if ($action === 'tambah') {
            $stmtI = $koneksi->prepare(
                "INSERT INTO activities (nama_kegiatan, deskripsi, foto, lokasi, latitude, longitude,
                  tanggal, waktu, kuota, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?)"
            );
            $stmtI->bind_param('ssssddssds', $namaKeg, $deskripsi, $namaFoto, $lokasi, $latVal, $lngVal, $tanggal, $waktu, $kuota, $status);
            if ($stmtI->execute()) {
                setFlash('sukses', 'Kegiatan berhasil ditambahkan.');
                redirect(BASE_URL . '/admin/kegiatan.php');
            } else {
                $errors['umum'] = 'Gagal menyimpan kegiatan.';
                hapusFoto(UPLOAD_ACTS, $namaFoto);
            }
            $stmtI->close();

        } elseif ($action === 'edit' && $editId) {
            $stmtU = $koneksi->prepare(
                "UPDATE activities SET nama_kegiatan=?, deskripsi=?, foto=?, lokasi=?,
                  latitude=?, longitude=?, tanggal=?, waktu=?, kuota=?, status=?
                 WHERE id=?"
            );
            $stmtU->bind_param(
                'ssssddssisi',
                $namaKeg,
                $deskripsi,
                $namaFoto,
                $lokasi,
                $latVal,
                $lngVal,
                $tanggal,
                $waktu,
                $kuota,
                $status,
                $editId
            );
            if ($stmtU->execute()) {
                // Hapus foto lama jika diganti
                if ($namaFoto !== ($keg['foto'] ?? '') && $keg['foto']) {
                    hapusFoto(UPLOAD_ACTS, $keg['foto']);
                }
                setFlash('sukses', 'Kegiatan berhasil diperbarui.');
                redirect(BASE_URL . '/admin/kegiatan.php');
            } else {
                $errors['umum'] = 'Gagal memperbarui.';
            }
            $stmtU->close();
        }
    }
}


// Hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus') {
    verifikasiCsrf();
    $hapusId = inputInt($_POST['hapus_id'] ?? 0);
    if ($hapusId) {
        $stmtF = $koneksi->prepare("SELECT foto FROM activities WHERE id = ?");
        $stmtF->bind_param('i', $hapusId);
        $stmtF->execute();
        $f = $stmtF->get_result()->fetch_assoc();
        $stmtF->close();
        $stmtD = $koneksi->prepare("DELETE FROM activities WHERE id = ?");
        $stmtD->bind_param('i', $hapusId);
        if ($stmtD->execute()) {
            hapusFoto(UPLOAD_ACTS, $f['foto'] ?? '');
            setFlash('sukses', 'Kegiatan dihapus.');
        } else {
            setFlash('gagal', 'Gagal menghapus.');
        }
        $stmtD->close();
    }
    redirect(BASE_URL . '/admin/kegiatan.php');
}

// Ambil daftar kegiatan
$kegiatan = $koneksi->query(
    "SELECT a.*,
            (SELECT COUNT(*) FROM activity_participants ap
             WHERE ap.activity_id = a.id AND ap.status_pendaftaran='Terdaftar') AS jml_peserta
     FROM activities a ORDER BY a.tanggal DESC"
)->fetch_all(MYSQLI_ASSOC);

$csrfToken = buatCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kegiatan Sosial | Admin Siaga Masyarakat</title>
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

    <?php if ($mode === 'list'): ?>
    <!-- DAFTAR KEGIATAN -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold mb-0">Kegiatan Sosial</h4>
      <a href="?mode=tambah" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Tambah Kegiatan
      </a>
    </div>

    <div class="card card-custom">
      <div class="card-body p-0">
        <?php if (empty($kegiatan)): ?>
          <div class="text-center py-5 text-muted"><i class="bi bi-calendar-x fs-1 d-block mb-2"></i>Belum ada kegiatan.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-custom table-hover mb-0">
            <thead><tr>
              <th>Nama Kegiatan</th>
              <th>Tanggal</th>
              <th>Lokasi</th>
              <th>Kuota</th>
              <th>Peserta</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr></thead>
            <tbody>
              <?php foreach ($kegiatan as $k): ?>
              <tr>
                <td class="fw-medium"><?= e($k['nama_kegiatan']) ?></td>
                <td class="small"><?= formatTanggal($k['tanggal']) ?><br><span class="text-muted"><?= substr($k['waktu'],0,5) ?></span></td>
                <td class="small text-muted"><?= e(mb_substr($k['lokasi'] ?? '-', 0, 35)) ?></td>
                <td class="text-center"><?= $k['kuota'] ?></td>
                <td class="text-center">
                  <span class="<?= $k['jml_peserta'] >= $k['kuota'] ? 'text-danger' : 'text-success' ?> fw-semibold">
                    <?= $k['jml_peserta'] ?>
                  </span>
                </td>
                <td><span class="badge <?= badgeKegiatan($k['status']) ?>"><?= e($k['status']) ?></span></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a href="<?= BASE_URL ?>/admin/peserta.php?id=<?= (int)$k['id'] ?>" class="btn btn-outline-secondary" title="Peserta">
                      <i class="bi bi-people"></i>
                    </a>
                    <a href="?mode=edit&id=<?= (int)$k['id'] ?>" class="btn btn-outline-warning" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form method="POST" class="form-hapus d-inline" data-pesan="Hapus kegiatan ini?">
                      <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                      <input type="hidden" name="action" value="hapus">
                      <input type="hidden" name="hapus_id" value="<?= (int)$k['id'] ?>">
                      <button type="submit" class="btn btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php else: ?>
    <!-- FORM TAMBAH / EDIT -->
    <div class="d-flex align-items-center mb-4">
      <a href="<?= BASE_URL ?>/admin/kegiatan.php" class="btn btn-outline-secondary btn-sm me-3">
        <i class="bi bi-arrow-left"></i>
      </a>
      <h4 class="fw-bold mb-0"><?= $mode === 'edit' ? 'Edit Kegiatan' : 'Tambah Kegiatan' ?></h4>
    </div>

    <div class="card card-custom">
      <div class="card-body p-4">
        <?php if (!empty($errors['umum'])): ?><div class="alert alert-danger"><?= e($errors['umum']) ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
          <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'edit' : 'tambah' ?>">
          <?php if ($mode === 'edit'): ?>
            <input type="hidden" name="edit_id" value="<?= (int)$id ?>">
          <?php endif; ?>

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
              <input type="text" name="nama_kegiatan" class="form-control <?= isset($errors['nama']) ? 'is-invalid' : '' ?>"
                     value="<?= e($_POST['nama_kegiatan'] ?? $keg['nama_kegiatan'] ?? '') ?>" required>
              <?php if (isset($errors['nama'])): ?><div class="invalid-feedback"><?= e($errors['nama']) ?></div><?php endif; ?>
            </div>
            <div class="col-12">
              <label class="form-label">Deskripsi</label>
              <textarea name="deskripsi" rows="3" class="form-control"><?= e($_POST['deskripsi'] ?? $keg['deskripsi'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tanggal <span class="text-danger">*</span></label>
              <input type="date" name="tanggal" class="form-control <?= isset($errors['tanggal']) ? 'is-invalid' : '' ?>"
                     value="<?= e($_POST['tanggal'] ?? $keg['tanggal'] ?? '') ?>" required>
              <?php if (isset($errors['tanggal'])): ?><div class="invalid-feedback"><?= e($errors['tanggal']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="form-label">Waktu <span class="text-danger">*</span></label>
              <input type="time" name="waktu" class="form-control <?= isset($errors['waktu']) ? 'is-invalid' : '' ?>"
                     value="<?= e($_POST['waktu'] ?? substr($keg['waktu'] ?? '', 0, 5)) ?>" required>
              <?php if (isset($errors['waktu'])): ?><div class="invalid-feedback"><?= e($errors['waktu']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="form-label">Kuota Peserta <span class="text-danger">*</span></label>
              <input type="number" name="kuota" class="form-control <?= isset($errors['kuota']) ? 'is-invalid' : '' ?>"
                     min="1" value="<?= e($_POST['kuota'] ?? $keg['kuota'] ?? 50) ?>" required>
              <?php if (isset($errors['kuota'])): ?><div class="invalid-feedback"><?= e($errors['kuota']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <?php foreach (['Akan Datang','Berlangsung','Selesai','Dibatalkan'] as $s): ?>
                  <option value="<?= $s ?>" <?= (($_POST['status'] ?? $keg['status'] ?? '') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Lokasi</label>
              <input type="text" name="lokasi" class="form-control"
                     value="<?= e($_POST['lokasi'] ?? $keg['lokasi'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Latitude</label>
              <input type="text" name="latitude" id="latitude" class="form-control" readonly
                     value="<?= e($_POST['latitude'] ?? $keg['latitude'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Longitude</label>
              <input type="text" name="longitude" id="longitude" class="form-control" readonly
                     value="<?= e($_POST['longitude'] ?? $keg['longitude'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Pilih Lokasi di Peta</label>
              <button type="button" id="btn-lokasi-saya" class="btn btn-outline-primary btn-sm mb-2">
                <i class="bi bi-geo-alt-fill me-1"></i>Gunakan Lokasi Saya
              </button>
              <div id="peta-pilih"></div>
            </div>
            <div class="col-12">
              <label class="form-label">Foto Kegiatan</label>
              <?php if (!empty($keg['foto'])): ?>
                <div class="mb-2">
                  <img src="<?= BASE_URL ?>/uploads/activities/<?= e($keg['foto']) ?>"
                       class="img-thumbnail" style="max-height:100px;" alt="Foto kegiatan">
                </div>
              <?php endif; ?>
              <input type="file" name="foto" class="form-control <?= isset($errors['foto']) ? 'is-invalid' : '' ?>"
                     accept=".jpg,.jpeg,.png,.webp">
              <?php if (isset($errors['foto'])): ?><div class="invalid-feedback"><?= e($errors['foto']) ?></div><?php endif; ?>
              <div id="preview-wrap" class="mt-2 d-none"><img id="preview-foto" src="" alt="Preview"></div>
            </div>
          </div>

          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold">
              <i class="bi bi-save me-2"></i>Simpan Kegiatan
            </button>
            <a href="<?= BASE_URL ?>/admin/kegiatan.php" class="btn btn-outline-secondary">Batal</a>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<script>
  initPreviewFoto('foto', 'preview-foto', 'preview-wrap');
  initPetaPilih('peta-pilih', 'latitude', 'longitude', 'lokasi');
</script>
</body>
</html>

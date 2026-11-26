<?php
/**
 * warga/edit_laporan.php
 * Warga dapat mengedit laporan miliknya yang masih berstatus "Menunggu".
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
paksakanRole('warga');

$user   = penggunaLogin();
$userId = $user['id'];
$id     = inputInt($_GET['id'] ?? 0);

if (!$id) { setFlash('gagal', 'ID laporan tidak valid.'); redirect(BASE_URL . '/warga/laporan_saya.php'); }

// Ambil data laporan — validasi kepemilikan & status
$stmt = $koneksi->prepare(
    "SELECT r.*, c.nama_kategori FROM reports r
     JOIN categories c ON r.category_id = c.id
     WHERE r.id = ? AND r.user_id = ?"
);
$stmt->bind_param('ii', $id, $userId);
$stmt->execute();
$laporan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$laporan) { setFlash('gagal', 'Laporan tidak ditemukan.'); redirect(BASE_URL . '/warga/laporan_saya.php'); }
if ($laporan['status'] !== 'Menunggu') {
    setFlash('gagal', 'Laporan hanya dapat diedit saat berstatus "Menunggu".');
    redirect(BASE_URL . '/warga/laporan_saya.php');
}

$kategori = $koneksi->query("SELECT id, nama_kategori FROM categories ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();

    $judul     = trim($_POST['judul']        ?? '');
    $catId     = inputInt($_POST['category_id'] ?? 0);
    $deskripsi = trim($_POST['deskripsi']    ?? '');
    $alamat    = trim($_POST['alamat_lokasi'] ?? '');
    $lat       = trim($_POST['latitude']     ?? '');
    $lng       = trim($_POST['longitude']    ?? '');

    if (kosong($judul))     $errors['judul'] = 'Judul wajib diisi.';
    if (!$catId)            $errors['catId'] = 'Pilih kategori.';
    if (kosong($deskripsi)) $errors['desc']  = 'Deskripsi wajib diisi.';
    if (kosong($alamat) && (kosong($lat) || kosong($lng))) {
        $errors['lokasi'] = 'Isi alamat lokasi atau pilih titik di peta.';
    }

    // Foto baru (opsional)
    $namaFotoBaru = $laporan['foto']; // default tetap foto lama
    if (!empty($_FILES['foto']['name'])) {
        $upload = uploadFoto($_FILES['foto'], UPLOAD_REPORTS);
        if (!$upload['ok']) {
            $errors['foto'] = $upload['pesan'];
        } else {
            $namaFotoBaru = $upload['nama'];
        }
    }

    if (empty($errors)) {
        $latVal = $lat !== '' ? (float)$lat : null;
        $lngVal = $lng !== '' ? (float)$lng : null;

        $stmtU = $koneksi->prepare(
            "UPDATE reports
             SET category_id=?, judul=?, deskripsi=?, foto=?,
                 alamat_lokasi=?, latitude=?, longitude=?
             WHERE id=? AND user_id=? AND status='Menunggu'"
        );
        $stmtU->bind_param(
            'issssddii',
            $catId, $judul, $deskripsi, $namaFotoBaru,
            $alamat, $latVal, $lngVal, $id, $userId
        );
        if ($stmtU->execute()) {
            // Hapus foto lama jika diganti
            if ($namaFotoBaru !== $laporan['foto'] && $laporan['foto']) {
                hapusFoto(UPLOAD_REPORTS, $laporan['foto']);
            }
            setFlash('sukses', 'Laporan berhasil diperbarui.');
            redirect(BASE_URL . '/warga/detail_laporan.php?id=' . $id);
        } else {
            $errors['umum'] = 'Gagal memperbarui laporan. Coba lagi.';
            if ($namaFotoBaru !== $laporan['foto']) hapusFoto(UPLOAD_REPORTS, $namaFotoBaru);
        }
        $stmtU->close();
    }
}

$csrfToken = buatCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Laporan | Siaga Masyarakat</title>
  <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container py-4">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb breadcrumb-custom">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/warga/laporan_saya.php">Laporan Saya</a></li>
      <li class="breadcrumb-item active">Edit Laporan</li>
    </ol>
  </nav>
  <h4 class="fw-bold mb-4">Edit Laporan</h4>

  <div class="card card-custom col-lg-8">
    <div class="card-header"><i class="bi bi-pencil-square me-2"></i>Ubah Data Laporan</div>
    <div class="card-body p-4">
      <div id="alert-container">
        <?php if (!empty($errors['umum'])): ?><div class="alert alert-danger"><?= e($errors['umum']) ?></div><?php endif; ?>
      </div>

      <form method="POST" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <div class="mb-3">
          <label for="judul" class="form-label">Judul Laporan <span class="text-danger">*</span></label>
          <input type="text" id="judul" name="judul"
                 class="form-control <?= isset($errors['judul']) ? 'is-invalid' : '' ?>"
                 value="<?= e($_POST['judul'] ?? $laporan['judul']) ?>" maxlength="200" required>
          <?php if (isset($errors['judul'])): ?><div class="invalid-feedback"><?= e($errors['judul']) ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
          <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
          <select id="category_id" name="category_id"
                  class="form-select <?= isset($errors['catId']) ? 'is-invalid' : '' ?>" required>
            <?php foreach ($kategori as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>"
                      <?= ((int)($_POST['category_id'] ?? $laporan['category_id']) === (int)$cat['id']) ? 'selected' : '' ?>>
                <?= e($cat['nama_kategori']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['catId'])): ?><div class="invalid-feedback"><?= e($errors['catId']) ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
          <label for="deskripsi" class="form-label">Deskripsi <span class="text-danger">*</span></label>
          <textarea id="deskripsi" name="deskripsi" rows="4"
                    class="form-control <?= isset($errors['desc']) ? 'is-invalid' : '' ?>"
                    required><?= e($_POST['deskripsi'] ?? $laporan['deskripsi']) ?></textarea>
          <?php if (isset($errors['desc'])): ?><div class="invalid-feedback"><?= e($errors['desc']) ?></div><?php endif; ?>
        </div>

        <!-- Foto -->
        <div class="mb-3">
          <label class="form-label">Foto Bukti</label>
          <?php if ($laporan['foto']): ?>
            <div class="mb-2">
              <img src="<?= BASE_URL ?>/uploads/reports/<?= e($laporan['foto']) ?>"
                   alt="Foto saat ini" class="img-thumbnail" style="max-height:120px;">
              <div class="small text-muted mt-1">Foto saat ini — unggah yang baru untuk menggantinya</div>
            </div>
          <?php endif; ?>
          <input type="file" id="foto" name="foto"
                 class="form-control <?= isset($errors['foto']) ? 'is-invalid' : '' ?>"
                 accept=".jpg,.jpeg,.png,.webp">
          <?php if (isset($errors['foto'])): ?><div class="invalid-feedback"><?= e($errors['foto']) ?></div><?php endif; ?>
          <div id="preview-wrap" class="mt-2 d-none">
            <img id="preview-foto" src="" alt="Preview">
          </div>
        </div>

        <!-- Peta -->
        <div class="mb-3">
          <label class="form-label">Lokasi di Peta</label>
          <button type="button" id="btn-lokasi-saya" class="btn btn-outline-primary btn-sm mb-2">
            <i class="bi bi-geo-alt-fill me-1"></i>Gunakan Lokasi Saya
          </button>
          <div id="peta-pilih"></div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="latitude" class="form-label">Latitude</label>
            <input type="text" id="latitude" name="latitude" class="form-control" readonly
                   value="<?= e($_POST['latitude'] ?? $laporan['latitude']) ?>">
          </div>
          <div class="col-md-6">
            <label for="longitude" class="form-label">Longitude</label>
            <input type="text" id="longitude" name="longitude" class="form-control" readonly
                   value="<?= e($_POST['longitude'] ?? $laporan['longitude']) ?>">
          </div>
        </div>

        <div class="mb-4">
          <label for="alamat_lokasi" class="form-label">Alamat Lokasi <span class="text-danger">*</span></label>
          <textarea id="alamat_lokasi" name="alamat_lokasi" rows="2"
                    class="form-control <?= isset($errors['lokasi']) ? 'is-invalid' : '' ?>"><?= e($_POST['alamat_lokasi'] ?? $laporan['alamat_lokasi']) ?></textarea>
          <?php if (isset($errors['lokasi'])): ?><div class="invalid-feedback"><?= e($errors['lokasi']) ?></div><?php endif; ?>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary fw-semibold">
            <i class="bi bi-save me-2"></i>Simpan Perubahan
          </button>
          <a href="<?= BASE_URL ?>/warga/detail_laporan.php?id=<?= $id ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js?v=20261118"></script>
<script>
  initPreviewFoto('foto', 'preview-foto', 'preview-wrap');
  initPetaPilih('peta-pilih', 'latitude', 'longitude', 'alamat_lokasi');
</script>
</body>
</html>

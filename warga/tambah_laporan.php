<?php
/**
 * warga/tambah_laporan.php
 * Form tambah laporan baru dengan upload foto dan pemilihan lokasi Leaflet.js
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
requireRole('warga');

$user   = penggunaLogin();
$userId = $user['id'];
$errors = [];

// Ambil kategori
$kategori = $koneksi->query("SELECT id, nama_kategori FROM categories ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();

    $judul     = trim($_POST['judul']       ?? '');
    $catId     = inputInt($_POST['category_id'] ?? 0);
    $deskripsi = trim($_POST['deskripsi']   ?? '');
    $alamat    = trim($_POST['alamat_lokasi'] ?? '');
    $lat       = trim($_POST['latitude']    ?? '');
    $lng       = trim($_POST['longitude']   ?? '');

    // Validasi
    if (kosong($judul))  $errors['judul']  = 'Judul laporan wajib diisi.';
    if (!$catId)         $errors['catId']  = 'Pilih kategori laporan.';
    if (kosong($deskripsi)) $errors['desc'] = 'Deskripsi masalah wajib diisi.';
    if (kosong($alamat) && (kosong($lat) || kosong($lng))) {
        $errors['lokasi'] = 'Isi alamat lokasi atau pilih titik di peta.';
    }

    // Validasi koordinat
    if (!kosong($lat) && !is_numeric($lat)) $errors['lat'] = 'Latitude tidak valid.';
    if (!kosong($lng) && !is_numeric($lng)) $errors['lng'] = 'Longitude tidak valid.';

    // Cek kategori valid
    if ($catId) {
        $stmtCat = $koneksi->prepare("SELECT id FROM categories WHERE id = ?");
        $stmtCat->bind_param('i', $catId);
        $stmtCat->execute();
        if (!$stmtCat->get_result()->num_rows) $errors['catId'] = 'Kategori tidak ditemukan.';
        $stmtCat->close();
    }

    // Upload foto
    $namaFoto = '';
    if (!empty($_FILES['foto']['name'])) {
        $upload = uploadFoto($_FILES['foto'], UPLOAD_REPORTS);
        if (!$upload['ok']) {
            $errors['foto'] = $upload['pesan'];
        } else {
            $namaFoto = $upload['nama'];
        }
    }

    if (empty($errors)) {
        $nomorLaporan = generateNomorLaporan($koneksi);
        $latVal = $lat !== '' ? (float)$lat : null;
        $lngVal = $lng !== '' ? (float)$lng : null;

        $stmt = $koneksi->prepare(
            "INSERT INTO reports
             (nomor_laporan, user_id, category_id, judul, deskripsi, foto,
              alamat_lokasi, latitude, longitude, status)
             VALUES (?,?,?,?,?,?,?,?,?,'Menunggu')"
        );
        $stmt->bind_param(
            'siissssdd',
            $nomorLaporan, $userId, $catId, $judul, $deskripsi,
            $namaFoto, $alamat, $latVal, $lngVal
        );

        if ($stmt->execute()) {
            $reportId = $koneksi->insert_id;
            // Simpan riwayat awal
            $catatan = 'Laporan berhasil dikirim. Menunggu verifikasi admin.';
            $stmtH   = $koneksi->prepare(
                "INSERT INTO report_histories (report_id, admin_id, status, catatan) VALUES (?,NULL,'Menunggu',?)"
            );
            $stmtH->bind_param('is', $reportId, $catatan);
            $stmtH->execute();
            $stmtH->close();

            setFlash('sukses', "Laporan berhasil dikirim dengan nomor $nomorLaporan. Tim kami akan segera menindaklanjuti.");
            redirect(BASE_URL . '/warga/laporan_saya.php');
        } else {
            $errors['umum'] = 'Gagal menyimpan laporan. Coba lagi.';
            hapusFoto(UPLOAD_REPORTS, $namaFoto);
        }
        $stmt->close();
    }
}

$csrfToken = buatCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buat Laporan | Siaga Masyarakat</title>
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
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Beranda</a></li>
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/warga/laporan_saya.php">Laporan Saya</a></li>
      <li class="breadcrumb-item active">Buat Laporan</li>
    </ol>
  </nav>

  <h4 class="fw-bold mb-4">Buat Laporan Baru</h4>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card card-custom">
        <div class="card-header"><i class="bi bi-clipboard2-plus me-2"></i>Formulir Laporan</div>
        <div class="card-body p-4">

          <div id="alert-container">
            <?php if (!empty($errors['umum'])): ?>
              <div class="alert alert-danger"><?= e($errors['umum']) ?></div>
            <?php endif; ?>
          </div>

          <form method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <!-- Judul -->
            <div class="mb-3">
              <label for="judul" class="form-label">Judul Laporan <span class="text-danger">*</span></label>
              <input type="text" id="judul" name="judul"
                     class="form-control <?= isset($errors['judul']) ? 'is-invalid' : '' ?>"
                     placeholder="Deskripsikan masalah secara singkat dan jelas"
                     value="<?= e($_POST['judul'] ?? '') ?>" maxlength="200" required>
              <?php if (isset($errors['judul'])): ?><div class="invalid-feedback"><?= e($errors['judul']) ?></div><?php endif; ?>
            </div>

            <!-- Kategori -->
            <div class="mb-3">
              <label for="category_id" class="form-label">Kategori Masalah <span class="text-danger">*</span></label>
              <select id="category_id" name="category_id"
                      class="form-select <?= isset($errors['catId']) ? 'is-invalid' : '' ?>" required>
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($kategori as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>"
                          <?= ((int)($_POST['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                    <?= e($cat['nama_kategori']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (isset($errors['catId'])): ?><div class="invalid-feedback"><?= e($errors['catId']) ?></div><?php endif; ?>
            </div>

            <!-- Deskripsi -->
            <div class="mb-3">
              <label for="deskripsi" class="form-label">Deskripsi Masalah <span class="text-danger">*</span></label>
              <textarea id="deskripsi" name="deskripsi" rows="4"
                        class="form-control <?= isset($errors['desc']) ? 'is-invalid' : '' ?>"
                        placeholder="Jelaskan masalah secara detail: kondisi, dampak, sudah berapa lama, dll."
                        required><?= e($_POST['deskripsi'] ?? '') ?></textarea>
              <?php if (isset($errors['desc'])): ?><div class="invalid-feedback"><?= e($errors['desc']) ?></div><?php endif; ?>
            </div>

            <!-- Upload Foto -->
            <div class="mb-3">
              <label for="foto" class="form-label">Foto Bukti</label>
              <input type="file" id="foto" name="foto"
                     class="form-control <?= isset($errors['foto']) ? 'is-invalid' : '' ?>"
                     accept=".jpg,.jpeg,.png,.webp">
              <div class="form-text text-muted small">Format: JPG, PNG, WebP. Maks 2 MB.</div>
              <?php if (isset($errors['foto'])): ?><div class="invalid-feedback"><?= e($errors['foto']) ?></div><?php endif; ?>
              <!-- Preview -->
              <div id="preview-wrap" class="mt-2 d-none">
                <img id="preview-foto" src="" alt="Preview Foto" class="d-block">
              </div>
            </div>

            <!-- Peta Lokasi -->
            <div class="mb-3">
              <label class="form-label">Lokasi di Peta</label>
              <div class="d-flex gap-2 mb-2">
                <button type="button" id="btn-lokasi-saya" class="btn btn-outline-primary btn-sm">
                  <i class="bi bi-geo-alt-fill me-1"></i>Gunakan Lokasi Saya
                </button>
                <small class="text-muted align-self-center">atau klik pada peta untuk memilih lokasi</small>
              </div>
              <div id="peta-pilih"></div>
              <?php if (isset($errors['lokasi'])): ?>
                <div class="text-danger small mt-1"><?= e($errors['lokasi']) ?></div>
              <?php endif; ?>
            </div>

            <!-- Koordinat (diisi otomatis oleh JS) -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="latitude" class="form-label">Latitude</label>
                <input type="text" id="latitude" name="latitude" class="form-control"
                       value="<?= e($_POST['latitude'] ?? '') ?>"
                       placeholder="-6.2088" readonly>
              </div>
              <div class="col-md-6">
                <label for="longitude" class="form-label">Longitude</label>
                <input type="text" id="longitude" name="longitude" class="form-control"
                       value="<?= e($_POST['longitude'] ?? '') ?>"
                       placeholder="106.8456" readonly>
              </div>
            </div>

            <!-- Alamat Lokasi -->
            <div class="mb-4">
              <label for="alamat_lokasi" class="form-label">Alamat Lokasi <span class="text-danger">*</span></label>
              <textarea id="alamat_lokasi" name="alamat_lokasi" rows="2"
                        class="form-control <?= isset($errors['lokasi']) ? 'is-invalid' : '' ?>"
                        placeholder="Alamat lokasi masalah (isi manual jika peta tidak tersedia)"><?= e($_POST['alamat_lokasi'] ?? '') ?></textarea>
              <div class="form-text text-muted small">Akan terisi otomatis setelah memilih lokasi di peta.</div>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary px-4 fw-semibold">
                <i class="bi bi-send-fill me-2"></i>Kirim Laporan
              </button>
              <a href="<?= BASE_URL ?>/warga/laporan_saya.php" class="btn btn-outline-secondary">Batal</a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Panel Panduan -->
    <div class="col-lg-4">
      <div class="card card-custom">
        <div class="card-header"><i class="bi bi-info-circle me-2"></i>Panduan Pelaporan</div>
        <div class="card-body">
          <ol class="ps-3 small text-muted">
            <li class="mb-2">Isi judul laporan secara singkat dan jelas.</li>
            <li class="mb-2">Pilih kategori masalah yang sesuai.</li>
            <li class="mb-2">Jelaskan masalah secara detail di kolom deskripsi.</li>
            <li class="mb-2">Unggah foto bukti untuk memperkuat laporan.</li>
            <li class="mb-2">Pilih lokasi masalah di peta atau gunakan GPS.</li>
            <li class="mb-2">Pastikan semua informasi sudah benar sebelum mengirim.</li>
          </ol>
          <hr>
          <div class="small">
            <div class="fw-semibold mb-2">Status Laporan:</div>
            <div class="d-flex flex-column gap-1">
              <span><span class="badge-status badge-menunggu">Menunggu</span> — Laporan diterima</span>
              <span><span class="badge-status badge-diverifikasi">Diverifikasi</span> — Sedang dikonfirmasi</span>
              <span><span class="badge-status badge-diproses">Diproses</span> — Sedang ditangani</span>
              <span><span class="badge-status badge-selesai">Selesai</span> — Masalah teratasi</span>
              <span><span class="badge-status badge-ditolak">Ditolak</span> — Tidak dapat diproses</span>
            </div>
          </div>
        </div>
      </div>
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

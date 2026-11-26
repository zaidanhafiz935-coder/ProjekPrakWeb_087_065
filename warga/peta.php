<?php
/**
 * warga/peta.php + admin/peta.php (sama-sama menggunakan file ini atau dipanggil terpisah)
 * Peta interaktif laporan menggunakan Leaflet.js + OpenStreetMap.
 * Dapat diakses tanpa login (publik) — data pribadi pelapor tidak ditampilkan.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();

// Ambil data laporan yang memiliki koordinat
$result = $koneksi->query(
    "SELECT r.id, r.nomor_laporan, r.judul, r.status, r.foto,
            r.alamat_lokasi, r.latitude, r.longitude,
            r.created_at, c.nama_kategori AS kategori
     FROM reports r
     JOIN categories c ON r.category_id = c.id
     WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL
     ORDER BY r.created_at DESC"
);
$laporan = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Siapkan data JSON untuk JavaScript (hapus data pribadi)
$peta_data = array_map(function($lap) {
    return [
        'nomor_laporan' => $lap['nomor_laporan'],
        'judul'         => $lap['judul'],
        'status'        => $lap['status'],
        'kategori'      => $lap['kategori'],
        'alamat_lokasi' => $lap['alamat_lokasi'],
        'latitude'      => $lap['latitude'],
        'longitude'     => $lap['longitude'],
        'tanggal'       => formatTanggal($lap['created_at']),
        'foto'          => $lap['foto'],
    ];
}, $laporan);

// Ambil kategori untuk filter
$kategoriList = $koneksi->query("SELECT id, nama_kategori FROM categories ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Peta Laporan | Siaga Masyarakat</title>
  <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
  <style>
    #peta-laporan { height: calc(100vh - 220px); min-height: 450px; border-radius: 12px; }
    .peta-legend { background: white; padding: 10px 14px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.12); font-size:.8rem; }
    .peta-legend .dot { display:inline-block; width:12px; height:12px; border-radius:50%; margin-right:6px; border:2px solid #fff; box-shadow:0 0 0 1.5px rgba(0,0,0,.2); }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-3 px-3 px-md-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="fw-bold mb-0">Peta Laporan Masyarakat</h4>
      <small class="text-muted">Menampilkan <?= count($laporan) ?> laporan dengan koordinat lokasi</small>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="card card-custom mb-3">
    <div class="card-body py-2 px-3">
      <div class="row g-2 align-items-center">
        <div class="col-md-3">
          <input type="text" id="cari-laporan" class="form-control form-control-sm"
                 placeholder="Cari judul laporan...">
        </div>
        <div class="col-md-2">
          <select id="filter-status" class="form-select form-select-sm">
            <option value="">Semua Status</option>
            <?php foreach (['Menunggu','Diverifikasi','Diproses','Selesai','Ditolak'] as $s): ?>
              <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <select id="filter-kategori" class="form-select form-select-sm">
            <option value="">Semua Kategori</option>
            <?php foreach ($kategoriList as $kat): ?>
              <option value="<?= e($kat['nama_kategori']) ?>"><?= e($kat['nama_kategori']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-auto">
          <button class="btn btn-sm btn-outline-secondary" onclick="resetFilter()">
            <i class="bi bi-x-circle me-1"></i>Reset
          </button>
        </div>
        <div class="col-auto ms-auto">
          <!-- Legenda -->
          <div class="peta-legend d-inline-block">
            <span class="dot" style="background:#ffc107;"></span>Menunggu &nbsp;
            <span class="dot" style="background:#0d6efd;"></span>Diverifikasi &nbsp;
            <span class="dot" style="background:#fd7e14;"></span>Diproses &nbsp;
            <span class="dot" style="background:#198754;"></span>Selesai &nbsp;
            <span class="dot" style="background:#dc3545;"></span>Ditolak
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Peta -->
  <div id="peta-laporan"></div>

  <?php if (empty($laporan)): ?>
    <div class="text-center py-4 text-muted mt-3">
      <i class="bi bi-geo-alt fs-1 d-block mb-2"></i>
      Belum ada laporan dengan data lokasi yang tersimpan.
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js?v=20261118"></script>
<script>
// Data laporan dari PHP ke JavaScript (diescaping dengan json_encode untuk keamanan)
const petaData = <?= json_encode($peta_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
if (document.readyState === 'loading') {
document.addEventListener('DOMContentLoaded', function () {
  initPetaLaporan('peta-laporan', petaData);
});
} else {
initPetaLaporan('peta-laporan', petaData);
}

function resetFilter() {
  document.getElementById('cari-laporan').value   = '';
  document.getElementById('filter-status').value  = '';
  document.getElementById('filter-kategori').value = '';
  // Trigger change event untuk update marker
  ['filter-status','filter-kategori'].forEach(function(id){
    document.getElementById(id).dispatchEvent(new Event('change'));
  });
  document.getElementById('cari-laporan').dispatchEvent(new Event('input'));
}
</script>
</body>
</html>

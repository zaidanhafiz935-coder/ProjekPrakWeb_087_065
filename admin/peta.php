<?php
/**
 * admin/peta.php
 * Peta laporan untuk admin (menampilkan semua laporan termasuk nama pelapor).
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
paksakanRole('admin');

$result = $koneksi->query(
    "SELECT r.id, r.nomor_laporan, r.judul, r.status, r.foto,
            r.alamat_lokasi, r.latitude, r.longitude, r.created_at,
            c.nama_kategori AS kategori, u.nama AS pelapor
     FROM reports r
     JOIN categories c ON r.category_id = c.id
     JOIN users u ON r.user_id = u.id
     WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL
     ORDER BY r.created_at DESC"
);
$laporan = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

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
        'pelapor'       => $lap['pelapor'],
    ];
}, $laporan);

$kategoriList = $koneksi->query("SELECT id, nama_kategori FROM categories ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Peta Laporan | Admin Siaga Masyarakat</title>
  <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
  <style>
    #peta-laporan { height: calc(100vh - 240px); min-height: 450px; }
    .peta-legend { background:#fff; padding:8px 12px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,.12); font-size:.78rem; }
    .dot { display:inline-block; width:11px; height:11px; border-radius:50%; margin-right:5px; border:2px solid #fff; box-shadow:0 0 0 1.5px rgba(0,0,0,.15); }
  </style>
</head>
<body>
<?php include __DIR__ . '/nav/navbar_admin.php'; ?>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="dashboard-wrapper">
  <?php include __DIR__ . '/nav/sidebar_admin.php'; ?>
  <div class="dashboard-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="fw-bold mb-0">Peta Laporan</h4>
      <small class="text-muted"><?= count($laporan) ?> laporan dengan koordinat</small>
    </div>

    <!-- Filter -->
    <div class="card card-custom mb-3">
      <div class="card-body py-2 px-3">
        <div class="row g-2 align-items-center">
          <div class="col-md-3">
            <input type="text" id="cari-laporan" class="form-control form-control-sm" placeholder="Cari judul...">
          </div>
          <div class="col-md-2">
            <select id="filter-status" class="form-select form-select-sm">
              <option value="">Semua Status</option>
              <?php foreach (['Menunggu','Diverifikasi','Diproses','Selesai','Ditolak'] as $s): ?>
                <option><?= $s ?></option>
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
          <div class="col-auto ms-auto">
            <div class="peta-legend d-inline-block">
              <span class="dot" style="background:#ffc107;"></span>Menunggu&nbsp;
              <span class="dot" style="background:#0d6efd;"></span>Diverifikasi&nbsp;
              <span class="dot" style="background:#fd7e14;"></span>Diproses&nbsp;
              <span class="dot" style="background:#198754;"></span>Selesai&nbsp;
              <span class="dot" style="background:#dc3545;"></span>Ditolak
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="peta-laporan" class="rounded border"></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<script>
const petaData = <?= json_encode($peta_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
initPetaLaporan('peta-laporan', petaData);
</script>
</body>
</html>

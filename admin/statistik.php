<?php
/**
 * admin/statistik.php
 * Dashboard statistik dengan Chart.js — laporan per kategori, per status, per bulan.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Pastikan hanya admin yang bisa mengakses halaman ini.
requireRole('admin');

// ---- Data untuk grafik ----

// 1. Laporan per kategori
$perKategori = $koneksi->query(
    "SELECT c.nama_kategori, COUNT(r.id) AS jumlah
     FROM categories c
     LEFT JOIN reports r ON r.category_id = c.id
     GROUP BY c.id, c.nama_kategori
     ORDER BY jumlah DESC"
)->fetch_all(MYSQLI_ASSOC);

// 2. Laporan per status
$perStatus = $koneksi->query(
    "SELECT status, COUNT(*) AS jumlah FROM reports GROUP BY status ORDER BY jumlah DESC"
)->fetch_all(MYSQLI_ASSOC);

// 3. Laporan per bulan (12 bulan terakhir)
$perBulan = $koneksi->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS bulan, COUNT(*) AS jumlah
     FROM reports
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY bulan ORDER BY bulan ASC"
)->fetch_all(MYSQLI_ASSOC);

// 4. Kegiatan per status
$kegStatus = $koneksi->query(
    "SELECT status, COUNT(*) AS jumlah FROM activities GROUP BY status"
)->fetch_all(MYSQLI_ASSOC);

// 5. Statistik ringkasan
$queryStat = "
    SELECT
        (SELECT COUNT(*) FROM reports) AS total,
        (SELECT COUNT(*) FROM reports WHERE status = 'Selesai') AS selesai,
        (SELECT COUNT(*) FROM users WHERE role = 'warga') AS total_warga,
        (SELECT COUNT(*) FROM activities) AS total_kegiatan
";


$resultStat = $koneksi->query($queryStat);
$stat = $resultStat->fetch_assoc();

// Buat data untuk Chart.js
$katLabels = json_encode(array_column($perKategori, 'nama_kategori'));
$katData   = json_encode(array_column($perKategori, 'jumlah'));

$statusLabels = json_encode(array_column($perStatus, 'status'));
$statusData   = json_encode(array_column($perStatus, 'jumlah'));
$statusColors = json_encode(array_map(function($s) {
    $map = ['Menunggu'=>'#ffc107','Diverifikasi'=>'#0d6efd','Diproses'=>'#fd7e14','Selesai'=>'#198754','Ditolak'=>'#dc3545'];
    return $map[$s['status']] ?? '#6c757d';
}, $perStatus));

$bulanLabels = json_encode(array_map(function($b) {
    $parts = explode('-', $b['bulan']);
    $bulan = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun',
              '07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];
    return ($bulan[$parts[1]] ?? $parts[1]) . ' ' . $parts[0];
}, $perBulan));
$bulanData = json_encode(array_column($perBulan, 'jumlah'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statistik | Admin Siaga Masyarakat</title>
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
    <h4 class="fw-bold mb-4">Laporan Statistik</h4>

    <!-- Statistik Ringkasan -->
    <div class="row g-3 mb-4">
      <?php
      $cards = [
        ['icon'=>'bi-files',           'label'=>'Total Laporan',   'value'=>$stat['total'],         'bg'=>'#e8f5ee', 'color'=>'var(--color-primary)'],
        ['icon'=>'bi-check-circle-fill','label'=>'Laporan Selesai', 'value'=>$stat['selesai'],       'bg'=>'#d1e7dd', 'color'=>'#198754'],
        ['icon'=>'bi-people-fill',      'label'=>'Total Warga',     'value'=>$stat['total_warga'],   'bg'=>'#cfe2ff', 'color'=>'#0a58ca'],
        ['icon'=>'bi-calendar-event',   'label'=>'Total Kegiatan',  'value'=>$stat['total_kegiatan'],'bg'=>'#fff0d3', 'color'=>'#fd7e14'],
      ];
      foreach ($cards as $c):
      ?>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:<?= $c['bg'] ?>;">
            <i class="<?= $c['icon'] ?>" style="color:<?= $c['color'] ?>"></i>
          </div>
          <div class="stat-number" style="color:<?= $c['color'] ?>"><?= $c['value'] ?></div>
          <div class="stat-label"><?= $c['label'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Grafik Baris 1 -->
    <div class="row g-4 mb-4">
      <!-- Laporan per Bulan -->
      <div class="col-lg-8">
        <div class="card card-custom h-100">
          <div class="card-header"><i class="bi bi-bar-chart-line me-2"></i>Laporan per Bulan (12 Bulan Terakhir)</div>
          <div class="card-body">
            <canvas id="chartBulan" height="100"></canvas>
          </div>
        </div>
      </div>
      <!-- Laporan per Status -->
      <div class="col-lg-4">
        <div class="card card-custom h-100">
          <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Laporan per Status</div>
          <div class="card-body d-flex align-items-center justify-content-center">
            <canvas id="chartStatus" style="max-height:250px;"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Grafik Baris 2 -->
    <div class="row g-4">
      <!-- Laporan per Kategori -->
      <div class="col-12">
        <div class="card card-custom">
          <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Laporan per Kategori</div>
          <div class="card-body">
            <canvas id="chartKategori" height="80"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabel Ringkasan -->
    <div class="row g-4 mt-1">
      <div class="col-md-6">
        <div class="card card-custom">
          <div class="card-header"><i class="bi bi-tags me-2"></i>Laporan per Kategori</div>
          <div class="card-body p-0">
            <table class="table table-custom mb-0">
              <thead><tr><th>Kategori</th><th class="text-end">Jumlah</th></tr></thead>
              <tbody>
                <?php foreach ($perKategori as $kat): ?>
                <tr>
                  <td><?= e($kat['nama_kategori']) ?></td>
                  <td class="text-end fw-semibold"><?= $kat['jumlah'] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card card-custom">
          <div class="card-header"><i class="bi bi-flag me-2"></i>Laporan per Status</div>
          <div class="card-body p-0">
            <table class="table table-custom mb-0">
              <thead><tr><th>Status</th><th class="text-end">Jumlah</th></tr></thead>
              <tbody>
                <?php foreach ($perStatus as $st): ?>
                <tr>
                  <td><span class="badge-status <?= badgeStatus($st['status']) ?>"><?= e($st['status']) ?></span></td>
                  <td class="text-end fw-semibold"><?= $st['jumlah'] ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<script>
// Chart: Laporan per Bulan
new Chart(document.getElementById('chartBulan'), {
  type: 'bar',
  data: {
    labels: <?= $bulanLabels ?>,
    datasets: [{
      label: 'Jumlah Laporan',
      data: <?= $bulanData ?>,
      backgroundColor: 'rgba(26,122,74,0.7)',
      borderColor: 'rgba(26,122,74,1)',
      borderWidth: 1,
      borderRadius: 4,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
  }
});

// Chart: Laporan per Status (Pie)
new Chart(document.getElementById('chartStatus'), {
  type: 'doughnut',
  data: {
    labels: <?= $statusLabels ?>,
    datasets: [{
      data: <?= $statusData ?>,
      backgroundColor: <?= $statusColors ?>,
      borderWidth: 2,
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'bottom', labels: { font: { size: 11 } } }
    }
  }
});

// Chart: Laporan per Kategori (Horizontal Bar)
new Chart(document.getElementById('chartKategori'), {
  type: 'bar',
  data: {
    labels: <?= $katLabels ?>,
    datasets: [{
      label: 'Jumlah Laporan',
      data: <?= $katData ?>,
      backgroundColor: [
        '#1a7a4a','#f97316','#0d6efd','#ffc107','#dc3545','#6f42c1','#20c997','#adb5bd'
      ],
      borderRadius: 4,
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
  }
});
</script>
</body>
</html>

<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';

if (($_SESSION['role'] ?? null) !== 'admin') {
    header('Location: ' . BASE_URL . '/logout.php');
    exit;
}

$userName = $_SESSION['username'] ?? $_SESSION['nama'] ?? 'Admin';

$stat = [
    'total' => 0,
    'menunggu' => 0,
    'diverifikasi' => 0,
    'diproses' => 0,
    'selesai' => 0,
    'ditolak' => 0,
    'total_warga' => 0,
    'total_kegiatan' => 0,
    'total_peserta' => 0,
];

$res = mysqli_query($conn, "SELECT status, COUNT(*) AS n FROM reports GROUP BY status");
while ($r = mysqli_fetch_assoc($res)) {
    $key = strtolower($r['status']);
    if (isset($stat[$key])) {
        $stat[$key] = (int) $r['n'];
    }
    $stat['total'] += (int) $r['n'];
}

$stat['total_warga'] = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='warga'"))[0];
$stat['total_kegiatan'] = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM activities"))[0];
$stat['total_peserta'] = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM activity_participants"))[0];

$laporanBaru = [];
$sql = "SELECT r.id, r.nomor_laporan, r.judul, r.status, r.created_at,
              c.nama_kategori AS nama_kategori,
              u.nama AS pelapor
        FROM reports r
        LEFT JOIN categories c ON c.id = r.category_id
        LEFT JOIN users u ON u.id = r.user_id
        ORDER BY r.id DESC
        LIMIT 5";
$res = mysqli_query($conn, $sql);

if (!$res) {
    die('<pre>SQL Error: ' . mysqli_error($conn) . "\n\n" . $sql . '</pre>');
}
while ($r = mysqli_fetch_assoc($res)) {
    $laporanBaru[] = $r;
}

function statusBadgeClass(string $status): string
{
    $status = strtolower(trim($status));
    $map = [
        'menunggu' => 'badge-menunggu',
        'diverifikasi' => 'badge-diverifikasi',
        'diproses' => 'badge-diproses',
        'selesai' => 'badge-selesai',
        'ditolak' => 'badge-ditolak',
    ];

    return $map[$status] ?? 'badge-menunggu';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Siaga Masyarakat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .admin-shell { min-height: 100vh; }
        .sidebar {
            width: 260px;
            background: #12382a;
            color: #fff;
            min-height: 100vh;
            padding: 1rem 0.75rem;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 10px;
            padding: 0.7rem 0.9rem;
            margin-bottom: 0.25rem;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }
        .dashboard-main {
            flex: 1;
            padding: 2rem;
        }
        .stat-card {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.05);
        }
        .stat-card .card-body {
            padding: 1.2rem 1.1rem;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .stat-value {
            font-size: 1.7rem;
            font-weight: 700;
            margin-top: 0.35rem;
            color: #0f172a;
        }
        .status-pill {
            display: inline-block;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
        }
        .table td, .table th { vertical-align: middle; }
        @media (max-width: 991.98px) {
            .admin-shell { display: block; }
            .sidebar {
                width: 100%;
                min-height: auto;
            }
            .dashboard-main { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="admin-shell d-flex">
        <?php require __DIR__ . '/nav/sidebar_admin.php'; ?>

        <main class="dashboard-main">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h1 class="h3 mb-1">Dashboard Admin</h1>
                    <p class="text-muted mb-0">Selamat datang, <?= htmlspecialchars($userName) ?>.</p>
                </div>
                <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl-4">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Total Laporan</div>
                            <div class="stat-value"><?= number_format($stat['total'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Menunggu</div>
                            <div class="stat-value"><?= number_format($stat['menunggu'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Diverifikasi</div>
                            <div class="stat-value"><?= number_format($stat['diverifikasi'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Diproses</div>
                            <div class="stat-value"><?= number_format($stat['diproses'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Selesai</div>
                            <div class="stat-value"><?= number_format($stat['selesai'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Ditolak</div>
                            <div class="stat-value"><?= number_format($stat['ditolak'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Total Warga</div>
                            <div class="stat-value"><?= number_format($stat['total_warga'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Total Kegiatan</div>
                            <div class="stat-value"><?= number_format($stat['total_kegiatan'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Total Peserta</div>
                            <div class="stat-value"><?= number_format($stat['total_peserta'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h2 class="h5 mb-0">Laporan Terbaru</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Judul</th>
                                    <th>Pelapor</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($laporanBaru)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada laporan terbaru.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($laporanBaru as $laporan): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?= htmlspecialchars($laporan['judul'] ?? '-') ?></div>
                                                <small class="text-muted">#<?= htmlspecialchars($laporan['nomor_laporan'] ?? '-') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($laporan['pelapor'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($laporan['nama_kategori'] ?? '-') ?></td>
                                            <td>
                                                <span class="status-pill <?= statusBadgeClass($laporan['status'] ?? 'Menunggu') ?>"><?= htmlspecialchars($laporan['status'] ?? 'Menunggu') ?></span>
                                            </td>
                                            <td><?= htmlspecialchars(date('d M Y', strtotime($laporan['created_at'] ?? 'now'))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

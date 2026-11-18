<?php

// ======================================
// SESSION HELPERS
// ======================================

/**
 * Start session if not already started.
 * Alias for the session bootstrap already done in auth.php,
 * but kept as a no-op guard so pages that call mulaiSession()
 * before including auth.php still work.
 */
function mulaiSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Enforce that the logged-in user has the given role.
 * Redirects to login if not authenticated, 403 if wrong role.
 */
function paksakanRole(string $role): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        include __DIR__ . '/403.php';
        exit;
    }
}


// ======================================
// FLASH MESSAGES
// ======================================

/**
 * Store a one-time flash message in the session.
 *
 * @param string $type  'sukses' | 'gagal' | 'info' | 'peringatan'
 * @param string $pesan Message text
 */
function setFlash(string $type, string $pesan): void
{
    $_SESSION['flash'][$type] = $pesan;
}


// ======================================
// REDIRECT HELPER
// ======================================

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}


// ======================================
// CSRF HELPERS
// ======================================

/**
 * Generate (or reuse) a CSRF token stored in the session.
 */
function buatCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify the CSRF token submitted with a POST request.
 * Terminates with 403 on failure.
 */
function verifikasiCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('403 Forbidden — Invalid CSRF token.');
    }
}


// ======================================
// INPUT HELPERS
// ======================================

/**
 * Cast a value to a non-negative integer (returns 0 for invalid input).
 */
function inputInt($value): int
{
    return max(0, (int) $value);
}

/**
 * Returns true when a string is empty or whitespace-only.
 */
function kosong(string $value): bool
{
    return trim($value) === '';
}


/**
 * Generate nomor laporan unik dengan format SM-YYYY-NNNNN.
 *
 * @param mysqli $db
 */
function generateNomorLaporan(mysqli $db): string
{
    $tahun = date('Y');
    $stmt  = $db->prepare(
        "SELECT nomor_laporan FROM reports
         WHERE nomor_laporan LIKE ? ORDER BY id DESC LIMIT 1"
    );
    $like  = 'SM-' . $tahun . '-%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $hasil = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $urutan = 1;
    if ($hasil) {
        $bagian = explode('-', $hasil['nomor_laporan']);
        $urutan = (int)end($bagian) + 1;
    }
    return 'SM-' . $tahun . '-' . str_pad($urutan, 5, '0', STR_PAD_LEFT);
}


// ======================================
// FILE UPLOAD HELPERS
// ======================================

/**
 * Upload a photo from a $_FILES entry to the given directory.
 *
 * @param  array  $file   Single entry from $_FILES
 * @param  string $dir    Absolute path to destination directory (with trailing slash)
 * @return array  ['ok' => bool, 'nama' => string, 'pesan' => string]
 */
function uploadFoto(array $file, string $dir): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'nama' => '', 'pesan' => 'Gagal mengunggah file (kode ' . $file['error'] . ').'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['ok' => false, 'nama' => '', 'pesan' => 'Ukuran file maksimal 2 MB.'];
    }

    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMG, true)) {
        return ['ok' => false, 'nama' => '', 'pesan' => 'Tipe file tidak diizinkan. Gunakan JPG, PNG, atau WebP.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXT, true)) {
        return ['ok' => false, 'nama' => '', 'pesan' => 'Ekstensi file tidak diizinkan.'];
    }

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $nama = uniqid('img_', true) . '.' . $ext;
    $dest = $dir . $nama;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'nama' => '', 'pesan' => 'Gagal menyimpan file ke server.'];
    }

    return ['ok' => true, 'nama' => $nama, 'pesan' => ''];
}

/**
 * Delete an uploaded photo from the given directory.
 * Silently ignores missing files.
 */
function hapusFoto(string $dir, ?string $nama): void
{
    if ($nama && file_exists($dir . $nama)) {
        @unlink($dir . $nama);
    }
}


// ======================================
// GENERAL HELPER FUNCTIONS
// ======================================

// Mengamankan output HTML
function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// ======================================
// DATE FORMAT
// ======================================

function formatTanggal($tanggal)
{
    if (empty($tanggal)) {
        return '-';
    }

    return date('d-m-Y', strtotime($tanggal));
}

/**
 * Format a datetime string as "d-m-Y H:i".
 */
function formatWaktu($tanggal): string
{
    if (empty($tanggal)) {
        return '-';
    }

    return date('d-m-Y H:i', strtotime($tanggal));
}


// ======================================
// REPORT STATISTICS
// ======================================

function getStatistikLaporan(mysqli $koneksi): array
{
    $query = "
        SELECT
            -- Statistik laporan
            (SELECT COUNT(*) FROM reports) AS total,

            (SELECT COUNT(*) FROM reports
             WHERE status = 'Menunggu') AS menunggu,

            (SELECT COUNT(*) FROM reports
             WHERE status = 'Diverifikasi') AS diverifikasi,

            (SELECT COUNT(*) FROM reports
             WHERE status = 'Diproses') AS diproses,

            (SELECT COUNT(*) FROM reports
             WHERE status = 'Selesai') AS selesai,

            (SELECT COUNT(*) FROM reports
             WHERE status = 'Ditolak') AS ditolak,

            -- Statistik warga
            (SELECT COUNT(*) FROM users
             WHERE role = 'warga') AS total_warga,

            -- Statistik kegiatan
            (SELECT COUNT(*) FROM activities) AS total_kegiatan,

            (SELECT COUNT(*) FROM activities
             WHERE status = 'Akan Datang') AS kegiatan_akan_datang,

            (SELECT COUNT(*) FROM activities
             WHERE status = 'Berlangsung') AS kegiatan_berlangsung,

            (SELECT COUNT(*) FROM activities
             WHERE status = 'Selesai') AS kegiatan_selesai,

            (SELECT COUNT(*) FROM activities
             WHERE status = 'Dibatalkan') AS kegiatan_dibatalkan,

            -- Statistik peserta
            (SELECT COUNT(*) FROM activity_participants
             WHERE status_pendaftaran = 'Terdaftar') AS total_peserta
    ";

    $result = $koneksi->query($query);

    return $result->fetch_assoc();
}


// ======================================
// REPORT STATUS BADGE
// ======================================

function badgeStatus($status)
{
    $badges = [
        'Menunggu' => 'bg-warning text-dark',
        'Diverifikasi' => 'bg-info text-dark',
        'Diproses' => 'bg-primary',
        'Selesai' => 'bg-success',
        'Ditolak' => 'bg-danger'
    ];

    return $badges[$status] ?? 'bg-secondary';
}


// ======================================
// ACTIVITY STATUS BADGE
// ======================================

function badgeKegiatan($status)
{
    $badges = [
        'Akan Datang' => 'bg-primary',
        'Berlangsung' => 'bg-success',
        'Selesai' => 'bg-secondary',
        'Dibatalkan' => 'bg-danger'
    ];

    return $badges[$status] ?? 'bg-secondary';
}

function tampilkanFlash(): string
{
    if (empty($_SESSION['flash'])) return '';

    $html = '';
    $map  = [
        'sukses'     => 'success',
        'gagal'      => 'danger',
        'info'       => 'info',
        'peringatan' => 'warning',
    ];
    foreach ($_SESSION['flash'] as $type => $pesan) {
        $bsClass = $map[$type] ?? 'info';
        $html   .= '<div class="alert alert-' . $bsClass . ' alert-dismissible fade show" role="alert">'
                 . e($pesan)
                 . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
                 . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

function getStatistikAdmin(mysqli $db): array
{
    $stat = getStatistikLaporan($db);
    $r    = $db->query("SELECT COUNT(*) AS total FROM users WHERE role='warga'")->fetch_assoc();
    $a    = $db->query("SELECT COUNT(*) AS total FROM activities")->fetch_assoc();
    $p    = $db->query("SELECT COUNT(*) AS total FROM activity_participants WHERE status_pendaftaran='Terdaftar'")->fetch_assoc();
    return array_merge($stat, [
        'total_warga'    => $r['total'] ?? 0,
        'total_kegiatan' => $a['total'] ?? 0,
        'total_peserta'  => $p['total'] ?? 0,
    ]);
}



?>
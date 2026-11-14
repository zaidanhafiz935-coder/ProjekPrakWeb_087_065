<?php

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


// ======================================
// REPORT STATISTICS
// ======================================

function getStatistikLaporan($koneksi)
{
    $stat = [
        'total' => 0,
        'menunggu' => 0,
        'diproses' => 0,
        'selesai' => 0
    ];

    $query = "
        SELECT
            COUNT(*) AS total,
            SUM(status = 'Menunggu') AS menunggu,
            SUM(status = 'Diproses') AS diproses,
            SUM(status = 'Selesai') AS selesai
        FROM reports
    ";

    $result = $koneksi->query($query);

    if ($result) {
        $stat = $result->fetch_assoc();
    }

    return $stat;
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

?>
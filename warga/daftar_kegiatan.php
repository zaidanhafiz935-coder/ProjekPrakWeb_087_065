<?php
/**
 * warga/daftar_kegiatan.php
 * Proses pendaftaran warga ke kegiatan sosial.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
paksakanRole('warga');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(BASE_URL . '/warga/kegiatan.php'); }
verifikasiCsrf();

$userId     = penggunaLogin()['id'];
$activityId = inputInt($_POST['activity_id'] ?? 0);

if (!$activityId) { setFlash('gagal', 'Data tidak valid.'); redirect(BASE_URL . '/warga/kegiatan.php'); }

// Ambil data kegiatan
$stmt = $koneksi->prepare(
    "SELECT a.id, a.status, a.kuota,
            (SELECT COUNT(*) FROM activity_participants ap
             WHERE ap.activity_id = a.id AND ap.status_pendaftaran = 'Terdaftar') AS jml_peserta
     FROM activities a WHERE a.id = ?"
);
$stmt->bind_param('i', $activityId);
$stmt->execute();
$keg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$keg) { setFlash('gagal', 'Kegiatan tidak ditemukan.'); redirect(BASE_URL . '/warga/kegiatan.php'); }
if ($keg['status'] !== 'Akan Datang') { setFlash('gagal', 'Pendaftaran sudah ditutup untuk kegiatan ini.'); redirect(BASE_URL . '/warga/detail_kegiatan.php?id=' . $activityId); }
if ($keg['jml_peserta'] >= $keg['kuota']) { setFlash('gagal', 'Maaf, kuota peserta sudah penuh.'); redirect(BASE_URL . '/warga/detail_kegiatan.php?id=' . $activityId); }

// Cek sudah terdaftar sebelumnya (termasuk yang dibatalkan — bisa re-register)
$stmtC = $koneksi->prepare(
    "SELECT id, status_pendaftaran FROM activity_participants WHERE activity_id = ? AND user_id = ?"
);
$stmtC->bind_param('ii', $activityId, $userId);
$stmtC->execute();
$existing = $stmtC->get_result()->fetch_assoc();
$stmtC->close();

if ($existing) {
    if ($existing['status_pendaftaran'] === 'Terdaftar') {
        setFlash('info', 'Anda sudah terdaftar dalam kegiatan ini.');
        redirect(BASE_URL . '/warga/detail_kegiatan.php?id=' . $activityId);
    }
    // Update kembali dari Dibatalkan ke Terdaftar
    $stmtU = $koneksi->prepare(
        "UPDATE activity_participants SET status_pendaftaran='Terdaftar', created_at=NOW() WHERE id = ?"
    );
    $stmtU->bind_param('i', $existing['id']);
    $stmtU->execute();
    $stmtU->close();
} else {
    // Insert baru
    $stmtI = $koneksi->prepare(
        "INSERT INTO activity_participants (activity_id, user_id, status_pendaftaran) VALUES (?,?,'Terdaftar')"
    );
    $stmtI->bind_param('ii', $activityId, $userId);
    $stmtI->execute();
    $stmtI->close();
}

setFlash('sukses', 'Pendaftaran berhasil! Kami menantikan kehadiran Anda.');
redirect(BASE_URL . '/warga/detail_kegiatan.php?id=' . $activityId);

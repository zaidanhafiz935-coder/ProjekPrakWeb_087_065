<?php
/**
 * warga/batal_kegiatan.php
 * Pembatalan pendaftaran warga dari kegiatan sosial.
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

// Cek kegiatan masih 'Akan Datang'
$stmt = $koneksi->prepare("SELECT status FROM activities WHERE id = ?");
$stmt->bind_param('i', $activityId);
$stmt->execute();
$keg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$keg || $keg['status'] !== 'Akan Datang') {
    setFlash('gagal', 'Tidak dapat membatalkan kegiatan yang sudah berlangsung atau selesai.');
    redirect(BASE_URL . '/warga/detail_kegiatan.php?id=' . $activityId);
}

$stmtU = $koneksi->prepare(
    "UPDATE activity_participants SET status_pendaftaran='Dibatalkan'
     WHERE activity_id = ? AND user_id = ? AND status_pendaftaran = 'Terdaftar'"
);
$stmtU->bind_param('ii', $activityId, $userId);
if ($stmtU->execute() && $stmtU->affected_rows > 0) {
    setFlash('sukses', 'Pendaftaran berhasil dibatalkan.');
} else {
    setFlash('gagal', 'Tidak dapat membatalkan. Anda mungkin belum terdaftar.');
}
$stmtU->close();

redirect(BASE_URL . '/warga/detail_kegiatan.php?id=' . $activityId);

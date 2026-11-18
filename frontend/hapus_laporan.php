<?php
/**
 * warga/hapus_laporan.php
 * Menghapus laporan milik warga jika status masih "Menunggu".
 * Hanya menerima POST dengan CSRF token.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
paksakanRole('warga');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/warga/laporan_saya.php');
}

verifikasiCsrf();

$user   = penggunaLogin();
$userId = $user['id'];
$id     = inputInt($_POST['id'] ?? 0);

if (!$id) { setFlash('gagal', 'ID laporan tidak valid.'); redirect(BASE_URL . '/warga/laporan_saya.php'); }

// Verifikasi kepemilikan + status
$stmt = $koneksi->prepare(
    "SELECT id, foto, status FROM reports WHERE id = ? AND user_id = ?"
);
$stmt->bind_param('ii', $id, $userId);
$stmt->execute();
$laporan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$laporan) {
    setFlash('gagal', 'Laporan tidak ditemukan atau bukan milik Anda.');
    redirect(BASE_URL . '/warga/laporan_saya.php');
}
if ($laporan['status'] !== 'Menunggu') {
    setFlash('gagal', 'Laporan hanya dapat dihapus jika masih berstatus "Menunggu".');
    redirect(BASE_URL . '/warga/laporan_saya.php');
}

// Hapus dari database (CASCADE akan hapus report_histories)
$stmtD = $koneksi->prepare("DELETE FROM reports WHERE id = ? AND user_id = ? AND status = 'Menunggu'");
$stmtD->bind_param('ii', $id, $userId);
if ($stmtD->execute() && $stmtD->affected_rows > 0) {
    hapusFoto(UPLOAD_REPORTS, $laporan['foto']);
    setFlash('sukses', 'Laporan berhasil dihapus.');
} else {
    setFlash('gagal', 'Gagal menghapus laporan.');
}
$stmtD->close();

redirect(BASE_URL . '/warga/laporan_saya.php');

<?php
/**
 * admin/hapus_laporan.php
 * Hapus laporan oleh admin — hanya POST.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

mulaiSession();
paksakanRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(BASE_URL . '/admin/laporan.php'); }
verifikasiCsrf();

$id = inputInt($_POST['id'] ?? 0);
if (!$id) { setFlash('gagal', 'ID tidak valid.'); redirect(BASE_URL . '/admin/laporan.php'); }

$stmt = $koneksi->prepare("SELECT id, foto FROM reports WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$lap = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lap) { setFlash('gagal', 'Laporan tidak ditemukan.'); redirect(BASE_URL . '/admin/laporan.php'); }

$stmtD = $koneksi->prepare("DELETE FROM reports WHERE id = ?");
$stmtD->bind_param('i', $id);
if ($stmtD->execute()) {
    hapusFoto(UPLOAD_REPORTS, $lap['foto']);
    setFlash('sukses', 'Laporan berhasil dihapus.');
} else {
    setFlash('gagal', 'Gagal menghapus laporan.');
}
$stmtD->close();
redirect(BASE_URL . '/admin/laporan.php');

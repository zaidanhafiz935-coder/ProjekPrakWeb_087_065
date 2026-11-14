<!-- includes/footer.php -->
<footer class="footer-custom mt-auto pt-5 pb-3">
  <div class="container">
    <div class="row g-4">
      <!-- Tentang -->
      <div class="col-lg-4 col-md-6">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-shield-check-fill fs-3 text-accent"></i>
          <h5 class="fw-bold mb-0 text-white">Siaga Masyarakat</h5>
        </div>
        <p class="text-footer-muted small">
          Platform digital pelaporan masalah lingkungan dan fasilitas umum.
          Menghubungkan warga dengan pengelola lingkungan untuk mewujudkan
          lingkungan yang lebih bersih, aman, dan tertata.
        </p>
        <p class="text-accent fst-italic small">"Peduli Lingkungan, Bersama Membangun Masyarakat"</p>
      </div>

      <!-- Navigasi Cepat -->
      <div class="col-lg-2 col-md-6">
        <h6 class="text-white fw-semibold mb-3">Navigasi</h6>
        <ul class="list-unstyled">
          <li class="mb-1"><a href="<?= BASE_URL ?>/" class="text-footer-muted text-decoration-none small">
            <i class="bi bi-chevron-right me-1"></i>Beranda</a></li>
          <li class="mb-1"><a href="<?= BASE_URL ?>/warga/tambah_laporan.php" class="text-footer-muted text-decoration-none small">
            <i class="bi bi-chevron-right me-1"></i>Lapor Masalah</a></li>
          <li class="mb-1"><a href="<?= BASE_URL ?>/warga/peta.php" class="text-footer-muted text-decoration-none small">
            <i class="bi bi-chevron-right me-1"></i>Peta Laporan</a></li>
          <li class="mb-1"><a href="<?= BASE_URL ?>/warga/kegiatan.php" class="text-footer-muted text-decoration-none small">
            <i class="bi bi-chevron-right me-1"></i>Kegiatan Sosial</a></li>
          <li class="mb-1"><a href="<?= BASE_URL ?>/login.php" class="text-footer-muted text-decoration-none small">
            <i class="bi bi-chevron-right me-1"></i>Login</a></li>
        </ul>
      </div>

      <!-- Kategori Laporan -->
      <div class="col-lg-3 col-md-6">
        <h6 class="text-white fw-semibold mb-3">Kategori Laporan</h6>
        <ul class="list-unstyled">
          <li class="mb-1"><span class="text-footer-muted small"><i class="bi bi-dot me-1"></i>Jalan Rusak</span></li>
          <li class="mb-1"><span class="text-footer-muted small"><i class="bi bi-dot me-1"></i>Sampah Menumpuk</span></li>
          <li class="mb-1"><span class="text-footer-muted small"><i class="bi bi-dot me-1"></i>Drainase Tersumbat</span></li>
          <li class="mb-1"><span class="text-footer-muted small"><i class="bi bi-dot me-1"></i>Lampu Jalan Mati</span></li>
          <li class="mb-1"><span class="text-footer-muted small"><i class="bi bi-dot me-1"></i>Fasilitas Umum Rusak</span></li>
          <li class="mb-1"><span class="text-footer-muted small"><i class="bi bi-dot me-1"></i>Banjir & Lainnya</span></li>
        </ul>
      </div>

      <!-- Kontak -->
      <div class="col-lg-3 col-md-6">
        <h6 class="text-white fw-semibold mb-3">Kontak</h6>
        <ul class="list-unstyled">
          <li class="mb-2 text-footer-muted small">
            <i class="bi bi-geo-alt-fill text-accent me-2"></i>Jl. Merdeka No. 1, Jakarta Pusat
          </li>
          <li class="mb-2 text-footer-muted small">
            <i class="bi bi-envelope-fill text-accent me-2"></i>info@siagamasyarakat.id
          </li>
          <li class="mb-2 text-footer-muted small">
            <i class="bi bi-telephone-fill text-accent me-2"></i>(021) 1234-5678
          </li>
          <li class="mb-2 text-footer-muted small">
            <i class="bi bi-clock-fill text-accent me-2"></i>Senin – Jumat, 08:00 – 16:00 WIB
          </li>
        </ul>
      </div>
    </div>

    <hr class="border-secondary mt-4">
    <div class="row align-items-center">
      <div class="col-md-6 text-footer-muted small text-center text-md-start">
        &copy; <?= date('Y') ?> <strong class="text-white">Siaga Masyarakat</strong>. Hak cipta dilindungi.
      </div>
      <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
        <span class="text-footer-muted small">Dibangun oleh
          <i class="bi bi-tree mx-1"></i>
          Gilberto Silvana & Zinidine Zidan
        </span>
      </div>
    </div>
  </div>
</footer>

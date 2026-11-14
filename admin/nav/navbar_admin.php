<!-- admin/partials/admin_navbar.php -->
<?php
$namaAdmin = htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom shadow-sm sticky-top">
  <div class="container-fluid px-3">
    <button class="btn btn-link text-white me-2 d-lg-none p-0" id="btn-sidebar-toggle">
      <i class="bi bi-list fs-4"></i>
    </button>
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/admin/dashboard.php">
      <i class="bi bi-shield-check-fill text-accent"></i>
      <span class="fw-bold">Siaga Masyarakat</span>
      <span class="badge bg-accent ms-1 small">Admin</span>
    </a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <div class="dropdown">
        <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle me-1"></i><?= $namaAdmin ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= BASE_URL ?>/"><i class="bi bi-house me-2"></i>Lihat Website</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
            <i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

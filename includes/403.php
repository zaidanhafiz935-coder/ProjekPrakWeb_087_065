<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 Akses Ditolak | Siaga Masyarakat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
  <div class="text-center p-4">
    <i class="bi bi-shield-lock-fill display-1 text-danger mb-3 d-block"></i>
    <h1 class="fw-bold">403</h1>
    <h4 class="text-muted mb-3">Akses Ditolak</h4>
    <p class="text-muted mb-4">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
    <a href="javascript:history.back()" class="btn btn-secondary me-2">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
    <a href="<?php echo defined('BASE_URL') ? BASE_URL : '/'; ?>/index.php" class="btn btn-primary">
      <i class="bi bi-house me-1"></i>Beranda
    </a>
  </div>
</body>
</html>

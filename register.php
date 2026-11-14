<?php

session_start();


require_once __DIR__ . '/config/koneksi.php';

// Jika sudah login, arahkan ke halaman sesuai role
if (isset($_SESSION['user_id'])) {
    $halaman = $_SESSION['role'] === 'admin'
        ? 'admin/dashboard.php'
        : 'index.php';

    header('Location: ' . BASE_URL . '/' . $halaman);
    exit;
}

$error = [];
$nama = '';
$email = '';
$no_telepon = '';
$alamat = '';

// Membuat token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasi CSRF
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Permintaan tidak valid. Silakan muat ulang halaman.');
    }

    // Mengambil data form
    $nama = trim($_POST['nama'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    // =========================
    // VALIDASI INPUT
    // =========================

    if ($nama === '') {
        $error['nama'] = 'Nama wajib diisi.';
    } elseif (mb_strlen($nama) < 3) {
        $error['nama'] = 'Nama minimal 3 karakter.';
    }

    if ($email === '') {
        $error['email'] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error['email'] = 'Format email tidak valid.';
    }

    if ($no_telepon !== '' &&
        !preg_match('/^[0-9+\-\s]{7,20}$/', $no_telepon)) {
        $error['no_telepon'] = 'Nomor telepon tidak valid.';
    }

    if ($password === '') {
        $error['password'] = 'Password wajib diisi.';
    } elseif (strlen($password) < 8) {
        $error['password'] = 'Password minimal 8 karakter.';
    }

    if ($konfirmasi === '') {
        $error['konfirmasi'] = 'Konfirmasi password wajib diisi.';
    } elseif ($password !== $konfirmasi) {
        $error['konfirmasi'] = 'Password tidak cocok.';
    }

    // =========================
    // CEK EMAIL DUPLIKAT
    // =========================

    if (!isset($error['email'])) {

        $stmt = $koneksi->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $hasil = $stmt->get_result();

        if ($hasil->num_rows > 0) {
            $error['email'] = 'Email sudah terdaftar.';
        }

        $stmt->close();
    }

    // =========================
    // SIMPAN KE DATABASE
    // =========================

    if (empty($error)) {

        // Hash password sebelum disimpan
        $password_hash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        // Registrasi publik selalu menjadi warga
        $role = 'warga';

        $stmt = $koneksi->prepare(
            "INSERT INTO users
            (nama, email, no_telepon, alamat, password, role)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssssss",
            $nama,
            $email,
            $no_telepon,
            $alamat,
            $password_hash,
            $role
        );

        if ($stmt->execute()) {

            // Hapus token setelah registrasi berhasil
            unset($_SESSION['csrf_token']);

            header('Location: ' . BASE_URL . '/login.php?register=success');
            exit;

        } else {
            $error['umum'] = 'Registrasi gagal. Silakan coba lagi.';
        }

        $stmt->close();
    }

    // Buat token baru untuk pengiriman form berikutnya
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register | Siaga Masyarakat</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">

        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">

                <!-- Brand -->
                <div class="text-center mb-4">

                    <a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">

                        <i class="bi bi-shield-check-fill fs-1 text-primary-custom"></i>

                        <h4 class="fw-bold text-primary-custom mt-1">
                            Siaga Masyarakat
                        </h4>

                    </a>

                    <p class="text-muted small">
                        Buat akun untuk mulai menggunakan layanan masyarakat
                    </p>

                </div>

                <!-- Register Card -->
                <div class="card card-custom border-0 shadow">

                    <div class="card-header">
                        <i class="bi bi-person-plus me-2"></i>
                        Daftar Akun Baru
                    </div>

                    <div class="card-body p-4">

                        <!-- Error Umum -->
                        <?php if (isset($error['umum'])): ?>

                            <div class="alert alert-danger">
                                <?= htmlspecialchars($error['umum']) ?>
                            </div>

                        <?php endif; ?>

                        <form action="" method="POST">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars($csrf_token) ?>"
                            >

                            <!-- Nama -->
                            <div class="mb-3">

                                <label for="nama" class="form-label">
                                    Nama Lengkap
                                </label>

                                <input
                                    type="text"
                                    id="nama"
                                    name="nama"
                                    class="form-control <?= isset($error['nama']) ? 'is-invalid' : '' ?>"
                                    value="<?= htmlspecialchars($nama) ?>"
                                    placeholder="Masukkan nama lengkap"
                                    maxlength="150"
                                    required
                                >

                                <?php if (isset($error['nama'])): ?>
                                    <div class="invalid-feedback">
                                        <?= htmlspecialchars($error['nama']) ?>
                                    </div>
                                <?php endif; ?>

                            </div>

                            <!-- Email -->
                            <div class="mb-3">

                                <label for="email" class="form-label">
                                    Alamat Email
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="form-control <?= isset($error['email']) ? 'is-invalid' : '' ?>"
                                    value="<?= htmlspecialchars($email) ?>"
                                    placeholder="email@domain.com"
                                    maxlength="150"
                                    required
                                >

                                <?php if (isset($error['email'])): ?>
                                    <div class="invalid-feedback">
                                        <?= htmlspecialchars($error['email']) ?>
                                    </div>
                                <?php endif; ?>

                            </div>

                            <!-- Nomor Telepon -->
                            <div class="mb-3">

                                <label for="no_telepon" class="form-label">
                                    Nomor Telepon
                                </label>

                                <input
                                    type="text"
                                    id="no_telepon"
                                    name="no_telepon"
                                    class="form-control <?= isset($error['no_telepon']) ? 'is-invalid' : '' ?>"
                                    value="<?= htmlspecialchars($no_telepon) ?>"
                                    placeholder="081234567890"
                                    maxlength="20"
                                >

                                <?php if (isset($error['no_telepon'])): ?>
                                    <div class="invalid-feedback">
                                        <?= htmlspecialchars($error['no_telepon']) ?>
                                    </div>
                                <?php endif; ?>

                            </div>

                            <!-- Alamat -->
                            <div class="mb-3">

                                <label for="alamat" class="form-label">
                                    Alamat
                                </label>

                                <textarea
                                    id="alamat"
                                    name="alamat"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Masukkan alamat tempat tinggal"
                                ><?= htmlspecialchars($alamat) ?></textarea>

                            </div>

                            <!-- Password -->
                            <div class="mb-3">

                                <label for="password" class="form-label">
                                    Password
                                </label>

                                <div class="input-group">

                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        class="form-control <?= isset($error['password']) ? 'is-invalid' : '' ?>"
                                        placeholder="Minimal 8 karakter"
                                        required
                                    >

                                    <button
                                        class="btn btn-outline-secondary"
                                        type="button"
                                        onclick="togglePassword('password', 'eye1')"
                                    >
                                        <i class="bi bi-eye" id="eye1"></i>
                                    </button>

                                    <?php if (isset($error['password'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($error['password']) ?>
                                        </div>
                                    <?php endif; ?>

                                </div>

                            </div>

                            <!-- Konfirmasi Password -->
                            <div class="mb-4">

                                <label for="konfirmasi" class="form-label">
                                    Konfirmasi Password
                                </label>

                                <div class="input-group">

                                    <input
                                        type="password"
                                        id="konfirmasi"
                                        name="konfirmasi"
                                        class="form-control <?= isset($error['konfirmasi']) ? 'is-invalid' : '' ?>"
                                        placeholder="Ulangi password"
                                        required
                                    >

                                    <button
                                        class="btn btn-outline-secondary"
                                        type="button"
                                        onclick="togglePassword('konfirmasi', 'eye2')"
                                    >
                                        <i class="bi bi-eye" id="eye2"></i>
                                    </button>

                                    <?php if (isset($error['konfirmasi'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($error['konfirmasi']) ?>
                                        </div>
                                    <?php endif; ?>

                                </div>

                            </div>

                            <!-- Register Button -->
                            <div class="d-grid">

                                <button
                                    type="submit"
                                    class="btn btn-primary btn-lg fw-semibold"
                                >
                                    <i class="bi bi-person-check me-2"></i>
                                    Buat Akun
                                </button>

                            </div>

                        </form>

                    </div>

                    <!-- Login Link -->
                    <div class="card-footer text-center text-muted small py-3">

                        Sudah punya akun?

                        <a
                            href="<?= BASE_URL ?>/login.php"
                            class="text-primary-custom fw-semibold text-decoration-none"
                        >
                            Login di sini
                        </a>

                    </div>

                </div>

                <!-- Back to Home -->
                <div class="text-center mt-3">

                    <a
                        href="<?= BASE_URL ?>/index.php"
                        class="text-muted text-decoration-none small"
                    >
                        <i class="bi bi-arrow-left me-1"></i>
                        Kembali ke Beranda
                    </a>

                </div>

            </div>
        </div>

    </div>

    <script>
        function togglePassword(inputId, iconId) {

            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }

        }
    </script>

</body>
</html>
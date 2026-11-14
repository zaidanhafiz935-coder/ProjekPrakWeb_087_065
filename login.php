<?php
session_start();

require_once __DIR__ . "/config/koneksi.php";

// Jika sudah login, arahkan ke halaman sesuai role
if (isset($_SESSION['user_id'])) {

    $destination = $_SESSION['role'] === 'admin'
        ? 'admin/dashboard.php'
        : 'index.php';

    header('Location: ' . BASE_URL . '/' . $destination);
    exit;
}

$error = "";
$success = "";
$email = "";

// Pesan setelah registrasi berhasil
if (isset($_GET['register']) && $_GET['register'] === 'success') {
    $success = "Registrasi berhasil! Silakan login.";
}

// Jika form login dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil input terlebih dahulu
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi input
    if (empty($email) || empty($password)) {

        $error = "Email dan password wajib diisi!";

    } else {

        // Cari akun berdasarkan email
        $stmt = $koneksi->prepare(
            "SELECT id, nama, email, password, role, status_akun
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $row = $result->fetch_assoc();

            // Validasi password, status, dan role
            if (
                password_verify($password, $row['password']) &&
                $row['status_akun'] === 'aktif' &&
                in_array($row['role'], ['admin', 'warga'], true)
            ) {

                // Regenerasi session ID setelah login
                session_regenerate_id(true);

                // Simpan informasi pengguna ke session
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['nama'];
                $_SESSION['nama'] = $row['nama'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];

                // Catat aktivitas terakhir
                $_SESSION['last_activity'] = time();

                // Redirect sesuai role
                $destination = $row['role'] === 'admin'
                    ? 'admin/dashboard.php'
                    : 'index.php';

                header('Location: ' . BASE_URL . '/' . $destination);
                exit;

            } else {
                $error = "Email atau password salah!";
            }

        } else {
            $error = "Email atau password salah!";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Siaga Masyarakat</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">

                <!-- Brand -->
                <div class="text-center mb-4">
                    <a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">
                        <i class="bi bi-shield-check-fill fs-1 text-primary"></i>
                        <h4 class="fw-bold text-primary-custom mt-1">
                            Siaga Masyarakat
                        </h4>
                    </a>
                    <p class="text-muted small">
                        Masuk ke akun Anda untuk mulai melaporkan masalah
                    </p>
                </div>

                <!-- Login Card -->
                <div class="card card-custom border-0 shadow">
                    <div class="card-header">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Masuk ke Akun
                    </div>

                    <div class="card-body p-4">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?= htmlspecialchars($success) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Error Message -->
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Login Form -->
                        <form action="login.php" method="POST">

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    Alamat Email
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        class="form-control"
                                        placeholder="email@domain.com"
                                        value="<?= htmlspecialchars($email) ?>"
                                        required
                                    >
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        class="form-control"
                                        placeholder="Masukkan password"
                                        required
                                    >
                                    <button
                                        class="btn btn-outline-secondary"
                                        type="button"
                                        onclick="togglePassword()"
                                    >
                                        <i class="bi bi-eye" id="eye-icon"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Login Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Login
                                </button>
                            </div>

                        </form>

                        <!-- Demo credentials -->
                        <div class="mt-4 p-3 bg-light rounded border small">
                            <div class="fw-semibold text-muted mb-1">
                                <i class="bi bi-info-circle me-1"></i>Akun Demo:
                            </div>
                            <table class="w-100 mb-1">
                                <tr>
                                    <td class="text-muted pe-2">Admin:</td>
                                    <td><code>admin@siagamasyarakat.id</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted pe-2">Warga:</td>
                                    <td><code>budi@email.com</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted pe-2">Password:</td>
                                    <td><code>Admin123!</code></td>
                                </tr>
                            </table>
                        </div>

                    </div>

                    <!-- Register Link -->
                    <div class="card-footer text-center text-muted small py-3">
                        Belum punya akun?
                        <a href="register.php" class="text-primary-custom fw-semibold text-decoration-none">
                            Daftar sekarang
                        </a>
                    </div>

                </div>

                <!-- Back to Home -->
                <div class="text-center mt-3">
                    <a href="<?= BASE_URL ?>/index.php" class="text-muted text-decoration-none small">
                        <i class="bi bi-arrow-left me-1"></i>
                        Kembali ke Beranda
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Show / Hide Password -->
    <script>
        function togglePassword() {
            const password = document.getElementById("password");
            const icon = document.getElementById("eye-icon");

            if (password.type === "password") {
                password.type = "text";
                icon.className = "bi bi-eye-slash";
            } else {
                password.type = "password";
                icon.className = "bi bi-eye";
            }
        }
    </script>

</body>
</html>
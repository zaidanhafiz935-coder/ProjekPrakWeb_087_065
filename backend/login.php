<?php
session_start();

require "koneksi.php";

$error = "";
$email = "";

// Jika form login dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Mencari user berdasarkan email
    $stmt = $koneksi->prepare(
        "SELECT username, email, password FROM users WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $row = $result->fetch_assoc();

        // Memverifikasi password
        if (password_verify($password, $row['password'])) {

            // Membuat session
            session_regenerate_id(true);

            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];

            // Redirect ke dashboard
            header("Location: ../dashboard.php");
            exit;

        } else {
            $error = "Email atau password salah!";
        }

    } else {
        $error = "Email atau password salah!";
    }

    $stmt->close();
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

</head>

<body class="bg-light">

    <div class="container py-5">

        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">

                <!-- Brand -->
                <div class="text-center mb-4">

                    <a href="../index.php" class="text-decoration-none">

                        <i class="bi bi-shield-check-fill fs-1 text-primary"></i>

                        <h4 class="fw-bold text-primary mt-2">
                            Siaga Masyarakat
                        </h4>

                    </a>

                    <p class="text-muted small">
                        Masuk ke akun Anda untuk mulai melaporkan masalah
                    </p>

                </div>

                <!-- Login Card -->
                <div class="card border-0 shadow">

                    <div class="card-header bg-primary text-white py-3">

                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Masuk ke Akun

                    </div>

                    <div class="card-body p-4">

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

                    </div>

                    <!-- Register Link -->
                    <div class="card-footer text-center text-muted small py-3">

                        Belum punya akun?

                        <a href="register.php" class="text-primary fw-semibold text-decoration-none">
                            Daftar sekarang
                        </a>

                    </div>

                </div>

                <!-- Back to Home -->
                <div class="text-center mt-3">

                    <a href="../index.php" class="text-muted text-decoration-none small">

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
        // Email    : tester@siaga.test
        // Password : Password123!

    </script>

</body>
</html>
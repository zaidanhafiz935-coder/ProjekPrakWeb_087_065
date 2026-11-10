<?php
session_start();

require "koneksi.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Mencari user berdasarkan email
    $stmt = $koneksi->prepare(
        "SELECT username, password FROM users WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $row = $result->fetch_assoc();

        // Memverifikasi password
        if (password_verify($password, $row['password'])) {

            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $email;

            // Redirect ke dashboard
            header("Location: ../dashboard.php");
            exit;

        } else {
            echo "Password salah!";
        }

    } else {
        echo "Email tidak ditemukan!";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>

    <h2>Login</h2>

    <form action="login.php" method="POST">

        <label>Email:</label>
        <br>
        <input type="email" name="email" required>
        <br><br>

        <label>Password:</label>
        <br>
        <input type="password" name="password" required>
        <br><br>

        <button type="submit">Login</button>

    </form>

</body>
</html>
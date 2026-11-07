<?php

session_start();

require_once "../config/database.php";

$username = $_POST['username'];
$password = $_POST['password'];

// Contoh sederhana
$query = mysqli_query(
    $conn,
    "SELECT * FROM users WHERE username='$username'"
);

$user = mysqli_fetch_assoc($query);

if ($user && password_verify($password, $user['password'])) {

    $_SESSION['username'] = $user['username'];

    header("Location: ../dashboard.php");
    exit;

} else {

    echo "Username atau password salah";

}
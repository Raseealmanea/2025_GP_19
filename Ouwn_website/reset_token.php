<?php
date_default_timezone_set('Asia/Riyadh'); // or your local timezone
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'vendor/autoload.php';
session_start();

// Database connection
$host = "127.0.0.1";
$user = "root";
$pass = "root";
$port = 8889;
$db   = "OuwnDB";

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$message = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check token validity
    $stmt = $conn->prepare("SELECT email FROM HealthCareP WHERE reset_token=? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        die("❌ Invalid or expired token.");
    }

    $stmt->bind_result($email);
    $stmt->fetch();

    // Process password update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];

        if ($password === $confirm) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE HealthCareP SET password=?, reset_token=NULL, reset_expires=NULL WHERE email=?");
            $update->bind_param("ss", $hashed, $email);
            $update->execute();
            $message = "✅ Password updated successfully! <a href='login.php'>Login</a>";
        } else {
            $message = "⚠️ Passwords do not match!";
        }
    }

} else {
    die("❌ No token provided.");
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="stylee.css">
</head>
<body class="token-page-body">
<div class="token-container">
    <h2>Reset Your Password</h2>
    <?php if($message != ""): ?>
        <p><?= $message ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="password" name="password" placeholder="Enter new password" required>
        <input type="password" name="confirm_password" placeholder="Confirm new password" required>
        <button type="submit">Update Password</button>
    </form>
    <a href="login.php">Back to Login</a>
</div>
</body>
</html>

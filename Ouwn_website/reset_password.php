<?php
date_default_timezone_set('Asia/Riyadh'); // or your local timezone
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
session_start();

// Database connection
$host = "127.0.0.1";
$user = "root";
$pass = "root";
$port = 8889;
$db   = "OuwnDB";

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Check if email exists
    $stmt = $conn->prepare("SELECT UserID FROM HealthCareP WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($username);
        $stmt->fetch();

        // Generate reset token
        $token = bin2hex(random_bytes(16));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Save token and expiry in database
        $update = $conn->prepare("UPDATE HealthCareP SET reset_token=?, reset_expires=? WHERE email=?");
        $update->bind_param("sss", $token, $expiry, $email);
        $update->execute();

        // Send email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'ouwnsystem@gmail.com'; // your Gmail
            $mail->Password   = 'hekwyotvhhijigbo';     // app password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('ouwnsystem@gmail.com', 'OuwN System');
            $mail->addAddress($email, $username);

            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Password';
            $mail->Body    = "Hi $username,<br><br>
                              Click the link below to reset your password:<br>
                              <a href='http://localhost:8888/ouwn/reset_token.php?token=$token'>Reset Password</a><br><br>
                              This link will expire in 1 hour.";

            $mail->send();
            $message = "✅ Reset link sent to your email!";
        } catch (Exception $e) {
            $message = "❌ Mailer Error: " . $mail->ErrorInfo;
        }

    } else {
        $message = "⚠️ Email not found!";
    }

    $stmt->close();
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
<body class="reset-page-body">
<div class="reset-container">
    <h2>Enter your email to reset password</h2>
    <?php if($message != ""): ?>
        <p style="color:red;"><?= $message ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Your email" required><br>
        <button type="submit">Send reset link</button>
    </form>
    <a href="login.php">Back to login</a>
</div>
</body>
</html>

<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
session_start();

// ---- Database Config ----
$DB_HOST = "127.0.0.1";
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'OuwnDB';
$TABLE   = 'HealthCareP'; 
$port = 8889; // Confirmed users go here
$successMsg = '';
$errorMsg   = '';

try {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME,$port);
    $mysqli->set_charset('utf8mb4');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $first   = trim($_POST['first_name'] ?? '');
        $last    = trim($_POST['last_name'] ?? '');
        $userID  = trim($_POST['username'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $pass    = $_POST['password'] ?? '';

        if ($first === '' || $last === '' || $userID === '' || $email === '' || $pass === '') {
            throw new RuntimeException('All fields are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email address.');
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $pass)) {
            throw new RuntimeException('Password must be at least 8 characters, include uppercase, lowercase, a number, and a special character.');
        }

        // Check duplicates in confirmed users
        $check = $mysqli->prepare("SELECT 1 FROM $TABLE WHERE Email = ? OR UserID = ? LIMIT 1");
        $check->bind_param('ss', $email, $userID);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            throw new RuntimeException('Email or Username already exists.');
        }
        $check->close();

        $fullName = $first . ' ' . $last;
        $passHash = password_hash($pass, PASSWORD_DEFAULT);

        // Encode user data for confirmation link
        $userdata = [
            'username' => $userID,
            'email'    => $email,
            'password' => $passHash,
            'fullname' => $fullName
        ];
        $encoded = base64_encode(json_encode($userdata));

        // Send confirmation email
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
            $mail->addAddress($email, $fullName);

            $confirmLink = "http://localhost:8888/ouwn/confirm_email.php?data=$encoded";

$mail->isHTML(true);
$mail->Subject = 'Confirm Your Email';
$mail->Body = "
<html>
  <body style=\"font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #2d004d; background: #f4eefc; padding: 20px;\">
    <div style=\"max-width: 600px; margin: auto; background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);\">
      <h2 style=\"color: #9975C1; text-align: center;\">OuwN Email Confirmation</h2>
      <p>Hi " . htmlspecialchars($fullName) . ",</p>
      <p>Welcome! Please confirm your email address by clicking the button below:</p>
      <div style=\"text-align: center; margin: 30px 0;\">
        <a href=\"$confirmLink\" style=\"background: #9975C1; color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: bold;\">
          Confirm Email
        </a>
      </div>
      <p>If you didn't create an account, you can ignore this email.</p>
      <p>Thanks,<br><strong>OuwN Team</strong></p>
    </div>
  </body>
</html>
";


            $mail->send();
            $successMsg = '✅ Account created! Please check your email to confirm your account.';
        } catch (Exception $e) {
            $errorMsg = "Mailer Error: " . $mail->ErrorInfo;
        }
    }
} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up • OuwN</title>
  <link rel="stylesheet" href="stylee.css">
  <style>
    .banner {
      display: none;
      padding: 12px 16px;
      border-radius: 10px;
      margin: 12px auto 0;
      max-width: 540px;
      text-align: center;
    }
    .banner.show { display: block; }
    .banner.err { background: #f44336; color: #fff; }
    .banner.ok  { background: #2e7d32; color: #fff; }
  </style>
</head>
<body class="auth-page-body">

  <?php if ($errorMsg): ?>
    <div class="banner err show"><?= htmlspecialchars($errorMsg) ?></div>
  <?php endif; ?>
  <?php if ($successMsg): ?>
    <div class="banner ok show"><?= htmlspecialchars($successMsg) ?></div>
  <?php endif; ?>

  <header class="header">
            <div class="header-left">
                <img src='logo.svg' alt="OuwN Logo" class="logo-img">
            </div>
            <div class="header-right">
                </div>
                <nav class="header-nav">
                    <a href="homePage.php#about">About</a>
                    <a href="homePage.php#vision">Vision</a>
                </nav>
            </div>
        </header>

  <main class="auth-container">
    <h2 class="auth-title">Create your account</h2>
    <p class="auth-subtitle">Join OuwN and save time for what matters.</p>

    <form method="POST" action="signup.php" class="auth-form" onsubmit="return validatePassword()">
      <div class="grid-2">
        <div>
          <label for="first_name">First name</label>
          <input id="first_name" name="first_name" type="text" placeholder="e.g., Sara" required>
        </div>
        <div>
          <label for="last_name">Last name</label>
          <input id="last_name" name="last_name" type="text" placeholder="e.g., Al-Harbi" required>
        </div>
      </div>

      <label for="username">Username</label>
      <input id="username" name="username" type="text" placeholder="Choose a username" required>

      <label for="email">Email</label>
      <input id="email" name="email" type="email" placeholder="you@example.com" required>

      <label for="password">Password</label>
      <input id="password" name="password" type="password" placeholder="Create a password" required>

      <button type="submit" class="btn">Sign up</button>
    </form>

    <p class="auth-meta">
      Already have an account?
      <a href="login.php">Log In</a>
    </p>
  </main>

  <footer>
    <p>&copy; 2025 OuwN. All Rights Reserved.</p>
  </footer>

  <script>
    function showClientBanner(type, message) {
      const el = document.querySelector('.banner#clientBanner');
      if (!el) return;
      el.textContent = message;
      el.className = 'banner show ' + (type === 'ok' ? 'ok' : 'err');
    }

    function validatePassword() {
      const pw = document.getElementById('password').value;
      const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;
      if (!regex.test(pw)) {
        alert('Password must be at least 8 characters and include uppercase, lowercase, number, special character.');
        return false;
      }
      return true;
    }
  </script>
</body>
</html>

<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
$DB_HOST = "127.0.0.1";
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'OuwnDB';
$port = 8889;
$TABLE   = 'HealthCareP';

$msg = '';

try {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME,$port);
    $mysqli->set_charset('utf8mb4');

    $data = $_GET['data'] ?? '';
    if (!$data) throw new RuntimeException('Invalid confirmation link.');

    $userdata = json_decode(base64_decode($data), true);
    if (!$userdata) throw new RuntimeException('Invalid confirmation link.');

    // Check if user already exists
    $check = $mysqli->prepare("SELECT 1 FROM $TABLE WHERE Email = ? OR UserID = ? LIMIT 1");
    $check->bind_param('ss', $userdata['email'], $userdata['username']);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        throw new RuntimeException('This account is already confirmed.');
    }
    $check->close();

    // Insert confirmed user
    $stmt = $mysqli->prepare("INSERT INTO $TABLE (UserID, Email, Password, Name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $userdata['username'], $userdata['email'], $userdata['password'], $userdata['fullname']);
    $stmt->execute();
    $stmt->close();

    $msg = '✅ Your email has been confirmed! You can now log in.';
} catch (Throwable $e) {
    $msg = '⚠️ ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email Confirmation</title>
  <link rel="stylesheet" href="stylee.css">
</head>
<body class="auth-page-body">
  <div class="banner <?= strpos($msg,'✅')===0?'ok':'err' ?> show"><?= htmlspecialchars($msg) ?></div>
  <main class="auth-container">
    <a href="login.php" class="btn" style="margin-top:20px;">Go to Login</a>
  </main>
</body>
</html>

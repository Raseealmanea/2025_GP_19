<?php
// login.php — single file: handles POST first, then renders the form
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();

// ---- Database Config ----
$DB_HOST = "127.0.0.1";
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'OuwnDB';
$port = 8889;
$TABLE = 'HealthCareP';   

$successMsg = '';
$errorMsg   = '';

try {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME,$port );
    $mysqli->set_charset('utf8mb4');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            throw new RuntimeException('Both username and password are required.');
        }

        // Fetch user by UserID
        $stmt = $mysqli->prepare("SELECT UserID, Password, Name, Email FROM $TABLE WHERE UserID = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            // Username not found
            throw new RuntimeException('Username does not exist.');
        }

        // Verify hashed password
        if (!password_verify($password, $user['Password'])) {
            throw new RuntimeException('Incorrect password.');
        }

        // (Optional) Rehash if algorithm updated
        if (password_needs_rehash($user['Password'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $mysqli->prepare("UPDATE $TABLE SET Password = ? WHERE UserID = ?");
            $upd->bind_param('ss', $newHash, $username);
            $upd->execute();
            $upd->close();
        }

        // Success: establish session
        $_SESSION['user_id']   = $user['UserID'];
        $_SESSION['user_name'] = $user['Name'] ?? $user['UserID'];
        $_SESSION['user_email']= $user['Email'] ?? '';

        $successMsg = '✅ Logged in successfully. Welcome back!';
        header('Location: dashboard.php');
       
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
  <title>Login • OuwN</title>
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
    <div class="banner err show">⚠️ <?= htmlspecialchars($errorMsg) ?></div>
  <?php endif; ?>
  <?php if ($successMsg): ?>
    <div class="banner ok show"><?= htmlspecialchars($successMsg) ?></div>
  <?php endif; ?>

   <header class="header">
            <div class="header-left">
                <img src='logo.svg' alt="OuwN Logo" class="logo-img">
            </div>
            
                <nav class="header-nav">
                    <a href="homePage.php#about">About</a>
                    <a href="homePage.php#vision">Vision</a>
                </nav>
            </div>
        </header>


  <main class="auth-container">
    <h2 class="auth-title">Welcome back</h2>
    <p class="auth-subtitle">Sign in to continue caring for your patients.</p>

    <!-- Post back to this same file -->
    <form method="POST" action="login.php" class="auth-form">
      <label for="username">Username</label>
      <input id="username" name="username" type="text" placeholder="Enter your username" required>

      <label for="password">Password</label>
      <input id="password" name="password" type="password" placeholder="Enter your password" required>

      <button type="submit" class="btn">Log in</button>
    </form>

    <p class="auth-meta" style="margin-top:10px">
      Forgot your password? <a class="link-sm" href="reset_password.php">Reset password</a>
    </p>

    <p class="auth-meta">
      Don’t have an account?
      <a href="signup.php">Sign Up</a>
    </p>
  </main>

  <footer>
    <p>&copy; 2025 OuwN. All Rights Reserved.</p>
  </footer>
</body>
</html>

<?php
// signup.php — single file: handles form submission + renders the form
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ---- Database Config ----
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'OuwnDB';
$TABLE   = 'HealthCareP';   // ⚠️ Change to your actual table name

$successMsg = '';
$errorMsg   = '';

try {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
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
            throw new RuntimeException('Password must be at least 8 characters and include uppercase, lowercase, a number, and a special character.');
        }

        $fullName = $first . ' ' . $last;
        $passHash = password_hash($pass, PASSWORD_DEFAULT);

        // Check duplicates
        $check = $mysqli->prepare("SELECT 1 FROM `$TABLE` WHERE `Email` = ? OR `UserID` = ? LIMIT 1");
        $check->bind_param('ss', $email, $userID);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            throw new RuntimeException('Email or Username already exists.');
        }
        $check->close();

        // Insert
        $stmt = $mysqli->prepare("INSERT INTO `$TABLE` (`UserID`, `Email`, `Password`, `Name`) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $userID, $email, $passHash, $fullName);
        $stmt->execute();
        $stmt->close();

        $successMsg = '✅ Account created successfully. You can now log in.';
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

  <!-- For client-side password validation messages -->
  <div id="clientBanner" class="banner" aria-live="polite"></div>

  <header>
    <div class="logo">
      <img src="{{ url_for('static', filename='logo.svg') }}" alt="OuwN Logo">
    </div>
    <nav>
      <a href="{{ url_for('home') }}#about">About</a>
      <a href="{{ url_for('home') }}#vision">Vision</a>
    </nav>
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
      const el = document.getElementById('clientBanner');
      el.textContent = message;
      el.className = 'banner show ' + (type === 'ok' ? 'ok' : 'err');
    }

    function clearClientBanner() {
      const el = document.getElementById('clientBanner');
      el.className = 'banner';
      el.textContent = '';
    }

    function validatePassword() {
      clearClientBanner();
      const pw = document.getElementById('password').value;
      const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;
      if (!regex.test(pw)) {
        showClientBanner('err', '⚠️ Password must be at least 8 characters and include uppercase, lowercase, a number, and a special character.');
        return false;
      }
      return true;
    }

    document.addEventListener('input', (e) => {
      if (e.target && e.target.id === 'password') {
        clearClientBanner();
      }
    });
  </script>
</body>
</html>

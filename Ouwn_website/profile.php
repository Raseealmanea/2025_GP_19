<?php
declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ---- Database Config ----
$DB_HOST = "127.0.0.1";
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'OuwnDB';
$port = 8889;
$TABLE   = 'HealthCareP';

// ---- Connect ----
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME,$port);
$mysqli->set_charset('utf8mb4');

// ---- Session check ----
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

// ---- Handle Profile Update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $newName     = trim($_POST['name'] ?? '');
    $newEmail    = trim($_POST['email'] ?? '');
    $newUsername = trim($_POST['username'] ?? '');

    try {
        if ($newName === '' || $newEmail === '' || $newUsername === '') {
            throw new RuntimeException('All fields are required.');
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email format.');
        }

        $check = $mysqli->prepare("SELECT 1 FROM $TABLE WHERE (Email = ? OR UserID = ?) AND UserID != ?");
        $check->bind_param('sss', $newEmail, $newUsername, $userID);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            throw new RuntimeException('Email or username already in use by another account.');
        }
        $check->close();

        $stmt = $mysqli->prepare("UPDATE $TABLE SET Name = ?, Email = ?, UserID = ? WHERE UserID = ?");
        $stmt->bind_param('ssss', $newName, $newEmail, $newUsername, $userID);
        $stmt->execute();
        $stmt->close();

        $_SESSION['user_id'] = $newUsername;
        $successMsg = '✅ Profile updated successfully.';
    } catch (Throwable $e) {
        $errorMsg = $e->getMessage();
    }
}

// ---- Handle Password Update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_password') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    try {
        if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
            throw new RuntimeException('All password fields are required.');
        }

        if ($newPassword !== $confirmPassword) {
            throw new RuntimeException('New password and confirmation do not match.');
        }

        if (!preg_match('/^(?=.[a-z])(?=.[A-Z])(?=.\d)(?=.[\W_]).{8,}$/', $newPassword)) {
            throw new RuntimeException('Password must contain uppercase, lowercase, number, special character, and be at least 8 characters long.');
        }

        $stmt = $mysqli->prepare("SELECT Password FROM $TABLE WHERE UserID = ?");
        $stmt->bind_param('s', $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($oldPassword, $row['Password'])) {
            throw new RuntimeException('Old password is incorrect.');
        }

        $hashedNew = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE $TABLE SET Password = ? WHERE UserID = ?");
        $stmt->bind_param('ss', $hashedNew, $userID);
        $stmt->execute();
        $stmt->close();

        $successMsg = '🔒 Password updated successfully.';
    } catch (Throwable $e) {
        $errorMsg = $e->getMessage();
    }
}

// ---- Fetch User Info ----
$stmt = $mysqli->prepare("SELECT UserID, Email, Name FROM $TABLE WHERE UserID = ?");
$stmt->bind_param('s', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Profile • OuwN</title>
  <link rel="stylesheet" href="stylee.css">
  <style>
    .auth-container {
      max-width: 600px;
      margin: 60px auto;
      background: white;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      text-align: center;
    }
    .profile-header {
      position: relative;
      text-align: center;
    }
    .profile-header img {
      width: 120px;
height: 120px;
border-radius: 50%;
object-fit: cover;
border: 3px solid #9975c2;
    }
    .profile-header h2 {
      margin: 10px 0;
      font-weight: 600;
      font-size: 22px;
      color: #333;
    }
    .edit-btn {
      position: absolute;
      top: 5px;
      right: 5px;
      background: none;
      border: none;
      cursor: pointer;
      font-size: 22px;
      color: #007bff;
    }
    .edit-btn:hover {
      color: #0056b3;
    }
    .profile-info {
      text-align: left;
      margin-top: 25px;
    }
    .profile-info input {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      margin-top: 6px;
      font-size: 15px;
      background: #f9f9f9;
    }
    .profile-info input[readonly] {
      background: #f9f9f9;
      border: 1px solid #f9f9f9;
    }
    .profile-header h3 {
      margin-bottom: 10px;
      color: #9975c2;
     }
    .password-toggle {
      display: block;
      margin: 25px auto 0;
      background: #9975c2;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 15px;
      cursor: pointer;
      font-size: 15px;
    }
    .password-toggle:hover {
      background: #7a5e9b;
    }
    .password-section {
      display: none;
      text-align: left;
      margin-top: 20px;
      border-top: 1px solid #ddd;
      padding-top: 20px;
    }
    .password-section.active {
      display: block;
    }
    .password-section input {
      width: 100%;
      padding: 10px;
      margin-top: 6px;
      border-radius: 8px;
      border: 1px solid #ccc;
      background: #f9f9f9;
    }
    .btn {
      display: block;
      background: #9975c2;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 15px;
      cursor: pointer;
      margin: 20px auto 0;
    }
    .btn:hover {
      background: #7a5e9b;
    }
    .banner {
      position: fixed; /* Fixed position */
      top: 20px; /* Space from the top */
      left: 50%;
      transform: translateX(-50%);
      z-index: 1000; /* Keep it above other elements */
      padding: 12px 16px;
      border-radius: 10px;
      margin: 0; /* Remove margin */
      max-width: 540px;
      text-align: center;
      font-weight: 500;
      display: none; /* Initially hidden */
    }
    .banner.show { display: block; }
    .banner.err { background: #f44336; color: #fff; }
    .banner.ok  { background: #2e7d32; color: #fff; }
    footer {
      text-align: center;
      color: #555;
      margin-top: 40px;
      padding: 20px;
    }
  </style>
</head>
<body>

<?php if ($errorMsg): ?>
  <div class="banner err show"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>
<?php if ($successMsg): ?>
  <div class="banner ok show"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
    <?php
        include "header.html";
    ?>
<main class="auth-container">
  <div class="profile-header">
    <img src="doctor.png" alt="Profile Image">
    <h2><?= htmlspecialchars($user['Name']) ?></h2>
    <button type="button" id="editBtn" class="edit-btn" title="Edit profile">✏️</button>
  </div>

  <!-- Profile Form -->
  <form method="POST" class="profile-info" id="profileForm">
    <input type="hidden" name="action" value="update_profile">
    <p><strong>Name:</strong><br>
      <input type="text" name="name" value="<?= htmlspecialchars($user['Name']) ?>" readonly required></p>
    <p><strong>Username:</strong><br>
      <input type="text" name="username" value="<?= htmlspecialchars($user['UserID']) ?>" readonly required></p>
    <p><strong>Email:</strong><br>
      <input type="email" name="email" value="<?= htmlspecialchars($user['Email']) ?>" readonly required></p>
  </form>

  <button type="button" id="togglePassword" class="password-toggle">Change Password</button>

  <form method="POST" class="password-section" id="passwordSection">
    <input type="hidden" name="action" value="update_password">
    <p><strong>Old Password:</strong><br>
      <input type="password" name="old_password" required></p>
    <p><strong>New Password:</strong><br>
      <input type="password" name="new_password" required></p>
    <p><strong>Confirm New Password:</strong><br>
      <input type="password" name="confirm_password" required></p>
    <button type="submit" class="btn">Update Password</button>
  </form>
</main>

<footer>
  <p>&copy; 2025 OuwN. All Rights Reserved.</p>
</footer>

<script>
  const editBtn = document.getElementById('editBtn');
  const form = document.getElementById('profileForm');
  const inputs = form.querySelectorAll('input:not([type=hidden])');
  const togglePassword = document.getElementById('togglePassword');
  const passwordSection = document.getElementById('passwordSection');

  let isEditing = false;

  editBtn.addEventListener('click', () => {
    if (!isEditing) {
      inputs.forEach(i => i.removeAttribute('readonly'));
      editBtn.textContent = '💾';
      editBtn.title = 'Save changes';
      isEditing = true;
    } else {
      form.submit();
      editBtn.textContent = '✏️';
      editBtn.title = 'Edit profile';
      isEditing = false;
    }
  });

  togglePassword.addEventListener('click', () => {
    passwordSection.classList.toggle('active');
    togglePassword.textContent = passwordSection.classList.contains('active')
      ? 'Hide Password Section'
      : 'Change Password';
  });

  // Notification handling
  if (document.querySelector('.banner.err') || document.querySelector('.banner.ok')) {
    const banners = document.querySelectorAll('.banner');
    banners.forEach(banner => {
        banner.classList.add('show'); // Show the banner
        setTimeout(() => {
            banner.classList.remove('show'); // Hide after 4 seconds
        }, 4000);
    });
  }
</script>

</body>

</html>

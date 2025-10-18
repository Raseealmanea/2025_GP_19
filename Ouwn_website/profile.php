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
$port = 3306;
$TABLE   = 'HealthCareP';

// ---- Connect ----
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $port);
$mysqli->set_charset('utf8mb4');

// ---- Session check ----
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

// ---- Fetch current user info ----
$stmt = $mysqli->prepare("SELECT UserID, Email, Name FROM $TABLE WHERE UserID = ?");
$stmt->bind_param('s', $userID);
$stmt->execute();
$result = $stmt->get_result();
$currentUser = $result->fetch_assoc();
$stmt->close();

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

        // ---- Check if anything actually changed ----
        if (
            $newName === $currentUser['Name'] &&
            $newEmail === $currentUser['Email'] &&
            $newUsername === $currentUser['UserID']
        ) {
            // Nothing changed → skip update and message
            throw new RuntimeException('No changes were made.');
        }

        // ---- Check if email already exists for another user ----
        $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM $TABLE WHERE Email = ? AND UserID != ?");
        $stmt->bind_param('ss', $newEmail, $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row['cnt'] > 0) {
            throw new RuntimeException('Invalid Email.');
        }

        // ---- Check if username already exists for another user ----
        $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM $TABLE WHERE UserID = ? AND UserID != ?");
        $stmt->bind_param('ss', $newUsername, $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row['cnt'] > 0) {
            throw new RuntimeException('Invalid Username.');
        }

        // ---- Perform the update ----
        $stmt = $mysqli->prepare("UPDATE $TABLE SET Name = ?, Email = ?, UserID = ? WHERE UserID = ?");
        $stmt->bind_param('ssss', $newName, $newEmail, $newUsername, $userID);
        $stmt->execute();
        $stmt->close();

        // ---- Update session username ----
        $_SESSION['user_id'] = $newUsername;
        $successMsg = '✅ Profile updated successfully.';

        // ---- Refresh user data immediately ----
        $stmt = $mysqli->prepare("SELECT UserID, Email, Name FROM $TABLE WHERE UserID = ?");
        $stmt->bind_param('s', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

    } catch (Throwable $e) {
        // Only show errors that are not "no changes made"
        if ($e->getMessage() !== 'No changes were made.') {
            $errorMsg = $e->getMessage();
        }

        // Reload original data from DB
        $stmt = $mysqli->prepare("SELECT UserID, Email, Name FROM $TABLE WHERE UserID = ?");
        $stmt->bind_param('s', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    }
} else {
    $user = $currentUser;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Profile • OuwN</title>
  <link rel="stylesheet" href="stylee.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <?php if ($errorMsg): ?>
        <div class="banner-profile err show"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>
    <?php if ($successMsg): ?>
        <div class="banner-profile ok show"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>

    <?php include "header.html"; ?>

    <main class="auth-container-profile">
       <div class="profile-header">
         <img src="profile.png" alt="Profile Image">
         <h2>Profile Information</h2>
         <button type="button" id="editBtn" class="edit-btn-profile" title="Edit profile"> <i class="fa-solid fa-pen edit-icon"></i></button>
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
     
            <!-- Action buttons (appear only when editing) -->
            <div class="action-buttons-profile" id="actionButtons">
                <button type="button" class="btn-profile discard" id="discardBtn">Discard Changes</button>
                <button type="submit" class="btn-profile save">Save Changes</button>
            </div>
        </form>
    </main>

<footer>
  <p>&copy; 2025 OuwN. All Rights Reserved.</p>
</footer>

<script>
  const editBtn = document.getElementById('editBtn');
  const form = document.getElementById('profileForm');
  const inputs = form.querySelectorAll('input:not([type=hidden])');
  const actionButtons = document.getElementById('actionButtons');
  const discardBtn = document.getElementById('discardBtn');

  let isEditing = false;
  const originalValues = {};

  // Store initial values
  inputs.forEach(input => originalValues[input.name] = input.value);

  editBtn.addEventListener('click', () => {
    if (!isEditing) {
      inputs.forEach(i => i.removeAttribute('readonly'));
      form.classList.add('edit-mode');
      actionButtons.classList.add('show');
      editBtn.style.display = 'none';
      isEditing = true;
    }
  });

  // Discard button restores values
  discardBtn.addEventListener('click', () => {
    inputs.forEach(i => {
      i.value = originalValues[i.name];
      i.setAttribute('readonly', true);
    });
    form.classList.remove('edit-mode');
    actionButtons.classList.remove('show');
    editBtn.style.display = 'block';
    isEditing = false;
  });

  // On submit, leave edit mode
  form.addEventListener('submit', () => {
    inputs.forEach(i => i.setAttribute('readonly', true));
    form.classList.remove('edit-mode');
    actionButtons.classList.remove('show');
    editBtn.style.display = 'block';
    isEditing = false;
  });

  // Notification handling
  if (document.querySelector('.banner-profile.err') || document.querySelector('.banner-profile.ok')) {
    const banners = document.querySelectorAll('.banner-profile');
    banners.forEach(banner => {
      banner.classList.add('show');
      setTimeout(() => {
        banner.classList.remove('show');
      }, 4000);
    });
  }
</script>
</body>
</html>

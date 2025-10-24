<?php
    date_default_timezone_set('Asia/Riyadh');
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Load PHPMailer 
    require 'vendor/autoload.php';

    // Start session
    session_start();

    // DATABASE CONNECTION
    $host = "localhost";
    $user = "root";
    $pass = "root";
    $port = 8889;
    $db   = "OuwnDB";
    $conn = new mysqli($host, $user, $pass, $db, $port);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // feedback message
    $message = "";

    // VERIFY TOKEN TO HANDLE PASSWORD RESET
    if (isset($_GET['token'])) {
        $token = $_GET['token'];

        // Check if the token exists and not expired
        $stmt = $conn->prepare("SELECT email FROM HealthCareP WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();

        // If token not found or expired
        if ($stmt->num_rows === 0) {
            die("❌ Invalid or expired token.");
        }

        // Fetch the email linked to this token
        $stmt->bind_result($email);
        $stmt->fetch();

        // If user submitted a new password
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'];
            $confirm  = $_POST['confirm_password'];

            // Password validation (strength and match)
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
                $message = "⚠️ Password must be at least 8 characters long and include uppercase, lowercase, a number, and a special character.";
            } elseif ($password !== $confirm) {
                $message = "⚠️ Passwords do not match!";
            } else {
                // Hash password securely
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                // Update password and clear token
                $update = $conn->prepare("UPDATE HealthCareP SET password = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?");
                $update->bind_param("ss", $hashed, $email);
                $update->execute();

                if ($update->affected_rows > 0) {
                    $message = "✅ Password updated successfully! <a href='login.php'>Login</a>";
                } else {
                    $message = "⚠️ Something went wrong. Please try again.";
                }
            }
        }
    } else {
        die("❌ No token provided.");
    }

    // Close the database connection
    $conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="stylee.css">
    <style>
        /* Optional: style form if not in your CSS file */
       
        .error {
            color: #b70000;
            font-weight: bold;
        }
        .success {
            color: #028a0f;
            font-weight: bold;
        }
    </style>
</head>

<body class="token-page-body">
    <!-- password reset form -->
    <div class="token-container">
        <h2>Reset Your Password</h2>

        <!-- Display messages -->
        <?php if ($message != ""): ?>
            <p class="<?= str_contains($message, '✅') ? 'success' : 'error' ?>"><?= $message ?></p>
        <?php endif; ?>

        <!-- Password reset form -->
        <form method="POST">
            <input type="password" name="password" placeholder="Enter new password" required>
            <input type="password" name="confirm_password" placeholder="Confirm new password" required>
            <button type="submit">Update Password</button>
        </form>

        <!-- Back to login page -->
        <a href="login.php">Back to Login</a>
    </div>
</body>
</html>

<?php
    // Enable strict typing for safer code
    declare(strict_types=1);

    ini_set('display_errors', '1');
    error_reporting(E_ALL);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Start the user session
    session_start();

    // Database connection details
    $DB_HOST = 'localhost';
    $DB_USER = 'root';
    $DB_PASS = 'root';
    $DB_NAME = 'OuwnDB';
    $port    = 8889;
    $TABLE   = 'HealthCareP';

    // Message to display to the user
    $msg = '';

    try {
        $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $port);
        $mysqli->set_charset('utf8mb4');

        // Retrieve the "data" parameter from the confirmation link
        $data = $_GET['data'] ?? '';

        // If the link doesn't contain data, stop execution
        if (!$data) {
            throw new RuntimeException('Invalid confirmation link.');
        }

        // Decode the Base64 JSON-encoded user data
        $userdata = json_decode(base64_decode($data), true);

        // If decoding fails, the link is not valid
        if (!$userdata) {
            throw new RuntimeException('Invalid confirmation link.');
        }

        // Check if this user already exists (by email or username)
        $check = $mysqli->prepare("SELECT 1 FROM $TABLE WHERE Email = ? OR UserID = ? LIMIT 1");
        $check->bind_param('ss', $userdata['email'], $userdata['username']);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // User already confirmed before
            throw new RuntimeException('This account is already confirmed.');
        }

        $check->close();

        // Insert the confirmed user into the database
        $stmt = $mysqli->prepare("INSERT INTO $TABLE (UserID, Email, Password, Name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $userdata['username'], $userdata['email'], $userdata['password'], $userdata['fullname']);
        $stmt->execute();
        $stmt->close();

        // Success message displayed after successful insertion
        $msg = '✅ Your email has been confirmed! You can now log in.';

    } catch (Throwable $e) {
        // Catch any error and show a message
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
    <style>
            body {
                margin: 0;
                height: 100vh;          
                display: flex;
                justify-content: center;  
                align-items: center;      
                background: linear-gradient(135deg, #F4F1FB, #E1D4F2);
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                flex-direction: column;  
            }

            .banner {
                position: fixed;
                top: 30px;
                left: 50%;
                transform: translateX(-50%);
                padding: 15px 25px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 500;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                text-align: center;
                z-index: 999;
            }
            .banner.ok {
                background-color: #e6ffe6;
                color: #067a00;
                border: 1px solid #067a00;
            }
            .banner.err {
                background-color: #ffeaea;
                color: #b50000;
                border: 1px solid #b50000;
            }

            .btn {
                padding: 12px 25px;
                font-weight: bold;
                border-radius: 8px;
                border: none;
                background: #6b4eb4;
                color: white;
                cursor: pointer;
                box-shadow: 0 3px 10px rgba(0,0,0,0.1);
                transition: background 0.3s, transform 0.2s;
            }

            .btn:hover {
                background: #382ccc;
                transform: scale(1.05);
            }
        </style>
    </head>

    <body>
        <div class="banner <?= strpos($msg, '✅') === 0 ? 'ok' : 'err' ?> show">
            <?= htmlspecialchars($msg) ?>
        </div>
        <a href="login.php" class="btn">Go to Login</a>
    </body>
</html>



<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
//connection
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'OuwnDB';
$port    = 8889;

$userID = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Doctor';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $port);
$mysqli->set_charset('utf8mb4');

// Fetch patients 
$query = "SELECT ID, FullName FROM Patient";
$result = $mysqli->query($query);
$patients = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="stylee.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php include "header.html"; ?>

    <main class="dashboard container">
        <h1>Welcome, Dr. <?= htmlspecialchars($userName) ?></h1>

        <div class="dashboard-actions">
            <button class="add-btn" onclick="location.href='AddPatient.php'"><i class="fa-solid fa-user-plus"></i> Add Patient</button>
        </div>

        <h2>Your Patients</h2>

        <?php if (count($patients) > 0): ?>
            <div class="patients-container">
                <?php foreach ($patients as $p): ?>
                    <div class="patient-card">
                        <h3><?= htmlspecialchars($p['FullName']) ?></h3>
                        <div class="patient-info">
                            <strong>ID:</strong> <?= htmlspecialchars($p['ID']) ?><br>
                        </div>
                        <!-- Add Medical Notes Button -->
                        <button 
                            class="add-btn" 
                            onclick="location.href='MedicalNotes.php?patient_id=<?= urlencode($p['ID']) ?>'">
                            <i class="fa-solid fa-file-medical"></i> Add Medical Notes
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No patients found.</p>
        <?php endif; ?>
    </main>
    <footer>
    <p>&copy; 2025 OuwN. All Rights Reserved.</p>
  </footer>
</body>
</html>

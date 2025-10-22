<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
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
    <title>Patient Dashboard - Medical Coding Portal</title>
    <link rel="stylesheet" href="stylee.css">
</head>
<body>
    <?php include "header.html"; ?>

    <!-- BREADCRUMB BAR -->
     <nav class="ouwn-breadcrumb-bar" role="navigation" aria-label="Breadcrumb">
    <div class="ouwn-crumbs">
        <a class="crumb" href="dashboard.php">
        <i class="fa-solid fa-house"></i>
        <span>Dashboard</span>
        </a>
    </div>
    </nav>
    <main class="main-content">
        <div class="container">
            <!-- Decorative Title Bar -->
            <div class="title-bar">
                <div class="title-bar-line"></div>
                <h2 class="section-title">Patient List</h2>
                <div class="title-bar-line-fade"></div>
            </div>

            <!-- Add Patient Button -->
            <div class="header-buttons">
                <button class="btn btn-primary" onclick="location.href='AddPatient.php'">
                    <i class="fa-solid fa-user-plus"></i> Add Patient
                </button>
            </div>

            <!-- Patient Grid -->
            <?php if (count($patients) > 0): ?>
                <div class="patient-grid">
                    <?php foreach ($patients as $p): ?>
                        <div class="patient-card">
                            <div class="card-corner"></div>
                            <div class="card-content">
                                <h3 class="patient-name"><?= htmlspecialchars($p['FullName']) ?></h3>
                                <div class="patient-id-row">
                                    <span class="id-label">Patient ID</span>
                                    <span class="patient-id"><?= htmlspecialchars($p['ID']) ?></span>
                                </div>
                                <!-- Add Medical Notes Button -->
                                <button class="btn btn-secondary" onclick="location.href='MedicalNotes.php?patient_id=<?= urlencode($p['ID']) ?>'">
                                    <i class="fa-solid fa-file-medical"></i> Add Medical Notes
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No patients found.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 OuwN. All Rights Reserved.</p>
    </footer>
</body>
</html>

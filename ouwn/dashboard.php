<?php
    declare(strict_types=1);
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    // Database connection settings
    $DB_HOST = 'localhost';
    $DB_USER = 'root';
    $DB_PASS = 'root';
    $DB_NAME = 'OuwnDB';
    $port    = 8889;

    // Get user data from session
    $userID   = $_SESSION['user_id'];
    $userName = $_SESSION['user_name'] ?? null;

    // Connect to database
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $port);
    $mysqli->set_charset('utf8mb4');

    // Fetch patients from database
    $query    = "SELECT ID, FullName FROM Patient";
    $result   = $mysqli->query($query);
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

        <!-- Breadcrumb navigation -->
        <nav class="ouwn-breadcrumb-bar" role="navigation" aria-label="Breadcrumb">
            <div class="ouwn-crumbs">
                <a class="crumb" href="dashboard.php">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </nav>

        <!-- Alert message if patient or note is added -->
        <?php if (isset($_GET['msg'])): ?>
            <?php
                // Set message text based on parameter
                $msgText = '';
                switch ($_GET['msg']) {
                    case 'patient_added':
                    case 'added':
                        $msgText = 'Patient added successfully!';
                        break;
                    case 'note_added':
                        $msgText = 'Medical note added successfully!';
                        break;
                    default:
                        $msgText = '';
                }
            ?>

            <?php if ($msgText): ?>
                <div id="toast" class="toast show">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= htmlspecialchars($msgText) ?></span>
                </div>

                <script>
                    // Hide toast after 3.5 seconds
                    setTimeout(() => {
                        const toast = document.getElementById('toast');
                        if (toast) toast.classList.remove('show');
                    }, 3500);

                    // Remove ?msg= from URL
                    window.history.replaceState({}, document.title, window.location.pathname);
                </script>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Main content section -->
        <main class="main-content">
            <div class="container">

                <!-- Section title -->
                <div class="title-bar">
                    <div class="title-bar-line"></div>
                    <h2 class="section-title">Patient List</h2>
                    <div class="title-bar-line-fade"></div>
                </div>

                <!-- Add patient button -->
                <div class="header-buttons">
                    <button class="btn-primary" onclick="location.href='AddPatient.php'">
                        <i class="fa-solid fa-user-plus"></i> Add Patient
                    </button>
                </div>

                <!-- Patient grid display -->
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
                                    <!-- Add medical notes button -->
                                    <button class="btn-secondary" onclick="location.href='MedicalNotes.php?patient_id=<?= urlencode($p['ID']) ?>'">
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

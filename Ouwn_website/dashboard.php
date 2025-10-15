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
        <?php
        include "header.html";
        ?>
        <main class="dashboard container">
            <h1>Dashboard</h1>

            <div class="dashboard-actions" id="dashboard-buttons">
                <button id="add-patient-btn" onclick="location.href='AddPatient.php'">
                <i class="fa-solid fa-user-plus"></i> Add Patient
                </button>
                <button id="write-notes-btn" onclick="location.href='MedicalNotes.php'">
                <i class="fa-solid fa-file-medical"></i> Write Notes
                </button>
            </div>
        </main>
        <footer>
                © 2025 OuwN Healthcare — All Rights Reserved
        </footer>

    </body>
</html>

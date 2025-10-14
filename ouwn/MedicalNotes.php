<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$connect = mysqli_connect("127.0.0.1", "root", "root", "OuwnDB",8889);

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}
$errors = [];
$messages = [];
if (!$connect) { die("Connection failed: " . mysqli_connect_error()); }

//if (!isset($_SESSION['UserID'])) {
    // Not logged in: redirect or block
   // header("Location: login.php");
  //  exit;
//}

//$HealthCareP = $_SESSION['UserID'];
$HealthCareP = 111;

// Handle form submit
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Medical Notes — Demo</title>
    <link rel="stylesheet" href="stylee.css">
</head>

<body class="form-page medical-note">
     <header class="header">
            <div class="header-left">
                <img src='logo.svg' alt="OuwN Logo" class="logo-img">
            </div>
            <div class="header-right">
                <div class="profile-icon" onclick="toggleDropdown(event)">
                    <img src="doctor.png" alt="Profile Icon">
                    <div class="dropdown" id="dropdownMenu">
                        <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
                        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
                <nav class="header-nav">
                    <a href="homePage.php#about">About</a>
                    <a href="homePage.php#vision">Vision</a>
                </nav>
            </div>
        </header>


    <main class="container">
        <h1>Add Medical Note</h1>
        <form id="noteForm" class="card"  method="POST" action="">
            <div class="row">
                <label>Patient ID *</label>
                <input name="pid" id="pid" type="text" required />
            </div>

            <div class="row">
                <label>Note *</label>
                <textarea name="note_text" id='note_text' rows="6" style="width:100%"required></textarea>
            </div>

            <div class="row actions">
                <button type='submit'></button>
            </div>

            <?php 
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // 1) Read & validate inputs
                $pid_raw   = $_POST['pid'] ?? '';
                $note_text = trim($_POST['note_text'] ?? '');

                // Require numeric PatientID
                if ($pid_raw === '' || !ctype_digit($pid_raw)) {
                    $errors[] = "Patient ID must be a number.";
                } else {
                    $pid = (int)$pid_raw;
                }

                if ($note_text === '') {
                    $errors[] = "Note is required.";
                }

                if (!$errors) {
                    // 2) Check that patient exists in Patient
                    $checkSql = "SELECT 1 FROM Patient WHERE ID = ? ";
                    $check = mysqli_prepare($connect, $checkSql);
                    mysqli_stmt_bind_param($check, "i", $pid);
                    mysqli_stmt_execute($check);
                    mysqli_stmt_store_result($check);
                    $exists = mysqli_stmt_num_rows($check) > 0;
                    mysqli_stmt_close($check);

                    if (!$exists) {
                        $errors[] = "No patient found with ID $pid.";
                    } else {
                        // 3) Insert into MedicalNote
                        
                        $insertSql = "INSERT INTO MedicalNote (id,patientid, note) VALUES (1,?, ?)";
                        $stmt = mysqli_prepare($connect, $insertSql);
                        mysqli_stmt_bind_param($stmt, "is", $pid, $note_text);
                        mysqli_stmt_execute($stmt);
                        $newId = mysqli_insert_id($connect);
                        mysqli_stmt_close($stmt);

                        $messages[] = "Note saved (NoteID: $newId) for PatientID $pid.";
                        // Optional redirect after success:
                        // header("Location: medical_notes_list.php?pid=$pid&msg=added"); exit;
                    }
                }
            }
            ?>
        </form>
    </main>
    <footer>
        <p>&copy; 2025 OuwN. All Rights Reserved.</p>
    </footer>
</body>
</html>
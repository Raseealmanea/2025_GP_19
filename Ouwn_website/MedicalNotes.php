<?php
    //activate the display error
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    //connection establish
    $connect = mysqli_connect("127.0.0.1", "root", "root", "OuwnDB",8889);

    if (!$connect) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $errors = [];
    if (!$connect) { die("Connection failed: " . mysqli_connect_error()); }

    //session process
    session_start();

    if (!isset($_SESSION['user_id'])) {
        // Not logged in: redirect or block
        header("Location: login.php");
        exit;
    }
    //for FK in patient table
    $HealthCareP = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     // 1) Read & validate inputs
     $pid_raw   = $_POST['pid'] ?? '';
     $note_text = trim($_POST['note_text'] ?? '');

     // Require numeric PatientID
     if ($pid_raw === '' || !ctype_digit($pid_raw)) {
        $errors[] = "patient ID must be a number.";
     } else {
        $pid = (int)$pid_raw;
        }

    if ($note_text === '') {
        $errors[] = "Note is required.";
        }
        if (!$errors) {
        // 2) Check that patient exists in Patient
        $checkSql = "SELECT 1 FROM patient WHERE ID = ? ";
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
            $insertSql = "INSERT INTO MedicalNote (patientid, note) VALUES (?, ?)";
            $stmt = mysqli_prepare($connect, $insertSql);
            mysqli_stmt_bind_param($stmt, "is", $pid, $note_text);
            mysqli_stmt_execute($stmt);
            $newId = mysqli_insert_id($connect);
            mysqli_stmt_close($stmt);

            $messages[] = "Note saved (NoteID: $newId) for PatientID $pid.";
            // redirect after success:
            header("Location: dashboard.php?pid=$pid&msg=added"); exit;
           }
        }
    }    
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
    <?php
        include "header.html";
    ?>
    <main class="container">
        <h1>Add Medical Note</h1>
        <form id="noteForm" class="card"  method="POST" action="">
            <div class="row">
                <label>Patient ID *</label>
                <input name="pid" id="pid" type="text" required />
            </div>

            <div class="row">
                <label>Note *</label>
                <textarea name="note_text" id='note_text' rows="6" required></textarea>
            </div>

            <div class="row actions">
                <button type='submit'>Add Note</button>
            </div>
            
            <div class="return-link right">
                <a href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> Return to Dashboard</a>
            </div>
        </form>
    </main>
    <footer>
        <p>&copy; 2025 OuwN. All Rights Reserved.</p>
    </footer>
</body>
</html>
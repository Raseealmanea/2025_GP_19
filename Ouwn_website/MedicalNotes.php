<?php
    //activate the display error
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    //connection establish
    $connect = mysqli_connect("127.0.0.1", "root", "root", "OuwnDB",8889);

    if (!$connect) {
        die("Connection failed: " . mysqli_connect_error());
    }

    
    if (!$connect) { die("Connection failed: " . mysqli_connect_error()); }

    //session process
    session_start();

    $errors = [];
    $messages = [];

    if (!isset($_SESSION['user_id'])) {
        // Not logged in: redirect or block
        header("Location: login.php");
        exit;
    }
    //for FK in patient table
    $HealthCareP = $_SESSION['user_id'];
    //The id of the selected patient from the dashboard
    $prefilledPid = $_GET['patient_id'] ?? '';

    //Handle post request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1) Read & validate inputs
        $pid   = (int)($_POST['pid'] ?? 0);
        $note_text = trim($_POST['note_text'] ?? '');
        //No submition without an Note
        if ($note_text === '') {
            $errors[] = "Note is required.";
        }
        if (empty($errors)){
        // Insert into MedicalNote
        $insertSql = "INSERT INTO MedicalNote (PatientID, Note) VALUES (?, ?)";
        $stmt = mysqli_prepare($connect, $insertSql);
        mysqli_stmt_bind_param($stmt, "is", $pid, $note_text);

        if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($connect);
        $messages[] = "Note saved (NoteID: $newId) for Patient ID $pid.";
        header("Location: dashboard.php?pid=$pid&msg=note_added");
        } else {
        $errors[] = "Insert failed: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
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

        <?php if (!empty($errors)): ?>
            <div class="alert error">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <h1>Add Medical Note</h1>
        <form id="noteForm" class="card"  method="POST" action="">

             <div class="row">
                <label for="pid">Patient ID <span class="required">*</span></label>
                <input name="pid" id="pid" type="text" 
                    value="<?= htmlspecialchars($prefilledPid) ?>" readonly required />
            </div>

            <div class="row">
                <label for="note_text">Note <span class="required">*</span></label>
                <textarea name="note_text" id="note_text" rows="6" required><?= htmlspecialchars($_POST['note_text'] ?? '') ?></textarea>
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

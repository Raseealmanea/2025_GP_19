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
    $added  = false;

// ---------- HANDLE POST BEFORE ANY OUTPUT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gather inputs
    $full_name  = trim($_POST['full_name'] ?? '');
    $dob        = $_POST['dob'] ?? '';
    $gender     = $_POST['gender'] ?? '';
    $nationalID = trim($_POST['ID'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $blood_type = trim($_POST['blood_type'] ?? '');

    // Basic validation
    if ($full_name === '') $errors[] = "Full Name is required.";
    if ($dob === '')       $errors[] = "Date of Birth is required.";
    if ($gender === '')    $errors[] = "Gender is required.";
    if ($nationalID === '')$errors[] = "ID is required.";
    if ($phone === '')     $errors[] = "Phone is required.";
    if ($email === '')     $errors[] = "Email is required.";
    if ($address === '')   $errors[] = "Address is required.";
    if ($blood_type === '')$errors[] = "Blood Type is required.";

    // Check if  ID already exists
    if ($nationalID !== '') {
        $checkSql = "SELECT COUNT(*) FROM Patient WHERE ID = ?";
        $checkStmt = mysqli_prepare($connect, $checkSql);
        mysqli_stmt_bind_param($checkStmt, "s", $nationalID);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_bind_result($checkStmt, $count);
        mysqli_stmt_fetch($checkStmt);
        mysqli_stmt_close($checkStmt);

        if ($count > 0) {
            $errors[] = "A patient with this ID already exists.";
        }
    }
     if ($dob !== '' && strtotime($dob) > time()) {
        $errors[] = "Date of birth cannot be in the future.";
    }

    if (empty($errors)) {
        $sql = "INSERT INTO Patient
                (FullName, DOB, Gender, `ID`, Phone, Email, Address, BloodType, UserID)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connect, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "sssssssss",
                $full_name,
                $dob,
                $gender,
                $nationalID,
                $phone,
                $email,
                $address,
                $blood_type,
                $HealthCareP
            );
            if (mysqli_stmt_execute($stmt)) {
                // PRG pattern: 303 See Other to prevent resubmits on refresh
                header("Location: dashboard.php?msg=added", true, 303);
                exit;
            } else {
                $errors[] = "Insert failed: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Prepare failed: " . mysqli_error($connect);
            }
        }
    }
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Add Patient — Demo</title>
    <link rel="stylesheet" href="stylee.css">
</head>
<body class="form-page medical-note">
    <?php
        include "header.html";
    ?>
    <main class="container">
        <h1>Add Patient</h1>
<!--Alert if there is an error in the input or missing field -->
        <?php if (!empty($errors)): ?>
        <div class="alert error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
<!--Gather Patient info-->
        <form id="patientForm" class="card" method="POST" action="">
            <div class="row">
                <label for="full_name">Full Name <span class="required">*</span></label>
                <input name="full_name" id="full_name" type="text" required
                    value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" />
            </div>

            <div class="row">
                <label for="dob">Date of Birth <span class="required">*</span></label>
                <input name="dob" id="dob" type="date" required
                    value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>" />
            </div>

            <div class="row">
                <label for="gender">Gender <span class="required">*</span></label>
                <select name="gender" id="gender" required>
                <option value="" disabled <?= empty($_POST['gender']) ? 'selected' : '' ?> hidden>Choose</option>
                <option value="M" <?= (($_POST['gender'] ?? '')==='M') ? 'selected' : '' ?>>Male</option>
                <option value="F" <?= (($_POST['gender'] ?? '')==='F') ? 'selected' : '' ?>>Female</option>
                </select>
            </div>

            <div class="row">
                <label for="ID">ID <span class="required">*</span></label>
                <input name="ID" id="ID" type="text" required
                    value="<?= htmlspecialchars($_POST['ID'] ?? '') ?>" />
            </div>

            <div class="row">
                <label for="phone">Phone <span class="required">*</span></label>
                <input id="phone" name="phone" type="tel" required
                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" />
            </div>

            <div class="row">
                <label for="email">Email <span class="required">*</span></label>
                <input name="email" id="email" type="email" required
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            </div>

            <div class="row">
                <label for="address">Address <span class="required">*</span></label>
                <input name="address" id="address" type="text" required
                    value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" />
            </div>

            <div class="row">
                <label for="blood_type">Blood Type <span class="required">*</span></label>
                <input name="blood_type" id="blood_type" type="text" required placeholder="e.g. O+, A-"
                    value="<?= htmlspecialchars($_POST['blood_type'] ?? '') ?>" />
            </div>

            <div class="row actions">
                <button type="submit">Add Patient</button> 
            </div>
<!--Redirect to dashboard when the pattient is added-->
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

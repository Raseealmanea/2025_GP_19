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
    if ($dob !== '' && strtotime($dob) > time()) $errors[] = "DOB cannot be in the future.";

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
        <form id="patientForm" class="card" method="POST" action="">
            <div class="row">
                <label>Full Name *</label>
                <input name="full_name" id="full_name" type="text" required />
            </div>

            <div class="row">
                <label>Date of Birth *</label>
                <input name="dob" id="dob" type="date" required />
            </div>

            <div class="row">
                <label>Gender *</label>
                <select name="gender" id="gender" required>
                    <option value="" disabled selected hidden>Choose</option>
                    <option value="M">Male</option>
                    <option value="F">Female</option>
                </select>
            </div>

            <div class="row">
                <label>ID</label>
                <input name="ID" id="ID" type="text" />
            </div>

            <div class="row">
                <label>Phone</label>
                <input id="phone" name="phone" type="tel" />
            </div>

            <div class="row">
                <label>Email</label>
                <input name="email" type="email" />
            </div>

            <div class="row">
                <label>Address</label>
                <input name="address" id="address" type="text" />
            </div>

            <div class="row">
                <label>Blood Type</label>
                <input name="blood_type" id="blood_type" type="text" placeholder="e.g. O+, A-" />
            </div>

            <div class="row actions">
                <button type="submit">Add Patient</button> 
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

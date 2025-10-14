<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$connect = mysqli_connect("127.0.0.1", "root", "root", "OuwnDB",8889);

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

$errors = [];
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
    <title>Add Patient — Demo</title>
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
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
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

            <div class="row actions"></div>
                <button type="submit">Add Patient</button>
            </div>
        </form>
        <?php
        $HealthCareP = 111;
        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
        // Get and sanitize inputs
                if (
                    $_POST['full_name'] &&
                    $_POST['dob'] &&
                    $_POST['gender'] &&
                    $_POST['ID'] &&
                    $_POST['phone'] &&
                    $_POST['address'] &&
                    $_POST['blood_type']
                ) {
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

                    if (empty($errors)) {
                        // Use prepared statement; wrap ID in backticks
                        $sql = "INSERT INTO patient
                                (UserID, FullName, DOB, Gender, ID, Phone, Email, Address, BloodType)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($connect, $sql);

                        if (!$stmt) {
                            $errors[] = "Prepare failed: " . mysqli_error($connect);
                        } else {
                            mysqli_stmt_bind_param(
                                $stmt,
                                "issssssss", // i = int (UserID), rest = strings
                                $HealthCareP,
                                $full_name,
                                $dob,
                                $gender,
                                $nationalID,
                                $phone,
                                $email,
                                $address,
                                $blood_type
                            );

                        /* if (mysqli_stmt_execute($stmt)) {
                                
                                header("Location: patient_dashboard.php?msg=added");
                                exit;
                            } else {
                                $errors[] = "Insert failed: " . mysqli_stmt_error($stmt);
                            }*/

                            mysqli_stmt_close($stmt);
                        }
                    }
                }
            } 
        ?>
    </main>
    <footer>
        <p>&copy; 2025 OuwN. All Rights Reserved.</p>
    </footer>
</body>
</html>
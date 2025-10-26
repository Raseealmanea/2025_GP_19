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
        if ($dob !== '') {
        $dobTimestamp = strtotime($dob);
        $currentTimestamp = time();

        // Check if DOB is in the future
        if ($dobTimestamp > $currentTimestamp) {
            $errors[] = "Date of birth cannot be in the future.";
        } else {
            // Calculate age
            $age = date('Y') - date('Y', $dobTimestamp);
            // Adjust if the birthday hasn't occurred yet this year
            if (date('md', $dobTimestamp) > date('md')) {
                $age--;
            }

            // Check if age exceeds 130
            if ($age > 130) {
                $errors[] = "Age cannot exceed 130 years.";
            }
        }
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
                header("Location: dashboard.php?msg=patient_added", true, 303);
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
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            box-shadow: none;
            border-color: #ccc; /* optional — keep border consistent */
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="form-page medical-note">
    <?php
        include "header.html";
    ?>
    <!-- BREADCRUMB BAR -->
    <nav class="ouwn-breadcrumb-bar" role="navigation" aria-label="Breadcrumb">
    <div class="ouwn-crumbs">
        <a class="crumb" href="dashboard.php">
        <i class="fa-solid fa-house"></i>
        <span>Dashboard</span>
        </a>
        <span class="sep" aria-hidden="true">/</span>
        <span class="crumb current" aria-current="page">
        <i class="fa-solid fa-user-plus"></i>
        <span>Add Patient</span>
        </span>
    </div>
    </nav>

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
        <form id="patientForm" class="card" method="POST" action="" autocomplete="off">
            <div class="row">
                <label for="full_name">Full Name <span class="required">*</span></label>
                <input name="full_name" id="full_name" type="text" required autocomplete="new-Name"
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
            <label for="ID">National ID / Iqama <span class="required">*</span></label>
            <input 
                name="ID" 
                id="ID" 
                type="text" 
                required 
                pattern="\d{10}" 
                maxlength="10" 
                minlength="10"
                title="Please enter exactly 10 digits."
                placeholder="e.g. 1234567890"
                value="<?= htmlspecialchars($_POST['ID'] ?? '') ?>" 
                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,10);"
                autocomplete="naw-id"
            />
        </div>

           <div class="row">
            <label for="phone">Phone <span class="required">*</span></label>
            <input 
                id="phone" 
                name="phone" 
                type="tel" 
                required 
                pattern="^05\d{8}$"
                maxlength="10"
                minlength="10"
                title="Phone number must start with 05 and be exactly 10 digits."
                placeholder="e.g. 05XXXXXXXX"
                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                autocomplete="new-phone"
            />
        </div>



            <div class="row">
                <label for="email">Email <span class="required">*</span></label>
                <input name="email" id="email" type="email" required 
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                    title="Please enter a valid email address (e.g., name@example.com)"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            </div>


            <div class="row">
                <label for="address">Address <span class="required">*</span></label>
                <input name="address" id="address" type="text" required autocomplete="new-address"
                    value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" />
            </div>

            <div class="row">
                <label for="blood_type">Blood Type <span class="required">*</span></label>
                <select name="blood_type" id="blood_type" required>
                    <option value="" disabled <?= empty($_POST['blood_type']) ? 'selected' : '' ?>>Select blood type</option>
                    <option value="A+" <?= (($_POST['blood_type'] ?? '') === 'A+') ? 'selected' : '' ?>>A+</option>
                    <option value="A-" <?= (($_POST['blood_type'] ?? '') === 'A-') ? 'selected' : '' ?>>A-</option>
                    <option value="B+" <?= (($_POST['blood_type'] ?? '') === 'B+') ? 'selected' : '' ?>>B+</option>
                    <option value="B-" <?= (($_POST['blood_type'] ?? '') === 'B-') ? 'selected' : '' ?>>B-</option>
                    <option value="AB+" <?= (($_POST['blood_type'] ?? '') === 'AB+') ? 'selected' : '' ?>>AB+</option>
                    <option value="AB-" <?= (($_POST['blood_type'] ?? '') === 'AB-') ? 'selected' : '' ?>>AB-</option>
                    <option value="O+" <?= (($_POST['blood_type'] ?? '') === 'O+') ? 'selected' : '' ?>>O+</option>
                    <option value="O-" <?= (($_POST['blood_type'] ?? '') === 'O-') ? 'selected' : '' ?>>O-</option>
                </select>
            </div>


            <div class="row actions">
                <button type="submit">Add Patient</button> 
            </div>
        </form>
    </main>
    <footer>
        <p>&copy; 2025 OuwN. All Rights Reserved.</p>
    </footer>
</body>
</html>

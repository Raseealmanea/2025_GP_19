<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$mysqli = new mysqli("127.0.0.1", "root", "root", "OuwnDB", 8889);
if ($mysqli->connect_error) { die("Connection failed: " . $mysqli->connect_error); }
$mysqli->set_charset('utf8mb4');

/* ---------- AJAX duplicate ID check ---------- */
if (isset($_GET['check']) && $_GET['check'] === 'id' && isset($_GET['v'])) {
  header('Content-Type: application/json; charset=UTF-8');
  $id = trim($_GET['v']);
  $stmt = $mysqli->prepare("SELECT COUNT(*) FROM Patient WHERE ID=?");
  $stmt->bind_param("s", $id);
  $stmt->execute();
  $stmt->bind_result($c);
  $stmt->fetch();
  echo json_encode(['exists' => ($c > 0)]);
  exit;
}

/* ---------- FORM POST ---------- */
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name   = trim($_POST['full_name'] ?? '');
  $dob    = $_POST['dob'] ?? '';
  $gender = $_POST['gender'] ?? '';
  $id     = trim($_POST['ID'] ?? '');
  $phone  = trim($_POST['phone'] ?? '');
  $email  = trim($_POST['email'] ?? '');
  $address= trim($_POST['address'] ?? '');
  $blood  = trim($_POST['blood_type'] ?? '');
  $uid    = $_SESSION['user_id'];

  // Required (keep server-side required to protect DB)
  foreach (['name'=>'Full Name','dob'=>'Date of Birth','gender'=>'Gender','id'=>'ID','phone'=>'Phone','email'=>'Email','address'=>'Address','blood'=>'Blood Type'] as $k=>$label) {
    if (empty($$k)) $errors[] = "$label is required.";
  }

  // Formats
  if ($id && !preg_match('/^\d{10}$/', $id)) $errors[] = "ID must be exactly 10 digits.";
  if ($phone && !preg_match('/^05\d{8}$/', $phone)) $errors[] = "Phone must start with 05 and be 10 digits.";
  if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";

  // DOB: not future, age ≤ 130
  if ($dob) {
    $dobTs = strtotime($dob);
    if ($dobTs === false) {
      $errors[] = "Invalid date format for Date of Birth.";
    } else {
      if ($dobTs > time()) {
        $errors[] = "Date of Birth cannot be in the future.";
      } else {
        $age = (int)date('Y') - (int)date('Y', $dobTs);
        if (date('md', $dobTs) > date('md')) $age--;
        if ($age > 130) $errors[] = "Age cannot exceed 130 years.";
      }
    }
  }

  // Duplicate ID
  if (empty($errors) && $id) {
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM Patient WHERE ID=?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();
    if ($cnt > 0) $errors[] = "A patient with this ID already exists.";
  }

  if (empty($errors)) {
    $stmt = $mysqli->prepare("INSERT INTO Patient (ID,FullName,Email,DOB,Address,Phone,Gender,BloodType,UserID)
                              VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssssssss",$id,$name,$email,$dob,$address,$phone,$gender,$blood,$uid);
    if ($stmt->execute()) { header("Location: dashboard.php?msg=added", true, 303); exit; }
    else { $errors[] = "Insert failed: ".$stmt->error; }
    $stmt->close();
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Add Patient</title>
<link rel="stylesheet" href="stylee.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* Layout */
.card .row {
  display: grid;
  grid-template-columns: 220px 1fr;
  gap: 12px 16px;
  align-items: start;
  margin-bottom: 14px;
}
.card .row label { padding-top: 10px; }
.control { display: flex; flex-direction: column; gap: 6px; width: 100%; }

/* Input base */
.control input, .control select {
  width: 100%;
  border: 1px solid #d9d9e3;
  border-radius: 12px;
  padding: 12px 14px;
  background: #fff;
  transition: border-color .2s ease, box-shadow .2s ease;
}

/* Red/green borders (strong override) */
.control input.is-valid, .control select.is-valid {
  border: 2px solid #2e7d32 !important;
  box-shadow: 0 0 0 2px rgba(46,125,50,.08) !important;
}
.control input.is-invalid, .control select.is-invalid {
  border: 2px solid #c62828 !important;
  box-shadow: 0 0 0 2px rgba(198,40,40,.08) !important;
}

/* Feedback text (under field) */
.feedback {
  font-size: 0.9em;
  line-height: 1.4;
  color: #c62828;               /* red by default */
  display: flex;
  align-items: center;
  gap: 6px;
  min-height: 1.2em;            /* keep layout steady */
}
.feedback:empty { display: none; }             /* hide when no message */
.feedback:not(:empty)::before {
  content: "•";
  font-weight: bold;
  line-height: 0;
  color: currentColor;                          /* dot follows text color */
}
.feedback.valid { color: #2e7d32; }
.required { color: #c62828; }
/* 1) Kill blue focus background + ring */
.control input:focus,
.control select:focus {
  background: #fff !important;    /* keep white */
  outline: none !important;
  box-shadow: none !important;     /* remove default glow */
}

/* 2) Ensure inputs are white by default (beats theme rules) */
.control input,
.control select {
  background: #fff !important;
}

/* 3) Chrome/Safari autofill (yellow/blue fill) — force white */
.control input:-webkit-autofill,
.control input:-webkit-autofill:hover,
.control input:-webkit-autofill:focus,
.control select:-webkit-autofill,
.control select:-webkit-autofill:hover,
.control select:-webkit-autofill:focus {
  -webkit-box-shadow: 0 0 0 1000px #fff inset !important; /* paint white */
  box-shadow: 0 0 0 1000px #fff inset !important;
  -webkit-text-fill-color: inherit !important;             /* keep your text color */
}

/* 4) Optional: disable iOS/macOS blue tint on active elements */
input, select, textarea, button {
  -webkit-tap-highlight-color: transparent;
}

</style>
</head>
<body class="form-page medical-note">

<?php include "header.html"; ?>

<main class="container card" style="padding:24px 24px 16px;">
  <h1 style="margin-top:0">Add Patient</h1>

  <?php if ($errors): ?>
  <div class="alert error" style="margin-bottom:16px">
    <ul><?php foreach($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?></ul>
  </div>
  <?php endif; ?>

  <form id="patientForm" method="POST" novalidate>
    <?php
    function field($label,$id,$type="text",$extra="",$value=""){ echo "
      <div class='row'>
        <label for='$id'>$label<span class='required'>*</span></label>
        <div class='control'>
          <input id='$id' name='$id' type='$type' $extra value='".htmlspecialchars($value)."'>
          <div class='feedback'></div>
        </div>
      </div>"; }
    ?>
    <?php field("Full Name","full_name","text","required",$_POST['full_name']??''); ?>
    <?php field("Date of Birth","dob","date","required",$_POST['dob']??''); ?>

    <div class="row">
      <label for="gender">Gender<span class="required">*</span></label>
      <div class="control">
        <select id="gender" name="gender" required>
          <option value="">Choose</option>
          <option value="M" <?=($_POST['gender']??'')==='M'?'selected':''?>>Male</option>
          <option value="F" <?=($_POST['gender']??'')==='F'?'selected':''?>>Female</option>
        </select>
        <div class="feedback"></div>
      </div>
    </div>

    <?php field("National ID / Iqama","ID","text",
      "maxlength='10' minlength='10' required oninput=\"this.value=this.value.replace(/[^0-9]/g,'').slice(0,10);\"",
      $_POST['ID']??''); ?>

    <?php field("Phone","phone","tel",
      "maxlength='10' minlength='10' required oninput=\"this.value=this.value.replace(/[^0-9]/g,'').slice(0,10);\"",
      $_POST['phone']??''); ?>

    <?php field("Email","email","email","required",$_POST['email']??''); ?>
    <?php field("Address","address","text","required",$_POST['address']??''); ?>

    <div class="row">
      <label for="blood_type">Blood Type<span class="required">*</span></label>
      <div class="control">
        <select id="blood_type" name="blood_type" required>
          <option value="">Select</option>
          <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $b): ?>
            <option value="<?=$b?>" <?=($_POST['blood_type']??'')===$b?'selected':''?>><?=$b?></option>
          <?php endforeach; ?>
        </select>
        <div class="feedback"></div>
      </div>
    </div>

    <div class="row" style="grid-template-columns: 1fr;">
      <div></div>
      <div class="control"><button type="submit" class="primary">Add Patient</button></div>
    </div>
  </form>
</main>

<script>
// Helper to set UI
function setValidity(input, ok, msg){
  const fb = input.closest('.control').querySelector('.feedback');
  input.classList.toggle('is-valid', ok);
  input.classList.toggle('is-invalid', !ok);
  fb.textContent = msg || "";
  fb.classList.toggle('valid', ok);
}

/* ========== LIVE VALIDATION ONLY FOR: DOB, ID, PHONE, EMAIL ========== */

// DOB: not future & age ≤ 130
const dob = document.getElementById('dob');
function checkDob() {
  const v = dob.value;
  if (!v) { setValidity(dob, false, "Date of Birth is required."); return; }
  const d = new Date(v + "T00:00:00");
  if (isNaN(d.getTime())) { setValidity(dob, false, "Invalid date."); return; }
  const now = new Date();
  if (d > now) { setValidity(dob, false, "Date of Birth cannot be in the future."); return; }
  let age = now.getFullYear() - d.getFullYear();
  const mdNow = (now.getMonth()+1)*100 + now.getDate();
  const mdDob = (d.getMonth()+1)*100 + d.getDate();
  if (mdDob > mdNow) age--;
  if (age > 130) { setValidity(dob, false, "Age cannot exceed 130 years."); return; }
  setValidity(dob, true, "DOB looks good.");
}
dob.addEventListener('input', checkDob);
dob.addEventListener('change', checkDob);

// ID: 10 digits + duplicate
let idTimer;
const idInput = document.getElementById('ID');
idInput.addEventListener('input', ()=>{
  const v = idInput.value.trim();
  if (!/^\d{10}$/.test(v)) { setValidity(idInput, false, "Must be exactly 10 digits."); return; }
  clearTimeout(idTimer);
  idTimer = setTimeout(()=>{
    fetch(`<?= basename(__FILE__) ?>?check=id&v=${encodeURIComponent(v)}`)
      .then(r=>r.json())
      .then(d=> setValidity(idInput, !d.exists, d.exists ? "This ID already exists." : "ID looks good.") )
      .catch(()=> setValidity(idInput, true, "ID looks good.") );
  }, 250);
});

// Phone: start 05 + 10 digits
const phone = document.getElementById('phone');
phone.addEventListener('input', ()=>{
  const ok = /^05\d{8}$/.test(phone.value.trim());
  setValidity(phone, ok, ok ? "Phone looks good." : "Must start with 05 and be 10 digits.");
});

// Email: simple pattern
const email = document.getElementById('email');
email.addEventListener('input', ()=>{
  const ok = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i.test(email.value.trim());
  setValidity(email, ok, ok ? "Email looks good." : "Invalid email format.");
});

/* No live validation for: full_name, gender, address, blood_type */
</script>
</body>
</html>

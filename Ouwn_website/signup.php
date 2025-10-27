<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
session_start();

// ---- Database Config ----
$DB_HOST = "127.0.0.1";
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'OuwnDB';
$TABLE   = 'HealthCareP';
$port    = 8889;

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $port);
$mysqli->set_charset('utf8mb4');

// ---------- AJAX: live duplication check ----------
if (isset($_GET['check'], $_GET['v'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $field = $_GET['check'];
    $v     = trim((string)$_GET['v']);
    $resp  = ['ok' => true, 'exists' => false, 'valid' => true];

    try {
        if ($field === 'username') {
            $resp['valid'] = (bool) preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $v);
            if ($resp['valid']) {
                $stmt = $mysqli->prepare("SELECT 1 FROM $TABLE WHERE UserID = ? LIMIT 1");
                $stmt->bind_param('s', $v);
                $stmt->execute();
                $stmt->store_result();
                $resp['exists'] = $stmt->num_rows > 0;
                $stmt->close();
            }
        } elseif ($field === 'email') {
            $resp['valid'] = filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
            if ($resp['valid']) {
                $stmt = $mysqli->prepare("SELECT 1 FROM $TABLE WHERE Email = ? LIMIT 1");
                $stmt->bind_param('s', $v);
                $stmt->execute();
                $stmt->store_result();
                $resp['exists'] = $stmt->num_rows > 0;
                $stmt->close();
            }
        } else {
            $resp = ['ok' => false];
        }
    } catch (Throwable $e) {
        $resp = ['ok' => false];
        error_log("AJAX check failed: ".$e->getMessage());
    }
    echo json_encode($resp);
    exit;
}

// ---------- Normal signup flow ----------
$successMsg = '';
$errorMsg   = '';
$isPost     = ($_SERVER['REQUEST_METHOD'] === 'POST');

function eval_password_checks(string $pw): array {
  return [
    'len'     => strlen($pw) >= 8,
    'upper'   => (bool) preg_match('/[A-Z]/', $pw),
    'lower'   => (bool) preg_match('/[a-z]/', $pw),
    'digit'   => (bool) preg_match('/\d/', $pw),
    'special' => (bool) preg_match('/[^A-Za-z0-9]/', $pw),
  ];
}

try {
    if ($isPost) {
        $first  = trim($_POST['first_name'] ?? '');
        $last   = trim($_POST['last_name'] ?? '');
        $userID = trim($_POST['username'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $pass   = $_POST['password'] ?? '';

        if ($first === '' || $last === '' || $userID === '' || $email === '' || $pass === '') {
            throw new RuntimeException('Some information entered is invalid. Please review and try again.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Some information entered is invalid. Please review and try again.');
        }

        $pwChecks = eval_password_checks($pass);
        if (!in_array(false, $pwChecks, true)) {
            $passHash = password_hash($pass, PASSWORD_DEFAULT);
        } else {
            throw new RuntimeException('Some information entered is invalid. Please review and try again.');
        }

        $stmt = $mysqli->prepare("SELECT 1 FROM $TABLE WHERE UserID = ? OR Email = ? LIMIT 1");
        $stmt->bind_param('ss', $userID, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            throw new RuntimeException('Unable to create account. Please try again or use different credentials.');
        }
        $stmt->close();

        // Send confirmation email
        $userdata = [
            'username' => $userID,
            'email'    => $email,
            'password' => $passHash,
            'fullname' => "$first $last"
        ];
        $encoded = base64_encode(json_encode($userdata));

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ouwnsystem@gmail.com';
        $mail->Password   = 'hekwyotvhhijigbo';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('ouwnsystem@gmail.com', 'OuwN System');
        $mail->addAddress($email, "$first $last");

        $confirmLink = "http://localhost:8888/ouwn/confirm_email.php?data=$encoded";
        $mail->isHTML(true);
        $mail->Subject = 'Confirm Your Email';
        $mail->Body = "
        <html><body style='font-family:sans-serif'>
        <p>Hi ".htmlspecialchars("$first $last").",</p>
        <p>Please confirm your email by clicking below:</p>
        <p><a href='$confirmLink'>Confirm Email</a></p>
        </body></html>";
        $mail->send();

        $successMsg = '✅ Account created! Please check your email to confirm your account.';
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    $errorMsg = 'Something went wrong. Please review your information and try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Sign Up • OuwN</title>
<link rel="stylesheet" href="stylee.css">
<style>
  .banner { display:none; padding:12px 16px; border-radius:10px; margin:12px auto 0; max-width:540px; text-align:center; }
  .banner.show{ display:block; }
  .banner.err{ background:#f44336; color:#fff; }
  .banner.ok{  background:#2e7d32; color:#fff; }

  .auth-form { max-width:540px; margin:0 auto; }
  .auth-form label { display:block; font-weight:600; margin-top:12px; }
  .auth-form input { width:100%; padding:10px 12px; border:1px solid #ccc; border-radius:8px; margin-top:6px; transition:border-color .15s, box-shadow .15s; }
  .auth-form input.is-invalid { border-color:#e53935 !important; box-shadow:0 0 0 3px rgba(229,57,53,.25) !important; }
  .auth-form input.is-valid   { border-color:#2e7d32 !important; box-shadow:0 0 0 3px rgba(46,125,50,.25) !important; }

  .pw-checklist { list-style:none; padding-left:0; margin:8px 0 0; font-size:13px; }
  .pw-checklist li { display:flex; align-items:center; gap:8px; margin:4px 0; }
  .pw-ok  { color:#2e7d32; }
  .pw-bad { color:#e53935; }
  .pw-icon { font-weight:700; width:1.1em; text-align:center; }
</style>
</head>
<body class="auth-page-body">

<?php if ($errorMsg): ?>
  <div class="banner err show"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>
<?php if ($successMsg): ?>
  <div class="banner ok show"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>

<header class="header">
  <div class="header-left">
    <img src="logo.svg" alt="OuwN Logo" class="logo-img">
  </div>
  <div class="header-right">
    <nav class="header-nav">
      <a href="homePage.php#about">About</a>
      <a href="homePage.php#vision">Vision</a>
    </nav>
  </div>
</header>

<main class="auth-container">
  <h2 class="auth-title">Create your account</h2>
  <form method="POST" action="signup.php" class="auth-form" novalidate>
    <label>First name</label>
    <input name="first_name" type="text" placeholder="e.g., Sara" required
      value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">

    <label>Last name</label>
    <input name="last_name" type="text" placeholder="e.g., Al-Harbi" required
      value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">

    <label>Username</label>
    <input id="username" name="username" type="text" placeholder="Choose a username" required
      value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

    <label>Email</label>
    <input id="email" name="email" type="email" placeholder="you@example.com" required
      value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

    <label>Password</label>
    <input id="password" name="password" type="password" placeholder="Create a password" required>

    <ul class="pw-checklist" id="pwChecklistLive">
      <li data-k="len"><span class="pw-icon">❌</span> At least 8 characters</li>
      <li data-k="upper"><span class="pw-icon">❌</span> Uppercase letter</li>
      <li data-k="lower"><span class="pw-icon">❌</span> Lowercase letter</li>
      <li data-k="digit"><span class="pw-icon">❌</span> Number (0–9)</li>
      <li data-k="special"><span class="pw-icon">❌</span> Special character (!@#…)</li>
    </ul>

    <button type="submit" class="btn">Sign up</button>
  </form>

  <p class="auth-meta">Already have an account? <a href="login.php">Log In</a></p>
</main>

<footer>
  <p>&copy; 2025 OuwN. All Rights Reserved.</p>
</footer>

<script>
function debounce(fn, wait) {
  let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
}

async function checkAvailability(field, value) {
  if (!value) return {ok:true, exists:false, valid:false};
  const res = await fetch(`signup.php?check=${encodeURIComponent(field)}&v=${encodeURIComponent(value)}`, {cache:'no-store'});
  return res.json();
}

function updateInput(input, state) {
  input.classList.remove('is-valid','is-invalid');
  if (!state.ok || !state.valid || state.exists) input.classList.add('is-invalid');
  else input.classList.add('is-valid');
}

const usernameEl=document.getElementById('username');
const emailEl=document.getElementById('email');

const runUserCheck=debounce(async()=>{
  const v=usernameEl.value.trim(); if(!v){usernameEl.classList.remove('is-valid','is-invalid');return;}
  const s=await checkAvailability('username',v); updateInput(usernameEl,s);
},300);
const runEmailCheck=debounce(async()=>{
  const v=emailEl.value.trim(); if(!v){emailEl.classList.remove('is-valid','is-invalid');return;}
  const s=await checkAvailability('email',v); updateInput(emailEl,s);
},300);

usernameEl.addEventListener('input',runUserCheck);
emailEl.addEventListener('input',runEmailCheck);

// Password live checklist
(function(){
  const pw=document.getElementById('password');
  const list=document.getElementById('pwChecklistLive');
  function evalPw(v){return{
    len:v.length>=8,upper:/[A-Z]/.test(v),lower:/[a-z]/.test(v),digit:/\d/.test(v),special:/[^A-Za-z0-9]/.test(v)};}
  function setRow(k,ok){const li=list.querySelector('li[data-k="'+k+'"]');if(!li)return;
    li.classList.toggle('pw-ok',ok);li.classList.toggle('pw-bad',!ok);
    li.querySelector('.pw-icon').textContent=ok?'✅':'❌';}
  pw.addEventListener('input',()=>{
    const c=evalPw(pw.value);['len','upper','lower','digit','special'].forEach(k=>setRow(k,c[k]));
    pw.classList.toggle('is-invalid',!Object.values(c).every(Boolean)&&pw.value.length>0);
    pw.classList.toggle('is-valid',Object.values(c).every(Boolean));
  });
})();
</script>
</body>
</html>

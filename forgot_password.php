<?php
session_start();
require_once 'db.php';

$step    = 1;
$error   = '';
$success = '';
$user    = null;

// Step 1 — Find user by username
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['step1'])) {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $result   = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (empty($user['security_question'])) {
            $error = "This account has no security question set. Please contact admin.";
        } else {
            $_SESSION['reset_user_id'] = $user['id'];
            $step = 2;
        }
    } else {
        $error = "No account found with this username!";
    }
}

// Step 2 — Verify security answer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['step2'])) {
    $user_id = $_SESSION['reset_user_id'] ?? 0;
    $answer  = trim(strtolower(mysqli_real_escape_string($conn, $_POST['security_answer'])));
    $result  = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
    $user    = mysqli_fetch_assoc($result);
    $correct = strtolower($user['security_answer'] ?? '');
    if ($answer === $correct) {
        $_SESSION['reset_verified'] = true;
        $step = 3;
    } else {
        $error = "Incorrect answer! Please try again.";
        $step  = 2;
    }
}

// Step 3 — Set new password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['step3'])) {
    if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified']) {
        header("Location: forgot_password.php"); exit();
    }
    $user_id  = $_SESSION['reset_user_id'];
    $password = trim($_POST['new_password']);
    $confirm  = trim($_POST['confirm_password']);
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!"; $step = 3;
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match!"; $step = 3;
    } else {
        $hashed = md5($password);
        mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE id=$user_id");
        unset($_SESSION['reset_user_id'], $_SESSION['reset_verified']);
        $success = "Password reset successfully! You can now login.";
        $step = 4;
    }
}

// Load user for step 2
if ($step == 2 && isset($_SESSION['reset_user_id'])) {
    $uid    = $_SESSION['reset_user_id'];
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id=$uid");
    $user   = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — Ride2School</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Poppins',sans-serif;background:#f5f0e8;min-height:100vh;display:flex;flex-direction:column;}
  nav{background:#0d3b6e;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:65px;box-shadow:0 2px 12px rgba(0,0,0,0.15);}
  .logo{font-size:22px;font-weight:700;color:#fff;}.logo span{color:#f0c040;}
  .nav-links a{color:#cde4f7;text-decoration:none;margin-left:28px;font-size:14px;font-weight:500;}
  .page-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 20px;}
  .form-card{background:#fff;border-radius:20px;padding:40px 44px;width:100%;max-width:460px;box-shadow:0 8px 40px rgba(13,59,110,0.10);}
  .form-top{text-align:center;margin-bottom:28px;}
  .form-top .icon{font-size:48px;margin-bottom:10px;}
  .form-top h2{font-size:22px;font-weight:700;color:#0d3b6e;margin-bottom:6px;}
  .form-top p{font-size:13px;color:#6b7c8d;}

  /* STEPS */
  .steps-row{display:flex;align-items:center;justify-content:center;margin-bottom:28px;gap:0;}
  .step-dot{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;transition:all 0.3s;}
  .step-dot.done{background:#0d3b6e;color:#fff;}
  .step-dot.active{background:#f0c040;color:#0d3b6e;}
  .step-dot.pending{background:#e0eaf5;color:#aab8c8;}
  .step-line{width:40px;height:2px;background:#e0eaf5;}
  .step-line.done{background:#0d3b6e;}

  .field{margin-bottom:18px;}
  .field label{display:block;font-size:13px;font-weight:500;color:#0d3b6e;margin-bottom:7px;}
  .field input,.field select{width:100%;padding:13px 16px;border:2px solid #e0eaf5;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;color:#1a2a3a;transition:all 0.25s;background:#f9fbff;outline:none;}
  .field input:focus,.field select:focus{border-color:#1a5fa8;background:#fff;box-shadow:0 0 0 4px rgba(26,95,168,0.08);}
  .field input::placeholder{color:#aab8c8;}
  .hint{font-size:12px;color:#aab8c8;margin-top:5px;}

  .question-box{background:#EBF3FC;border-radius:12px;padding:14px 18px;margin-bottom:18px;}
  .question-box .q-label{font-size:12px;color:#6b7c8d;margin-bottom:4px;font-weight:500;}
  .question-box .q-text{font-size:14px;color:#0d3b6e;font-weight:600;}

  .submit-btn{width:100%;padding:14px;background:#0d3b6e;color:#fff;border:none;border-radius:12px;font-family:'Poppins',sans-serif;font-size:15px;font-weight:600;cursor:pointer;transition:all 0.3s;margin-top:4px;}
  .submit-btn:hover{background:#1a5fa8;transform:translateY(-2px);box-shadow:0 6px 18px rgba(13,59,110,0.25);}
  .error-msg{background:#fff0f0;border-left:4px solid #cc2222;color:#cc2222;padding:12px 16px;border-radius:0 10px 10px 0;font-size:13px;margin-bottom:18px;}
  .success-wrap{text-align:center;padding:20px 0;}
  .success-wrap .s-icon{font-size:56px;margin-bottom:14px;}
  .success-wrap h3{font-size:20px;font-weight:700;color:#1D9E75;margin-bottom:8px;}
  .success-wrap p{font-size:14px;color:#6b7c8d;margin-bottom:20px;}
  .back-btn{display:inline-block;padding:12px 28px;background:#0d3b6e;color:#fff;border-radius:12px;text-decoration:none;font-size:15px;font-weight:600;}
  .back-link{text-align:center;margin-top:18px;font-size:13px;}
  .back-link a{color:#1a5fa8;font-weight:500;text-decoration:none;}

  .pw-wrap{position:relative;}
  .pw-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:16px;color:#aab8c8;background:none;border:none;}

  .strength-bars{display:flex;gap:4px;margin-top:8px;}
  .s-bar{flex:1;height:4px;border-radius:2px;background:#e0eaf5;transition:background 0.3s;}
  .strength-text{font-size:11px;margin-top:4px;font-weight:500;}

  footer{background:#091f38;color:#6b8aaa;text-align:center;padding:18px;font-size:13px;}
  footer span{color:#f0c040;font-weight:600;}
</style>
</head>
<body>
<nav>
  <div class="logo">Ride<span>2</span>School</div>
  <div class="nav-links"><a href="login.php">Login</a></div>
</nav>
<div class="page-wrap">
  <div class="form-card">

    <?php if ($step == 4): ?>
    <div class="success-wrap">
      <div class="s-icon">🎉</div>
      <h3>Password Reset!</h3>
      <p>Your password has been changed successfully.</p>
      <a href="login.php" class="back-btn">Go to Login →</a>
    </div>

    <?php else: ?>
    <div class="form-top">
      <div class="icon">🔐</div>
      <h2>Forgot Password</h2>
      <p>Reset your password using your security question</p>
    </div>

    <!-- STEPS INDICATOR -->
    <div class="steps-row">
      <div class="step-dot <?php echo $step>=1?($step>1?'done':'active'):'pending'; ?>">1</div>
      <div class="step-line <?php echo $step>1?'done':''; ?>"></div>
      <div class="step-dot <?php echo $step>=2?($step>2?'done':'active'):'pending'; ?>">2</div>
      <div class="step-line <?php echo $step>2?'done':''; ?>"></div>
      <div class="step-dot <?php echo $step>=3?'active':'pending'; ?>">3</div>
    </div>

    <?php if ($error): ?>
      <div class="error-msg">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($step == 1): ?>
    <form method="POST">
      <div class="field">
        <label>Enter your Username</label>
        <input type="text" name="username" placeholder="Your registered username" autocomplete="off" required>
      </div>
      <button type="submit" name="step1" class="submit-btn">Find My Account →</button>
    </form>

    <?php elseif ($step == 2): ?>
    <div class="question-box">
      <div class="q-label">Your security question:</div>
      <div class="q-text"><?php echo htmlspecialchars($user['security_question']); ?></div>
    </div>
    <form method="POST">
      <div class="field">
        <label>Your Answer</label>
        <input type="text" name="security_answer" placeholder="Type your answer" autocomplete="off" required>
        <div class="hint">Answer is not case-sensitive</div>
      </div>
      <button type="submit" name="step2" class="submit-btn">Verify Answer →</button>
    </form>

    <?php elseif ($step == 3): ?>
    <form method="POST">
      <div class="field">
        <label>New Password</label>
        <div class="pw-wrap">
          <input type="password" name="new_password" id="new_pw"
            placeholder="Min 6 characters" oninput="checkStrength(this.value)" required>
          <button type="button" class="pw-toggle" onclick="togglePw('new_pw',this)">👁</button>
        </div>
        <div class="strength-bars">
          <div class="s-bar" id="sb1"></div><div class="s-bar" id="sb2"></div>
          <div class="s-bar" id="sb3"></div><div class="s-bar" id="sb4"></div>
          <div class="s-bar" id="sb5"></div>
        </div>
        <div class="strength-text" id="st"></div>
      </div>
      <div class="field">
        <label>Confirm New Password</label>
        <div class="pw-wrap">
          <input type="password" name="confirm_password" id="cf_pw"
            placeholder="Re-enter password" required>
          <button type="button" class="pw-toggle" onclick="togglePw('cf_pw',this)">👁</button>
        </div>
      </div>
      <button type="submit" name="step3" class="submit-btn">Reset Password →</button>
    </form>
    <?php endif; ?>

    <div class="back-link"><a href="login.php">← Back to Login</a></div>
    <?php endif; ?>

  </div>
</div>
<footer><p>&copy; 2024 <span>Ride2School</span> — Safe School Transport</p></footer>
<script>
function togglePw(id,btn){const i=document.getElementById(id);i.type=i.type==='password'?'text':'password';btn.textContent=i.type==='password'?'👁':'🙈';}
function checkStrength(val){
  let s=0;
  if(val.length>=6)s++;if(val.length>=10)s++;
  if(/[A-Z]/.test(val))s++;if(/[0-9]/.test(val))s++;if(/[^A-Za-z0-9]/.test(val))s++;
  const c=['#cc2222','#e8830a','#f0c040','#1D9E75','#0d3b6e'];
  const l=['Very Weak','Weak','Fair','Strong','Very Strong'];
  for(let i=1;i<=5;i++)document.getElementById('sb'+i).style.background=i<=s?c[s-1]:'#e0eaf5';
  const t=document.getElementById('st');t.textContent=s>0?l[s-1]:'';t.style.color=s>0?c[s-1]:'';
}
</script>
</body>
</html>

<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = md5(trim($_POST['password']));
    
    // Debug — check what's in DB
    $result = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    
    if (mysqli_num_rows($result) == 0) {
        $error = "No account found with username: $username";
    } else {
        $user = mysqli_fetch_assoc($result);
        if ($user['password'] !== $password) {
            $error = "Password incorrect! Please try again.";
        } elseif (isset($user['is_verified']) && $user['is_verified'] == 0) {
            $error = "Email not verified! <a href='#' onclick='fixVerify()'>Click here to fix</a>";
        } else {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            header("Location: select_city.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Ride2School</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Poppins',sans-serif;background:#f5f0e8;min-height:100vh;display:flex;flex-direction:column;}
  nav{background:#0d3b6e;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:65px;box-shadow:0 2px 12px rgba(0,0,0,0.15);}
  .logo{font-size:22px;font-weight:700;color:#fff;}.logo span{color:#f0c040;}
  .nav-links a{color:#cde4f7;text-decoration:none;margin-left:28px;font-size:14px;font-weight:500;}
  .nav-links a:hover{color:#f0c040;}
  .page-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 20px;}
  .form-card{background:#fff;border-radius:20px;padding:40px 44px;width:100%;max-width:440px;box-shadow:0 8px 40px rgba(13,59,110,0.10);}
  .form-top{text-align:center;margin-bottom:24px;}
  .form-top .icon{font-size:44px;margin-bottom:8px;}
  .form-top h2{font-size:24px;font-weight:700;color:#0d3b6e;margin-bottom:6px;}
  .form-top p{font-size:13px;color:#6b7c8d;}
  .who-section{margin-bottom:22px;}
  .who-label{font-size:13px;font-weight:500;color:#0d3b6e;margin-bottom:10px;display:block;}
  .who-cards{display:flex;gap:12px;}
  .who-card{flex:1;border:2px solid #e0eaf5;border-radius:12px;padding:14px 10px;text-align:center;cursor:pointer;transition:all 0.25s;background:#f9fbff;}
  .who-card:hover{border-color:#1a5fa8;background:#EBF3FC;}
  .who-card.selected{border-color:#0d3b6e;background:#EBF3FC;}
  .who-card .who-icon{font-size:28px;margin-bottom:6px;}
  .who-card .who-name{font-size:13px;font-weight:600;color:#0d3b6e;}
  .field{margin-bottom:18px;}
  .field label{display:block;font-size:13px;font-weight:500;color:#0d3b6e;margin-bottom:7px;}
  .input-wrap{position:relative;}
  .field input{width:100%;padding:13px 44px 13px 16px;border:2px solid #e0eaf5;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;color:#1a2a3a;transition:all 0.25s;background:#f9fbff;outline:none;}
  .field input:focus{border-color:#1a5fa8;background:#fff;box-shadow:0 0 0 4px rgba(26,95,168,0.08);}
  .field input::placeholder{color:#aab8c8;}
  .pw-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:16px;color:#aab8c8;background:none;border:none;padding:0;line-height:1;}
  .submit-btn{width:100%;padding:14px;background:#0d3b6e;color:#fff;border:none;border-radius:12px;font-family:'Poppins',sans-serif;font-size:16px;font-weight:600;cursor:pointer;transition:all 0.3s;margin-top:6px;}
  .submit-btn:hover{background:#1a5fa8;transform:translateY(-2px);}
  .error-msg{background:#fff0f0;border-left:4px solid #cc2222;color:#cc2222;padding:12px 16px;border-radius:0 10px 10px 0;font-size:13px;margin-bottom:18px;}
  .divider{text-align:center;margin:20px 0;font-size:13px;color:#aab8c8;position:relative;}
  .divider::before,.divider::after{content:'';position:absolute;top:50%;width:42%;height:1px;background:#e0eaf5;}
  .divider::before{left:0;}.divider::after{right:0;}
  .bottom-link{text-align:center;font-size:13px;color:#6b7c8d;margin-bottom:8px;}
  .bottom-link a{color:#1a5fa8;font-weight:500;text-decoration:none;}
  footer{background:#091f38;color:#6b8aaa;text-align:center;padding:18px;font-size:13px;}
  footer span{color:#f0c040;font-weight:600;}
</style>
</head>
<body>
<nav>
  <div class="logo">Ride<span>2</span>School</div>
  <div class="nav-links">
    <a href="index.php">Home</a>
    <a href="register.php">Register</a>
  </div>
</nav>
<div class="page-wrap">
  <div class="form-card">
    <div class="form-top">
      <div class="icon">🔑</div>
      <h2>Welcome Back!</h2>
      <p>Sign in to your Ride2School account</p>
    </div>
    <?php if ($error): ?>
      <div class="error-msg">❌ <?php echo $error; ?></div>
    <?php endif; ?>
    <div class="who-section">
      <span class="who-label">Who are you?</span>
      <div class="who-cards">
        <div class="who-card selected" id="card-driver" onclick="selectCard('driver')">
          <div class="who-icon">🚐</div><div class="who-name">Driver</div>
        </div>
        <div class="who-card" id="card-parent" onclick="selectCard('parent')">
          <div class="who-icon">👨‍👩‍👧</div><div class="who-name">Parent</div>
        </div>
      </div>
    </div>
    <form method="POST" action="login.php" autocomplete="off">
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" placeholder="Enter your username"
          autocomplete="off"
          value="<?php echo isset($_POST['username'])?htmlspecialchars($_POST['username']):''; ?>" required>
      </div>
      <div class="field">
        <label>Password</label>
        <div class="input-wrap">
          <input type="password" name="password" id="pw" placeholder="Enter your password"
            autocomplete="new-password" required>
          <button type="button" class="pw-toggle" onclick="togglePw()">👁</button>
        </div>
      </div>
      <button type="submit" class="submit-btn">Sign In →</button>
    </form>
    <div class="divider">or</div>
    <div class="bottom-link">Don't have an account? <a href="register.php">Register here</a></div>
    <div class="bottom-link"><a href="forgot_password.php">Forgot Password?</a></div>
  </div>
</div>
<footer><p>&copy; 2024 <span>Ride2School</span> — Safe School Transport</p></footer>
<script>
function selectCard(r){
  document.getElementById('card-driver').classList.toggle('selected',r==='driver');
  document.getElementById('card-parent').classList.toggle('selected',r==='parent');
}
function togglePw(){
  const i=document.getElementById('pw');
  i.type=i.type==='password'?'text':'password';
  event.target.textContent=i.type==='password'?'👁':'🙈';
}
</script>
</body>
</html>

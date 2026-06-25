<?php
session_start();
require_once 'db.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $email    = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);
    $role     = (isset($_POST['role']) && $_POST['role'] === 'driver') ? 'driver' : 'parent';

    if (empty($username)||empty($email)||empty($password)||empty($confirm)) {
        $error = "Please fill in all fields!";
    } elseif (strlen($username) < 4) {
        $error = "Username must be at least 4 characters!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' OR email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username or email already taken!";
        } else {
            $hashed = md5($password);
            $token  = bin2hex(random_bytes(32));

            // is_verified=1 for localhost
            $insert = mysqli_query($conn,
                "INSERT INTO users (username, email, password, role, verify_token, is_verified)
                 VALUES ('$username','$email','$hashed','$role','$token', 1)"
            );

            if ($insert) {
                $new_id = mysqli_insert_id($conn);
                if ($role == 'parent') {
                    mysqli_query($conn,
                        "INSERT INTO parents (user_id, full_name, mobile)
                         VALUES ($new_id, '$username', 'Not provided')"
                    );
                }
                enqueue($conn, $new_id, $role);
                $_SESSION['user_id']  = $new_id;
                $_SESSION['username'] = $username;
                $_SESSION['role']     = $role;
                header("Location: select_city.php");
                exit();
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
$default_role = isset($_GET['role']) ? $_GET['role'] : 'parent';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — Ride2School</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Poppins',sans-serif;background:#f5f0e8;min-height:100vh;display:flex;flex-direction:column;}
  nav{background:#0d3b6e;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:65px;box-shadow:0 2px 12px rgba(0,0,0,0.15);}
  .logo{font-size:22px;font-weight:700;color:#fff;}.logo span{color:#f0c040;}
  .nav-links a{color:#cde4f7;text-decoration:none;margin-left:28px;font-size:14px;font-weight:500;}
  .nav-links a:hover{color:#f0c040;}
  .page-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 20px;}
  .form-card{background:#fff;border-radius:20px;padding:40px 44px;width:100%;max-width:500px;box-shadow:0 8px 40px rgba(13,59,110,0.10);}
  .form-top{text-align:center;margin-bottom:28px;}
  .form-top .icon{font-size:44px;margin-bottom:8px;}
  .form-top h2{font-size:24px;font-weight:700;color:#0d3b6e;margin-bottom:6px;}
  .form-top p{font-size:13px;color:#6b7c8d;}

  /* ROLE */
  .role-section{margin-bottom:22px;}
  .role-label{font-size:13px;font-weight:600;color:#0d3b6e;margin-bottom:10px;display:block;}
  .role-cards{display:flex;gap:12px;}
  .role-card{flex:1;border:2px solid #e0eaf5;border-radius:14px;padding:16px 12px;text-align:center;cursor:pointer;transition:all 0.25s;background:#f9fbff;}
  .role-card:hover{border-color:#1a5fa8;}
  .role-card.active-driver{border-color:#0d3b6e;background:#EBF3FC;}
  .role-card.active-parent{border-color:#1D9E75;background:#E1F5EE;}
  .role-card .r-icon{font-size:30px;margin-bottom:8px;}
  .role-card .r-name{font-size:14px;font-weight:600;color:#0d3b6e;}
  .role-indicator{text-align:center;padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-top:10px;}
  .ind-driver{background:#EBF3FC;color:#0d3b6e;}
  .ind-parent{background:#E1F5EE;color:#085041;}

  /* FIELDS */
  .field{margin-bottom:16px;position:relative;}
  .field label{display:block;font-size:13px;font-weight:500;color:#0d3b6e;margin-bottom:7px;}
  .field input{width:100%;padding:13px 16px;border:2px solid #e0eaf5;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;color:#1a2a3a;background:#f9fbff;outline:none;transition:all 0.25s;}
  .field input:focus{border-color:#1a5fa8;background:#fff;box-shadow:0 0 0 4px rgba(26,95,168,0.08);}
  .field input::placeholder{color:#aab8c8;}
  .hint{font-size:11px;color:#aab8c8;margin-top:4px;}

  /* PASSWORD STRENGTH */
  .strength-bars{display:flex;gap:4px;margin-top:8px;}
  .s-bar{flex:1;height:4px;border-radius:2px;background:#e0eaf5;transition:background 0.3s;}
  .strength-text{font-size:11px;margin-top:4px;font-weight:500;}

  .pw-wrap{position:relative;}
  .pw-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:16px;color:#aab8c8;background:none;border:none;padding:0;line-height:1;}

  .submit-btn{width:100%;padding:14px;background:#0d3b6e;color:#fff;border:none;border-radius:12px;font-family:'Poppins',sans-serif;font-size:16px;font-weight:600;cursor:pointer;transition:all 0.3s;margin-top:8px;}
  .submit-btn:hover{background:#1a5fa8;transform:translateY(-2px);box-shadow:0 6px 18px rgba(13,59,110,0.25);}
  .error-msg{background:#fff0f0;border-left:4px solid #cc2222;color:#cc2222;padding:12px 16px;border-radius:0 10px 10px 0;font-size:13px;margin-bottom:18px;}
  .success-msg{background:#eafaf1;border-left:4px solid #1D9E75;color:#1a7a40;padding:14px 16px;border-radius:0 10px 10px 0;font-size:14px;margin-bottom:18px;font-weight:500;}
  .bottom-link{text-align:center;margin-top:18px;font-size:13px;color:#6b7c8d;}
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
    <a href="login.php">Login</a>
  </div>
</nav>
<div class="page-wrap">
  <div class="form-card">
    <div class="form-top">
      <div class="icon">🚐</div>
      <h2>Create Account</h2>
      <p>Register and become a part of Ride2School</p>
    </div>
    <?php if ($error): ?>
      <div class="error-msg">❌ <?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="success-msg">✅ <?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" autocomplete="off">

      <!-- ROLE -->
      <div class="role-section">
        <span class="role-label">Register as:</span>
        <div class="role-cards">
          <div class="role-card <?php echo $default_role=='driver'?'active-driver':''; ?>"
            id="rcard-driver" onclick="setRole('driver')">
            <div class="r-icon">🚐</div>
            <div class="r-name">Driver</div>
          </div>
          <div class="role-card <?php echo $default_role=='parent'?'active-parent':''; ?>"
            id="rcard-parent" onclick="setRole('parent')">
            <div class="r-icon">👨‍👩‍👧</div>
            <div class="r-name">Parent</div>
          </div>
        </div>
        <div class="role-indicator <?php echo $default_role=='driver'?'ind-driver':'ind-parent'; ?>" id="role-ind">
          <?php echo $default_role=='driver'?'🚐 Registering as Driver':'👨‍👩‍👧 Registering as Parent'; ?>
        </div>
      </div>
      <input type="hidden" name="role" id="role-input" value="<?php echo htmlspecialchars($default_role); ?>">

      <div class="field">
        <label>Username</label>
        <input type="text" name="username" placeholder="At least 4 characters"
          autocomplete="off"
          value="<?php echo isset($_POST['username'])?htmlspecialchars($_POST['username']):''; ?>" required>
        <div class="hint">No spaces allowed</div>
      </div>

      <div class="field">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="yourname@gmail.com"
          autocomplete="off"
          value="<?php echo isset($_POST['email'])?htmlspecialchars($_POST['email']):''; ?>" required>
      </div>

      <div class="field">
        <label>Password</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="pw1" placeholder="Min 6 characters"
            autocomplete="new-password"
            oninput="checkStrength(this.value)" required>
          <button type="button" class="pw-toggle" onclick="togglePw('pw1',this)">👁</button>
        </div>
        <div class="strength-bars">
          <div class="s-bar" id="sb1"></div><div class="s-bar" id="sb2"></div>
          <div class="s-bar" id="sb3"></div><div class="s-bar" id="sb4"></div>
          <div class="s-bar" id="sb5"></div>
        </div>
        <div class="strength-text" id="sw-text"></div>
      </div>

      <div class="field">
        <label>Confirm Password</label>
        <div class="pw-wrap">
          <input type="password" name="confirm_password" id="pw2" placeholder="Re-enter password"
            autocomplete="new-password" required>
          <button type="button" class="pw-toggle" onclick="togglePw('pw2',this)">👁</button>
        </div>
      </div>

      <button type="submit" class="submit-btn">Create Account →</button>
    </form>

    <div class="bottom-link">Already have an account? <a href="login.php">Login here</a></div>
  </div>
</div>
<footer><p>&copy; 2024 <span>Ride2School</span> — Safe School Transport</p></footer>
<script>
function setRole(role) {
  document.getElementById('role-input').value = role;
  document.getElementById('rcard-driver').className = 'role-card'+(role==='driver'?' active-driver':'');
  document.getElementById('rcard-parent').className = 'role-card'+(role==='parent'?' active-parent':'');
  const ind = document.getElementById('role-ind');
  ind.className = 'role-indicator '+(role==='driver'?'ind-driver':'ind-parent');
  ind.textContent = role==='driver'?'🚐 Registering as Driver':'👨‍👩‍👧 Registering as Parent';
}
function togglePw(id,btn){
  const i=document.getElementById(id);
  i.type=i.type==='password'?'text':'password';
  btn.textContent=i.type==='password'?'👁':'🙈';
}
function checkStrength(val){
  let s=0;
  if(val.length>=6)s++;if(val.length>=10)s++;
  if(/[A-Z]/.test(val))s++;if(/[0-9]/.test(val))s++;if(/[^A-Za-z0-9]/.test(val))s++;
  const c=['#cc2222','#e8830a','#f0c040','#1D9E75','#0d3b6e'];
  const l=['Very Weak','Weak','Fair','Strong','Very Strong'];
  for(let i=1;i<=5;i++)document.getElementById('sb'+i).style.background=i<=s?c[s-1]:'#e0eaf5';
  const t=document.getElementById('sw-text');
  t.textContent=s>0?l[s-1]:'';t.style.color=s>0?c[s-1]:'';
}
</script>
</body>
</html>

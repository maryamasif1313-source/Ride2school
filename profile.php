<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

// Get user data
$user_res = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$user     = mysqli_fetch_assoc($user_res);

// Get driver/parent data
$driver = null;
$parent = null;
if ($user['role'] == 'driver') {
    $res    = mysqli_query($conn, "SELECT * FROM drivers WHERE user_id=$user_id");
    $driver = mysqli_fetch_assoc($res);
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim(mysqli_real_escape_string($conn, $_POST['full_name']));
    $phone     = trim(mysqli_real_escape_string($conn, $_POST['phone']));
    $sec_q     = trim(mysqli_real_escape_string($conn, $_POST['security_question']));
    $sec_a     = trim(strtolower(mysqli_real_escape_string($conn, $_POST['security_answer'])));

    mysqli_query($conn,
        "UPDATE users SET full_name='$full_name', phone='$phone',
         security_question='$sec_q', security_answer='$sec_a'
         WHERE id=$user_id"
    );

    // Refresh
    $user_res = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
    $user     = mysqli_fetch_assoc($user_res);
    $success  = "Profile updated successfully!";
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current = md5(trim($_POST['current_password']));
    $new_pw  = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    if ($user['password'] !== $current) {
        $error = "Current password is incorrect!";
    } elseif (strlen($new_pw) < 6) {
        $error = "New password must be at least 6 characters!";
    } elseif ($new_pw !== $confirm) {
        $error = "New passwords do not match!";
    } else {
        $hashed = md5($new_pw);
        mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE id=$user_id");
        $success = "Password changed successfully!";
    }
}

$sec_questions = [
    "What is your mother's maiden name?",
    "What was the name of your first pet?",
    "What city were you born in?",
    "What was your childhood nickname?",
    "What is your oldest sibling's name?",
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — Ride2School</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Poppins',sans-serif;background:#f5f0e8;min-height:100vh;display:flex;flex-direction:column;}
  nav{background:#0d3b6e;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:65px;box-shadow:0 2px 12px rgba(0,0,0,0.15);}
  .logo{font-size:22px;font-weight:700;color:#fff;}.logo span{color:#f0c040;}
  .nav-right{display:flex;align-items:center;gap:16px;}
  .nav-user{color:#f0c040;font-size:14px;font-weight:500;}
  .nav-link{color:#cde4f7;text-decoration:none;font-size:14px;}
  .logout-btn{background:#c0392b;color:#fff;border:none;padding:8px 16px;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;text-decoration:none;}

  .page-wrap{flex:1;padding:36px 20px;max-width:800px;margin:0 auto;width:100%;}

  /* PROFILE HEADER */
  .profile-header{background:linear-gradient(135deg,#0d3b6e,#1a5fa8);border-radius:20px;padding:30px;color:#fff;display:flex;align-items:center;gap:24px;margin-bottom:24px;flex-wrap:wrap;}
  .avatar{width:80px;height:80px;border-radius:50%;background:rgba(240,192,64,0.3);border:3px solid #f0c040;display:flex;align-items:center;justify-content:center;font-size:32px;flex-shrink:0;}
  .profile-info h2{font-size:22px;font-weight:700;margin-bottom:4px;}
  .profile-info p{font-size:14px;color:#b8d4f0;margin-bottom:4px;}
  .role-pill{display:inline-block;background:rgba(240,192,64,0.25);border:1px solid #f0c040;color:#f0c040;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600;}

  /* TABS */
  .tabs{display:flex;gap:0;margin-bottom:20px;background:#fff;border-radius:14px;padding:5px;box-shadow:0 2px 12px rgba(13,59,110,0.08);}
  .tab{flex:1;padding:11px;text-align:center;border-radius:10px;cursor:pointer;font-size:14px;font-weight:500;color:#6b7c8d;transition:all 0.25s;border:none;background:none;font-family:'Poppins',sans-serif;}
  .tab.active{background:#0d3b6e;color:#fff;}
  .tab:hover:not(.active){background:#f0f7ff;color:#0d3b6e;}

  /* CARDS */
  .card{background:#fff;border-radius:18px;padding:28px 30px;box-shadow:0 4px 16px rgba(13,59,110,0.07);margin-bottom:20px;display:none;}
  .card.active{display:block;}
  .card-title{font-size:16px;font-weight:600;color:#0d3b6e;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #f0f7ff;}

  .fields-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;}
  .field{margin-bottom:0;}
  .field.full{grid-column:1/-1;}
  .field label{display:block;font-size:12px;font-weight:600;color:#0d3b6e;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;}
  .field input,.field select{width:100%;padding:12px 14px;border:2px solid #e0eaf5;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;color:#1a2a3a;background:#f9fbff;outline:none;transition:all 0.25s;}
  .field input:focus,.field select:focus{border-color:#1a5fa8;background:#fff;box-shadow:0 0 0 4px rgba(26,95,168,0.08);}
  .field input::placeholder{color:#aab8c8;}
  .field input[readonly]{background:#f0f4f8;color:#6b7c8d;cursor:not-allowed;}

  .pw-wrap{position:relative;}
  .pw-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;color:#aab8c8;}

  .save-btn{background:#0d3b6e;color:#fff;border:none;padding:13px 32px;border-radius:12px;font-family:'Poppins',sans-serif;font-size:15px;font-weight:600;cursor:pointer;transition:all 0.3s;}
  .save-btn:hover{background:#1a5fa8;transform:translateY(-2px);}

  .success-msg{background:#eafaf1;border-left:4px solid #1D9E75;color:#1a7a40;padding:12px 16px;border-radius:0 10px 10px 0;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
  .error-msg{background:#fff0f0;border-left:4px solid #cc2222;color:#cc2222;padding:12px 16px;border-radius:0 10px 10px 0;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px;}

  /* DRIVER STATS */
  .stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:20px;}
  .stat{background:#f0f7ff;border-radius:12px;padding:16px;text-align:center;}
  .stat .s-num{font-size:22px;font-weight:700;color:#0d3b6e;}
  .stat .s-label{font-size:12px;color:#6b7c8d;margin-top:4px;}

  /* STAR RATING display */
  .stars{color:#f0c040;font-size:20px;letter-spacing:2px;}

  @media(max-width:600px){.fields-grid{grid-template-columns:1fr;}.tabs{flex-direction:column;}.profile-header{flex-direction:column;text-align:center;}}
  footer{background:#091f38;color:#6b8aaa;text-align:center;padding:18px;font-size:13px;}
  footer span{color:#f0c040;font-weight:600;}
</style>
</head>
<body>
<nav>
  <div class="logo">Ride<span>2</span>School</div>
  <div class="nav-right">
    <?php if($user['role']=='driver'): ?>
      <a href="driver_dashboard.php" class="nav-link">Dashboard</a>
    <?php else: ?>
      <a href="parent_search.php" class="nav-link">Search</a>
    <?php endif; ?>
    <span class="nav-user">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</nav>

<div class="page-wrap">

  <!-- PROFILE HEADER -->
  <div class="profile-header">
    <div class="avatar"><?php echo $user['role']=='driver'?'🚐':'👨‍👩‍👧'; ?></div>
    <div class="profile-info">
      <h2><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h2>
      <p>@<?php echo htmlspecialchars($user['username']); ?> &bull; <?php echo htmlspecialchars($user['email'] ?? 'No email'); ?></p>
      <span class="role-pill"><?php echo ucfirst($user['role']); ?></span>
      <?php if($user['role']=='driver' && $driver): ?>
        <span class="role-pill" style="margin-left:8px;background:rgba(29,158,117,0.25);border-color:#1D9E75;color:#9FE1CB;">
          <?php echo ucfirst($driver['doc_status']??'pending'); ?>
        </span>
      <?php endif; ?>
    </div>
  </div>

  <!-- TABS -->
  <div class="tabs">
    <button class="tab active" onclick="showTab('info')">👤 Profile Info</button>
    <?php if($user['role']=='driver' && $driver): ?>
    <button class="tab" onclick="showTab('stats')">📊 My Stats</button>
    <?php endif; ?>
    <button class="tab" onclick="showTab('security')">🔒 Security</button>
    <button class="tab" onclick="showTab('password')">🔑 Password</button>
  </div>

  <?php if ($success): ?>
    <div class="success-msg">✅ <?php echo $success; ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="error-msg">❌ <?php echo $error; ?></div>
  <?php endif; ?>

  <!-- PROFILE INFO TAB -->
  <div class="card active" id="tab-info">
    <div class="card-title">Personal Information</div>
    <form method="POST">
      <div class="fields-grid">
        <div class="field">
          <label>Full Name</label>
          <input type="text" name="full_name" placeholder="Your full name"
            value="<?php echo htmlspecialchars($user['full_name']??''); ?>">
        </div>
        <div class="field">
          <label>Phone Number</label>
          <input type="text" name="phone" placeholder="e.g. 0300-1234567"
            value="<?php echo htmlspecialchars($user['phone']??''); ?>">
        </div>
        <div class="field">
          <label>Username</label>
          <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
        </div>
        <div class="field">
          <label>Email</label>
          <input type="text" value="<?php echo htmlspecialchars($user['email']??''); ?>" readonly>
        </div>
        <div class="field">
          <label>Role</label>
          <input type="text" value="<?php echo ucfirst($user['role']); ?>" readonly>
        </div>
        <div class="field">
          <label>City</label>
          <input type="text" value="<?php echo htmlspecialchars($_SESSION['city']??'Not selected'); ?>" readonly>
        </div>
      </div>
      <!-- Hidden security fields to preserve -->
      <input type="hidden" name="security_question" value="<?php echo htmlspecialchars($user['security_question']??''); ?>">
      <input type="hidden" name="security_answer" value="<?php echo htmlspecialchars($user['security_answer']??''); ?>">
      <button type="submit" name="update_profile" class="save-btn">💾 Save Changes</button>
    </form>
  </div>

  <!-- DRIVER STATS TAB -->
  <?php if($user['role']=='driver' && $driver): ?>
  <div class="card" id="tab-stats">
    <div class="card-title">My Driver Statistics</div>
    <div class="stats-row">
      <div class="stat"><div class="s-num"><?php echo $driver['total_seats']; ?></div><div class="s-label">Total Seats</div></div>
      <div class="stat"><div class="s-num"><?php echo $driver['available_seats']; ?></div><div class="s-label">Available</div></div>
      <div class="stat"><div class="s-num">PKR <?php echo number_format($driver['monthly_fare']); ?></div><div class="s-label">Monthly Fare</div></div>
      <div class="stat">
        <div class="s-num"><?php echo number_format($driver['rating']??0, 1); ?></div>
        <div class="s-label">Avg Rating</div>
      </div>
      <div class="stat"><div class="s-num"><?php echo $driver['total_ratings']??0; ?></div><div class="s-label">Total Ratings</div></div>
    </div>
    <?php
    $rating = floatval($driver['rating']??0);
    $stars  = str_repeat('★', round($rating)) . str_repeat('☆', 5-round($rating));
    ?>
    <div style="text-align:center;padding:10px 0;">
      <div class="stars"><?php echo $stars; ?></div>
      <div style="font-size:13px;color:#6b7c8d;margin-top:6px;"><?php echo $driver['total_ratings']??0; ?> reviews</div>
    </div>
    <div style="margin-top:16px;">
      <div style="font-size:13px;font-weight:600;color:#0d3b6e;margin-bottom:8px;">Vehicle</div>
      <div style="font-size:14px;color:#1a2a3a;"><?php echo htmlspecialchars($driver['vehicle_name'].' '.$driver['vehicle_model']); ?> (<?php echo ucfirst($driver['vehicle_type']); ?>)</div>
      <div style="margin-top:12px;font-size:13px;font-weight:600;color:#0d3b6e;margin-bottom:6px;">Areas</div>
      <div style="font-size:13px;color:#6b7c8d;"><?php echo htmlspecialchars($driver['areas']); ?></div>
      <div style="margin-top:12px;font-size:13px;font-weight:600;color:#0d3b6e;margin-bottom:6px;">Schools</div>
      <div style="font-size:13px;color:#6b7c8d;"><?php echo htmlspecialchars($driver['schools']); ?></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- SECURITY TAB -->
  <div class="card" id="tab-security">
    <div class="card-title">Security Question — for Password Reset</div>
    <p style="font-size:13px;color:#6b7c8d;margin-bottom:20px;">This question will be used if you forget your password.</p>
    <form method="POST">
      <div class="fields-grid">
        <div class="field full">
          <label>Security Question</label>
          <select name="security_question">
            <option value="">— Select a question —</option>
            <?php foreach($sec_questions as $q): ?>
              <option value="<?php echo htmlspecialchars($q); ?>"
                <?php echo ($user['security_question']==$q)?'selected':''; ?>>
                <?php echo htmlspecialchars($q); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field full">
          <label>Your Answer</label>
          <input type="text" name="security_answer" placeholder="Type your answer"
            value="<?php echo htmlspecialchars($user['security_answer']??''); ?>" autocomplete="off">
        </div>
      </div>
      <!-- Hidden profile fields to preserve -->
      <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($user['full_name']??''); ?>">
      <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone']??''); ?>">
      <button type="submit" name="update_profile" class="save-btn">💾 Save Security Question</button>
    </form>
  </div>

  <!-- PASSWORD TAB -->
  <div class="card" id="tab-password">
    <div class="card-title">Change Password</div>
    <form method="POST">
      <div class="fields-grid">
        <div class="field full">
          <label>Current Password</label>
          <div class="pw-wrap">
            <input type="password" name="current_password" id="cur_pw" placeholder="Enter current password">
            <button type="button" class="pw-toggle" onclick="togglePw('cur_pw',this)">👁</button>
          </div>
        </div>
        <div class="field">
          <label>New Password</label>
          <div class="pw-wrap">
            <input type="password" name="new_password" id="new_pw" placeholder="Min 6 characters">
            <button type="button" class="pw-toggle" onclick="togglePw('new_pw',this)">👁</button>
          </div>
        </div>
        <div class="field">
          <label>Confirm New Password</label>
          <div class="pw-wrap">
            <input type="password" name="confirm_password" id="cf_pw" placeholder="Re-enter new password">
            <button type="button" class="pw-toggle" onclick="togglePw('cf_pw',this)">👁</button>
          </div>
        </div>
      </div>
      <button type="submit" name="change_password" class="save-btn">🔑 Change Password</button>
    </form>
  </div>

</div>
<footer><p>&copy; 2024 <span>Ride2School</span> — Safe School Transport</p></footer>
<script>
function showTab(name) {
  document.querySelectorAll('.card').forEach(c => c.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active');
  event.target.classList.add('active');
}
function togglePw(id,btn){const i=document.getElementById(id);i.type=i.type==='password'?'text':'password';btn.textContent=i.type==='password'?'👁':'🙈';}
</script>
</body>
</html>

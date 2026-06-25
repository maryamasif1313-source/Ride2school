<?php
session_start();
require_once 'db.php';

$admin_user = 'admin';
$admin_pass = 'admin123';

if (isset($_POST['admin_login'])) {
    if ($_POST['admin_username']==$admin_user && $_POST['admin_password']==$admin_pass) {
        $_SESSION['admin'] = true;
    } else {
        $login_error = "Incorrect credentials!";
    }
}
if (isset($_GET['logout'])) { unset($_SESSION['admin']); header("Location: admin_panel.php"); exit(); }

// Approve/Reject driver
if (isset($_GET['action']) && isset($_GET['driver_id']) && $_SESSION['admin']) {
    $did = (int)$_GET['driver_id'];
    $act = $_GET['action'];
    if ($act=='approve') mysqli_query($conn, "UPDATE drivers SET doc_status='approved' WHERE id=$did");
    elseif ($act=='reject') mysqli_query($conn, "UPDATE drivers SET doc_status='rejected' WHERE id=$did");
    header("Location: admin_panel.php?tab=drivers"); exit();
}

// Mark message as read
if (isset($_GET['read_msg']) && $_SESSION['admin']) {
    $mid = (int)$_GET['read_msg'];
    mysqli_query($conn, "UPDATE messages SET status='read' WHERE id=$mid");
    header("Location: admin_panel.php?tab=messages"); exit();
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'drivers';

// Get data
$drivers  = [];
$messages = [];
$ratings  = [];

if (isset($_SESSION['admin'])) {
    $dr = mysqli_query($conn, "SELECT d.*, u.username, u.email FROM drivers d JOIN users u ON d.user_id=u.id ORDER BY d.registered_at DESC");
    while ($r = mysqli_fetch_assoc($dr)) $drivers[] = $r;

    // Check if subject and status columns exist
    $has_subject = mysqli_num_rows(mysqli_query($conn, "SHOW COLUMNS FROM messages LIKE 'subject'")) > 0;
    $has_status  = mysqli_num_rows(mysqli_query($conn, "SHOW COLUMNS FROM messages LIKE 'status'")) > 0;

    $msg_sql = "SELECT m.*" . ($has_subject?", m.subject":"") . " FROM messages m ORDER BY m.sent_at DESC";
    $mr = mysqli_query($conn, $msg_sql);
    while ($r = mysqli_fetch_assoc($mr)) $messages[] = $r;

    $rt = mysqli_query($conn,
        "SELECT r.*, d.full_name as driver_name, u.username as reviewer
         FROM ratings r
         JOIN drivers d ON r.driver_id=d.id
         JOIN users u ON r.parent_user_id=u.id
         ORDER BY r.created_at DESC"
    );
    while ($r = mysqli_fetch_assoc($rt)) $ratings[] = $r;
}

$total     = count($drivers);
$pending   = count(array_filter($drivers, fn($d)=>($d['doc_status']??'pending')=='pending'));
$approved  = count(array_filter($drivers, fn($d)=>($d['doc_status']??'pending')=='approved'));
$unread_msgs = count(array_filter($messages, fn($m)=>($m['status']??'unread')=='unread'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — Ride2School</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Poppins',sans-serif;background:#f5f0e8;min-height:100vh;display:flex;flex-direction:column;}
  nav{background:#0d3b6e;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:65px;box-shadow:0 2px 12px rgba(0,0,0,0.15);}
  .logo{font-size:22px;font-weight:700;color:#fff;}.logo span{color:#f0c040;}
  .nav-right{display:flex;align-items:center;gap:16px;}
  .nav-badge{background:#f0c040;color:#0d3b6e;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:600;}
  .logout-btn{background:#c0392b;color:#fff;border:none;padding:8px 16px;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;text-decoration:none;}
  .login-wrap{display:flex;align-items:center;justify-content:center;min-height:80vh;}
  .login-card{background:#fff;border-radius:20px;padding:40px;width:100%;max-width:380px;box-shadow:0 8px 40px rgba(13,59,110,0.12);text-align:center;}
  .login-card .icon{font-size:48px;margin-bottom:14px;}
  .login-card h2{font-size:22px;font-weight:700;color:#0d3b6e;margin-bottom:6px;}
  .login-card p{font-size:13px;color:#6b7c8d;margin-bottom:24px;}
  .login-card input{width:100%;padding:12px 16px;border:2px solid #e0eaf5;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;margin-bottom:14px;background:#f9fbff;outline:none;}
  .login-card input:focus{border-color:#1a5fa8;}
  .login-card button{width:100%;padding:13px;background:#0d3b6e;color:#fff;border:none;border-radius:10px;font-family:'Poppins',sans-serif;font-size:15px;font-weight:600;cursor:pointer;}
  .login-card button:hover{background:#1a5fa8;}
  .err{background:#fff0f0;border:1px solid #ffaaaa;color:#cc2222;padding:10px;border-radius:8px;font-size:13px;margin-bottom:14px;}
  .page-wrap{flex:1;padding:30px 24px;max-width:1100px;margin:0 auto;width:100%;}
  .page-title{font-size:24px;font-weight:700;color:#0d3b6e;margin-bottom:24px;}
  .stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:28px;}
  .stat{background:#fff;border-radius:14px;padding:18px;text-align:center;box-shadow:0 4px 16px rgba(13,59,110,0.07);border:2px solid #e8f0f8;}
  .stat-num{font-size:26px;font-weight:700;color:#0d3b6e;}
  .stat-label{font-size:12px;color:#6b7c8d;margin-top:4px;}

  /* TABS */
  .tabs{display:flex;gap:8px;margin-bottom:22px;flex-wrap:wrap;}
  .tab-btn{padding:10px 20px;border-radius:10px;border:2px solid #e0eaf5;background:#fff;font-family:'Poppins',sans-serif;font-size:14px;font-weight:500;cursor:pointer;color:#6b7c8d;text-decoration:none;transition:all 0.2s;position:relative;}
  .tab-btn:hover{border-color:#1a5fa8;color:#0d3b6e;}
  .tab-btn.active{background:#0d3b6e;color:#fff;border-color:#0d3b6e;}
  .tab-badge{position:absolute;top:-6px;right:-6px;background:#cc2222;color:#fff;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;}

  /* DRIVER CARDS */
  .driver-card{background:#fff;border-radius:16px;padding:20px 24px;margin-bottom:14px;box-shadow:0 4px 16px rgba(13,59,110,0.07);border:2px solid #e8f0f8;}
  .driver-top{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;}
  .driver-name{font-size:16px;font-weight:700;color:#0d3b6e;margin-bottom:3px;}
  .driver-meta{font-size:13px;color:#6b7c8d;}
  .status-badge{padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;}
  .s-pending{background:#FEF6E4;color:#BA7517;border:1px solid #f0c040;}
  .s-approved{background:#E1F5EE;color:#085041;border:1px solid #82e0aa;}
  .s-rejected{background:#fff0f0;color:#cc2222;border:1px solid #ffaaaa;}
  .info-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px;}
  .info-item label{font-size:11px;font-weight:600;color:#aab8c8;text-transform:uppercase;display:block;margin-bottom:3px;}
  .info-item .val{font-size:13px;color:#1a2a3a;font-weight:500;}
  .docs-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;}
  .doc-badge{background:#f0f7ff;border:1px solid #b5d4f4;border-radius:8px;padding:6px 12px;font-size:12px;color:#0d3b6e;display:flex;align-items:center;gap:5px;}
  .doc-badge.missing{background:#fff5f5;border-color:#ffaaaa;color:#cc2222;}
  .stars-disp{color:#f0c040;font-size:15px;letter-spacing:1px;}
  .action-row{display:flex;gap:10px;}
  .btn-approve{background:#1D9E75;color:#fff;border:none;padding:9px 20px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;}
  .btn-approve:hover{background:#158a63;}
  .btn-reject{background:#cc2222;color:#fff;border:none;padding:9px 20px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;}
  .btn-reject:hover{background:#aa1a1a;}

  /* MESSAGES */
  .msg-card{background:#fff;border-radius:14px;padding:18px 22px;margin-bottom:12px;box-shadow:0 4px 16px rgba(13,59,110,0.07);border-left:4px solid #b5d4f4;}
  .msg-card.unread{border-left-color:#f0c040;}
  .msg-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px;}
  .msg-sender{font-size:14px;font-weight:600;color:#0d3b6e;}
  .msg-subject{font-size:12px;color:#1a5fa8;background:#f0f7ff;padding:3px 10px;border-radius:6px;}
  .msg-date{font-size:12px;color:#aab8c8;}
  .msg-body{font-size:13px;color:#6b7c8d;line-height:1.6;margin-bottom:10px;}
  .msg-email{font-size:12px;color:#1a5fa8;}
  .read-btn{background:#EBF3FC;color:#0d3b6e;border:none;padding:6px 14px;border-radius:8px;font-family:'Poppins',sans-serif;font-size:12px;font-weight:500;cursor:pointer;text-decoration:none;display:inline-block;}

  /* RATINGS */
  .rating-card{background:#fff;border-radius:14px;padding:16px 20px;margin-bottom:10px;box-shadow:0 4px 16px rgba(13,59,110,0.07);display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;}
  .rc-left{flex:1;min-width:200px;}
  .rc-driver{font-size:14px;font-weight:600;color:#0d3b6e;margin-bottom:3px;}
  .rc-reviewer{font-size:12px;color:#6b7c8d;margin-bottom:8px;}
  .rc-review{font-size:13px;color:#1a2a3a;line-height:1.5;}
  .rc-right{text-align:right;}
  .rc-stars{font-size:22px;color:#f0c040;}
  .rc-date{font-size:11px;color:#aab8c8;margin-top:4px;}

  .empty-state{text-align:center;padding:50px 20px;background:#fff;border-radius:16px;}
  .empty-state .e-icon{font-size:48px;margin-bottom:12px;}
  .empty-state h3{font-size:17px;color:#0d3b6e;font-weight:600;}

  @media(max-width:600px){.info-grid{grid-template-columns:1fr 1fr;}}
  footer{background:#091f38;color:#6b8aaa;text-align:center;padding:16px;font-size:13px;}
  footer span{color:#f0c040;font-weight:600;}
</style>
</head>
<body>
<nav>
  <div class="logo">Ride<span>2</span>School</div>
  <?php if(isset($_SESSION['admin'])): ?>
  <div class="nav-right">
    <span class="nav-badge">🔐 Admin Panel</span>
    <a href="admin_panel.php?logout=1" class="logout-btn">Logout</a>
  </div>
  <?php endif; ?>
</nav>

<?php if (!isset($_SESSION['admin'])): ?>
<div class="login-wrap">
  <div class="login-card">
    <div class="icon">🔐</div>
    <h2>Admin Login</h2>
    <p>Sign in to manage Ride2School</p>
    <?php if(isset($login_error)): ?><div class="err">❌ <?php echo $login_error; ?></div><?php endif; ?>
    <form method="POST">
      <input type="text" name="admin_username" placeholder="Admin Username" autocomplete="off" required>
      <input type="password" name="admin_password" placeholder="Admin Password" required>
      <button type="submit" name="admin_login">Sign In →</button>
    </form>
    <p style="margin-top:14px;font-size:12px;color:#aab8c8;">Default: admin / admin123</p>
  </div>
</div>

<?php else: ?>
<div class="page-wrap">
  <div class="page-title">🛡️ Admin Dashboard</div>

  <!-- STATS -->
  <div class="stats-row">
    <div class="stat"><div class="stat-num"><?php echo $total; ?></div><div class="stat-label">Total Drivers</div></div>
    <div class="stat" style="border-color:#f0c040;"><div class="stat-num" style="color:#BA7517;"><?php echo $pending; ?></div><div class="stat-label">Pending Review</div></div>
    <div class="stat" style="border-color:#82e0aa;"><div class="stat-num" style="color:#085041;"><?php echo $approved; ?></div><div class="stat-label">Approved</div></div>
    <div class="stat" style="border-color:#f0c040;"><div class="stat-num" style="color:#cc2222;"><?php echo $unread_msgs; ?></div><div class="stat-label">Unread Messages</div></div>
    <div class="stat"><div class="stat-num"><?php echo count($ratings); ?></div><div class="stat-label">Total Ratings</div></div>
  </div>

  <!-- TABS -->
  <div class="tabs">
    <a href="admin_panel.php?tab=drivers" class="tab-btn <?php echo $tab=='drivers'?'active':''; ?>">
      🚐 Drivers <?php if($pending>0): ?><span class="tab-badge"><?php echo $pending; ?></span><?php endif; ?>
    </a>
    <a href="admin_panel.php?tab=messages" class="tab-btn <?php echo $tab=='messages'?'active':''; ?>">
      📩 Contact Messages <?php if($unread_msgs>0): ?><span class="tab-badge"><?php echo $unread_msgs; ?></span><?php endif; ?>
    </a>
    <a href="admin_panel.php?tab=ratings" class="tab-btn <?php echo $tab=='ratings'?'active':''; ?>">
      ⭐ Driver Ratings
    </a>
  </div>

  <!-- DRIVERS TAB -->
  <?php if ($tab=='drivers'): ?>
    <?php if(empty($drivers)): ?>
      <div class="empty-state"><div class="e-icon">🚐</div><h3>No drivers registered yet</h3></div>
    <?php else: ?>
      <?php foreach ($drivers as $d): ?>
      <?php $status=$d['doc_status']??'pending'; ?>
      <div class="driver-card">
        <div class="driver-top">
          <div>
            <div class="driver-name"><?php echo htmlspecialchars($d['full_name']); ?></div>
            <div class="driver-meta">@<?php echo htmlspecialchars($d['username']); ?> &bull; <?php echo htmlspecialchars($d['email']); ?> &bull; <?php echo htmlspecialchars($d['mobile']); ?> &bull; <?php echo htmlspecialchars($d['city']); ?></div>
          </div>
          <span class="status-badge s-<?php echo $status; ?>">
            <?php echo $status=='approved'?'✅ Approved':($status=='rejected'?'❌ Rejected':'⏳ Pending'); ?>
          </span>
        </div>
        <div class="info-grid">
          <div class="info-item"><label>CNIC</label><div class="val"><?php echo htmlspecialchars($d['cnic']); ?></div></div>
          <div class="info-item"><label>Vehicle</label><div class="val"><?php echo htmlspecialchars($d['vehicle_name'].' '.$d['vehicle_model']); ?> (<?php echo $d['vehicle_type']; ?>)</div></div>
          <div class="info-item"><label>Fare</label><div class="val">PKR <?php echo number_format($d['monthly_fare']); ?></div></div>
          <div class="info-item"><label>Seats</label><div class="val"><?php echo $d['available_seats']; ?>/<?php echo $d['total_seats']; ?></div></div>
          <div class="info-item"><label>Rating</label><div class="val">
            <span class="stars-disp"><?php $r=round($d['rating']??0);echo str_repeat('★',$r).str_repeat('☆',5-$r); ?></span>
            (<?php echo $d['total_ratings']??0; ?> reviews)
          </div></div>
          <div class="info-item"><label>City</label><div class="val"><?php echo htmlspecialchars($d['city']); ?></div></div>
        </div>
        <div class="docs-row">
          <div class="doc-badge <?php echo empty($d['cnic_front'])?'missing':''; ?>">
            🪪 CNIC Front: <?php echo !empty($d['cnic_front'])?'<a href="'.$d['cnic_front'].'" target="_blank">View</a>':'Not uploaded'; ?>
          </div>
          <div class="doc-badge <?php echo empty($d['cnic_back'])?'missing':''; ?>">
            🪪 CNIC Back: <?php echo !empty($d['cnic_back'])?'<a href="'.$d['cnic_back'].'" target="_blank">View</a>':'Not uploaded'; ?>
          </div>
          <div class="doc-badge <?php echo empty($d['driving_license'])?'missing':''; ?>">
            🚗 License: <?php echo !empty($d['driving_license'])?'<a href="'.$d['driving_license'].'" target="_blank">View</a>':'Not uploaded'; ?>
          </div>
        </div>
        <div class="action-row">
          <?php if($status!='approved'): ?>
            <a href="admin_panel.php?action=approve&driver_id=<?php echo $d['id']; ?>&tab=drivers" class="btn-approve">✅ Approve</a>
          <?php endif; ?>
          <?php if($status!='rejected'): ?>
            <a href="admin_panel.php?action=reject&driver_id=<?php echo $d['id']; ?>&tab=drivers" class="btn-reject">❌ Reject</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>

  <!-- MESSAGES TAB -->
  <?php elseif ($tab=='messages'): ?>
    <?php if(empty($messages)): ?>
      <div class="empty-state"><div class="e-icon">📩</div><h3>No messages yet</h3></div>
    <?php else: ?>
      <?php foreach ($messages as $m): ?>
      <div class="msg-card <?php echo ($m['status']??'unread')=='unread'?'unread':''; ?>">
        <div class="msg-top">
          <div>
            <div class="msg-sender">
              <?php if(($m['status']??'unread')=='unread'): ?>
                <span style="background:#f0c040;color:#0d3b6e;padding:2px 8px;border-radius:5px;font-size:11px;margin-right:6px;">NEW</span>
              <?php endif; ?>
              <?php echo htmlspecialchars($m['sender_name']); ?>
            </div>
            <div class="msg-email">📧 <?php echo htmlspecialchars($m['sender_email']); ?></div>
          </div>
          <div style="text-align:right;">
            <?php if(!empty($m['subject'])): ?>
              <div class="msg-subject"><?php echo htmlspecialchars($m['subject']); ?></div>
            <?php endif; ?>
            <div class="msg-date"><?php echo date('M d, Y h:i A', strtotime($m['sent_at'])); ?></div>
          </div>
        </div>
        <div class="msg-body"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
        <?php if(($m['status']??'unread')=='unread'): ?>
          <a href="admin_panel.php?read_msg=<?php echo $m['id']; ?>&tab=messages" class="read-btn">✓ Mark as Read</a>
        <?php else: ?>
          <span style="font-size:12px;color:#1D9E75;">✓ Read</span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>

  <!-- RATINGS TAB -->
  <?php elseif ($tab=='ratings'): ?>
    <?php if(empty($ratings)): ?>
      <div class="empty-state"><div class="e-icon">⭐</div><h3>No ratings yet</h3></div>
    <?php else: ?>
      <?php foreach ($ratings as $r): ?>
      <div class="rating-card">
        <div class="rc-left">
          <div class="rc-driver">🚐 <?php echo htmlspecialchars($r['driver_name']); ?></div>
          <div class="rc-reviewer">Reviewed by: <?php echo htmlspecialchars($r['reviewer']); ?></div>
          <?php if(!empty($r['review'])): ?>
            <div class="rc-review">"<?php echo htmlspecialchars($r['review']); ?>"</div>
          <?php else: ?>
            <div class="rc-review" style="color:#aab8c8;font-style:italic;">No written review</div>
          <?php endif; ?>
        </div>
        <div class="rc-right">
          <div class="rc-stars"><?php echo str_repeat('★',$r['rating']).str_repeat('☆',5-$r['rating']); ?></div>
          <div style="font-size:14px;font-weight:600;color:#0d3b6e;margin-top:4px;"><?php echo $r['rating']; ?>/5</div>
          <div class="rc-date"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>

</div>
<?php endif; ?>

<footer><p>&copy; 2024 <span>Ride2School</span> — Safe School Transport</p></footer>
</body>
</html>

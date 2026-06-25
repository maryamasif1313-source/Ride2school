<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'driver') {
    header("Location: login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$driver  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM drivers WHERE user_id=$user_id"));

if (!$driver) { header("Location: driver_form.php"); exit(); }

$doc_status = $driver['doc_status'] ?? 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driver Dashboard — Ride2School</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Poppins',sans-serif;background:#f5f0e8;min-height:100vh;display:flex;flex-direction:column;}
  nav{background:#0d3b6e;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:65px;box-shadow:0 2px 12px rgba(0,0,0,0.15);}
  .logo{font-size:22px;font-weight:700;color:#fff;}.logo span{color:#f0c040;}
  .nav-right{display:flex;align-items:center;gap:16px;}
  .nav-user{color:#f0c040;font-size:14px;font-weight:500;}
  .logout-btn{background:#c0392b;color:#fff;border:none;padding:8px 16px;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;text-decoration:none;}

  .page-wrap{flex:1;padding:36px 20px;max-width:920px;margin:0 auto;width:100%;}

  /* STATUS BANNER */
  .status-banner{border-radius:16px;padding:20px 24px;margin-bottom:24px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;}
  .status-banner.pending{background:#FEF6E4;border:2px solid #f0c040;}
  .status-banner.approved{background:#E1F5EE;border:2px solid #1D9E75;}
  .status-banner.rejected{background:#fff0f0;border:2px solid #ffaaaa;}
  .status-icon{font-size:36px;flex-shrink:0;}
  .status-text h3{font-size:16px;font-weight:700;margin-bottom:4px;}
  .status-text p{font-size:13px;line-height:1.6;}
  .pending .status-text h3{color:#BA7517;}
  .pending .status-text p{color:#7A5400;}
  .approved .status-text h3{color:#085041;}
  .approved .status-text p{color:#0F6E56;}
  .rejected .status-text h3{color:#cc2222;}
  .rejected .status-text p{color:#991a1a;}

  /* VERIFIED BADGE */
  .verified-badge{display:inline-flex;align-items:center;gap:6px;background:#0d3b6e;color:#f0c040;padding:6px 16px;border-radius:20px;font-size:13px;font-weight:600;margin-left:10px;}
  .pending-badge{display:inline-flex;align-items:center;gap:6px;background:#FEF6E4;color:#BA7517;padding:6px 16px;border-radius:20px;font-size:13px;font-weight:600;border:1px solid #f0c040;margin-left:10px;}
  .rejected-badge{display:inline-flex;align-items:center;gap:6px;background:#fff0f0;color:#cc2222;padding:6px 16px;border-radius:20px;font-size:13px;font-weight:600;border:1px solid #ffaaaa;margin-left:10px;}

  .welcome-banner{background:linear-gradient(135deg,#0d3b6e,#1a5fa8);border-radius:18px;padding:26px 30px;color:#fff;display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:14px;}
  .welcome-banner h2{font-size:21px;font-weight:700;margin-bottom:4px;display:flex;align-items:center;flex-wrap:wrap;gap:8px;}
  .welcome-banner p{font-size:13px;color:#b8d4f0;}
  .welcome-icon{font-size:50px;}

  .stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:24px;}
  .stat-card{background:#fff;border-radius:14px;padding:18px;text-align:center;box-shadow:0 4px 16px rgba(13,59,110,0.07);border:2px solid #e8f0f8;}
  .stat-card .s-icon{font-size:26px;margin-bottom:6px;}
  .stat-card .s-num{font-size:24px;font-weight:700;color:#0d3b6e;}
  .stat-card .s-label{font-size:12px;color:#6b7c8d;margin-top:2px;}

  .info-card{background:#fff;border-radius:18px;padding:26px 30px;box-shadow:0 4px 16px rgba(13,59,110,0.07);margin-bottom:20px;}
  .info-card-title{font-size:15px;font-weight:600;color:#0d3b6e;margin-bottom:18px;padding-bottom:10px;border-bottom:2px solid #f0f7ff;}
  .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
  .info-item label{font-size:11px;font-weight:600;color:#aab8c8;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:3px;}
  .info-item .val{font-size:14px;font-weight:500;color:#1a2a3a;}
  .tag-list{display:flex;flex-wrap:wrap;gap:6px;margin-top:4px;}
  .tag{background:#f0f7ff;color:#1a5fa8;border-radius:20px;padding:3px 12px;font-size:12px;font-weight:500;border:1px solid #b5d4f4;}

  .docs-card{background:#fff;border-radius:18px;padding:26px 30px;box-shadow:0 4px 16px rgba(13,59,110,0.07);margin-bottom:20px;}
  .doc-row{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid #f0f7ff;flex-wrap:wrap;gap:8px;}
  .doc-row:last-child{border-bottom:none;}
  .doc-name{font-size:14px;font-weight:500;color:#0d3b6e;display:flex;align-items:center;gap:8px;}
  .doc-status-ok{background:#E1F5EE;color:#085041;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;}
  .doc-status-no{background:#fff0f0;color:#cc2222;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;}

  .ds-section{background:#fff;border-radius:18px;padding:26px 30px;box-shadow:0 4px 16px rgba(13,59,110,0.07);}
  .ds-title{font-size:15px;font-weight:600;color:#0d3b6e;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #f0f7ff;}
  .ds-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;}
  .ds-card{border-radius:12px;padding:14px;border:2px solid #e8f0f8;text-align:center;}
  .ds-card .ds-icon{font-size:22px;margin-bottom:6px;}
  .ds-card .ds-name{font-size:13px;font-weight:600;color:#0d3b6e;margin-bottom:3px;}
  .ds-card .ds-desc{font-size:11px;color:#6b7c8d;line-height:1.5;}
  .ll{border-color:#b5d4f4;background:#f0f7ff;}
  .qu{border-color:#c8e6c9;background:#f1f8e9;}
  .st{border-color:#ffe0b2;background:#fff8f0;}

  footer{background:#091f38;color:#6b8aaa;text-align:center;padding:18px;font-size:13px;margin-top:auto;}
  footer span{color:#f0c040;font-weight:600;}
</style>
</head>
<body>
<nav>
  <div class="logo">Ride<span>2</span>School</div>
  <div class="nav-right">
    <a href="profile.php" style="color:#cde4f7;text-decoration:none;font-size:14px;font-weight:500;">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></a>
    <a href="chat.php" style="color:#cde4f7;text-decoration:none;font-size:14px;font-weight:500;">💬 Messages</a>
    <a href="contact.php" style="color:#cde4f7;text-decoration:none;font-size:14px;font-weight:500;">Contact</a>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</nav>

<div class="page-wrap">

  <!-- DOCUMENT STATUS BANNER -->
  <?php if ($doc_status == 'pending'): ?>
  <div class="status-banner pending">
    <div class="status-icon">⏳</div>
    <div class="status-text">
      <h3>Documents Under Review</h3>
      <p>Your CNIC and Driving License are being verified by admin. This usually takes 24 hours. You will be visible to parents once approved.</p>
    </div>
  </div>
  <?php elseif ($doc_status == 'approved'): ?>
  <div class="status-banner approved">
    <div class="status-icon">✅</div>
    <div class="status-text">
      <h3>Documents Verified — You are Active!</h3>
      <p>Your documents have been approved. Parents in <?php echo htmlspecialchars($driver['city']); ?> can now find and book you.</p>
    </div>
  </div>
  <?php elseif ($doc_status == 'rejected'): ?>
  <div class="status-banner rejected">
    <div class="status-icon">❌</div>
    <div class="status-text">
      <h3>Documents Rejected</h3>
      <p>Your documents were not accepted. Please contact admin or re-register with correct documents.</p>
    </div>
  </div>
  <?php endif; ?>

  <!-- WELCOME -->
  <div class="welcome-banner">
    <div>
      <h2>
        Welcome, <?php echo htmlspecialchars($driver['full_name']); ?>!
        <?php if ($doc_status == 'approved'): ?>
          <span class="verified-badge">✅ Verified Driver</span>
        <?php elseif ($doc_status == 'rejected'): ?>
          <span class="rejected-badge">❌ Rejected</span>
        <?php else: ?>
          <span class="pending-badge">⏳ Pending Verification</span>
        <?php endif; ?>
      </h2>
      <p>City: <?php echo htmlspecialchars($driver['city']); ?> &bull; <?php echo ucfirst($driver['vehicle_type']); ?></p>
    </div>
    <div class="welcome-icon">🚐</div>
  </div>

  <!-- STATS -->
  <div class="stats-row">
    <div class="stat-card"><div class="s-icon">💺</div><div class="s-num"><?php echo $driver['total_seats']; ?></div><div class="s-label">Total Seats</div></div>
    <div class="stat-card"><div class="s-icon">✅</div><div class="s-num"><?php echo $driver['available_seats']; ?></div><div class="s-label">Available</div></div>
    <div class="stat-card"><div class="s-icon">💰</div><div class="s-num" style="font-size:16px;">PKR <?php echo number_format($driver['monthly_fare']); ?></div><div class="s-label">Monthly Fare</div></div>
    <div class="stat-card"><div class="s-icon"><?php echo $driver['vehicle_type']=='van'?'🚐':'🛺'; ?></div><div class="s-num" style="font-size:16px;"><?php echo ucfirst($driver['vehicle_type']); ?></div><div class="s-label">Vehicle</div></div>
  </div>

  <!-- DRIVER INFO -->
  <div class="info-card">
    <div class="info-card-title">👤 Your Details</div>
    <div class="info-grid">
      <div class="info-item"><label>Full Name</label><div class="val"><?php echo htmlspecialchars($driver['full_name']); ?></div></div>
      <div class="info-item"><label>CNIC</label><div class="val"><?php echo htmlspecialchars($driver['cnic']); ?></div></div>
      <div class="info-item"><label>Mobile</label><div class="val"><?php echo htmlspecialchars($driver['mobile']); ?></div></div>
      <div class="info-item"><label>Vehicle</label><div class="val"><?php echo htmlspecialchars($driver['vehicle_name'].' '.$driver['vehicle_model']); ?></div></div>
      <div class="info-item">
        <label>Areas Covered</label>
        <div class="tag-list"><?php foreach(explode(',',$driver['areas']) as $a): ?><span class="tag"><?php echo trim(htmlspecialchars($a)); ?></span><?php endforeach; ?></div>
      </div>
      <div class="info-item">
        <label>Schools Covered</label>
        <div class="tag-list"><?php foreach(explode(',',$driver['schools']) as $s): ?><span class="tag"><?php echo trim(htmlspecialchars($s)); ?></span><?php endforeach; ?></div>
      </div>
    </div>
  </div>

  <!-- DOCUMENTS STATUS -->
  <div class="docs-card">
    <div class="info-card-title">🪪 Uploaded Documents</div>
    <div class="doc-row">
      <div class="doc-name">🪪 CNIC Front</div>
      <?php if (!empty($driver['cnic_front'])): ?>
        <span class="doc-status-ok">✅ Uploaded</span>
      <?php else: ?>
        <span class="doc-status-no">❌ Missing</span>
      <?php endif; ?>
    </div>
    <div class="doc-row">
      <div class="doc-name">🪪 CNIC Back</div>
      <?php if (!empty($driver['cnic_back'])): ?>
        <span class="doc-status-ok">✅ Uploaded</span>
      <?php else: ?>
        <span class="doc-status-no">❌ Missing</span>
      <?php endif; ?>
    </div>
    <div class="doc-row">
      <div class="doc-name">🚗 Driving License</div>
      <?php if (!empty($driver['driving_license'])): ?>
        <span class="doc-status-ok">✅ Uploaded</span>
      <?php else: ?>
        <span class="doc-status-no">❌ Missing</span>
      <?php endif; ?>
    </div>
  </div>

  <!-- DS SECTION -->
  <div class="ds-section">
    <div class="ds-title">🧠 Data Structures in This System</div>
    <div class="ds-cards">
      <div class="ds-card ll"><div class="ds-icon">🔗</div><div class="ds-name">Linked List</div><div class="ds-desc">Your profile is a node linked to other drivers</div></div>
      <div class="ds-card qu"><div class="ds-icon">📋</div><div class="ds-name">Queue</div><div class="ds-desc">Registration processed in FIFO order</div></div>
      <div class="ds-card st"><div class="ds-icon">📚</div><div class="ds-name">Stack</div><div class="ds-desc">Every action recorded for history tracking</div></div>
    </div>
  </div>

</div>
<footer><p>&copy; 2024 <span>Ride2School</span> — Safe School Transport</p></footer>
</body>
</html>
<!-- This file already exists, updating navbar messages link -->

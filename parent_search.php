<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php"); exit();
}

$drivers         = [];
$searched        = false;
$booking_success = false;
$booking_error   = '';

// HANDLE BOOKING
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_driver_id'])) {
    $driver_id     = (int)$_POST['book_driver_id'];
    $parent_mobile = trim(mysqli_real_escape_string($conn, $_POST['parent_mobile']));
    $children_num  = (int)$_POST['children_num'];
    $user_id       = $_SESSION['user_id'];
    $dr  = mysqli_query($conn, "SELECT * FROM drivers WHERE id=$driver_id");
    $drv = mysqli_fetch_assoc($dr);
    if (!$drv) {
        $booking_error = "Driver not found!";
    } elseif ($drv['available_seats'] < $children_num) {
        $booking_error = "Not enough seats! Only ".$drv['available_seats']." seats left.";
    } else {
        $new_seats = $drv['available_seats'] - $children_num;
        mysqli_query($conn, "UPDATE drivers SET available_seats=$new_seats WHERE id=$driver_id");
        stackPush($conn, $driver_id, 'booking', ['parent_user_id'=>$user_id,'parent_mobile'=>$parent_mobile,'children'=>$children_num]);
        $_SESSION['last_booking'] = [
            'driver_name'   => $drv['full_name'],
            'driver_mobile' => $drv['mobile'],
            'vehicle'       => $drv['vehicle_name'].' '.$drv['vehicle_model'],
            'vehicle_type'  => $drv['vehicle_type'],
            'fare'          => $drv['monthly_fare'],
            'children'      => $children_num,
            'parent_mobile' => $parent_mobile,
            'seats_left'    => $new_seats
        ];
        $booking_success = true;
    }
}

// HANDLE SEARCH
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['school']) && !isset($_POST['book_driver_id'])) {
    $searched     = true;
    $school       = trim(mysqli_real_escape_string($conn, $_POST['school']));
    $area         = trim(mysqli_real_escape_string($conn, $_POST['area']));
    $children     = (int)$_POST['children'];
    $vehicle_type = $_POST['vehicle_type'];
    $sort_by      = $_POST['sort_by'];
    $city         = isset($_SESSION['city']) ? mysqli_real_escape_string($conn, $_SESSION['city']) : '';

    $query = "SELECT * FROM drivers WHERE doc_status='approved' AND available_seats >= $children";
    if (!empty($city))         $query .= " AND city='$city'";
    if (!empty($school))       $query .= " AND schools LIKE '%$school%'";
    if (!empty($area))         $query .= " AND areas LIKE '%$area%'";
    if ($vehicle_type !== 'any') $query .= " AND vehicle_type='$vehicle_type'";

    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) $drivers[] = $row;

    if ($sort_by == 'fare') bubbleSortByFare($drivers);
    else sortByAvailableSeats($drivers);

    $_SESSION['last_search'] = ['school'=>$school,'area'=>$area,'children'=>$children,'vehicle_type'=>$vehicle_type,'sort_by'=>$sort_by];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Find a Driver — Ride2School</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Poppins',sans-serif;background:#f5f0e8;min-height:100vh;display:flex;flex-direction:column;}
  nav{background:#0d3b6e;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:65px;box-shadow:0 2px 12px rgba(0,0,0,0.15);}
  .logo{font-size:22px;font-weight:700;color:#fff;}.logo span{color:#f0c040;}
  .nav-right{display:flex;align-items:center;gap:16px;}
  .nav-link{color:#cde4f7;text-decoration:none;font-size:14px;font-weight:500;}
  .nav-link:hover{color:#f0c040;}
  .logout-btn{background:#c0392b;color:#fff;border:none;padding:8px 16px;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;text-decoration:none;font-weight:500;}
  .hero-search{background:linear-gradient(135deg,#0d3b6e 0%,#1a5fa8 100%);padding:50px 20px 70px;text-align:center;position:relative;overflow:hidden;}
  .hero-search::before{content:'';position:absolute;width:400px;height:400px;background:rgba(255,255,255,0.04);border-radius:50%;top:-150px;right:-100px;}
  .hero-search h1{font-size:30px;font-weight:800;color:#fff;margin-bottom:6px;position:relative;z-index:1;}
  .hero-search p{font-size:14px;color:#b8d4f0;margin-bottom:6px;position:relative;z-index:1;}
  .city-badge{display:inline-block;background:rgba(240,192,64,0.2);border:1px solid #f0c040;color:#f0c040;padding:5px 16px;border-radius:20px;font-size:13px;font-weight:500;margin-bottom:24px;position:relative;z-index:1;}
  .search-card{background:#fff;border-radius:20px;padding:32px 36px;max-width:800px;margin:0 auto;box-shadow:0 8px 40px rgba(0,0,0,0.18);position:relative;z-index:2;}
  .search-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
  .search-grid.three{grid-template-columns:1fr 1fr 1fr;}
  .field label{display:block;font-size:11px;font-weight:600;color:#0d3b6e;margin-bottom:7px;text-transform:uppercase;letter-spacing:0.5px;}
  .field input,.field select{width:100%;padding:13px 14px;border:2px solid #e0eaf5;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;color:#1a2a3a;transition:border-color 0.2s;background:#f9fbff;outline:none;}
  .field input:focus,.field select:focus{border-color:#1a5fa8;background:#fff;}
  .field input::placeholder{color:#aab8c8;}
  .search-btn{width:100%;padding:15px;background:#0d3b6e;color:#fff;border:none;border-radius:12px;font-family:'Poppins',sans-serif;font-size:16px;font-weight:600;cursor:pointer;transition:all 0.3s;display:flex;align-items:center;justify-content:center;gap:10px;}
  .search-btn:hover{background:#1a5fa8;transform:translateY(-2px);}
  .results-wrap{max-width:920px;margin:36px auto;padding:0 20px;width:100%;}
  .results-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px;}
  .results-count{font-size:17px;font-weight:600;color:#0d3b6e;}
  .results-count span{color:#1a5fa8;}
  .driver-card{background:#fff;border-radius:20px;margin-bottom:20px;box-shadow:0 4px 20px rgba(13,59,110,0.09);border:2px solid #e8f0f8;transition:all 0.3s;overflow:hidden;}
  .driver-card:hover{border-color:#1a5fa8;transform:translateY(-3px);box-shadow:0 10px 32px rgba(13,59,110,0.15);}
  .card-top{display:flex;gap:20px;align-items:flex-start;padding:24px 26px;flex-wrap:wrap;}
  .driver-avatar{width:66px;height:66px;border-radius:16px;background:linear-gradient(135deg,#0d3b6e,#1a5fa8);display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0;position:relative;}
  .avatar-badge{position:absolute;bottom:-6px;right:-6px;background:#1D9E75;color:#fff;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-size:11px;border:2px solid #fff;}
  .driver-info{flex:1;min-width:200px;}
  .driver-name-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:3px;}
  .driver-name{font-size:18px;font-weight:700;color:#0d3b6e;}
  .verified-label{display:inline-flex;align-items:center;gap:4px;background:#E1F5EE;color:#085041;border:1px solid #82e0aa;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:600;}
  .driver-vehicle{font-size:13px;color:#6b7c8d;margin-bottom:10px;}
  .driver-tags{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;}
  .dtag{background:#f0f7ff;color:#1a5fa8;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:500;border:1px solid #b5d4f4;}
  .dtag.area{background:#fff8f0;color:#ba7517;border-color:#f0c040;}
  .dtag.phone{background:#f0fff4;color:#1a7a40;border-color:#82e0aa;}
  .dtag.city{background:#f5f0ff;color:#6b21a8;border-color:#d8b4fe;}
  .driver-meta{display:flex;gap:14px;flex-wrap:wrap;}
  .meta-item{font-size:13px;color:#6b7c8d;}
  .meta-item strong{color:#1a2a3a;}
  .driver-fare{text-align:center;flex-shrink:0;}
  .fare-box{background:linear-gradient(135deg,#0d3b6e,#1a5fa8);border-radius:14px;padding:16px 20px;color:#fff;}
  .fare-amount{font-size:21px;font-weight:800;color:#f0c040;}
  .fare-label{font-size:11px;color:#b8d4f0;margin-top:2px;}
  .seats-badge{display:inline-block;margin-top:10px;border-radius:8px;padding:5px 14px;font-size:12px;font-weight:600;}
  .seats-ok{background:#E1F5EE;color:#085041;}
  .seats-low{background:#fff0f0;color:#cc2222;}
  .book-section{background:#f7fafd;border-top:2px solid #e8f0f8;padding:16px 26px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;}
  .book-label{font-size:13px;font-weight:600;color:#0d3b6e;white-space:nowrap;}
  .book-section input[type="tel"]{flex:1;min-width:160px;padding:10px 14px;border:2px solid #e0eaf5;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;background:#fff;outline:none;}
  .book-section input[type="tel"]:focus{border-color:#1a5fa8;}
  .book-section input[type="number"]{width:80px;padding:10px 14px;border:2px solid #e0eaf5;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;background:#fff;outline:none;}
  .book-btn{background:#f0c040;color:#0d3b6e;border:none;padding:11px 20px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:all 0.25s;white-space:nowrap;}
  .book-btn:hover{background:#e6b020;transform:translateY(-1px);}
  /* ACTION ROW */
  .action-row{background:#f0f7ff;border-top:1px solid #e8f0f8;padding:12px 26px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
  .rating-info{font-size:13px;color:#6b7c8d;display:flex;align-items:center;gap:6px;}
  .stars-disp{color:#f0c040;font-size:15px;letter-spacing:1px;}
  .action-btns{display:flex;gap:10px;}
  .rate-btn{background:#fff;color:#0d3b6e;border:2px solid #0d3b6e;padding:8px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;transition:all 0.2s;}
  .rate-btn:hover{background:#0d3b6e;color:#fff;}
  .msg-btn{background:#1D9E75;color:#fff;border:none;padding:8px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;transition:all 0.2s;}
  .msg-btn:hover{background:#158a63;}
  /* MODAL */
  .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:1000;align-items:center;justify-content:center;}
  .modal-overlay.show{display:flex;}
  .modal{background:#fff;border-radius:24px;padding:38px 34px;max-width:480px;width:90%;text-align:center;animation:popIn 0.4s ease;}
  @keyframes popIn{from{opacity:0;transform:scale(0.8);}to{opacity:1;transform:scale(1);}}
  .modal-icon{font-size:58px;margin-bottom:14px;}
  .modal h2{font-size:22px;font-weight:700;color:#0d3b6e;margin-bottom:8px;}
  .modal p{font-size:13px;color:#6b7c8d;margin-bottom:18px;line-height:1.6;}
  .modal-details{background:#f7fafd;border-radius:14px;padding:16px;margin-bottom:22px;text-align:left;}
  .modal-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #e8f0f8;font-size:13px;}
  .modal-row:last-child{border-bottom:none;}
  .m-label{color:#6b7c8d;font-weight:500;}
  .m-val{color:#0d3b6e;font-weight:600;}
  .modal-close{background:#0d3b6e;color:#fff;border:none;padding:12px 28px;border-radius:12px;font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;cursor:pointer;}
  .modal-close:hover{background:#1a5fa8;}
  .error-banner{background:#fff0f0;border:1.5px solid #ffaaaa;color:#cc2222;padding:14px 20px;border-radius:12px;font-size:13px;margin:16px auto;display:flex;align-items:center;gap:10px;max-width:920px;width:calc(100% - 40px);}
  .no-results{text-align:center;padding:60px 20px;background:#fff;border-radius:18px;box-shadow:0 4px 16px rgba(13,59,110,0.08);}
  .no-results .nr-icon{font-size:52px;margin-bottom:14px;}
  .no-results h3{font-size:19px;color:#0d3b6e;font-weight:600;margin-bottom:8px;}
  .no-results p{font-size:14px;color:#6b7c8d;}
  footer{background:#091f38;color:#6b8aaa;text-align:center;padding:18px;font-size:13px;margin-top:auto;}
  footer span{color:#f0c040;font-weight:600;}
  @media(max-width:600px){.search-grid,.search-grid.three{grid-template-columns:1fr;}.card-top{flex-direction:column;}.driver-fare{width:100%;}.book-section{flex-direction:column;align-items:stretch;}.book-section input[type="number"]{width:100%;}}
</style>
</head>
<body>
<nav>
  <div class="logo">Ride<span>2</span>School</div>
  <div class="nav-right">
    <a href="chat.php" class="nav-link">💬 Messages</a>
    <a href="profile.php" class="nav-link">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></a>
    <a href="contact.php" class="nav-link">Contact</a>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</nav>

<?php if ($booking_success && isset($_SESSION['last_booking'])): $b=$_SESSION['last_booking']; ?>
<div class="modal-overlay show" id="successModal">
  <div class="modal">
    <div style="font-size:24px;margin-bottom:8px;">🎉🎊🎉</div>
    <div class="modal-icon">✅</div>
    <h2>Successfully Booked!</h2>
    <p>Your child's transport is confirmed. The driver will contact you soon.</p>
    <div class="modal-details">
      <div class="modal-row"><span class="m-label">Driver Name</span><span class="m-val"><?php echo htmlspecialchars($b['driver_name']); ?></span></div>
      <div class="modal-row"><span class="m-label">Vehicle</span><span class="m-val"><?php echo htmlspecialchars($b['vehicle']); ?></span></div>
      <div class="modal-row"><span class="m-label">Driver Mobile</span><span class="m-val">📞 <?php echo htmlspecialchars($b['driver_mobile']); ?></span></div>
      <div class="modal-row"><span class="m-label">Your Mobile</span><span class="m-val">📱 <?php echo htmlspecialchars($b['parent_mobile']); ?></span></div>
      <div class="modal-row"><span class="m-label">Children</span><span class="m-val"><?php echo $b['children']; ?></span></div>
      <div class="modal-row"><span class="m-label">Monthly Fare</span><span class="m-val" style="color:#c8a000;">PKR <?php echo number_format($b['fare']); ?> / child</span></div>
      <div class="modal-row"><span class="m-label">Seats Remaining</span><span class="m-val"><?php echo $b['seats_left']; ?></span></div>
    </div>
    <button class="modal-close" onclick="closeModal()">Got It! 👍</button>
  </div>
</div>
<?php unset($_SESSION['last_booking']); endif; ?>

<div class="hero-search">
  <h1>🔍 Find School Transport</h1>
  <p>Search verified & approved drivers only</p>
  <div class="city-badge">📍 <?php echo isset($_SESSION['city'])?htmlspecialchars($_SESSION['city']):'All Cities'; ?></div>
  <div class="search-card">
    <form method="POST" action="parent_search.php">
      <div class="search-grid">
        <div class="field">
          <label>School Name</label>
          <input type="text" name="school" placeholder="e.g. Beaconhouse, LGS"
            value="<?php echo isset($_SESSION['last_search']['school'])?htmlspecialchars($_SESSION['last_search']['school']):''; ?>">
        </div>
        <div class="field">
          <label>Pickup Area</label>
          <input type="text" name="area" placeholder="e.g. Gulberg, Model Town"
            value="<?php echo isset($_SESSION['last_search']['area'])?htmlspecialchars($_SESSION['last_search']['area']):''; ?>">
        </div>
      </div>
      <div class="search-grid three">
        <div class="field">
          <label>Number of Children</label>
          <input type="number" name="children" min="1" max="10" placeholder="e.g. 1"
            value="<?php echo isset($_SESSION['last_search']['children'])?htmlspecialchars($_SESSION['last_search']['children']):'1'; ?>" required>
        </div>
        <div class="field">
          <label>Vehicle Type</label>
          <select name="vehicle_type">
            <option value="any">Any Vehicle</option>
            <option value="van">🚐 Van</option>
            <option value="rickshaw">🛺 Rickshaw</option>
          </select>
        </div>
        <div class="field">
          <label>Sort By</label>
          <select name="sort_by">
            <option value="fare">💰 Lowest Fare</option>
            <option value="seats">💺 Most Seats</option>
          </select>
        </div>
      </div>
      <button type="submit" class="search-btn">🔍 Search Verified Drivers</button>
    </form>
  </div>
</div>

<?php if ($booking_error): ?>
  <div class="error-banner">❌ <?php echo $booking_error; ?></div>
<?php endif; ?>

<?php if ($searched): ?>
<div class="results-wrap">
  <div class="results-header">
    <div class="results-count">
      <?php if(count($drivers)>0): ?>
        Found <span><?php echo count($drivers); ?></span> verified driver<?php echo count($drivers)>1?'s':''; ?>
        in <?php echo isset($_SESSION['city'])?htmlspecialchars($_SESSION['city']):'your area'; ?>
      <?php else: ?>
        No verified drivers found
      <?php endif; ?>
    </div>
  </div>

  <?php if(count($drivers)>0): ?>
    <?php foreach($drivers as $d): ?>
    <div class="driver-card">
      <div class="card-top">
        <div class="driver-avatar">
          <?php echo $d['vehicle_type']=='van'?'🚐':'🛺'; ?>
          <div class="avatar-badge">✓</div>
        </div>
        <div class="driver-info">
          <div class="driver-name-row">
            <div class="driver-name"><?php echo htmlspecialchars($d['full_name']); ?></div>
            <span class="verified-label">✅ Verified</span>
          </div>
          <div class="driver-vehicle"><?php echo htmlspecialchars($d['vehicle_name'].' '.$d['vehicle_model']); ?> &bull; <?php echo ucfirst($d['vehicle_type']); ?></div>
          <div class="driver-tags">
            <?php foreach(explode(',',$d['schools']) as $sc): ?>
              <span class="dtag">🏫 <?php echo trim(htmlspecialchars($sc)); ?></span>
            <?php endforeach; ?>
            <?php foreach(explode(',',$d['areas']) as $ar): ?>
              <span class="dtag area">📍 <?php echo trim(htmlspecialchars($ar)); ?></span>
            <?php endforeach; ?>
            <span class="dtag city">🌆 <?php echo htmlspecialchars($d['city']); ?></span>
            <span class="dtag phone">📞 <?php echo htmlspecialchars($d['mobile']); ?></span>
          </div>
          <div class="driver-meta">
            <div class="meta-item">💺 <strong><?php echo $d['total_seats']; ?></strong> total seats</div>
            <div class="meta-item">🪪 CNIC Verified</div>
            <div class="meta-item">🚗 License Verified</div>
          </div>
        </div>
        <div class="driver-fare">
          <div class="fare-box">
            <div class="fare-amount">PKR <?php echo number_format($d['monthly_fare']); ?></div>
            <div class="fare-label">per child / month</div>
          </div>
          <div class="seats-badge <?php echo $d['available_seats']<=2?'seats-low':'seats-ok'; ?>">
            <?php echo $d['available_seats']; ?> seats available
          </div>
        </div>
      </div>

      <!-- BOOK SECTION -->
      <?php if($d['available_seats']>0): ?>
      <div class="book-section">
        <span class="book-label">📋 Book:</span>
        <form method="POST" action="parent_search.php" style="display:flex;gap:10px;flex:1;flex-wrap:wrap;align-items:center;">
          <input type="hidden" name="book_driver_id" value="<?php echo $d['id']; ?>">
          <input type="tel" name="parent_mobile" placeholder="Your mobile e.g. 0300-1234567" required>
          <input type="number" name="children_num" min="1" max="<?php echo $d['available_seats']; ?>" value="1" required>
          <button type="submit" class="book-btn">✅ Book Now</button>
        </form>
      </div>
      <?php else: ?>
      <div class="book-section" style="background:#fff5f5;">
        <span style="color:#cc2222;font-size:13px;font-weight:600;">❌ Fully booked — no seats available.</span>
      </div>
      <?php endif; ?>

      <!-- ACTION ROW — Rate + Message -->
      <div class="action-row">
        <div class="rating-info">
          <?php if(($d['total_ratings']??0)>0): ?>
            <span class="stars-disp"><?php $r=round($d['rating']??0);echo str_repeat('★',$r).str_repeat('☆',5-$r); ?></span>
            <strong style="color:#0d3b6e;"><?php echo number_format($d['rating']??0,1); ?></strong>
            <span>(<?php echo $d['total_ratings']??0; ?> reviews)</span>
          <?php else: ?>
            <span>⭐ No ratings yet</span>
          <?php endif; ?>
        </div>
        <div class="action-btns">
          <a href="chat.php?driver_id=<?php echo $d['id']; ?>" class="msg-btn">💬 Message Driver</a>
          <a href="rate_driver.php?driver_id=<?php echo $d['id']; ?>" class="rate-btn">⭐ Rate</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

  <?php else: ?>
    <div class="no-results">
      <div class="nr-icon">🔍</div>
      <h3>No verified drivers found</h3>
      <p>Try leaving school/area empty to see all drivers in your city.</p>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<footer><p>&copy; 2024 <span>Ride2School</span> — Safe School Transport</p></footer>
<script>
function closeModal(){document.getElementById('successModal').classList.remove('show');}
</script>
</body>
</html>

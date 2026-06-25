<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php"); exit();
}

$success = '';
$error   = '';
$driver  = null;

$driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;
if ($driver_id) {
    $res    = mysqli_query($conn, "SELECT * FROM drivers WHERE id=$driver_id");
    $driver = mysqli_fetch_assoc($res);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $driver_id    = (int)$_POST['driver_id'];
    $rating       = (int)$_POST['rating'];
    $review       = trim(mysqli_real_escape_string($conn, $_POST['review']));
    $parent_uid   = $_SESSION['user_id'];

    if ($rating < 1 || $rating > 5) {
        $error = "Please select a rating between 1 and 5!";
    } else {
        // Check if already rated
        $check = mysqli_query($conn,
            "SELECT id FROM ratings WHERE driver_id=$driver_id AND parent_user_id=$parent_uid"
        );
        if (mysqli_num_rows($check) > 0) {
            // Update existing rating
            mysqli_query($conn,
                "UPDATE ratings SET rating=$rating, review='$review' WHERE driver_id=$driver_id AND parent_user_id=$parent_uid"
            );
        } else {
            // New rating
            mysqli_query($conn,
                "INSERT INTO ratings (driver_id, parent_user_id, rating, review)
                 VALUES ($driver_id, $parent_uid, $rating, '$review')"
            );
        }

        // Recalculate average rating
        $avg_res = mysqli_query($conn,
            "SELECT AVG(rating) as avg_r, COUNT(*) as total FROM ratings WHERE driver_id=$driver_id"
        );
        $avg_row = mysqli_fetch_assoc($avg_res);
        $avg_r   = round($avg_row['avg_r'], 2);
        $total   = $avg_row['total'];

        mysqli_query($conn,
            "UPDATE drivers SET rating=$avg_r, total_ratings=$total WHERE id=$driver_id"
        );

        $success = "Thank you for your rating!";

        // Reload driver
        $res    = mysqli_query($conn, "SELECT * FROM drivers WHERE id=$driver_id");
        $driver = mysqli_fetch_assoc($res);
    }
}

// Get all reviews for this driver
$reviews = [];
if ($driver_id) {
    $r_res = mysqli_query($conn,
        "SELECT r.*, u.username FROM ratings r
         JOIN users u ON r.parent_user_id = u.id
         WHERE r.driver_id=$driver_id
         ORDER BY r.created_at DESC"
    );
    while ($row = mysqli_fetch_assoc($r_res)) {
        $reviews[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rate Driver — Ride2School</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Poppins',sans-serif;background:#f5f0e8;min-height:100vh;display:flex;flex-direction:column;}
  nav{background:#0d3b6e;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:65px;box-shadow:0 2px 12px rgba(0,0,0,0.15);}
  .logo{font-size:22px;font-weight:700;color:#fff;}.logo span{color:#f0c040;}
  .nav-links a{color:#cde4f7;text-decoration:none;margin-left:28px;font-size:14px;}
  .page-wrap{flex:1;padding:36px 20px;max-width:700px;margin:0 auto;width:100%;}

  /* DRIVER INFO */
  .driver-info-card{background:linear-gradient(135deg,#0d3b6e,#1a5fa8);border-radius:18px;padding:24px 28px;color:#fff;display:flex;align-items:center;gap:18px;margin-bottom:24px;flex-wrap:wrap;}
  .d-avatar{width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0;}
  .d-name{font-size:19px;font-weight:700;margin-bottom:4px;}
  .d-meta{font-size:13px;color:#b8d4f0;}
  .d-rating{margin-left:auto;text-align:center;}
  .d-rating .big-num{font-size:36px;font-weight:700;color:#f0c040;}
  .d-rating .stars{font-size:18px;color:#f0c040;letter-spacing:2px;}
  .d-rating .total{font-size:12px;color:#b8d4f0;margin-top:2px;}

  /* RATING FORM */
  .rate-card{background:#fff;border-radius:18px;padding:28px;box-shadow:0 4px 16px rgba(13,59,110,0.08);margin-bottom:24px;}
  .rate-title{font-size:16px;font-weight:600;color:#0d3b6e;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #f0f7ff;}

  /* STAR SELECTOR */
  .star-selector{display:flex;justify-content:center;gap:8px;margin-bottom:20px;flex-direction:row-reverse;}
  .star-selector input{display:none;}
  .star-selector label{font-size:40px;cursor:pointer;color:#e0eaf5;transition:color 0.15s;line-height:1;}
  .star-selector label:hover,.star-selector label:hover~label,
  .star-selector input:checked~label{color:#f0c040;}
  .star-label{text-align:center;font-size:14px;font-weight:600;color:#0d3b6e;margin-bottom:16px;min-height:22px;}

  .field{margin-bottom:18px;}
  .field label{display:block;font-size:12px;font-weight:600;color:#0d3b6e;margin-bottom:7px;text-transform:uppercase;letter-spacing:0.5px;}
  .field textarea{width:100%;padding:13px 16px;border:2px solid #e0eaf5;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;color:#1a2a3a;background:#f9fbff;outline:none;transition:all 0.25s;resize:vertical;min-height:100px;}
  .field textarea:focus{border-color:#1a5fa8;background:#fff;box-shadow:0 0 0 4px rgba(26,95,168,0.08);}
  .field textarea::placeholder{color:#aab8c8;}

  .submit-btn{width:100%;padding:14px;background:#0d3b6e;color:#fff;border:none;border-radius:12px;font-family:'Poppins',sans-serif;font-size:16px;font-weight:600;cursor:pointer;transition:all 0.3s;}
  .submit-btn:hover{background:#1a5fa8;transform:translateY(-2px);box-shadow:0 8px 20px rgba(13,59,110,0.25);}

  .success-msg{background:#eafaf1;border-left:4px solid #1D9E75;color:#1a7a40;padding:12px 16px;border-radius:0 10px 10px 0;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
  .error-msg{background:#fff0f0;border-left:4px solid #cc2222;color:#cc2222;padding:12px 16px;border-radius:0 10px 10px 0;font-size:13px;margin-bottom:18px;}

  /* REVIEWS */
  .reviews-section{background:#fff;border-radius:18px;padding:28px;box-shadow:0 4px 16px rgba(13,59,110,0.08);}
  .reviews-title{font-size:16px;font-weight:600;color:#0d3b6e;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid #f0f7ff;}
  .review-item{padding:14px 0;border-bottom:1px solid #f0f7ff;}
  .review-item:last-child{border-bottom:none;}
  .review-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;flex-wrap:wrap;gap:8px;}
  .reviewer{font-size:13px;font-weight:600;color:#0d3b6e;}
  .review-stars{color:#f0c040;font-size:16px;letter-spacing:1px;}
  .review-date{font-size:11px;color:#aab8c8;}
  .review-text{font-size:13px;color:#6b7c8d;line-height:1.6;}
  .no-reviews{text-align:center;padding:30px;color:#aab8c8;font-size:14px;}

  footer{background:#091f38;color:#6b8aaa;text-align:center;padding:18px;font-size:13px;}
  footer span{color:#f0c040;font-weight:600;}
</style>
</head>
<body>
<nav>
  <div class="logo">Ride<span>2</span>School</div>
  <div class="nav-links">
    <a href="parent_search.php">← Back to Search</a>
  </div>
</nav>

<div class="page-wrap">

  <?php if ($driver): ?>
  <!-- DRIVER INFO -->
  <div class="driver-info-card">
    <div class="d-avatar"><?php echo $driver['vehicle_type']=='van'?'🚐':'🛺'; ?></div>
    <div>
      <div class="d-name"><?php echo htmlspecialchars($driver['full_name']); ?></div>
      <div class="d-meta"><?php echo htmlspecialchars($driver['vehicle_name'].' '.$driver['vehicle_model']); ?> &bull; <?php echo ucfirst($driver['vehicle_type']); ?></div>
      <div class="d-meta" style="margin-top:4px;">📞 <?php echo htmlspecialchars($driver['mobile']); ?> &bull; 🌆 <?php echo htmlspecialchars($driver['city']); ?></div>
    </div>
    <div class="d-rating">
      <div class="big-num"><?php echo number_format($driver['rating']??0,1); ?></div>
      <div class="stars">
        <?php
        $r = round($driver['rating']??0);
        echo str_repeat('★',$r).str_repeat('☆',5-$r);
        ?>
      </div>
      <div class="total"><?php echo $driver['total_ratings']??0; ?> reviews</div>
    </div>
  </div>

  <!-- RATING FORM -->
  <div class="rate-card">
    <div class="rate-title">⭐ Rate this Driver</div>

    <?php if ($success): ?>
      <div class="success-msg">✅ <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="error-msg">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="driver_id" value="<?php echo $driver['id']; ?>">

      <!-- STAR SELECTOR -->
      <div class="star-selector" id="starSelector">
        <input type="radio" name="rating" id="s5" value="5"><label for="s5" title="Excellent">★</label>
        <input type="radio" name="rating" id="s4" value="4"><label for="s4" title="Good">★</label>
        <input type="radio" name="rating" id="s3" value="3"><label for="s3" title="Average">★</label>
        <input type="radio" name="rating" id="s2" value="2"><label for="s2" title="Poor">★</label>
        <input type="radio" name="rating" id="s1" value="1"><label for="s1" title="Terrible">★</label>
      </div>
      <div class="star-label" id="star-label">Click a star to rate</div>

      <div class="field">
        <label>Your Review (Optional)</label>
        <textarea name="review" placeholder="Share your experience with this driver..."></textarea>
      </div>

      <button type="submit" class="submit-btn">Submit Rating ⭐</button>
    </form>
  </div>

  <!-- REVIEWS LIST -->
  <div class="reviews-section">
    <div class="reviews-title">💬 All Reviews (<?php echo count($reviews); ?>)</div>
    <?php if (empty($reviews)): ?>
      <div class="no-reviews">No reviews yet — be the first to rate!</div>
    <?php else: ?>
      <?php foreach ($reviews as $rev): ?>
      <div class="review-item">
        <div class="review-top">
          <span class="reviewer">👤 <?php echo htmlspecialchars($rev['username']); ?></span>
          <span class="review-stars"><?php echo str_repeat('★',$rev['rating']).str_repeat('☆',5-$rev['rating']); ?></span>
          <span class="review-date"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
        </div>
        <?php if (!empty($rev['review'])): ?>
          <div class="review-text"><?php echo htmlspecialchars($rev['review']); ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <div style="text-align:center;padding:60px 20px;">
    <div style="font-size:52px;margin-bottom:14px;">⚠️</div>
    <h3 style="color:#0d3b6e;font-size:20px;margin-bottom:8px;">Driver not found</h3>
    <a href="parent_search.php" style="color:#1a5fa8;font-weight:500;">← Back to Search</a>
  </div>
  <?php endif; ?>

</div>
<footer><p>&copy; 2024 <span>Ride2School</span> — Safe School Transport</p></footer>
<script>
const labels = ['','😞 Terrible','😕 Poor','😐 Average','😊 Good','🤩 Excellent!'];
document.querySelectorAll('#starSelector input').forEach(input => {
  input.addEventListener('change', function() {
    document.getElementById('star-label').textContent = labels[this.value];
  });
});
</script>
</body>
</html>

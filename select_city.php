<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $_SESSION['city'] = $city;

    if ($_SESSION['role'] == 'driver') {
        $check = mysqli_query($conn, "SELECT id FROM drivers WHERE user_id=".$_SESSION['user_id']);
        if (mysqli_num_rows($check) > 0) {
            header("Location: driver_dashboard.php");
        } else {
            header("Location: driver_form.php");
        }
    } else {
        header("Location: parent_search.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Select Your City — Ride2School</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Poppins',sans-serif;background:#f5f0e8;min-height:100vh;display:flex;flex-direction:column;}
  nav{background:#0d3b6e;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:65px;box-shadow:0 2px 12px rgba(0,0,0,0.15);}
  .logo{font-size:22px;font-weight:700;color:#fff;} .logo span{color:#f0c040;}
  .nav-user{color:#f0c040;font-size:14px;font-weight:500;}

  .page-wrap{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 20px;}

  .top-text{text-align:center;margin-bottom:40px;}
  .top-text .icon{font-size:52px;margin-bottom:14px;}
  .top-text h1{font-size:30px;font-weight:800;color:#0d3b6e;margin-bottom:8px;}
  .top-text p{font-size:15px;color:#6b7c8d;}

  .cities-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:580px;width:100%;margin-bottom:30px;}

  .city-card{
    position:relative;
    border-radius:20px;
    overflow:hidden;
    cursor:pointer;
    border:3px solid transparent;
    transition:all 0.3s ease;
    box-shadow:0 4px 16px rgba(0,0,0,0.08);
  }
  .city-card:hover{transform:translateY(-6px);box-shadow:0 12px 32px rgba(13,59,110,0.18);}
  .city-card.selected{border-color:#f0c040;box-shadow:0 12px 32px rgba(240,192,64,0.35);}
  .city-card input[type="radio"]{position:absolute;opacity:0;width:0;height:0;}

  .city-inner{
    padding:32px 20px;text-align:center;
    background:#fff;
    transition:background 0.3s;
  }
  .city-card.selected .city-inner{background:#EBF3FC;}
  .city-card:hover .city-inner{background:#f7fafd;}

  .city-emoji{font-size:42px;margin-bottom:12px;display:block;}
  .city-name{font-size:20px;font-weight:700;color:#0d3b6e;margin-bottom:4px;}
  .city-desc{font-size:12px;color:#6b7c8d;}
  .city-check{
    position:absolute;top:12px;right:12px;
    width:26px;height:26px;border-radius:50%;
    background:#f0c040;color:#0d3b6e;
    display:none;align-items:center;justify-content:center;
    font-size:14px;font-weight:700;
  }
  .city-card.selected .city-check{display:flex;}

  /* City colors */
  .city-lahore  .city-inner{border-top:4px solid #e74c3c;}
  .city-faisalabad .city-inner{border-top:4px solid #27ae60;}
  .city-okara   .city-inner{border-top:4px solid #2980b9;}
  .city-sahiwal .city-inner{border-top:4px solid #8e44ad;}

  .submit-btn{
    padding:16px 60px;background:#0d3b6e;color:#fff;
    border:none;border-radius:50px;font-family:'Poppins',sans-serif;
    font-size:16px;font-weight:700;cursor:pointer;transition:all 0.3s;
    opacity:0.5;pointer-events:none;
  }
  .submit-btn.active{opacity:1;pointer-events:all;}
  .submit-btn.active:hover{background:#1a5fa8;transform:translateY(-2px);box-shadow:0 8px 24px rgba(13,59,110,0.3);}

  .error-msg{background:#fff0f0;border:1.5px solid #ffaaaa;color:#cc2222;padding:12px 20px;border-radius:10px;font-size:13px;margin-bottom:20px;text-align:center;}

  footer{background:#091f38;color:#6b8aaa;text-align:center;padding:18px;font-size:13px;}
  footer span{color:#f0c040;font-weight:600;}

  @media(max-width:480px){
    .cities-grid{grid-template-columns:1fr 1fr;gap:12px;}
    .city-inner{padding:22px 12px;}
    .city-emoji{font-size:32px;}
    .city-name{font-size:16px;}
  }
</style>
</head>
<body>

<nav>
  <div class="logo">Ride<span>2</span>School</div>
  <span class="nav-user">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
</nav>

<div class="page-wrap">

  <div class="top-text">
    <div class="icon">📍</div>
    <h1>Select Your City</h1>
    <p>We'll show you drivers and routes available in your city only</p>
  </div>

  <form method="POST" action="select_city.php" id="cityForm">

    <div class="cities-grid">

      <!-- LAHORE -->
      <label class="city-card city-lahore" id="card-lahore">
        <input type="radio" name="city" value="Lahore" onchange="selectCity('lahore')">
        <div class="city-check">✓</div>
        <div class="city-inner">
          <span class="city-emoji">🏙️</span>
          <div class="city-name">Lahore</div>
          <div class="city-desc">Punjab's Capital City</div>
        </div>
      </label>

      <!-- FAISALABAD -->
      <label class="city-card city-faisalabad" id="card-faisalabad">
        <input type="radio" name="city" value="Faisalabad" onchange="selectCity('faisalabad')">
        <div class="city-check">✓</div>
        <div class="city-inner">
          <span class="city-emoji">🏭</span>
          <div class="city-name">Faisalabad</div>
          <div class="city-desc">City of Lights</div>
        </div>
      </label>

      <!-- OKARA -->
      <label class="city-card city-okara" id="card-okara">
        <input type="radio" name="city" value="Okara" onchange="selectCity('okara')">
        <div class="city-check">✓</div>
        <div class="city-inner">
          <span class="city-emoji">🌾</span>
          <div class="city-name">Okara</div>
          <div class="city-desc">Agricultural Hub</div>
        </div>
      </label>

      <!-- SAHIWAL -->
      <label class="city-card city-sahiwal" id="card-sahiwal">
        <input type="radio" name="city" value="Sahiwal" onchange="selectCity('sahiwal')">
        <div class="city-check">✓</div>
        <div class="city-inner">
          <span class="city-emoji">🌿</span>
          <div class="city-name">Sahiwal</div>
          <div class="city-desc">Historic Punjab City</div>
        </div>
      </label>

    </div>

    <button type="submit" class="submit-btn" id="submitBtn">
      Continue →
    </button>

  </form>

</div>

<footer><p>&copy; 2024 <span>Ride2School</span> — Safe School Transport</p></footer>

<script>
function selectCity(city) {
  ['lahore','faisalabad','okara','sahiwal'].forEach(c => {
    document.getElementById('card-'+c).classList.remove('selected');
  });
  document.getElementById('card-'+city).classList.add('selected');
  document.getElementById('submitBtn').classList.add('active');
}
</script>
</body>
</html>

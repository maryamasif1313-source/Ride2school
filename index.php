<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ride2School — Safe School Transport</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

  * { margin:0; padding:0; box-sizing:border-box; }

  body {
    font-family: 'Poppins', sans-serif;
    background: #f5f0e8;
    color: #1a2a3a;
    overflow-x: hidden;
  }

  /* ── NAVBAR ── */
  nav {
    background: #0d3b6e;
    padding: 0 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 65px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
  }
  .logo {
    font-size: 22px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 1px;
  }
  .logo span { color: #f0c040; }
  .nav-links a {
    color: #cde4f7;
    text-decoration: none;
    margin-left: 28px;
    font-size: 14px;
    font-weight: 500;
    transition: color 0.2s;
  }
  .nav-links a:hover { color: #f0c040; }

  /* ── HERO SECTION ── */
  .hero {
    background: linear-gradient(135deg, #0d3b6e 0%, #1a5fa8 60%, #2176c7 100%);
    min-height: 88vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 60px 20px;
    position: relative;
    overflow: hidden;
  }

  /* Animated circles background */
  .hero::before {
    content: '';
    position: absolute;
    width: 500px; height: 500px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
    top: -100px; right: -100px;
  }
  .hero::after {
    content: '';
    position: absolute;
    width: 300px; height: 300px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
    bottom: -50px; left: -50px;
  }

  .hero-content { position: relative; z-index: 2; max-width: 800px; }

  .hero-badge {
    display: inline-block;
    background: rgba(240,192,64,0.2);
    border: 1px solid #f0c040;
    color: #f0c040;
    padding: 6px 18px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 20px;
    letter-spacing: 1px;
  }

  .hero h1 {
    font-size: 52px;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.15;
    margin-bottom: 18px;
  }
  .hero h1 span { color: #f0c040; }

  .hero p {
    font-size: 17px;
    color: #b8d4f0;
    margin-bottom: 40px;
    line-height: 1.7;
    max-width: 580px;
    margin-left: auto;
    margin-right: auto;
  }

  /* ── LOGIN BUTTONS ── */
  .btn-group {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 50px;
  }

  .btn-main {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 36px;
    border-radius: 50px;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
  }

  .btn-driver {
    background: #f0c040;
    color: #0d3b6e;
    box-shadow: 0 6px 20px rgba(240,192,64,0.35);
  }
  .btn-driver:hover {
    background: #e6b020;
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(240,192,64,0.45);
  }

  .btn-parent {
    background: transparent;
    color: #ffffff;
    border: 2px solid rgba(255,255,255,0.6);
  }
  .btn-parent:hover {
    background: rgba(255,255,255,0.12);
    border-color: #ffffff;
    transform: translateY(-3px);
  }

  .btn-icon { font-size: 22px; }

  /* ── CAR VISUAL ── */
  .car-visual {
    margin-top: 10px;
    position: relative;
  }

  .road {
    background: #1a2a3a;
    height: 18px;
    border-radius: 9px;
    width: 560px;
    max-width: 90vw;
    margin: 0 auto;
    position: relative;
    overflow: hidden;
  }
  .road::after {
    content: '';
    position: absolute;
    top: 50%;
    left: -100%;
    width: 200%;
    height: 3px;
    background: repeating-linear-gradient(90deg, #f0c040 0px, #f0c040 30px, transparent 30px, transparent 60px);
    transform: translateY(-50%);
    animation: roadMove 1.2s linear infinite;
  }
  @keyframes roadMove { to { left: 0%; } }

  .cars-row {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin-bottom: 8px;
  }

  /* SVG Van */
  .car-svg { filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3)); }

  /* ── STATS BAR ── */
  .stats-bar {
    background: #ffffff;
    padding: 28px 40px;
    display: flex;
    justify-content: center;
    gap: 60px;
    flex-wrap: wrap;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  }
  .stat-item { text-align: center; }
  .stat-number { font-size: 32px; font-weight: 700; color: #0d3b6e; }
  .stat-label { font-size: 13px; color: #6b7c8d; font-weight: 500; margin-top: 2px; }

  /* ── HOW IT WORKS ── */
  .section {
    padding: 70px 40px;
    max-width: 1100px;
    margin: 0 auto;
  }
  .section-title {
    text-align: center;
    font-size: 32px;
    font-weight: 700;
    color: #0d3b6e;
    margin-bottom: 8px;
  }
  .section-sub {
    text-align: center;
    color: #6b7c8d;
    font-size: 15px;
    margin-bottom: 50px;
  }

  .steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 24px;
  }

  .step-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 30px 24px;
    text-align: center;
    border: 2px solid #e8f0f8;
    transition: all 0.3s ease;
    position: relative;
  }
  .step-card:hover {
    border-color: #1a5fa8;
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(26,95,168,0.12);
  }
  .step-num {
    background: #0d3b6e;
    color: white;
    width: 38px; height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    margin: 0 auto 16px;
  }
  .step-icon { font-size: 36px; margin-bottom: 12px; }
  .step-card h3 { font-size: 16px; font-weight: 600; color: #0d3b6e; margin-bottom: 8px; }
  .step-card p { font-size: 13px; color: #6b7c8d; line-height: 1.6; }

  /* ── WHY US ── */
  .why-section {
    background: #0d3b6e;
    padding: 70px 40px;
  }
  .why-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 24px;
    max-width: 1000px;
    margin: 0 auto;
  }
  .why-card {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 14px;
    padding: 26px 20px;
    text-align: center;
    color: white;
    transition: background 0.3s;
  }
  .why-card:hover { background: rgba(255,255,255,0.14); }
  .why-icon { font-size: 34px; margin-bottom: 12px; }
  .why-card h3 { font-size: 15px; font-weight: 600; margin-bottom: 8px; color: #f0c040; }
  .why-card p { font-size: 13px; color: #b8d4f0; line-height: 1.6; }

  /* ── FOOTER ── */
  footer {
    background: #091f38;
    color: #6b8aaa;
    text-align: center;
    padding: 24px;
    font-size: 13px;
  }
  footer span { color: #f0c040; font-weight: 600; }

  /* ── ANIMATIONS ── */
  @keyframes vanDrive {
    0%   { transform: translateX(-120px); opacity:0; }
    15%  { opacity:1; }
    85%  { opacity:1; }
    100% { transform: translateX(120px); opacity:0; }
  }
  .van-anim { animation: vanDrive 4s ease-in-out infinite; }
  .van-anim2 { animation: vanDrive 4s ease-in-out infinite 2s; }

  @keyframes fadeUp {
    from { opacity:0; transform:translateY(30px); }
    to   { opacity:1; transform:translateY(0); }
  }
  .fade-up { animation: fadeUp 0.7s ease forwards; }
  .fade-up-2 { animation: fadeUp 0.7s ease 0.2s forwards; opacity:0; }
  .fade-up-3 { animation: fadeUp 0.7s ease 0.4s forwards; opacity:0; }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav>
  <div class="logo">Ride<span>2</span>School</div>
  <div class="nav-links">
    <a href="index.php">Home</a>
    <a href="register.php">Register</a>
    <a href="login.php">Login</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-content">

    <div class="hero-badge fade-up">🎒 Safe &amp; Reliable School Transport</div>

    <h1 class="fade-up-2">
      Your Child's Safe<br>
      <span>Ride to School</span><br>
      Starts Here
    </h1>

    <p class="fade-up-3">
      Connect with verified drivers in your area. Find the perfect van or
      rickshaw for your child's daily school commute — safe, affordable, and reliable.
    </p>

    <!-- TWO MAIN BUTTONS -->
    <div class="btn-group fade-up-3">
      <a href="register.php?role=driver" class="btn-main btn-driver">
        <span class="btn-icon">🚐</span>
        Login as Driver
      </a>
      <a href="register.php?role=parent" class="btn-main btn-parent">
        <span class="btn-icon">👨‍👩‍👧</span>
        Login as Parent
      </a>
    </div>

    <!-- CAR ANIMATION -->
    <div class="car-visual">
      <div class="cars-row">
        <!-- Van SVG -->
        <svg class="car-svg van-anim" width="110" height="55" viewBox="0 0 110 55">
          <rect x="5" y="18" width="90" height="28" rx="6" fill="#f0c040"/>
          <rect x="10" y="10" width="50" height="20" rx="4" fill="#0d3b6e"/>
          <rect x="14" y="13" width="18" height="13" rx="2" fill="#7ec8f0" opacity="0.85"/>
          <rect x="36" y="13" width="18" height="13" rx="2" fill="#7ec8f0" opacity="0.85"/>
          <circle cx="25" cy="47" r="8" fill="#1a2a3a"/>
          <circle cx="25" cy="47" r="4" fill="#888"/>
          <circle cx="75" cy="47" r="8" fill="#1a2a3a"/>
          <circle cx="75" cy="47" r="4" fill="#888"/>
          <rect x="88" y="25" width="12" height="6" rx="2" fill="#ff4444" opacity="0.8"/>
          <rect x="3"  y="25" width="8"  height="5" rx="2" fill="#ffe066" opacity="0.9"/>
        </svg>

        <!-- Rickshaw SVG -->
        <svg class="car-svg van-anim2" width="90" height="55" viewBox="0 0 90 55">
          <rect x="5" y="20" width="65" height="24" rx="8" fill="#e86a3a"/>
          <rect x="8" y="11" width="38" height="20" rx="5" fill="#0d3b6e"/>
          <rect x="12" y="14" width="14" height="12" rx="2" fill="#7ec8f0" opacity="0.8"/>
          <rect x="30" y="14" width="10" height="12" rx="2" fill="#7ec8f0" opacity="0.8"/>
          <circle cx="20" cy="46" r="8" fill="#1a2a3a"/>
          <circle cx="20" cy="46" r="4" fill="#888"/>
          <circle cx="60" cy="46" r="8" fill="#1a2a3a"/>
          <circle cx="60" cy="46" r="4" fill="#888"/>
          <rect x="68" y="26" width="10" height="5" rx="2" fill="#ff4444" opacity="0.8"/>
          <rect x="2"  y="26" width="7"  height="4" rx="2" fill="#ffe066" opacity="0.9"/>
        </svg>
      </div>
      <div class="road"></div>
    </div>

  </div>
</section>

<!-- STATS -->
<div class="stats-bar">
  <div class="stat-item">
    <div class="stat-number">150+</div>
    <div class="stat-label">Registered Drivers</div>
  </div>
  <div class="stat-item">
    <div class="stat-number">500+</div>
    <div class="stat-label">Happy Families</div>
  </div>
  <div class="stat-item">
    <div class="stat-number">30+</div>
    <div class="stat-label">Schools Covered</div>
  </div>
  <div class="stat-item">
    <div class="stat-number">20+</div>
    <div class="stat-label">Areas Served</div>
  </div>
</div>

<!-- HOW IT WORKS -->
<div class="section">
  <div class="section-title">How It Works</div>
  <div class="section-sub">Simple steps to find the perfect school transport</div>
  <div class="steps-grid">
    <div class="step-card">
      <div class="step-num">1</div>
      <div class="step-icon">📝</div>
      <h3>Register</h3>
      <p>Create your account as a driver or parent in just a few clicks</p>
    </div>
    <div class="step-card">
      <div class="step-num">2</div>
      <div class="step-icon">🔍</div>
      <h3>Search</h3>
      <p>Parents search by school, area, and vehicle type to find matching drivers</p>
    </div>
    <div class="step-card">
      <div class="step-num">3</div>
      <div class="step-icon">✅</div>
      <h3>Connect</h3>
      <p>View driver details, fare, and available seats — then connect directly</p>
    </div>
    <div class="step-card">
      <div class="step-num">4</div>
      <div class="step-icon">🎒</div>
      <h3>Safe Ride</h3>
      <p>Your child travels safely to school every day with a verified driver</p>
    </div>
  </div>
</div>

<!-- WHY US -->
<div class="why-section">
  <div class="section-title" style="color:white; margin-bottom:8px;">Why Choose Ride2School?</div>
  <div class="section-sub" style="color:#b8d4f0; margin-bottom:40px;">We make school transport safe and easy</div>
  <div class="why-grid">
    <div class="why-card">
      <div class="why-icon">🛡️</div>
      <h3>Verified Drivers</h3>
      <p>All drivers are registered with CNIC verification for your child's safety</p>
    </div>
    <div class="why-card">
      <div class="why-icon">📍</div>
      <h3>Area Based Search</h3>
      <p>Find drivers who cover your exact pickup area and school route</p>
    </div>
    <div class="why-card">
      <div class="why-icon">💰</div>
      <h3>Affordable Fares</h3>
      <p>Compare monthly fares from multiple drivers and choose what fits your budget</p>
    </div>
    <div class="why-card">
      <div class="why-icon">🚐</div>
      <h3>Van or Rickshaw</h3>
      <p>Choose between spacious vans or convenient rickshaws as per your preference</p>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer>
  <p>&copy; 2024 <span>Ride2School</span> — Safe School Transport Platform. All rights reserved.</p>
</footer>

</body>
</html>
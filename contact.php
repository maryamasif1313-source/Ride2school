<?php
session_start();
require_once 'db.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name    = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $email   = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $subject = trim(mysqli_real_escape_string($conn, $_POST['subject']));
    $message = trim(mysqli_real_escape_string($conn, $_POST['message']));
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    if (empty($name)||empty($email)||empty($subject)||empty($message)) {
        $error = "Please fill in all fields!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email!";
    } elseif (strlen($message) < 10) {
        $error = "Message must be at least 10 characters!";
    } else {
        // Check if subject column exists, if not add it
        $cols = mysqli_query($conn, "SHOW COLUMNS FROM messages LIKE 'subject'");
        if (mysqli_num_rows($cols) == 0) {
            mysqli_query($conn, "ALTER TABLE messages ADD COLUMN subject VARCHAR(200) DEFAULT NULL");
        }
        $ucols = mysqli_query($conn, "SHOW COLUMNS FROM messages LIKE 'user_id'");
        if (mysqli_num_rows($ucols) == 0) {
            mysqli_query($conn, "ALTER TABLE messages ADD COLUMN user_id INT DEFAULT NULL");
        }
        $scols = mysqli_query($conn, "SHOW COLUMNS FROM messages LIKE 'status'");
        if (mysqli_num_rows($scols) == 0) {
            mysqli_query($conn, "ALTER TABLE messages ADD COLUMN status ENUM('unread','read') DEFAULT 'unread'");
        }

        $insert = mysqli_query($conn,
            "INSERT INTO messages (sender_name, sender_email, subject, message, user_id, status)
             VALUES ('$name','$email','$subject','$message'," . ($user_id?$user_id:'NULL') . ",'unread')"
        );
        if ($insert) {
            $success = "Your message has been sent successfully! Admin will get back to you soon.";
        } else {
            $error = "Something went wrong: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us — Ride2School</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Poppins',sans-serif;background:#f5f0e8;min-height:100vh;display:flex;flex-direction:column;}
  nav{background:#0d3b6e;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:65px;box-shadow:0 2px 12px rgba(0,0,0,0.15);}
  .logo{font-size:22px;font-weight:700;color:#fff;}.logo span{color:#f0c040;}
  .nav-links a{color:#cde4f7;text-decoration:none;margin-left:24px;font-size:14px;font-weight:500;}
  .nav-links a:hover{color:#f0c040;}
  .hero{background:linear-gradient(135deg,#0d3b6e,#1a5fa8);padding:50px 20px;text-align:center;}
  .hero h1{font-size:30px;font-weight:800;color:#fff;margin-bottom:8px;}
  .hero p{font-size:14px;color:#b8d4f0;}
  .page-wrap{flex:1;padding:40px 20px;max-width:980px;margin:0 auto;width:100%;}
  .contact-grid{display:grid;grid-template-columns:1fr 1.7fr;gap:28px;}
  .info-card{background:#fff;border-radius:16px;padding:20px 22px;margin-bottom:14px;box-shadow:0 4px 16px rgba(13,59,110,0.07);}
  .ic-head{display:flex;align-items:center;gap:12px;margin-bottom:8px;}
  .ic-icon{width:38px;height:38px;border-radius:10px;background:#EBF3FC;display:flex;align-items:center;justify-content:center;font-size:17px;}
  .ic-title{font-size:14px;font-weight:600;color:#0d3b6e;}
  .ic-text{font-size:13px;color:#6b7c8d;line-height:1.6;}
  .form-card{background:#fff;border-radius:20px;padding:30px;box-shadow:0 4px 20px rgba(13,59,110,0.09);}
  .form-title{font-size:18px;font-weight:700;color:#0d3b6e;margin-bottom:4px;}
  .form-sub{font-size:13px;color:#6b7c8d;margin-bottom:22px;}
  .field{margin-bottom:16px;}
  .field label{display:block;font-size:12px;font-weight:600;color:#0d3b6e;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;}
  .field input,.field select,.field textarea{width:100%;padding:12px 16px;border:2px solid #e0eaf5;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;color:#1a2a3a;background:#f9fbff;outline:none;transition:border-color 0.2s;}
  .field input:focus,.field select:focus,.field textarea:focus{border-color:#1a5fa8;background:#fff;}
  .field input::placeholder,.field textarea::placeholder{color:#aab8c8;}
  .field textarea{resize:vertical;min-height:120px;}
  .fields-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
  .char-info{font-size:11px;color:#aab8c8;text-align:right;margin-top:4px;}
  .submit-btn{width:100%;padding:14px;background:#0d3b6e;color:#fff;border:none;border-radius:12px;font-family:'Poppins',sans-serif;font-size:15px;font-weight:600;cursor:pointer;transition:all 0.3s;}
  .submit-btn:hover{background:#1a5fa8;transform:translateY(-2px);}
  .success-wrap{text-align:center;padding:30px 20px;}
  .success-wrap .s-icon{font-size:60px;margin-bottom:16px;}
  .success-wrap h3{font-size:20px;font-weight:700;color:#1D9E75;margin-bottom:8px;}
  .success-wrap p{font-size:14px;color:#6b7c8d;margin-bottom:20px;}
  .back-btn{display:inline-block;padding:12px 28px;background:#0d3b6e;color:#fff;border-radius:12px;text-decoration:none;font-size:14px;font-weight:600;}
  .error-msg{background:#fff0f0;border-left:4px solid #cc2222;color:#cc2222;padding:12px 16px;border-radius:0 10px 10px 0;font-size:13px;margin-bottom:16px;}
  @media(max-width:700px){.contact-grid{grid-template-columns:1fr;}.fields-row{grid-template-columns:1fr;}}
  footer{background:#091f38;color:#6b8aaa;text-align:center;padding:18px;font-size:13px;}
  footer span{color:#f0c040;font-weight:600;}
</style>
</head>
<body>
<nav>
  <div class="logo">Ride<span>2</span>School</div>
  <div class="nav-links">
    <a href="index.php">Home</a>
    <?php if(isset($_SESSION['user_id'])): ?>
      <?php if($_SESSION['role']=='driver'): ?>
        <a href="driver_dashboard.php">Dashboard</a>
      <?php else: ?>
        <a href="parent_search.php">Search</a>
      <?php endif; ?>
    <?php else: ?>
      <a href="login.php">Login</a>
    <?php endif; ?>
  </div>
</nav>

<div class="hero">
  <h1>📩 Contact Us</h1>
  <p>Have a question or issue? Send us a message!</p>
</div>

<div class="page-wrap">
  <div class="contact-grid">
    <div>
      <div class="info-card">
        <div class="ic-head"><div class="ic-icon">📧</div><div class="ic-title">Email Support</div></div>
        <div class="ic-text">support@ride2school.com<br>We reply within 24 hours</div>
      </div>
      <div class="info-card">
        <div class="ic-head"><div class="ic-icon">📞</div><div class="ic-title">Phone Support</div></div>
        <div class="ic-text">Mon–Sat, 9am to 6pm</div>
      </div>
      <div class="info-card">
        <div class="ic-head"><div class="ic-icon">📍</div><div class="ic-title">Cities Covered</div></div>
        <div class="ic-text">Lahore • Faisalabad<br>Okara • Sahiwal</div>
      </div>
      <div class="info-card">
        <div class="ic-head"><div class="ic-icon">⏰</div><div class="ic-title">Response Time</div></div>
        <div class="ic-text">We typically respond within 4–8 business hours</div>
      </div>
    </div>

    <div class="form-card">
      <?php if ($success): ?>
      <div class="success-wrap">
        <div class="s-icon">✅</div>
        <h3>Message Sent!</h3>
        <p><?php echo $success; ?></p>
        <a href="<?php echo isset($_SESSION['role'])&&$_SESSION['role']=='driver'?'driver_dashboard.php':'parent_search.php'; ?>" class="back-btn">Go Back →</a>
      </div>
      <?php else: ?>
      <div class="form-title">Send us a Message</div>
      <div class="form-sub">Fill in the form below and we'll get back to you</div>
      <?php if ($error): ?>
        <div class="error-msg">❌ <?php echo $error; ?></div>
      <?php endif; ?>
      <form method="POST" action="contact.php">
        <div class="fields-row">
          <div class="field">
            <label>Full Name</label>
            <input type="text" name="name" placeholder="Your name"
              value="<?php echo isset($_POST['name'])?htmlspecialchars($_POST['name']):(isset($_SESSION['username'])?htmlspecialchars($_SESSION['username']):''); ?>" required>
          </div>
          <div class="field">
            <label>Email</label>
            <input type="email" name="email" placeholder="name@gmail.com"
              value="<?php echo isset($_POST['email'])?htmlspecialchars($_POST['email']):''; ?>" required>
          </div>
        </div>
        <div class="field">
          <label>Subject</label>
          <select name="subject" required>
            <option value="">— Select a topic —</option>
            <option value="Driver Registration Issue" <?php echo (isset($_POST['subject'])&&$_POST['subject']=='Driver Registration Issue')?'selected':''; ?>>Driver Registration Issue</option>
            <option value="Document Verification" <?php echo (isset($_POST['subject'])&&$_POST['subject']=='Document Verification')?'selected':''; ?>>Document Verification</option>
            <option value="Booking Problem" <?php echo (isset($_POST['subject'])&&$_POST['subject']=='Booking Problem')?'selected':''; ?>>Booking Problem</option>
            <option value="Account Issue" <?php echo (isset($_POST['subject'])&&$_POST['subject']=='Account Issue')?'selected':''; ?>>Account Issue</option>
            <option value="Report a Driver" <?php echo (isset($_POST['subject'])&&$_POST['subject']=='Report a Driver')?'selected':''; ?>>Report a Driver</option>
            <option value="General Inquiry" <?php echo (isset($_POST['subject'])&&$_POST['subject']=='General Inquiry')?'selected':''; ?>>General Inquiry</option>
            <option value="Other" <?php echo (isset($_POST['subject'])&&$_POST['subject']=='Other')?'selected':''; ?>>Other</option>
          </select>
        </div>
        <div class="field">
          <label>Message</label>
          <textarea name="message" id="msg-area" placeholder="Describe your issue or question..."
            maxlength="1000" oninput="document.getElementById('char-cnt').textContent=this.value.length"><?php echo isset($_POST['message'])?htmlspecialchars($_POST['message']):''; ?></textarea>
          <div class="char-info"><span id="char-cnt"><?php echo isset($_POST['message'])?strlen($_POST['message']):0; ?></span>/1000</div>
        </div>
        <button type="submit" class="submit-btn">Send Message 📩</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<footer><p>&copy; 2024 <span>Ride2School</span> — Safe School Transport</p></footer>
</body>
</html>

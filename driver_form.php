<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'driver') {
    header("Location: login.php"); exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id         = $_SESSION['user_id'];
    $city            = isset($_SESSION['city']) ? mysqli_real_escape_string($conn, $_SESSION['city']) : 'Lahore';
    $full_name       = trim(mysqli_real_escape_string($conn, $_POST['full_name']));
    $cnic            = trim(mysqli_real_escape_string($conn, $_POST['cnic']));
    $mobile          = trim(mysqli_real_escape_string($conn, $_POST['mobile']));
    $vehicle_name    = trim(mysqli_real_escape_string($conn, $_POST['vehicle_name']));
    $vehicle_model   = trim(mysqli_real_escape_string($conn, $_POST['vehicle_model']));
    $vehicle_type    = $_POST['vehicle_type'];
    $total_seats     = (int)$_POST['total_seats'];
    $available_seats = (int)$_POST['available_seats'];
    $monthly_fare    = (float)$_POST['monthly_fare'];
    $areas           = trim(mysqli_real_escape_string($conn, $_POST['areas']));
    $schools         = trim(mysqli_real_escape_string($conn, $_POST['schools']));

    // ── Allowed file types for each document ──
    $allowed_types = [
        'image/jpeg'       => 'jpg',
        'image/jpg'        => 'jpg',
        'image/png'        => 'png',
        'image/gif'        => 'gif',
        'application/pdf'  => 'pdf',
    ];

    $max_size = 2 * 1024 * 1024; // 2MB

    // Required docs
    $doc_fields = [
        'cnic_front' => 'CNIC Front',
        'cnic_back'  => 'CNIC Back',
        'license'    => 'Driving License',
    ];

    if (empty($full_name)||empty($cnic)||empty($mobile)||empty($vehicle_name)||empty($vehicle_model)||empty($areas)||empty($schools)) {
        $error = "Please fill in all required fields!";
    } elseif ($available_seats > $total_seats) {
        $error = "Available seats cannot be more than total seats!";
    } elseif ($monthly_fare <= 0) {
        $error = "Please enter a valid monthly fare!";
    } else {
        // ── Validate each document ──
        $upload_ok = true;
        foreach ($doc_fields as $field => $label) {
            if (empty($_FILES[$field]['name'])) {
                $error = "$label is required! Please upload it.";
                $upload_ok = false; break;
            }
            // Check real MIME type
            $finfo     = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES[$field]['tmp_name']);
            finfo_close($finfo);

            if (!array_key_exists($mime_type, $allowed_types)) {
                $error = "$label: Only JPG, PNG, GIF, PDF files are allowed! You uploaded a '$mime_type' file — affidavit, Word docs, or other files are NOT accepted.";
                $upload_ok = false; break;
            }
            if ($_FILES[$field]['size'] > $max_size) {
                $error = "$label: File size must be under 2MB!";
                $upload_ok = false; break;
            }
        }

        if ($upload_ok) {
            $check = mysqli_query($conn, "SELECT id FROM drivers WHERE cnic='$cnic'");
            if (mysqli_num_rows($check) > 0) {
                $error = "A driver with this CNIC is already registered!";
            } else {
                // ── Upload files ──
                $upload_dir = 'uploads/drivers/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                $paths = [];
                foreach ($doc_fields as $field => $label) {
                    $finfo     = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $_FILES[$field]['tmp_name']);
                    finfo_close($finfo);
                    $ext   = $allowed_types[$mime_type];
                    $fname = $field . '_' . $user_id . '_' . time() . '.' . $ext;
                    $dest  = $upload_dir . $fname;
                    move_uploaded_file($_FILES[$field]['tmp_name'], $dest);
                    $paths[$field] = mysqli_real_escape_string($conn, $dest);
                }

                $insert = mysqli_query($conn,
                    "INSERT INTO drivers (user_id, full_name, cnic, mobile, vehicle_name, vehicle_model, vehicle_type, total_seats, available_seats, areas, schools, monthly_fare, city, cnic_front, cnic_back, driving_license, doc_status)
                     VALUES ($user_id,'$full_name','$cnic','$mobile','$vehicle_name','$vehicle_model','$vehicle_type',$total_seats,$available_seats,'$areas','$schools',$monthly_fare,'$city','{$paths['cnic_front']}','{$paths['cnic_back']}','{$paths['license']}','pending')"
                );

                if ($insert) {
                    $new_driver_id = mysqli_insert_id($conn);
                    linkedListAddDriver($conn, $new_driver_id);
                    stackPush($conn, $new_driver_id, 'register');
                    header("Location: driver_dashboard.php");
                    exit();
                } else {
                    $error = "Database error: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driver Registration — Ride2School</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Poppins',sans-serif;background:#f5f0e8;min-height:100vh;display:flex;flex-direction:column;}
  nav{background:#0d3b6e;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:65px;box-shadow:0 2px 12px rgba(0,0,0,0.15);}
  .logo{font-size:22px;font-weight:700;color:#fff;}.logo span{color:#f0c040;}
  .nav-user{color:#f0c040;font-size:14px;font-weight:500;}
  .page-wrap{flex:1;padding:40px 20px;display:flex;flex-direction:column;align-items:center;}
  .page-header{text-align:center;margin-bottom:28px;}
  .page-header h1{font-size:26px;font-weight:700;color:#0d3b6e;margin-bottom:6px;}
  .page-header p{font-size:14px;color:#6b7c8d;}
  .city-badge{display:inline-block;background:#EBF3FC;color:#0d3b6e;border-radius:20px;padding:6px 18px;font-size:13px;font-weight:600;margin-bottom:10px;}

  .progress-steps{display:flex;align-items:center;justify-content:center;margin-bottom:32px;}
  .step-item{display:flex;flex-direction:column;align-items:center;gap:6px;}
  .step-circle{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;}
  .s-done{background:#0d3b6e;color:#fff;}
  .s-active{background:#f0c040;color:#0d3b6e;}
  .s-pending{background:#e0eaf5;color:#aab8c8;}
  .step-label{font-size:11px;color:#6b7c8d;}
  .step-line{width:50px;height:2px;background:#e0eaf5;margin-bottom:18px;}
  .step-line.done{background:#0d3b6e;}

  .form-card{background:#fff;border-radius:20px;padding:36px 40px;width:100%;max-width:700px;box-shadow:0 8px 40px rgba(13,59,110,0.10);}
  .section-title{font-size:15px;font-weight:600;color:#0d3b6e;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #f0f7ff;display:flex;align-items:center;gap:8px;}
  .fields-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;}
  .fields-grid.three{grid-template-columns:1fr 1fr 1fr;}
  .field-full{grid-column:1/-1;}
  .field label{display:block;font-size:12px;font-weight:600;color:#0d3b6e;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;}
  .field input,.field select,.field textarea{width:100%;padding:12px 14px;border:2px solid #e0eaf5;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;color:#1a2a3a;background:#f9fbff;transition:border-color 0.2s;}
  .field input:focus,.field select:focus,.field textarea:focus{outline:none;border-color:#1a5fa8;background:#fff;}
  .field input::placeholder,.field textarea::placeholder{color:#aab8c8;}
  .field textarea{resize:vertical;min-height:75px;}
  .hint{font-size:11px;color:#aab8c8;margin-top:4px;}
  .vehicle-cards{display:flex;gap:12px;margin-top:4px;}
  .v-card{flex:1;border:2px solid #e0eaf5;border-radius:12px;padding:14px;text-align:center;cursor:pointer;transition:all 0.25s;background:#f9fbff;}
  .v-card.selected{border-color:#0d3b6e;background:#EBF3FC;}
  .v-card .v-icon{font-size:26px;margin-bottom:5px;}
  .v-card .v-name{font-size:13px;font-weight:600;color:#0d3b6e;}
  .fare-preview{background:linear-gradient(135deg,#0d3b6e,#1a5fa8);border-radius:12px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;margin-top:8px;}
  .fare-label{color:#b8d4f0;font-size:12px;}
  .fare-amount{color:#f0c040;font-size:20px;font-weight:700;}
  .divider-line{border:none;border-top:2px solid #f0f7ff;margin:22px 0;}

  /* UPLOAD SECTION */
  .upload-info{background:#FEF6E4;border:1.5px solid #f0c040;border-radius:12px;padding:14px 18px;margin-bottom:18px;font-size:13px;color:#7A5400;}
  .upload-info strong{display:block;margin-bottom:4px;font-size:14px;}
  .upload-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:6px;}
  .upload-box{border:2px dashed #b5d4f4;border-radius:14px;padding:20px 12px;text-align:center;cursor:pointer;transition:all 0.3s;background:#f9fbff;position:relative;overflow:hidden;}
  .upload-box:hover{border-color:#1a5fa8;background:#EBF3FC;}
  .upload-box.uploaded{border-color:#1D9E75;background:#E1F5EE;border-style:solid;}
  .upload-box.error-box{border-color:#cc2222;background:#fff0f0;}
  .upload-box input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
  .upload-icon{font-size:28px;margin-bottom:8px;}
  .upload-label{font-size:12px;font-weight:600;color:#0d3b6e;margin-bottom:3px;}
  .upload-hint{font-size:11px;color:#aab8c8;}
  .upload-fname{font-size:11px;color:#1D9E75;margin-top:6px;display:none;font-weight:500;}
  .upload-box.uploaded .upload-fname{display:block;}
  .upload-err{font-size:11px;color:#cc2222;margin-top:6px;display:none;}
  .upload-box.error-box .upload-err{display:block;}
  .allowed-types{background:#f0f7ff;border-radius:8px;padding:10px 14px;font-size:12px;color:#1a5fa8;margin-top:14px;}

  .submit-btn{width:100%;padding:15px;background:#0d3b6e;color:#fff;border:none;border-radius:12px;font-family:'Poppins',sans-serif;font-size:16px;font-weight:600;cursor:pointer;transition:all 0.3s;margin-top:16px;}
  .submit-btn:hover{background:#1a5fa8;transform:translateY(-2px);}
  .error-msg{background:#fff0f0;border:1.5px solid #ffaaaa;color:#cc2222;padding:14px 16px;border-radius:10px;font-size:13px;margin-bottom:20px;line-height:1.6;}
  footer{background:#091f38;color:#6b8aaa;text-align:center;padding:18px;font-size:13px;}
  footer span{color:#f0c040;font-weight:600;}
  @media(max-width:600px){.fields-grid,.fields-grid.three,.upload-grid{grid-template-columns:1fr;}.form-card{padding:22px 16px;}}
</style>
</head>
<body>
<nav>
  <div class="logo">Ride<span>2</span>School</div>
  <span class="nav-user">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
</nav>
<div class="page-wrap">
  <div class="page-header">
    <div class="city-badge">📍 City: <?php echo isset($_SESSION['city'])?htmlspecialchars($_SESSION['city']):'Not Selected'; ?></div>
    <h1>🚐 Driver Registration</h1>
    <p>Fill in your details to start accepting students</p>
  </div>

  <div class="progress-steps">
    <div class="step-item"><div class="step-circle s-done">✓</div><div class="step-label">Account</div></div>
    <div class="step-line done"></div>
    <div class="step-item"><div class="step-circle s-done">✓</div><div class="step-label">City</div></div>
    <div class="step-line done"></div>
    <div class="step-item"><div class="step-circle s-active">3</div><div class="step-label">Details</div></div>
    <div class="step-line"></div>
    <div class="step-item"><div class="step-circle s-pending">4</div><div class="step-label">Done</div></div>
  </div>

  <div class="form-card">
    <?php if ($error): ?>
      <div class="error-msg">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="driver_form.php" enctype="multipart/form-data" id="driverForm">

      <div class="section-title">👤 Personal Information</div>
      <div class="fields-grid">
        <div class="field">
          <label>Full Name</label>
          <input type="text" name="full_name" placeholder="e.g. Ahmed Khan"
            value="<?php echo isset($_POST['full_name'])?htmlspecialchars($_POST['full_name']):'';?>" required>
        </div>
        <div class="field">
          <label>CNIC Number</label>
          <input type="text" name="cnic" id="cnic" placeholder="e.g. 35201-1234567-1"
            value="<?php echo isset($_POST['cnic'])?htmlspecialchars($_POST['cnic']):'';?>"
            oninput="validateCNIC()" required>
          <div class="hint" id="cnic-msg"></div>
        </div>
        <div class="field field-full">
          <label>Mobile Number</label>
          <input type="text" name="mobile" id="mobile" placeholder="e.g. 0300-1234567"
            value="<?php echo isset($_POST['mobile'])?htmlspecialchars($_POST['mobile']):'';?>"
            oninput="validateMobile()" required>
          <div class="hint" id="mobile-msg"></div>
        </div>
      </div>

      <hr class="divider-line">
      <div class="section-title">🚗 Vehicle Information</div>
      <div class="field" style="margin-bottom:18px;">
        <label>Vehicle Type</label>
        <div class="vehicle-cards">
          <div class="v-card selected" id="vcard-van" onclick="setVehicle('van')">
            <div class="v-icon">🚐</div><div class="v-name">Van</div>
          </div>
          <div class="v-card" id="vcard-rickshaw" onclick="setVehicle('rickshaw')">
            <div class="v-icon">🛺</div><div class="v-name">Rickshaw</div>
          </div>
        </div>
        <input type="hidden" name="vehicle_type" id="vehicle_type" value="van">
      </div>
      <div class="fields-grid three">
        <div class="field">
          <label>Vehicle Brand</label>
          <input type="text" name="vehicle_name" placeholder="e.g. Toyota"
            value="<?php echo isset($_POST['vehicle_name'])?htmlspecialchars($_POST['vehicle_name']):'';?>" required>
        </div>
        <div class="field">
          <label>Model & Year</label>
          <input type="text" name="vehicle_model" placeholder="e.g. HiAce 2020"
            value="<?php echo isset($_POST['vehicle_model'])?htmlspecialchars($_POST['vehicle_model']):'';?>" required>
        </div>
        <div class="field">
          <label>Total Seats</label>
          <input type="number" name="total_seats" placeholder="e.g. 14" min="1" max="20"
            value="<?php echo isset($_POST['total_seats'])?htmlspecialchars($_POST['total_seats']):'';?>"
            onchange="document.getElementById('available_seats').max=this.value" required>
        </div>
      </div>
      <div class="field" style="margin-bottom:20px;">
        <label>Available Seats</label>
        <input type="number" name="available_seats" id="available_seats" placeholder="e.g. 5" min="0"
          value="<?php echo isset($_POST['available_seats'])?htmlspecialchars($_POST['available_seats']):'';?>" required>
      </div>

      <hr class="divider-line">
      <div class="section-title">📍 Route & Coverage</div>
      <div class="fields-grid">
        <div class="field">
          <label>Areas Covered</label>
          <textarea name="areas" placeholder="e.g. Gulberg, Model Town"><?php echo isset($_POST['areas'])?htmlspecialchars($_POST['areas']):'';?></textarea>
          <div class="hint">💡 Separate with commas</div>
        </div>
        <div class="field">
          <label>Schools Covered</label>
          <textarea name="schools" placeholder="e.g. Beaconhouse, LGS"><?php echo isset($_POST['schools'])?htmlspecialchars($_POST['schools']):'';?></textarea>
          <div class="hint">💡 Separate with commas</div>
        </div>
      </div>

      <hr class="divider-line">
      <div class="section-title">💰 Monthly Fare</div>
      <div class="field">
        <label>Monthly Fare Per Student (PKR)</label>
        <input type="number" name="monthly_fare" id="fare_input" placeholder="e.g. 3500" min="0"
          onchange="updateFare(this.value)"
          value="<?php echo isset($_POST['monthly_fare'])?htmlspecialchars($_POST['monthly_fare']):'';?>" required>
      </div>
      <div class="fare-preview">
        <div class="fare-label">Monthly Fare Per Student</div>
        <div class="fare-amount" id="fare-display">PKR 0</div>
      </div>

      <hr class="divider-line">

      <!-- DOCUMENT UPLOAD -->
      <div class="section-title">🪪 Upload Documents <span style="font-size:12px;color:#cc2222;font-weight:400;">(All 3 Required)</span></div>

      <div class="upload-info">
        <strong>⚠️ Important — Strict Document Rules:</strong>
        Only <strong>JPG, PNG, GIF, or PDF</strong> files accepted.<br>
        Affidavits, Word docs, Excel, or any other files will be <strong>rejected</strong>.<br>
        Max size: <strong>2MB per file</strong>. Upload clear, readable photos/scans.
      </div>

      <div class="upload-grid">
        <div class="upload-box" id="box-cnic-front">
          <input type="file" name="cnic_front" accept=".jpg,.jpeg,.png,.gif,.pdf"
            onchange="validateFile(this,'box-cnic-front','fname-cnic-front','err-cnic-front')">
          <div class="upload-icon">🪪</div>
          <div class="upload-label">CNIC Front</div>
          <div class="upload-hint">Front side of ID card</div>
          <div class="upload-fname" id="fname-cnic-front"></div>
          <div class="upload-err" id="err-cnic-front"></div>
        </div>

        <div class="upload-box" id="box-cnic-back">
          <input type="file" name="cnic_back" accept=".jpg,.jpeg,.png,.gif,.pdf"
            onchange="validateFile(this,'box-cnic-back','fname-cnic-back','err-cnic-back')">
          <div class="upload-icon">🪪</div>
          <div class="upload-label">CNIC Back</div>
          <div class="upload-hint">Back side of ID card</div>
          <div class="upload-fname" id="fname-cnic-back"></div>
          <div class="upload-err" id="err-cnic-back"></div>
        </div>

        <div class="upload-box" id="box-license">
          <input type="file" name="license" accept=".jpg,.jpeg,.png,.gif,.pdf"
            onchange="validateFile(this,'box-license','fname-license','err-license')">
          <div class="upload-icon">🚗</div>
          <div class="upload-label">Driving License</div>
          <div class="upload-hint">Valid driving license only</div>
          <div class="upload-fname" id="fname-license"></div>
          <div class="upload-err" id="err-license"></div>
        </div>
      </div>

      <div class="allowed-types">
        ✅ <strong>Allowed:</strong> JPG, JPEG, PNG, GIF, PDF &nbsp;&nbsp;
        ❌ <strong>NOT Allowed:</strong> DOC, DOCX, XLS, TXT, Affidavit, or any other file type
      </div>

      <button type="submit" class="submit-btn" onclick="return checkAllFiles()">
        ✅ Complete Registration
      </button>
    </form>
  </div>
</div>
<footer><p>&copy; 2024 <span>Ride2School</span> — Safe School Transport</p></footer>

<script>
// Allowed types on frontend too
const ALLOWED_TYPES = ['image/jpeg','image/jpg','image/png','image/gif','application/pdf'];
const ALLOWED_EXT   = ['jpg','jpeg','png','gif','pdf'];
const MAX_SIZE      = 2 * 1024 * 1024; // 2MB

function validateFile(input, boxId, fnameId, errId) {
  const box   = document.getElementById(boxId);
  const fname = document.getElementById(fnameId);
  const err   = document.getElementById(errId);

  box.classList.remove('uploaded','error-box');
  fname.style.display = 'none';
  err.style.display   = 'none';

  if (!input.files || !input.files[0]) return;

  const file = input.files[0];
  const ext  = file.name.split('.').pop().toLowerCase();
  const type = file.type.toLowerCase();

  // Check extension AND mime type
  if (!ALLOWED_EXT.includes(ext) || !ALLOWED_TYPES.includes(type)) {
    box.classList.add('error-box');
    err.textContent = '❌ Invalid file! Only JPG, PNG, GIF, PDF allowed.';
    err.style.display = 'block';
    input.value = '';
    return;
  }

  if (file.size > MAX_SIZE) {
    box.classList.add('error-box');
    err.textContent = '❌ File too large! Max 2MB allowed.';
    err.style.display = 'block';
    input.value = '';
    return;
  }

  // Valid file
  box.classList.add('uploaded');
  fname.textContent = '✓ ' + file.name;
  fname.style.display = 'block';
}

function checkAllFiles() {
  const fields = [
    {id:'box-cnic-front', name:'CNIC Front'},
    {id:'box-cnic-back',  name:'CNIC Back'},
    {id:'box-license',    name:'Driving License'},
  ];
  for (let f of fields) {
    if (!document.getElementById(f.id).classList.contains('uploaded')) {
      alert('Please upload: ' + f.name);
      return false;
    }
  }
  return true;
}

function setVehicle(type) {
  document.getElementById('vehicle_type').value = type;
  document.getElementById('vcard-van').classList.toggle('selected', type==='van');
  document.getElementById('vcard-rickshaw').classList.toggle('selected', type==='rickshaw');
}

function updateFare(val) {
  document.getElementById('fare-display').textContent = 'PKR '+(parseInt(val)||0).toLocaleString();
}

function validateCNIC() {
  const val = document.getElementById('cnic').value;
  const re  = /^\d{5}-\d{7}-\d{1}$/;
  const msg = document.getElementById('cnic-msg');
  msg.style.color = re.test(val) ? '#1D9E75' : '#cc2222';
  msg.textContent = re.test(val) ? '✓ Valid CNIC format' : 'Format: 35201-1234567-1';
}

function validateMobile() {
  const val = document.getElementById('mobile').value;
  const re  = /^03[0-9]{2}-[0-9]{7}$/;
  const msg = document.getElementById('mobile-msg');
  msg.style.color = re.test(val) ? '#1D9E75' : '#cc2222';
  msg.textContent = re.test(val) ? '✓ Valid mobile number' : 'Format: 0300-1234567';
}

const fareVal = document.getElementById('fare_input').value;
if (fareVal) updateFare(fareVal);
</script>
</body>
</html>

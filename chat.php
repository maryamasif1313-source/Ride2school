<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$error   = '';

// ── Create or get conversation (parent initiates) ──
if ($role == 'parent' && isset($_GET['driver_id'])) {
    $driver_id = (int)$_GET['driver_id'];
    // Check if conversation exists
    $conv = mysqli_query($conn,
        "SELECT id FROM conversations WHERE parent_user_id=$user_id AND driver_id=$driver_id"
    );
    if (mysqli_num_rows($conv) == 0) {
        mysqli_query($conn,
            "INSERT INTO conversations (parent_user_id, driver_id) VALUES ($user_id, $driver_id)"
        );
    }
    $conv    = mysqli_query($conn,
        "SELECT id FROM conversations WHERE parent_user_id=$user_id AND driver_id=$driver_id"
    );
    $conv_id = mysqli_fetch_assoc($conv)['id'];
    header("Location: chat.php?conv_id=$conv_id");
    exit();
}

// ── Send message ──
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $conv_id = (int)$_POST['conv_id'];
    $message = trim(mysqli_real_escape_string($conn, $_POST['message']));
    if (!empty($message)) {
        mysqli_query($conn,
            "INSERT INTO chat_messages (conversation_id, sender_user_id, message)
             VALUES ($conv_id, $user_id, '$message')"
        );
        // Mark other person's messages as read
        mysqli_query($conn,
            "UPDATE chat_messages SET is_read=1
             WHERE conversation_id=$conv_id AND sender_user_id != $user_id"
        );
    }
    header("Location: chat.php?conv_id=$conv_id");
    exit();
}

// ── Get current conversation ──
$conv_id = isset($_GET['conv_id']) ? (int)$_GET['conv_id'] : 0;
$conv    = null;
$messages_list = [];
$other_name = '';
$other_avatar = '';

if ($conv_id) {
    // Verify user belongs to this conversation
    if ($role == 'parent') {
        $cv = mysqli_query($conn,
            "SELECT c.*, d.full_name as driver_name, d.vehicle_type, d.id as did
             FROM conversations c
             JOIN drivers d ON c.driver_id = d.id
             WHERE c.id=$conv_id AND c.parent_user_id=$user_id"
        );
    } else {
        // Driver — find their driver record
        $drv = mysqli_query($conn, "SELECT id FROM drivers WHERE user_id=$user_id");
        $drv_row = mysqli_fetch_assoc($drv);
        $did = $drv_row ? $drv_row['id'] : 0;
        $cv  = mysqli_query($conn,
            "SELECT c.*, u.username as parent_name
             FROM conversations c
             JOIN users u ON c.parent_user_id = u.id
             WHERE c.id=$conv_id AND c.driver_id=$did"
        );
    }
    if ($cv && mysqli_num_rows($cv) > 0) {
        $conv = mysqli_fetch_assoc($cv);
        $other_name   = $role=='parent' ? $conv['driver_name'] : $conv['parent_name'];
        $other_avatar = $role=='parent' ? ($conv['vehicle_type']=='van'?'🚐':'🛺') : '👨‍👩‍👧';

        // Get messages
        $msgs = mysqli_query($conn,
            "SELECT m.*, u.username, u.role FROM chat_messages m
             JOIN users u ON m.sender_user_id = u.id
             WHERE m.conversation_id=$conv_id
             ORDER BY m.sent_at ASC"
        );
        while ($r = mysqli_fetch_assoc($msgs)) $messages_list[] = $r;

        // Mark messages as read
        mysqli_query($conn,
            "UPDATE chat_messages SET is_read=1
             WHERE conversation_id=$conv_id AND sender_user_id != $user_id"
        );
    }
}

// ── Get all conversations for sidebar ──
$conversations = [];
if ($role == 'driver') {
    $drv    = mysqli_query($conn, "SELECT id FROM drivers WHERE user_id=$user_id");
    $drv_r  = mysqli_fetch_assoc($drv);
    $did    = $drv_r ? $drv_r['id'] : 0;
    $convs  = mysqli_query($conn,
        "SELECT c.*, u.username as parent_name,
         (SELECT COUNT(*) FROM chat_messages cm WHERE cm.conversation_id=c.id AND cm.sender_user_id!=u2.id AND cm.is_read=0) as unread
         FROM conversations c
         JOIN users u ON c.parent_user_id = u.id
         JOIN users u2 ON u2.id=$user_id
         WHERE c.driver_id=$did
         ORDER BY c.created_at DESC"
    );
} else {
    $convs = mysqli_query($conn,
        "SELECT c.*, d.full_name as driver_name, d.vehicle_type,
         (SELECT COUNT(*) FROM chat_messages cm WHERE cm.conversation_id=c.id AND cm.sender_user_id!=u.id AND cm.is_read=0) as unread
         FROM conversations c
         JOIN drivers d ON c.driver_id = d.id
         JOIN users u ON u.id=$user_id
         WHERE c.parent_user_id=$user_id
         ORDER BY c.created_at DESC"
    );
}
if ($convs) while ($r = mysqli_fetch_assoc($convs)) $conversations[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages — Ride2School</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Poppins',sans-serif;background:#f5f0e8;min-height:100vh;display:flex;flex-direction:column;}
  nav{background:#0d3b6e;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:65px;box-shadow:0 2px 12px rgba(0,0,0,0.15);flex-shrink:0;}
  .logo{font-size:22px;font-weight:700;color:#fff;}.logo span{color:#f0c040;}
  .nav-links a{color:#cde4f7;text-decoration:none;margin-left:20px;font-size:14px;}
  .nav-links a:hover{color:#f0c040;}

  .chat-layout{display:flex;flex:1;height:calc(100vh - 65px);overflow:hidden;}

  /* SIDEBAR */
  .sidebar{width:300px;background:#fff;border-right:2px solid #e8f0f8;display:flex;flex-direction:column;flex-shrink:0;}
  .sidebar-head{padding:18px 20px;border-bottom:2px solid #f0f7ff;background:#0d3b6e;color:#fff;}
  .sidebar-head h3{font-size:15px;font-weight:600;}
  .sidebar-head p{font-size:12px;color:#b8d4f0;margin-top:2px;}
  .conv-list{flex:1;overflow-y:auto;}
  .conv-item{padding:14px 18px;border-bottom:1px solid #f0f7ff;cursor:pointer;transition:background 0.2s;display:flex;align-items:center;gap:12px;}
  .conv-item:hover{background:#f0f7ff;}
  .conv-item.active{background:#EBF3FC;border-left:3px solid #0d3b6e;}
  .conv-avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#0d3b6e,#1a5fa8);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
  .conv-info{flex:1;min-width:0;}
  .conv-name{font-size:14px;font-weight:600;color:#0d3b6e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  .conv-last{font-size:12px;color:#aab8c8;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  .unread-badge{background:#f0c040;color:#0d3b6e;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;}
  .no-convs{text-align:center;padding:40px 20px;color:#aab8c8;font-size:13px;}

  /* CHAT AREA */
  .chat-area{flex:1;display:flex;flex-direction:column;overflow:hidden;}
  .chat-header{padding:14px 24px;background:#fff;border-bottom:2px solid #e8f0f8;display:flex;align-items:center;gap:14px;flex-shrink:0;}
  .ch-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#0d3b6e,#1a5fa8);display:flex;align-items:center;justify-content:center;font-size:22px;}
  .ch-name{font-size:15px;font-weight:700;color:#0d3b6e;}
  .ch-status{font-size:12px;color:#1D9E75;margin-top:2px;}

  .messages-wrap{flex:1;overflow-y:auto;padding:20px 24px;display:flex;flex-direction:column;gap:12px;background:#f5f0e8;}

  .msg-row{display:flex;align-items:flex-end;gap:8px;}
  .msg-row.mine{flex-direction:row-reverse;}
  .msg-avatar{width:32px;height:32px;border-radius:50%;background:#0d3b6e;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;color:#fff;}
  .msg-bubble{max-width:65%;padding:12px 16px;border-radius:18px;font-size:14px;line-height:1.5;position:relative;}
  .msg-row.theirs .msg-bubble{background:#fff;color:#1a2a3a;border-bottom-left-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,0.07);}
  .msg-row.mine .msg-bubble{background:#0d3b6e;color:#fff;border-bottom-right-radius:4px;}
  .msg-time{font-size:10px;margin-top:5px;opacity:0.7;}
  .msg-row.mine .msg-time{text-align:right;}

  .empty-chat{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#aab8c8;text-align:center;padding:40px;}
  .empty-chat .e-icon{font-size:52px;margin-bottom:14px;}
  .empty-chat h3{font-size:18px;color:#6b7c8d;margin-bottom:6px;}

  /* MESSAGE INPUT */
  .msg-input-wrap{padding:16px 24px;background:#fff;border-top:2px solid #e8f0f8;display:flex;gap:12px;align-items:flex-end;flex-shrink:0;}
  .msg-input{flex:1;padding:12px 18px;border:2px solid #e0eaf5;border-radius:24px;font-family:'Poppins',sans-serif;font-size:14px;color:#1a2a3a;background:#f9fbff;outline:none;resize:none;max-height:120px;overflow-y:auto;transition:border-color 0.2s;}
  .msg-input:focus{border-color:#1a5fa8;background:#fff;}
  .msg-input::placeholder{color:#aab8c8;}
  .send-btn{width:46px;height:46px;border-radius:50%;background:#0d3b6e;color:#fff;border:none;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;transition:all 0.25s;flex-shrink:0;}
  .send-btn:hover{background:#1a5fa8;transform:scale(1.05);}

  .no-conv-selected{flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;color:#aab8c8;text-align:center;}
  .no-conv-selected .nc-icon{font-size:56px;margin-bottom:16px;}
  .no-conv-selected h3{font-size:18px;color:#6b7c8d;margin-bottom:6px;}
  .no-conv-selected p{font-size:13px;}

  @media(max-width:600px){.sidebar{width:100%;display:<?php echo $conv_id?'none':'flex'; ?>;}.chat-layout{flex-direction:column;}}

  footer{background:#091f38;color:#6b8aaa;text-align:center;padding:14px;font-size:13px;flex-shrink:0;}
  footer span{color:#f0c040;font-weight:600;}
</style>
</head>
<body>
<nav>
  <div class="logo">Ride<span>2</span>School</div>
  <div class="nav-links">
    <?php if($role=='driver'): ?>
      <a href="driver_dashboard.php">Dashboard</a>
    <?php else: ?>
      <a href="parent_search.php">Search</a>
    <?php endif; ?>
    <a href="profile.php">Profile</a>
    <a href="logout.php" style="background:#c0392b;padding:6px 14px;border-radius:8px;color:#fff;">Logout</a>
  </div>
</nav>

<div class="chat-layout">

  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sidebar-head">
      <h3>💬 Messages</h3>
      <p><?php echo ucfirst($role); ?> — <?php echo htmlspecialchars($_SESSION['username']); ?></p>
    </div>
    <div class="conv-list">
      <?php if (empty($conversations)): ?>
        <div class="no-convs">
          <div style="font-size:36px;margin-bottom:10px;">💬</div>
          <?php if ($role=='parent'): ?>
            <p>No conversations yet.<br>Go to <a href="parent_search.php" style="color:#1a5fa8;">Search</a> and message a driver!</p>
          <?php else: ?>
            <p>No messages yet.<br>Parents will message you here.</p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <?php foreach ($conversations as $c): ?>
          <div class="conv-item <?php echo $conv_id==$c['id']?'active':''; ?>"
            onclick="location.href='chat.php?conv_id=<?php echo $c['id']; ?>'">
            <div class="conv-avatar">
              <?php echo $role=='parent'?($c['vehicle_type']=='van'?'🚐':'🛺'):'👨‍👩‍👧'; ?>
            </div>
            <div class="conv-info">
              <div class="conv-name"><?php echo htmlspecialchars($role=='parent'?$c['driver_name']:$c['parent_name']); ?></div>
              <div class="conv-last">Click to view messages</div>
            </div>
            <?php if ($c['unread'] > 0): ?>
              <div class="unread-badge"><?php echo $c['unread']; ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- CHAT AREA -->
  <div class="chat-area">
    <?php if ($conv && $conv_id): ?>

      <!-- CHAT HEADER -->
      <div class="chat-header">
        <div class="ch-avatar"><?php echo $other_avatar; ?></div>
        <div>
          <div class="ch-name"><?php echo htmlspecialchars($other_name); ?></div>
          <div class="ch-status">● Online</div>
        </div>
        <div style="margin-left:auto;">
          <a href="chat.php" style="color:#6b7c8d;font-size:13px;text-decoration:none;">← All Chats</a>
        </div>
      </div>

      <!-- MESSAGES -->
      <div class="messages-wrap" id="msgs-wrap">
        <?php if (empty($messages_list)): ?>
          <div class="empty-chat">
            <div class="e-icon">👋</div>
            <h3>Say Hello!</h3>
            <p style="font-size:13px;">Start the conversation below</p>
          </div>
        <?php else: ?>
          <?php foreach ($messages_list as $msg): ?>
            <?php $is_mine = $msg['sender_user_id'] == $user_id; ?>
            <div class="msg-row <?php echo $is_mine?'mine':'theirs'; ?>">
              <div class="msg-avatar">
                <?php echo $is_mine ? ($role=='driver'?'🚐':'👨‍👩‍👧') : $other_avatar; ?>
              </div>
              <div>
                <div class="msg-bubble">
                  <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                </div>
                <div class="msg-time">
                  <?php echo date('h:i A', strtotime($msg['sent_at'])); ?>
                  <?php echo $is_mine ? ($msg['is_read']?'✓✓':'✓') : ''; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- INPUT -->
      <div class="msg-input-wrap">
        <form method="POST" action="chat.php" style="display:flex;gap:12px;flex:1;align-items:flex-end;">
          <input type="hidden" name="conv_id" value="<?php echo $conv_id; ?>">
          <textarea name="message" class="msg-input" placeholder="Type a message..."
            rows="1" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}"
            required></textarea>
          <button type="submit" class="send-btn">➤</button>
        </form>
      </div>

    <?php else: ?>
      <div class="no-conv-selected">
        <div class="nc-icon">💬</div>
        <h3>Select a conversation</h3>
        <p>Choose from the left to start chatting</p>
        <?php if($role=='parent'): ?>
          <br><a href="parent_search.php" style="color:#1a5fa8;font-weight:600;font-size:14px;">+ Start a new chat with a driver</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<footer><p>&copy; 2024 <span>Ride2School</span> — Safe School Transport</p></footer>
<script>
// Auto scroll to bottom
const wrap = document.getElementById('msgs-wrap');
if (wrap) wrap.scrollTop = wrap.scrollHeight;

// Auto refresh every 5 seconds
<?php if ($conv_id): ?>
setTimeout(() => location.reload(), 5000);
<?php endif; ?>
</script>
</body>
</html>

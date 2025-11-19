<?php
require_once __DIR__ . '/includes/db.php';
session_start();
$me = current_user();
if (!$me) { header('Location: login.php'); exit; }

$db = db_connect();

// Fetch notifications
$stmt = $db->prepare('SELECT n.*, u.username, u.profile_pic FROM notifications n JOIN users u ON n.from_user_id=u.id WHERE n.user_id=? ORDER BY n.created_at DESC LIMIT 100');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
$notifications = [];
while ($row = $res->fetch_assoc()) $notifications[] = $row;
$stmt->close();

// Mark all as read
$stmt = $db->prepare('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->close();

$db->close();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Notifications - Codegram</title>
  <script src="theme.js"></script>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="theme.css">
  <style>
    .notif-container{max-width:600px;margin:24px auto;background:#fff;border:1px solid #e6e6e6;border-radius:6px}
    .notif-item{display:flex;gap:12px;padding:16px;border-bottom:1px solid #f0f0f0;align-items:flex-start;text-decoration:none;color:inherit;transition:background 0.2s}
    .notif-item:last-child{border-bottom:none}
    .notif-item.unread{background:#f8f9ff}
    .notif-item:hover{background:#f5f5f5;cursor:pointer}
    .notif-avatar{width:48px;height:48px;border-radius:50%;background:#ccc;flex:0 0 48px}
    .notif-content{flex:1}
    .notif-user{font-weight:600}
    .notif-text{color:#666;margin-top:4px}
    .notif-time{font-size:12px;color:#999;margin-top:4px}
    .notif-delete{position:absolute;top:12px;right:12px;background:#ff3b3b;color:white;border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;}
  </style>
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <a href="/Nathan/wd-project/" class="logo" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:8px;">
        <svg viewBox="0 0 24 24" class="camera" aria-hidden="true"><path d="M12 7a5 5 0 100 10 5 5 0 000-10z" fill="none" stroke="currentColor" stroke-width="1.2"/><rect x="2" y="3" width="20" height="18" rx="4" ry="4" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
        <span class="brand">Codegram</span>
      </a>
      <div class="search"><input type="search" placeholder="Search" aria-label="Search" /></div>
      <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme">
        <span class="theme-toggle-slider">🌙</span>
      </button>
    </div>
  </header>

  <main class="main">
    <div class="app-inner">
      <?php include __DIR__ . '/left-nav.php'; ?>
      <section class="layout">
        <div class="notif-container">
          <h2 style="padding:16px;margin:0;border-bottom:1px solid #e6e6e6">Notifications</h2>
          <?php if (empty($notifications)): ?>
            <div style="padding:32px;text-align:center;color:#666">No notifications yet.</div>
          <?php else: ?>
            <?php foreach ($notifications as $n): 
              // Determine link based on notification type
              $link = '#';
              if ($n['type'] === 'like' || $n['type'] === 'comment') {
                $link = 'index.php#post-' . $n['reference_id'];
              } elseif ($n['type'] === 'follow') {
                $link = 'profile?user=' . $n['from_user_id'];
              }
            ?>
              <div class="notif-item <?php echo empty($n['read_at']) ? 'unread' : ''; ?>" style="position:relative;">
                <a href="<?php echo htmlspecialchars($link); ?>" style="flex:1;text-decoration:none;color:inherit;display:flex;gap:12px;align-items:flex-start;">
                  <div class="notif-avatar" style="background-image:url('<?php echo htmlspecialchars($n['profile_pic']?:''); ?>');background-size:cover"></div>
                  <div class="notif-content">
                    <div class="notif-user"><?php echo htmlspecialchars($n['username']); ?></div>
                    <div class="notif-text"><?php echo htmlspecialchars($n['message']); ?></div>
                    <div class="notif-time"><?php echo htmlspecialchars($n['created_at']); ?></div>
                  </div>
                </a>
                <button class="notif-delete" data-id="<?php echo $n['id']; ?>" title="Delete">&times;</button>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </main>

  <script>
    // Request browser notification permission
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission();
    }
    
    // Poll for new notifications every 10s
    var lastCheck = new Date().toISOString();
    setInterval(function(){
      fetch('api/notifications_fetch.php?since='+encodeURIComponent(lastCheck), {credentials:'same-origin'})
        .then(r=>r.json())
        .then(function(data){
          if (data && data.notifications && data.notifications.length > 0) {
            data.notifications.forEach(function(n){
              if ('Notification' in window && Notification.permission === 'granted') {
                new Notification(n.username, {
                  body: n.message,
                  icon: n.profile_pic || '/Media/profiles/default.png'
                });
              }
            });
            lastCheck = new Date().toISOString();
          }
        });
    }, 10000);

    document.querySelectorAll('.notif-delete').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (!confirm('Delete this notification?')) return;
        fetch('api/notification_delete.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'id=' + encodeURIComponent(btn.dataset.id)
        }).then(r => r.json()).then(res => {
          if (res.success) {
            btn.closest('.notif-item').remove();
          }
        });
      });
    });
  </script>
</body>
</html>

<?php
require_once __DIR__ . '/includes/db.php';
session_start();
$me = current_user();
if (!$me) { header('Location: login.php'); exit; }
$db = db_connect();

// Helper to check user exists
function user_exists($db, $id) {
    $stmt = $db->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok = (bool)$res->fetch_row();
    $stmt->close();
    return $ok;
}

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['other_id'])) {
    $other = intval($_POST['other_id']);
    $text = trim($_POST['message'] ?? '');
    if ($text !== '' && $other > 0 && user_exists($db, $other)) {
        $stmt = $db->prepare('INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $_SESSION['user_id'], $other, $text);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: messages.php?u=' . $other);
    exit;
}

// New chat search by username/email
$q = trim($_GET['q'] ?? '');
$search_results = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $db->prepare('SELECT id, username, profile_pic FROM users WHERE (username LIKE ? OR email LIKE ?) AND id <> ? ORDER BY username LIMIT 20');
    $stmt->bind_param('ssi', $like, $like, $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $search_results[] = $row;
    $stmt->close();
}

// Build conversations: get latest message per partner
$conversations = [];
$uid = intval($_SESSION['user_id']);
$res = $db->query("SELECT * FROM messages WHERE sender_id = {$uid} OR receiver_id = {$uid} ORDER BY created_at DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $other = ($row['sender_id'] == $uid) ? $row['receiver_id'] : $row['sender_id'];
        if (!isset($conversations[$other])) $conversations[$other] = $row; // keep latest
    }
    $res->free();
}

// Preload partner details in one query
$partners = [];
if (!empty($conversations)) {
    $ids = implode(',', array_map('intval', array_keys($conversations)));
    $res = $db->query("SELECT id, username, profile_pic FROM users WHERE id IN ($ids)");
    while ($u = $res->fetch_assoc()) { $partners[$u['id']] = $u; }
}

// Load thread if requested
$other_id = isset($_GET['u']) ? intval($_GET['u']) : null;
$thread = [];
$other_user = null;
if ($other_id) {
    $stmt = $db->prepare('SELECT id, username, profile_pic FROM users WHERE id = ?');
    $stmt->bind_param('i', $other_id);
    $stmt->execute();
    $r = $stmt->get_result();
    $other_user = $r->fetch_assoc();
    $stmt->close();

    if ($other_user) {
        $stmt = $db->prepare('SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC');
        $stmt->bind_param('iiii', $_SESSION['user_id'], $other_id, $other_id, $_SESSION['user_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $thread[] = $row;
        $stmt->close();

        // mark unread messages as read
        $stmt = $db->prepare('UPDATE messages SET read_at = NOW() WHERE sender_id = ? AND receiver_id = ? AND read_at IS NULL');
        $stmt->bind_param('ii', $other_id, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }
}

$db->close();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Messages - Codegram</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .messages-wrap{display:flex;gap:20px;max-width:1000px;margin:24px auto}
    .conversations{width:280px;background:#fff;border:1px solid #e6e6e6;padding:12px;border-radius:6px;height:600px;overflow:auto}
    .conv-item{padding:8px;display:flex;gap:10px;align-items:center;border-radius:6px;cursor:pointer}
    .conv-item:hover{background:#fafafa}
    .conv-avatar{width:44px;height:44px;border-radius:50%;background:#ccc;flex:0 0 44px}
    .conv-meta{flex:1}
    .conv-user{font-weight:600}
    .conv-last{font-size:13px;color:#666}
    .chat{flex:1;background:#fff;border:1px solid #e6e6e6;padding:12px;border-radius:6px;display:flex;flex-direction:column;height:600px}
    .chat-header{display:flex;gap:12px;align-items:center;padding-bottom:8px;border-bottom:1px solid #eee}
    .chat-body{flex:1;overflow:auto;padding:12px 0}
    .msg{max-width:70%;padding:8px 10px;border-radius:12px;margin-bottom:8px;position:relative}
    .msg.me{background:#dcf8c6;margin-left:auto}
    .msg.other{background:#f1f1f1}
    .msg-reactions{display:flex;gap:4px;margin-top:4px;flex-wrap:wrap}
    .msg-reaction{font-size:12px;padding:2px 6px;background:#fff;border-radius:10px;cursor:pointer;border:1px solid #ddd}
    .msg-reaction:hover{background:#f0f0f0}
    .react-btn{position:absolute;top:4px;right:4px;background:rgba(0,0,0,0.1);border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;opacity:0;transition:opacity 0.2s}
    .msg:hover .react-btn{opacity:1}
    .chat-form{display:flex;gap:8px;padding-top:8px}
    .chat-form textarea{flex:1;resize:none;padding:8px}
    .conv-search{display:flex;gap:6px;margin-bottom:10px}
    .conv-search input{flex:1;padding:8px;border:1px solid #dbdbdb;border-radius:6px}
  </style>
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="logo">
        <svg viewBox="0 0 24 24" class="camera" aria-hidden="true"><path d="M12 7a5 5 0 100 10 5 5 0 000-10z" fill="none" stroke="currentColor" stroke-width="1.2"/><rect x="2" y="3" width="20" height="18" rx="4" ry="4" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
        <span class="brand">Codegram</span>
      </div>
      <div class="search"><input type="search" placeholder="Search" aria-label="Search" /></div>
    </div>
  </header>

  <main class="main">
    <div class="app-inner">
      <?php include __DIR__ . '/left-nav.php'; ?>
      <section class="layout">
        <div class="messages-wrap">
          <div class="conversations">
            <h3>Messages</h3>
            <form class="conv-search" method="get" action="messages.php">
              <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search users..." />
              <button type="submit">Search</button>
            </form>
            <?php if ($q !== ''): ?>
              <?php if (empty($search_results)): ?>
                <div style="color:#666">No users match "<?php echo htmlspecialchars($q); ?>"</div>
              <?php else: ?>
                <div style="margin-bottom:10px;color:#666">Results</div>
                <?php foreach ($search_results as $u): ?>
                  <a class="conv-item" href="messages.php?u=<?php echo intval($u['id']); ?>" style="text-decoration:none;color:inherit">
                    <div class="conv-avatar" style="background-image:url('<?php echo htmlspecialchars($u['profile_pic'] ?: ''); ?>');background-size:cover"></div>
                    <div class="conv-meta"><div class="conv-user"><?php echo htmlspecialchars($u['username']); ?></div></div>
                  </a>
                <?php endforeach; ?>
                <hr style="margin:10px 0;border:none;border-top:1px solid #eee">
              <?php endif; ?>
            <?php endif; ?>

            <?php if (empty($conversations)): ?>
              <div style="color:#666">No conversations yet. Search above to start chatting.</div>
            <?php else: ?>
              <?php foreach ($conversations as $uid => $msg): $partner = $partners[$uid] ?? null; ?>
                <a class="conv-item" href="messages.php?u=<?php echo intval($uid); ?>" style="text-decoration:none;color:inherit">
                  <div class="conv-avatar" style="background-image: url('<?php echo htmlspecialchars($partner['profile_pic'] ?? ''); ?>'); background-size:cover"></div>
                  <div class="conv-meta">
                    <div class="conv-user"><?php echo htmlspecialchars($partner['username'] ?? 'User'); ?></div>
                    <div class="conv-last"><?php echo htmlspecialchars(mb_strimwidth($msg['message'] ?? '', 0, 80, '...')); ?></div>
                  </div>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div class="chat">
            <?php if (!$other_id || !$other_user): ?>
              <div style="color:#666">Select a conversation or search for a user to start chatting.</div>
            <?php else: ?>
              <div class="chat-header">
                <a href="messages" style="padding:6px;margin-right:8px;text-decoration:none;color:#222;font-size:20px" title="Back">←</a>
                <div class="conv-avatar" style="width:56px;height:56px;background-image: url('<?php echo htmlspecialchars($other_user['profile_pic'] ?? ''); ?>');background-size:cover"></div>
                <div>
                  <div style="font-weight:700"><?php echo htmlspecialchars($other_user['username'] ?? 'User'); ?></div>
                  <div style="font-size:13px;color:#666">Conversation</div>
                </div>
              </div>
              <div class="chat-body" id="chat-body" data-other-id="<?php echo (int)$other_id; ?>">
                <?php foreach ($thread as $m): ?>
                  <div class="msg <?php echo ($m['sender_id'] == $_SESSION['user_id']) ? 'me' : 'other'; ?>" data-id="<?php echo (int)$m['id']; ?>">
                    <div style="font-size:14px"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
                    <div style="font-size:11px;color:#666;margin-top:6px"><?php echo htmlspecialchars($m['created_at']); ?></div>
                  </div>
                <?php endforeach; ?>
              </div>

              <form class="chat-form" id="chat-form" method="post" onsubmit="return false;">
                <input type="hidden" name="other_id" value="<?php echo htmlspecialchars($other_id); ?>">
                <textarea name="message" id="chat-text" rows="2" placeholder="Write a message..." required></textarea>
                <button type="submit" id="chat-send">Send</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </section>
    </div>
  </main>

  <script>
    (function(){
      var cb = document.getElementById('chat-body'); if (cb) cb.scrollTop = cb.scrollHeight;
      var form = document.getElementById('chat-form');
      var textarea = document.getElementById('chat-text');
      var otherId = cb ? parseInt(cb.getAttribute('data-other-id')||'0',10) : 0;
      function lastId(){
        var last = cb ? cb.querySelector('.msg:last-of-type') : null;
        return last ? parseInt(last.getAttribute('data-id')||'0',10) : 0;
      }
      function appendMessage(m){
        var div = document.createElement('div');
        var USER_ID = <?php echo (int)$_SESSION['user_id']; ?>;
        div.className = 'msg ' + (m.sender_id == USER_ID ? 'me' : 'other');
        div.setAttribute('data-id', m.id);
        div.innerHTML = '<button class="react-btn" onclick="reactToMsg('+m.id+')" title="React">❤️</button><div style="font-size:14px"></div><div style="font-size:11px;color:#666;margin-top:6px"></div><div class="msg-reactions"></div>';
        div.children[1].innerHTML = (m.message||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
        div.children[2].textContent = m.created_at;
        cb.appendChild(div);
        cb.scrollTop = cb.scrollHeight;
      }
      function poll(){
        if (!otherId) return; // no thread selected
        var lid = lastId();
        var url = 'api/messages_fetch.php?u='+otherId + (lid? '&since='+encodeURIComponent(cb.querySelector('.msg:last-of-type')?.querySelector('div+div')?.textContent || '') : '');
        // Better: use timestamp of last message for since; we'll compute from last DOM node if present
        var lastTimeEl = cb && cb.querySelector('.msg:last-of-type > div+div');
        var since = lastTimeEl ? encodeURIComponent(lastTimeEl.textContent) : '';
        url = 'api/messages_fetch.php?u='+otherId + (since? '&since='+since: '');
        fetch(url, {credentials:'same-origin'}).then(r=>r.json()).then(function(data){
          if (!data || !data.messages) return;
          data.messages.forEach(appendMessage);
        }).catch(function(){});
      }
      if (form) {
        form.addEventListener('submit', function(){
          var text = textarea.value.trim(); if (!text) return false;
          var fd = new FormData(); fd.append('other_id', otherId); fd.append('message', text);
          fetch('api/messages_send.php', {method:'POST', body:fd, credentials:'same-origin'}).then(r=>r.json()).then(function(res){
            if (res && res.message) { appendMessage(res.message); textarea.value=''; textarea.focus(); }
          }).catch(function(){});
          return false;
        });
      }
      // Poll every 2s
      setInterval(poll, 2000);
      
      // React to message function
      window.reactToMsg = function(msgId){
        var emoji = prompt('Enter emoji (❤️ 👍 😂 😮 😢 🔥):', '❤️');
        if (!emoji) return;
        var fd = new FormData(); fd.append('message_id', msgId); fd.append('emoji', emoji);
        fetch('api/reaction_add.php', {method:'POST', body:fd, credentials:'same-origin'})
          .then(r=>r.json()).then(function(){ loadReactions(msgId); });
      };
      
      function loadReactions(msgId){
        fetch('api/reactions_fetch.php?message_id='+msgId, {credentials:'same-origin'})
          .then(r=>r.json()).then(function(data){
            var msg = cb.querySelector('.msg[data-id="'+msgId+'"]');
            if (!msg) return;
            var reactDiv = msg.querySelector('.msg-reactions');
            if (!reactDiv) return;
            reactDiv.innerHTML = '';
            if (data.reactions) {
              data.reactions.forEach(function(r){
                var span = document.createElement('span');
                span.className = 'msg-reaction';
                span.textContent = r.emoji + ' ' + r.count;
                reactDiv.appendChild(span);
              });
            }
          });
      }
    })();
  </script>
</body>
</html>

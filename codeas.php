<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = db_connect();
$current_user = current_user();

// Fetch all codeas with user info
$query = "SELECT c.*, u.id as user_id, u.username, u.profile_pic,
          (SELECT COUNT(*) FROM codeas_likes WHERE codea_id = c.id AND user_id = ?) as user_liked,
          (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = c.user_id) as is_following
          FROM codeas c
          JOIN users u ON c.user_id = u.id
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bind_param('ii', $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$codeas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Codeas - Codegram</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .codeas-container {
      max-width: 480px;
      margin: 0 auto;
      height: calc(100vh - 60px);
      overflow-y: scroll;
      scroll-snap-type: y mandatory;
      -webkit-overflow-scrolling: touch;
      background: #000;
      scrollbar-width: none; /* Firefox */
      -ms-overflow-style: none; /* IE/Edge */
    }
    
    .codeas-container::-webkit-scrollbar {
      display: none; /* Chrome/Safari/Opera */
    }
    
    .codea-item {
      position: relative;
      width: 100%;
      height: calc(100vh - 60px);
      scroll-snap-align: start;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #000;
    }
    
    .codea-video {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }
    
    .codea-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 20px;
      background: linear-gradient(transparent, rgba(0,0,0,0.8));
      color: white;
    }
    
    .codea-user {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
    }
    
    .codea-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid white;
      background: #333;
    }
    
    .codea-username {
      font-weight: 700;
      font-size: 15px;
    }
    
    .codea-follow-btn {
      margin-left: auto;
      padding: 6px 16px;
      background: transparent;
      border: 1px solid white;
      color: white;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .codea-follow-btn.following {
      background: rgba(255,255,255,0.2);
    }
    
    .codea-follow-btn:hover {
      background: white;
      color: black;
    }
    
    .codea-caption {
      font-size: 14px;
      margin-bottom: 12px;
      line-height: 1.4;
    }
    
    .codea-actions {
      position: absolute;
      right: 12px;
      bottom: 80px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    
    .codea-action {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
      cursor: pointer;
      color: white;
      transition: transform 0.2s;
    }
    
    .codea-action:active {
      transform: scale(0.9);
    }
    
    .action-icon {
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
    }
    
    .action-count {
      font-size: 12px;
      font-weight: 600;
    }
    
    .liked .action-icon {
      color: #ff3b5c;
    }
    
    .comment-modal {
      display: none;
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: white;
      border-radius: 16px 16px 0 0;
      max-height: 70vh;
      z-index: 1000;
      animation: slideUp 0.3s ease;
    }
    
    .comment-modal.active {
      display: block;
    }
    
    @keyframes slideUp {
      from { transform: translateY(100%); }
      to { transform: translateY(0); }
    }
    
    .comment-header {
      padding: 16px;
      border-bottom: 1px solid #e6e6e6;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .comment-header h3 {
      margin: 0;
      font-size: 16px;
    }
    
    .comment-close {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: #666;
    }
    
    .comments-list {
      max-height: 50vh;
      overflow-y: auto;
      padding: 16px;
    }
    
    .comment-item {
      display: flex;
      gap: 12px;
      margin-bottom: 16px;
    }
    
    .comment-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
      background: #e6e6e6;
    }
    
    .comment-content {
      flex: 1;
    }
    
    .comment-username {
      font-weight: 600;
      font-size: 14px;
      margin-right: 8px;
    }
    
    .comment-text {
      font-size: 14px;
      display: inline;
    }
    
    .comment-time {
      font-size: 12px;
      color: #666;
      margin-top: 4px;
    }
    
    .comment-input-container {
      border-top: 1px solid #e6e6e6;
      padding: 12px 16px;
      display: flex;
      gap: 12px;
    }
    
    .comment-input {
      flex: 1;
      border: 1px solid #e6e6e6;
      border-radius: 20px;
      padding: 8px 16px;
      font-size: 14px;
      outline: none;
    }
    
    .comment-submit {
      padding: 8px 20px;
      background: #0095f6;
      color: white;
      border: none;
      border-radius: 20px;
      font-weight: 600;
      cursor: pointer;
      transition: opacity 0.2s;
    }
    
    .comment-submit:hover {
      opacity: 0.8;
    }
    
    .comment-submit:disabled {
      opacity: 0.3;
      cursor: not-allowed;
    }
    
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
      z-index: 999;
    }
    
    .modal-overlay.active {
      display: block;
    }
    
    .codeas-header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 60px;
      background: rgba(0,0,0,0.8);
      backdrop-filter: blur(10px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      color: white;
    }
    
    .codeas-logo {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 24px;
      font-weight: 700;
    }
    
    .codeas-logo svg {
      width: 32px;
      height: 32px;
    }
  </style>
</head>
<body>
  <header class="codeas-header">
    <div class="codeas-logo">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
        <path d="M12 7a5 5 0 100 10 5 5 0 000-10z"/>
        <rect x="2" y="3" width="20" height="18" rx="4" ry="4"/>
      </svg>
      <span>Codegram</span>
    </div>
  </header>
  
  <main class="main">
    <div class="app-inner">
      <?php include __DIR__ . '/left-nav.php'; ?>
      
      <div class="codeas-container" id="codeasContainer">
        <?php if (empty($codeas)): ?>
          <div style="display:flex;align-items:center;justify-content:center;height:100%;color:white;flex-direction:column;gap:16px">
            <div style="font-size:48px">🎬</div>
            <div style="font-size:18px;font-weight:600">No Codeas Yet</div>
            <div style="color:#999">Be the first to create a Codea!</div>
          </div>
        <?php else: ?>
          <?php foreach ($codeas as $codea): ?>
            <div class="codea-item" data-codea-id="<?php echo $codea['id']; ?>">
              <video class="codea-video" src="<?php echo htmlspecialchars($codea['video_path']); ?>" loop playsinline></video>
              
              <div class="codea-overlay">
                <div class="codea-user">
                  <?php if ($codea['profile_pic']): ?>
                    <img src="<?php echo htmlspecialchars($codea['profile_pic']); ?>" alt="<?php echo htmlspecialchars($codea['username']); ?>" class="codea-avatar">
                  <?php else: ?>
                    <div class="codea-avatar"></div>
                  <?php endif; ?>
                  <span class="codea-username">@<?php echo htmlspecialchars($codea['username']); ?></span>
                  <?php if ($codea['user_id'] != $_SESSION['user_id']): ?>
                    <button class="codea-follow-btn <?php echo $codea['is_following'] ? 'following' : ''; ?>" 
                            data-user-id="<?php echo $codea['user_id']; ?>"
                            onclick="toggleFollow(this)">
                      <?php echo $codea['is_following'] ? 'Following' : 'Follow'; ?>
                    </button>
                  <?php endif; ?>
                </div>
                <?php if ($codea['caption']): ?>
                  <div class="codea-caption"><?php echo htmlspecialchars($codea['caption']); ?></div>
                <?php endif; ?>
              </div>
              
              <div class="codea-actions">
                <div class="codea-action <?php echo $codea['user_liked'] ? 'liked' : ''; ?>" onclick="toggleLike(this, <?php echo $codea['id']; ?>)">
                  <div class="action-icon">❤️</div>
                  <div class="action-count"><?php echo $codea['likes_count']; ?></div>
                </div>
                <div class="codea-action" onclick="openComments(<?php echo $codea['id']; ?>)">
                  <div class="action-icon">💬</div>
                  <div class="action-count"><?php echo $codea['comments_count']; ?></div>
                </div>
                <div class="codea-action" onclick="shareCodea(<?php echo $codea['id']; ?>)">
                  <div class="action-icon">📤</div>
                  <div class="action-count"><?php echo $codea['shares_count']; ?></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <div class="modal-overlay" id="modalOverlay" onclick="closeComments()"></div>
  <div class="comment-modal" id="commentModal">
    <div class="comment-header">
      <h3>Comments</h3>
      <button class="comment-close" onclick="closeComments()">×</button>
    </div>
    <div class="comments-list" id="commentsList"></div>
    <div class="comment-input-container">
      <input type="text" class="comment-input" id="commentInput" placeholder="Add a comment...">
      <button class="comment-submit" id="commentSubmit" onclick="postComment()">Post</button>
    </div>
  </div>

    <div class="modal-overlay" id="shareOverlay" onclick="closeShare()"></div>
    <div class="comment-modal" id="shareModal">
      <div class="comment-header">
        <h3>Share</h3>
        <button class="comment-close" onclick="closeShare()">×</button>
      </div>
      <div class="share-search">
        <input type="text" id="shareSearch" placeholder="Search users..." style="width:100%;padding:12px;border:1px solid #e6e6e6;border-radius:8px;font-size:14px">
      </div>
      <div class="comments-list" id="shareUsersList"></div>
      <div style="padding:16px;border-top:1px solid #e6e6e6">
        <button onclick="shareExternal()" style="width:100%;padding:12px;background:#0095f6;color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer">Share Externally</button>
      </div>
    </div>

  <script>
    let currentCodeaId = null;
      let currentShareCodeaId = null;
    const container = document.getElementById('codeasContainer');
    const videos = document.querySelectorAll('.codea-video');
    
    // Auto-play video on scroll
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        const video = entry.target;
        if (entry.isIntersecting) {
          video.play();
          video.muted = false;
        } else {
          video.pause();
        }
      });
    }, { threshold: 0.5 });
    
    videos.forEach(video => observer.observe(video));
    
    // Like functionality
    async function toggleLike(el, codeaId) {
      const wasLiked = el.classList.contains('liked');
      const countEl = el.querySelector('.action-count');
      
      try {
        const res = await fetch('api/codea_like.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ codea_id: codeaId })
        });
        const data = await res.json();
        
        if (data.success) {
          el.classList.toggle('liked');
          countEl.textContent = data.likes_count;
        }
      } catch (err) {
        console.error('Like error:', err);
      }
    }
    
    // Follow functionality
    async function toggleFollow(btn) {
      const userId = btn.dataset.userId;
      const wasFollowing = btn.classList.contains('following');
      
      try {
        const res = await fetch('api/follow_user.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ user_id: userId })
        });
        const data = await res.json();
        
        if (data.success) {
          btn.classList.toggle('following');
          btn.textContent = data.following ? 'Following' : 'Follow';
        }
      } catch (err) {
        console.error('Follow error:', err);
      }
    }
    
    // Comments modal
    async function openComments(codeaId) {
      currentCodeaId = codeaId;
      document.getElementById('modalOverlay').classList.add('active');
      document.getElementById('commentModal').classList.add('active');
      await loadComments(codeaId);
    }
    
    function closeComments() {
      document.getElementById('modalOverlay').classList.remove('active');
      document.getElementById('commentModal').classList.remove('active');
      document.getElementById('commentInput').value = '';
      currentCodeaId = null;
    }
    
    async function loadComments(codeaId) {
      try {
        const res = await fetch(`api/codea_comments.php?codea_id=${codeaId}`);
        const data = await res.json();
        
        const list = document.getElementById('commentsList');
        if (data.comments && data.comments.length > 0) {
          list.innerHTML = data.comments.map(c => `
            <div class="comment-item">
              <img src="${c.profile_pic || 'Media/dp/default.png'}" class="comment-avatar">
              <div class="comment-content">
                <div>
                  <span class="comment-username">${c.username}</span>
                  <span class="comment-text">${c.comment}</span>
                </div>
                <div class="comment-time">${timeAgo(c.created_at)}</div>
              </div>
            </div>
          `).join('');
        } else {
          list.innerHTML = '<div style="text-align:center;color:#666;padding:40px 0">No comments yet</div>';
        }
      } catch (err) {
        console.error('Load comments error:', err);
      }
    }
    
    async function postComment() {
      const input = document.getElementById('commentInput');
      const comment = input.value.trim();
      if (!comment || !currentCodeaId) return;
      
      try {
        const res = await fetch('api/codea_comments.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ codea_id: currentCodeaId, comment })
        });
        const data = await res.json();
        
        if (data.success) {
          input.value = '';
          await loadComments(currentCodeaId);
          // Update comment count
          const codeaItem = document.querySelector(`[data-codea-id="${currentCodeaId}"]`);
          const countEl = codeaItem.querySelector('.codea-action:nth-child(2) .action-count');
          countEl.textContent = parseInt(countEl.textContent) + 1;
        }
      } catch (err) {
        console.error('Post comment error:', err);
      }
    }
    
    async function shareCodea(codeaId) {
        currentShareCodeaId = codeaId;
        document.getElementById('shareOverlay').classList.add('active');
        document.getElementById('shareModal').classList.add('active');
        await loadUsersForShare();
      }
    
      function closeShare() {
        document.getElementById('shareOverlay').classList.remove('active');
        document.getElementById('shareModal').classList.remove('active');
        document.getElementById('shareSearch').value = '';
        currentShareCodeaId = null;
      }
    
      async function loadUsersForShare(query = '') {
        try {
          const res = await fetch(`api/search_users.php?q=${encodeURIComponent(query || 'a')}`);
          const data = await res.json();
        
          const list = document.getElementById('shareUsersList');
          if (data.success && data.users && data.users.length > 0) {
            list.innerHTML = data.users.map(u => `
              <div class="comment-item" style="cursor:pointer" onclick="shareToUser(${u.id}, '${u.username}')">
                <img src="${u.profile_pic || 'Media/dp/default.png'}" class="comment-avatar">
                <div class="comment-content">
                  <div class="comment-username">${u.username}</div>
                  ${u.display_name ? `<div style="font-size:12px;color:#666">${u.display_name}</div>` : ''}
                </div>
              </div>
            `).join('');
          } else {
            list.innerHTML = '<div style="text-align:center;color:#666;padding:20px">No users found</div>';
          }
        } catch (err) {
          console.error('Load users error:', err);
        }
      }
    
      async function shareToUser(userId, username) {
        if (!currentShareCodeaId) return;
      
        // Here you would implement sending a message or notification to the user
        // For now, we'll just show a confirmation
        alert(`Shared with @${username}!`);
        closeShare();
      
        // Increment share count
      try {
        await fetch('api/codea_share.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ codea_id: currentShareCodeaId })
        });
        } catch (err) {
          console.error('Share error:', err);
        }
      }
    
      async function shareExternal() {
        if (!currentShareCodeaId) return;
      
        try {
        if (navigator.share) {
          await navigator.share({
            title: 'Check out this Codea!',
              url: window.location.origin + '/Nathan/wd-project/codeas?id=' + currentShareCodeaId
          });
        } else {
            const url = window.location.origin + '/Nathan/wd-project/codeas?id=' + currentShareCodeaId;
            await navigator.clipboard.writeText(url);
            alert('Link copied to clipboard!');
        }
          closeShare();
      } catch (err) {
        console.error('Share error:', err);
      }
    }
    
      // Share search
      document.getElementById('shareSearch')?.addEventListener('input', (e) => {
        loadUsersForShare(e.target.value);
      });
    
    function timeAgo(timestamp) {
      const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000);
      if (seconds < 60) return 'just now';
      const minutes = Math.floor(seconds / 60);
      if (minutes < 60) return minutes + 'm';
      const hours = Math.floor(minutes / 60);
      if (hours < 24) return hours + 'h';
      const days = Math.floor(hours / 24);
      if (days < 7) return days + 'd';
      return Math.floor(days / 7) + 'w';
    }
    
    // Enable comment submit on Enter
    document.getElementById('commentInput').addEventListener('keypress', (e) => {
      if (e.key === 'Enter') postComment();
    });
  </script>
</body>
</html>

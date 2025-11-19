<?php
require_once __DIR__ . '/includes/db.php';
session_start();
$me = current_user();
if (!$me) {
    header('Location: login.php'); exit;
}

$db = db_connect();

// Create saved_posts table if it doesn't exist
$db->query("CREATE TABLE IF NOT EXISTS saved_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    post_type VARCHAR(10) DEFAULT 'post',
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_save (user_id, post_id, post_type),
    INDEX idx_user_id (user_id)
)");

$posts = [];
$user_id = $_SESSION['user_id'];

// Fetch both posts and codeas combined
$query = "
    SELECT 
        p.id,
        p.user_id,
        p.caption COLLATE utf8mb4_unicode_ci as caption,
        p.media_path COLLATE utf8mb4_unicode_ci as media_path,
        p.media_type COLLATE utf8mb4_unicode_ci as media_type,
        p.created_at,
        u.username COLLATE utf8mb4_unicode_ci as username,
        u.profile_pic COLLATE utf8mb4_unicode_ci as profile_pic,
        u.display_name COLLATE utf8mb4_unicode_ci as display_name,
        'post' as content_type,
        (SELECT COUNT(*) FROM saved_posts WHERE user_id = $user_id AND post_id = p.id AND post_type = 'post') as is_saved
    FROM posts p
    JOIN users u ON p.user_id = u.id
    
    UNION ALL
    
    SELECT 
        c.id,
        c.user_id,
        c.caption COLLATE utf8mb4_unicode_ci as caption,
        c.video_path COLLATE utf8mb4_unicode_ci as media_path,
        'video' COLLATE utf8mb4_unicode_ci as media_type,
        c.created_at,
        u.username COLLATE utf8mb4_unicode_ci as username,
        u.profile_pic COLLATE utf8mb4_unicode_ci as profile_pic,
        u.display_name COLLATE utf8mb4_unicode_ci as display_name,
        'codea' as content_type,
        (SELECT COUNT(*) FROM saved_posts WHERE user_id = $user_id AND post_id = c.id AND post_type = 'codea') as is_saved
    FROM codeas c
    JOIN users u ON c.user_id = u.id
    
    ORDER BY RAND()
";

$res = $db->query($query);

if ($res) {
    while ($row = $res->fetch_assoc()) $posts[] = $row;
    $res->free();
} else {
    error_log("Query error: " . $db->error);
}

$db->close();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Codegram — Home</title>
    <script src="theme.js"></script>
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="theme.css" />
    <style>
      .search {
        position: relative;
      }
      
      .search-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e6e6e6;
        border-radius: 8px;
        margin-top: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-height: 400px;
        overflow-y: auto;
        z-index: 1000;
      }
      
      .search-dropdown.active {
        display: block;
      }
      
      .search-result {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
        color: inherit;
      }
      
      .search-result:hover {
        background: #f5f5f5;
      }
      
      .search-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        background: #e6e6e6;
      }
      
      .search-info {
        flex: 1;
      }
      
      .search-username {
        font-weight: 600;
        font-size: 14px;
      }
      
      .search-fullname {
        font-size: 14px;
        color: #666;
      }
      
      .search-empty {
        padding: 20px;
        text-align: center;
        color: #666;
        font-size: 14px;
      }
      
      [data-theme="dark"] .search-fullname,
      [data-theme="dark"] .search-empty {
        color: var(--text-secondary);
      }

      /* Comment Modal Styles */
      .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.65);
        z-index: 9998;
      }

      .modal-overlay.active {
        display: block;
      }

      .comment-modal {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        max-width: 500px;
        max-height: 80vh;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        flex-direction: column;
      }

      .comment-modal.active {
        display: flex;
      }

      .comment-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #e6e6e6;
      }

      .comment-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
      }

      .comment-close {
        background: none;
        border: none;
        font-size: 32px;
        cursor: pointer;
        color: #262626;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
      }

      .comments-list {
        flex: 1;
        overflow-y: auto;
        padding: 16px 20px;
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
        margin-right: 8px;
        font-size: 14px;
      }

      .comment-text {
        font-size: 14px;
        word-wrap: break-word;
      }

      .comment-time {
        font-size: 12px;
        color: #8e8e8e;
        margin-top: 4px;
      }

      .comment-actions {
        display: flex;
        gap: 12px;
        margin-top: 8px;
        font-size: 12px;
      }

      .comment-action-btn {
        background: none;
        border: none;
        color: #8e8e8e;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        padding: 0;
        transition: color 0.2s;
      }

      .comment-action-btn:hover {
        color: #262626;
      }

      .comment-action-btn.liked {
        color: #ed4956;
      }

      .comment-action-btn.delete {
        color: #ed4956;
      }

      .comment-edit-input {
        width: 100%;
        border: 1px solid #e6e6e6;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 14px;
        margin-top: 8px;
      }

      .comment-edit-actions {
        display: flex;
        gap: 8px;
        margin-top: 8px;
      }

      .comment-edit-btn {
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        border: none;
      }

      .comment-save-btn {
        background: #0095f6;
        color: white;
      }

      .comment-cancel-btn {
        background: #efefef;
        color: #262626;
      }

      .comment-input-container {
        display: flex;
        gap: 8px;
        padding: 16px 20px;
        border-top: 1px solid #e6e6e6;
      }

      .comment-input {
        flex: 1;
        border: 1px solid #e6e6e6;
        border-radius: 20px;
        padding: 10px 16px;
        font-size: 14px;
        outline: none;
      }

      .comment-input:focus {
        border-color: #0095f6;
      }

      .comment-submit {
        background: #0095f6;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
      }

      .comment-submit:hover {
        background: #0084e6;
      }

      .comment-submit:disabled {
        background: #b2dffc;
        cursor: not-allowed;
      }

      /* Dark theme support */
      [data-theme="dark"] .comment-modal {
        background: var(--card-bg);
        color: var(--text-primary);
      }

      [data-theme="dark"] .comment-header {
        border-bottom-color: var(--border-color);
      }

      [data-theme="dark"] .comment-close {
        color: var(--text-primary);
      }

      [data-theme="dark"] .comment-input-container {
        border-top-color: var(--border-color);
      }

      [data-theme="dark"] .comment-input {
        background: var(--bg-primary);
        color: var(--text-primary);
        border-color: var(--border-color);
      }

      [data-theme="dark"] .comment-time {
        color: var(--text-secondary);
      }

      [data-theme="dark"] .comment-action-btn {
        color: var(--text-secondary);
      }

      [data-theme="dark"] .comment-action-btn:hover {
        color: var(--text-primary);
      }

      [data-theme="dark"] .comment-edit-input {
        background: var(--bg-primary);
        color: var(--text-primary);
        border-color: var(--border-color);
      }

      [data-theme="dark"] .comment-cancel-btn {
        background: var(--hover-bg);
        color: var(--text-primary);
      }
    </style>
  </head>
  <body>
    <header class="topbar">
      <div class="topbar-inner">
        <a href="/Nathan/wd-project/" class="logo" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:8px;">
          <svg viewBox="0 0 24 24" class="camera" aria-hidden="true"><path d="M12 7a5 5 0 100 10 5 5 0 000-10z" fill="none" stroke="currentColor" stroke-width="1.2"/><rect x="2" y="3" width="20" height="18" rx="4" ry="4" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
          <span class="brand">Codegram</span>
        </a>
        <div class="search">
          <input type="search" id="searchInput" placeholder="Search" aria-label="Search" autocomplete="off" />
          <div class="search-dropdown" id="searchDropdown"></div>
        </div>
        <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme">
          <span class="theme-toggle-slider">🌙</span>
        </button>
      </div>
    </header>

    <main class="main">
      <div class="app-inner">
        <?php include __DIR__ . '/left-nav.php'; ?>
        <section class="layout">
          <section class="feed">
            <?php foreach ($posts as $post): ?>
              <article class="post">
                <header class="post-header">
                  <div class="avatar" style="background-image: url('<?php echo htmlspecialchars($post['profile_pic'] ?: ''); ?>'); background-size:cover"></div>
                  <div class="post-user"><?php echo htmlspecialchars($post['username']); ?></div>
                  <div class="post-menu">⋯</div>
                </header>
                <div class="post-image">
                  <?php if ($post['media_type'] === 'video'): ?>
                    <video controls style="width:100%;max-height:500px;object-fit:contain;background:#000"><source src="<?php echo htmlspecialchars($post['media_path']); ?>"></video>
                  <?php else: ?>
                    <img src="<?php echo htmlspecialchars($post['media_path']); ?>" alt="post image" style="width:100%;max-height:500px;object-fit:contain;display:block;background:#f5f5f5">
                  <?php endif; ?>
                </div>
                <div class="post-actions">
                  <button class="btn like" aria-pressed="false">♡</button>
                  <button class="btn" onclick="openPostComments(<?php echo $post['id']; ?>)">💬</button>
                  <div class="spacer"></div>
                  <button class="btn save" data-post-id="<?php echo $post['id']; ?>" data-content-type="<?php echo $post['content_type']; ?>" title="<?php echo $post['is_saved'] ? 'Unsave' : 'Save'; ?>" onclick="savePost(this, <?php echo $post['id']; ?>, '<?php echo $post['content_type']; ?>')" style="color:<?php echo $post['is_saved'] ? '#ffc107' : 'inherit'; ?>">🔖</button>
                </div>
                <div class="post-likes">— likes</div>
                <div class="post-caption"><strong><?php echo htmlspecialchars($post['username']); ?></strong> <?php echo htmlspecialchars($post['caption']); ?></div>
                <div class="post-time"><?php echo htmlspecialchars($post['created_at']); ?></div>
              </article>
            <?php endforeach; ?>
          </section>
        </section>
      </div>
    </main>

    <!-- Comments Modal -->
    <div class="modal-overlay" id="commentOverlay" onclick="closePostComments()"></div>
    <div class="comment-modal" id="postCommentModal">
      <div class="comment-header">
        <h3>Comments</h3>
        <button class="comment-close" onclick="closePostComments()">×</button>
      </div>
      <div class="comments-list" id="postCommentsList"></div>
      <div class="comment-input-container">
        <input type="text" class="comment-input" id="postCommentInput" placeholder="Add a comment...">
        <button class="comment-submit" id="postCommentSubmit" onclick="postPostComment()">Post</button>
      </div>
    </div>

    <script src="script.js"></script>
    <script>
      // Search functionality
      const searchInput = document.getElementById('searchInput');
      const searchDropdown = document.getElementById('searchDropdown');
      let searchTimeout;
      
      searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 1) {
          searchDropdown.classList.remove('active');
          return;
        }
        
        searchTimeout = setTimeout(async () => {
          try {
            const res = await fetch(`api/search_users.php?q=${encodeURIComponent(query)}`);
            const data = await res.json();
            
            if (data.success && data.users.length > 0) {
              searchDropdown.innerHTML = data.users.map(user => `
                <a href="profile.php?user_id=${user.id}" class="search-result">
                  <img src="${user.profile_pic || 'Media/dp/default.png'}" class="search-avatar">
                  <div class="search-info">
                    <div class="search-username">${user.username}</div>
                    ${user.display_name ? `<div class="search-fullname">${user.display_name}</div>` : ''}
                  </div>
                </a>
              `).join('');
              searchDropdown.classList.add('active');
            } else {
              searchDropdown.innerHTML = '<div class="search-empty">No users found</div>';
              searchDropdown.classList.add('active');
            }
          } catch (err) {
            console.error('Search error:', err);
          }
        }, 300);
      });
      
      // Close dropdown when clicking outside
      document.addEventListener('click', (e) => {
        if (!e.target.closest('.search')) {
          searchDropdown.classList.remove('active');
        }
      });
      
      searchInput.addEventListener('focus', () => {
        if (searchInput.value.trim().length > 0 && searchDropdown.innerHTML) {
          searchDropdown.classList.add('active');
        }
      });
      
      // Save post functionality
      async function savePost(element, postId, contentType) {
        try {
          const response = await fetch('api/save_post.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `post_id=${postId}&type=${contentType}`
          });
          const data = await response.json();
          
          if (data.success) {
            // Toggle saved state visually
            if (data.saved) {
              element.style.color = '#ffc107';
              element.title = 'Unsave';
            } else {
              element.style.color = 'inherit';
              element.title = 'Save';
            }
          }
        } catch (error) {
          console.error('Save error:', error);
        }
      }

      // Post Comments functionality
      let currentPostId = null;

      function openPostComments(postId) {
        currentPostId = postId;
        document.getElementById('commentOverlay').classList.add('active');
        document.getElementById('postCommentModal').classList.add('active');
        loadPostComments(postId);
      }

      function closePostComments() {
        document.getElementById('commentOverlay').classList.remove('active');
        document.getElementById('postCommentModal').classList.remove('active');
        document.getElementById('postCommentInput').value = '';
        currentPostId = null;
      }

      async function loadPostComments(postId) {
        try {
          const res = await fetch(`api/post_comments.php?post_id=${postId}`);
          const data = await res.json();
          
          const list = document.getElementById('postCommentsList');
          if (data.comments && data.comments.length > 0) {
            list.innerHTML = data.comments.map(c => `
              <div class="comment-item" id="comment-${c.id}">
                <img src="${c.profile_pic || 'Media/dp/default.png'}" class="comment-avatar">
                <div class="comment-content">
                  <div>
                    <span class="comment-username">${c.username}</span>
                    <span class="comment-text" id="comment-text-${c.id}">${c.comment}</span>
                  </div>
                  <div class="comment-actions">
                    <span class="comment-time">${timeAgo(c.created_at)}</span>
                    <button class="comment-action-btn" onclick="likeComment(${c.id}, 'post')" id="like-btn-${c.id}">Like</button>
                    ${c.user_id == <?php echo $_SESSION['user_id']; ?> ? `
                      <button class="comment-action-btn" onclick="editComment(${c.id}, 'post')">Edit</button>
                      <button class="comment-action-btn delete" onclick="deleteComment(${c.id}, 'post', ${postId})">Delete</button>
                    ` : ''}
                  </div>
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

      async function postPostComment() {
        const input = document.getElementById('postCommentInput');
        const comment = input.value.trim();
        if (!comment || !currentPostId) return;
        
        try {
          const res = await fetch('api/post_comments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: currentPostId, comment })
          });
          const data = await res.json();
          
          if (data.success) {
            input.value = '';
            await loadPostComments(currentPostId);
          } else {
            alert('Failed to post comment');
          }
        } catch (err) {
          console.error('Post comment error:', err);
        }
      }

      // Time ago helper function
      function timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + 'm';
        if (seconds < 86400) return Math.floor(seconds / 3600) + 'h';
        if (seconds < 604800) return Math.floor(seconds / 86400) + 'd';
        return Math.floor(seconds / 604800) + 'w';
      }

      // Delete comment
      async function deleteComment(commentId, type, postId) {
        if (!confirm('Delete this comment?')) return;
        
        try {
          const res = await fetch('api/delete_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comment_id: commentId, type: type })
          });
          const data = await res.json();
          
          if (data.success) {
            await loadPostComments(postId);
          } else {
            alert('Failed to delete comment');
          }
        } catch (err) {
          console.error('Delete comment error:', err);
        }
      }

      // Edit comment
      let editingCommentId = null;
      function editComment(commentId, type) {
        if (editingCommentId) return; // Already editing another comment
        editingCommentId = commentId;
        
        const commentText = document.getElementById(`comment-text-${commentId}`);
        const originalText = commentText.textContent;
        
        const editHTML = `
          <input type="text" class="comment-edit-input" id="edit-input-${commentId}" value="${originalText}">
          <div class="comment-edit-actions">
            <button class="comment-edit-btn comment-save-btn" onclick="saveCommentEdit(${commentId}, '${type}', ${currentPostId})">Save</button>
            <button class="comment-edit-btn comment-cancel-btn" onclick="cancelCommentEdit(${commentId}, '${originalText}')">Cancel</button>
          </div>
        `;
        
        commentText.innerHTML = editHTML;
        document.getElementById(`edit-input-${commentId}`).focus();
      }

      async function saveCommentEdit(commentId, type, postId) {
        const input = document.getElementById(`edit-input-${commentId}`);
        const newText = input.value.trim();
        
        if (!newText) {
          alert('Comment cannot be empty');
          return;
        }
        
        try {
          const res = await fetch('api/edit_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comment_id: commentId, comment: newText, type: type })
          });
          const data = await res.json();
          
          if (data.success) {
            editingCommentId = null;
            await loadPostComments(postId);
          } else {
            alert('Failed to update comment');
          }
        } catch (err) {
          console.error('Edit comment error:', err);
        }
      }

      function cancelCommentEdit(commentId, originalText) {
        const commentText = document.getElementById(`comment-text-${commentId}`);
        commentText.textContent = originalText;
        editingCommentId = null;
      }

      // Like comment
      async function likeComment(commentId, type) {
        try {
          const res = await fetch('api/like_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comment_id: commentId, type: type })
          });
          const data = await res.json();
          
          if (data.success) {
            const btn = document.getElementById(`like-btn-${commentId}`);
            if (data.liked) {
              btn.classList.add('liked');
              btn.textContent = 'Liked';
            } else {
              btn.classList.remove('liked');
              btn.textContent = 'Like';
            }
          }
        } catch (err) {
          console.error('Like comment error:', err);
        }
      }

      // Allow Enter key to submit comment
      document.getElementById('postCommentInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          postPostComment();
        }
      });

      // Close modal on ESC key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.getElementById('postCommentModal').classList.contains('active')) {
          closePostComments();
        }
      });
    </script>
  </body>
</html>

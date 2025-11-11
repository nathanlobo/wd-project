<?php
require_once __DIR__ . '/includes/db.php';
session_start();
$me = current_user();
if (!$me) {
    header('Location: login.php'); exit;
}

$db = db_connect();
$posts = [];
$res = $db->query('SELECT p.*, u.username, u.profile_pic, u.display_name FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 50');
while ($row = $res->fetch_assoc()) $posts[] = $row;
$res->free();
$db->close();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Codegram — Home</title>
    <link rel="stylesheet" href="styles.css" />
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
    </style>
  </head>
  <body>
    <header class="topbar">
      <div class="topbar-inner">
        <div class="logo">
          <svg viewBox="0 0 24 24" class="camera" aria-hidden="true"><path d="M12 7a5 5 0 100 10 5 5 0 000-10z" fill="none" stroke="currentColor" stroke-width="1.2"/><rect x="2" y="3" width="20" height="18" rx="4" ry="4" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
          <span class="brand">Codegram</span>
        </div>
        <div class="search">
          <input type="search" id="searchInput" placeholder="Search" aria-label="Search" autocomplete="off" />
          <div class="search-dropdown" id="searchDropdown"></div>
        </div>
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
                    <video controls style="width:100%"><source src="<?php echo htmlspecialchars($post['media_path']); ?>"></video>
                  <?php else: ?>
                    <img src="<?php echo htmlspecialchars($post['media_path']); ?>" alt="post image" style="width:100%;display:block">
                  <?php endif; ?>
                </div>
                <div class="post-actions">
                  <button class="btn like" aria-pressed="false">♡</button>
                  <button class="btn">💬</button>
                  <div class="spacer"></div>
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
    </script>
  </body>
</html>

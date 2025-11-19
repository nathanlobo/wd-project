<?php
session_start();
require_once __DIR__ . '/includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = db_connect();
$stmt = $db->prepare('SELECT id, username, email, display_name, profile_pic, bio, created_at FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get post count for this user
$stmt = $db->prepare('SELECT COUNT(*) as post_count FROM posts WHERE user_id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$post_count = $stats['post_count'] ?? 0;
$stmt->close();

// Get follower count
$stmt = $db->prepare('SELECT COUNT(*) as follower_count FROM follows WHERE following_id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$follower_stats = $result->fetch_assoc();
$follower_count = $follower_stats['follower_count'] ?? 0;
$stmt->close();

// Get following count
$stmt = $db->prepare('SELECT COUNT(*) as following_count FROM follows WHERE follower_id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$following_stats = $result->fetch_assoc();
$following_count = $following_stats['following_count'] ?? 0;
$stmt->close();

// Fetch user's posts
$posts = [];
$stmt = $db->prepare('SELECT id, caption, media_path, media_type, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
}
$stmt->close();

$db->close();

if (!$user) {
    // User not found, maybe deleted?
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?php echo htmlspecialchars($user['username']); ?> - Codegram</title>
    <script src="theme.js"></script>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="theme.css">
  <link rel="stylesheet" href="profile.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a href="/Nathan/wd-project/" class="logo" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:8px;">
                <svg viewBox="0 0 24 24" class="camera" aria-hidden="true"><path d="M12 7a5 5 0 100 10 5 5 0 000-10z" fill="none" stroke="currentColor" stroke-width="1.2"/><rect x="2" y="3" width="20" height="18" rx="4" ry="4" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                <span class="brand">Codegram</span>
            </a>
            <div class="search">
                <input type="search" placeholder="Search" aria-label="Search" />
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
                <div class="profile-main">
        <div class="profile-container">
            <!-- Profile Header -->
            <header class="profile-header">
                <div class="profile-pic-container">
                    <?php if ($user['profile_pic']): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="profile-pic-large">
                    <?php else: ?>
                        <div class="profile-pic-large profile-pic-placeholder">
                            <span><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-info">
                    <div class="profile-info-row">
                        <h1 class="profile-username"><?php echo htmlspecialchars($user['username']); ?></h1>
                        <a href="edit_profile.php" class="btn-edit-profile" style="text-decoration:none;display:inline-flex;align-items:center">Edit profile</a>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="stat">
                            <span class="stat-count"><?php echo $post_count; ?></span>
                            <span class="stat-label">posts</span>
                        </div>
                        <div class="stat">
                            <span class="stat-count"><?php echo $follower_count; ?></span>
                            <span class="stat-label">followers</span>
                        </div>
                        <div class="stat">
                            <span class="stat-count"><?php echo $following_count; ?></span>
                            <span class="stat-label">following</span>
                        </div>
                    </div>
                    
                    <div class="profile-bio">
                        <p class="profile-name"><?php echo htmlspecialchars($user['display_name'] ?: $user['username']); ?></p>
                        <p class="bio-text"><?php echo !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : 'No bio yet.'; ?></p>
                    </div>
                    
                    <div class="profile-actions">
                        <button class="btn-action btn-follow">Follow</button>
                        <button class="btn-action btn-share">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="18" cy="5" r="3"></circle>
                                <circle cx="6" cy="12" r="3"></circle>
                                <circle cx="18" cy="19" r="3"></circle>
                                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                            </svg>
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- Tabs -->
            <div class="profile-tabs">
                <button class="tab-btn active" data-tab="posts">
                    <span class="tab-icon">▦</span>
                    <span class="tab-label">POSTS</span>
                </button>
                <button class="tab-btn" data-tab="codeas">
                    <span class="tab-icon">🎬</span>
                    <span class="tab-label">CODEAS</span>
                </button>
                <button class="tab-btn" data-tab="saved">
                    <span class="tab-icon">🔖</span>
                    <span class="tab-label">SAVED</span>
                </button>
            </div>
            
            <!-- Tab Content -->
            <div class="profile-content">
            <div class="tab-content" id="posts-tab">
                <?php if (empty($posts)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📷</div>
                        <h2>No Posts Yet</h2>
                        <p>When you share photos, they'll appear on your profile.</p>
                    </div>
                <?php else: ?>
                    <div class="posts-grid">
                        <?php foreach ($posts as $post): ?>
                            <div class="grid-item">
                                <?php if ($post['media_type'] === 'video'): ?>
                                    <video src="<?php echo htmlspecialchars($post['media_path']); ?>" class="grid-media" preload="metadata" muted loop></video>
                                    <div class="video-indicator">▶</div>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($post['media_path']); ?>" alt="Post" class="grid-media">
                                <?php endif; ?>
                                <div class="grid-overlay">
                                    <div class="grid-stats">
                                        <span>❤️ 0</span>
                                        <span>💬 0</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="codeas-tab" style="display:none;">
                <?php
                // Fetch user's codeas (short videos)
                $db = db_connect();
                $codea_stmt = $db->prepare('SELECT id, video_path, caption, created_at FROM codeas WHERE user_id = ? ORDER BY created_at DESC');
                $codea_stmt->bind_param('i', $user['id']);
                $codea_stmt->execute();
                $user_codeas = $codea_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $codea_stmt->close();
                $db->close();
                ?>
                <?php if (empty($user_codeas)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🎬</div>
                        <h2>No Codeas Yet</h2>
                        <p>Share short videos and they'll show up here.</p>
                    </div>
                <?php else: ?>
                    <div class="posts-grid">
                        <?php foreach ($user_codeas as $c): ?>
                            <div class="grid-item">
                                <video src="<?php echo htmlspecialchars($c['video_path']); ?>" class="grid-media" preload="metadata" muted loop></video>
                                <div class="video-indicator">▶</div>
                                <div class="grid-overlay">
                                    <div class="grid-stats">
                                        <span>❤️ <?php // placeholder ?></span>
                                        <span>💬 <?php // placeholder ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="saved-tab" style="display:none;">
                <?php
                // Fetch saved posts
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
                
                $stmt = $db->prepare('SELECT sp.post_id, sp.post_type, 
                                            CASE 
                                                WHEN sp.post_type = "post" THEN p.media_path
                                                WHEN sp.post_type = "codea" THEN c.video_path
                                            END as media_path,
                                            CASE 
                                                WHEN sp.post_type = "post" THEN p.media_type
                                                WHEN sp.post_type = "codea" THEN "video"
                                            END as media_type
                                      FROM saved_posts sp
                                      LEFT JOIN posts p ON sp.post_id = p.id AND sp.post_type = "post"
                                      LEFT JOIN codeas c ON sp.post_id = c.id AND sp.post_type = "codea"
                                      WHERE sp.user_id = ?
                                      ORDER BY sp.saved_at DESC');
                $stmt->bind_param('i', $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $saved_items = [];
                while ($row = $result->fetch_assoc()) {
                    if ($row['media_path']) {
                        $saved_items[] = $row;
                    }
                }
                $stmt->close();
                $db->close();
                ?>
                <?php if (empty($saved_items)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🔖</div>
                        <h2>Save</h2>
                        <p>Save photos and videos that you want to see again.</p>
                    </div>
                <?php else: ?>
                    <div class="posts-grid">
                        <?php foreach ($saved_items as $item): ?>
                            <div class="grid-item">
                                <?php if ($item['media_type'] === 'video'): ?>
                                    <video src="<?php echo htmlspecialchars($item['media_path']); ?>" class="grid-media" preload="metadata" muted loop></video>
                                    <div class="video-indicator">▶</div>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($item['media_path']); ?>" alt="Saved post" class="grid-media">
                                <?php endif; ?>
                                <div class="grid-overlay">
                                    <div class="grid-stats">
                                        <span>🔖 Saved</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            </div>
                </div>
            </section>
        </div>
    </main>
    
    <!-- Lightbox for viewing posts -->
    <div id="lightbox" class="lightbox" style="display:none;">
        <span id="close-lightbox" class="close-lightbox">&times;</span>
        <div id="lightbox-content"></div>
    </div>
    
    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                
                // Remove active class from all tabs
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
                
                // Add active to clicked tab
                this.classList.add('active');
                document.getElementById(tabName + '-tab').style.display = 'block';
            });
        });
        
        // Video playback on hover
        document.querySelectorAll('.grid-item').forEach(item => {
            const video = item.querySelector('video');
            if (video) {
                item.addEventListener('mouseenter', () => {
                    video.play();
                });
                item.addEventListener('mouseleave', () => {
                    video.pause();
                    video.currentTime = 0;
                });
                // Click to play/pause
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (video.paused) {
                        video.play();
                    } else {
                        video.pause();
                    }
                });
            }
        });
        
        // Lightbox for viewing posts/videos
        const lightbox = document.getElementById('lightbox');
        const lightboxContent = document.getElementById('lightbox-content');
        const closeLightbox = document.getElementById('close-lightbox');
        
        document.querySelectorAll('.grid-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const img = item.querySelector('img');
                const video = item.querySelector('video');
                
                if (img) {
                    lightboxContent.innerHTML = `<img src="${img.src}" style="max-width:90vw;max-height:90vh;object-fit:contain;">`;
                } else if (video) {
                    lightboxContent.innerHTML = `<video src="${video.src}" controls autoplay style="max-width:90vw;max-height:90vh;object-fit:contain;"></video>`;
                }
                
                lightbox.style.display = 'flex';
            });
        });
        
        closeLightbox.addEventListener('click', () => {
            lightbox.style.display = 'none';
            lightboxContent.innerHTML = '';
        });
        
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                lightbox.style.display = 'none';
                lightboxContent.innerHTML = '';
            }
        });
    </script>
</body>
</html>

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
                            <span class="stat-count"><strong><?php echo number_format($post_count); ?></strong></span>
                            <span class="stat-label">posts</span>
                        </div>
                        <div class="stat">
                            <span class="stat-count"><strong><?php echo number_format($follower_count); ?></strong></span>
                            <span class="stat-label">followers</span>
                        </div>
                        <div class="stat">
                            <span class="stat-count"><strong><?php echo number_format($following_count); ?></strong></span>
                            <span class="stat-label">following</span>
                        </div>
                    </div>
                    
                    <div class="profile-bio">
                        <p class="profile-name"><strong><?php echo htmlspecialchars($user['display_name'] ?: $user['username']); ?></strong></p>
                        <p class="bio-text"><?php echo !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : 'No bio yet.'; ?></p>
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
    
    <!-- Instagram-style Lightbox Modal -->
    <div id="lightbox" class="lightbox-modal">
        <div class="lightbox-overlay"></div>
        <button class="lightbox-close" id="close-lightbox">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        <div class="lightbox-content">
            <div class="lightbox-media-container" id="lightbox-media"></div>
        </div>
    </div>
    
    <script>
        // Instagram-style Lightbox
        const lightbox = document.getElementById('lightbox');
        const lightboxMedia = document.getElementById('lightbox-media');
        const closeLightboxBtn = document.getElementById('close-lightbox');
        
        function openLightbox(mediaElement) {
            const img = mediaElement.querySelector('img');
            const video = mediaElement.querySelector('video');
            
            if (img) {
                lightboxMedia.innerHTML = `<img src="${img.src}" class="lightbox-img" alt="Post">`;
            } else if (video) {
                lightboxMedia.innerHTML = `
                    <video src="${video.src}" class="lightbox-video" controls autoplay loop>
                        Your browser does not support the video tag.
                    </video>`;
            }
            
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeLightbox() {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
            
            // Stop and clean up video
            const video = lightboxMedia.querySelector('video');
            if (video) {
                video.pause();
                video.currentTime = 0;
            }
            
            // Clean up media after animation
            setTimeout(() => {
                lightboxMedia.innerHTML = '';
            }, 300);
        }
        
        // Close button
        closeLightboxBtn.addEventListener('click', closeLightbox);
        
        // Close on overlay click
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox || e.target.classList.contains('lightbox-overlay')) {
                closeLightbox();
            }
        });
        
        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && lightbox.classList.contains('active')) {
                closeLightbox();
            }
        });
        
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
                
                // Re-initialize hover effects and click handlers for newly visible items
                initializeGridItems();
            });
        });
        
        // Function to initialize grid items (hover effects and click handlers)
        function initializeGridItems() {
            // Remove old listeners by cloning (prevents duplicate listeners)
            document.querySelectorAll('.grid-item').forEach(item => {
                // Add click handler using event delegation approach
                item.style.cursor = 'pointer';
                
                // Video hover effects
                const video = item.querySelector('video');
                if (video) {
                    // Remove old listeners
                    const newItem = item.cloneNode(true);
                    item.parentNode.replaceChild(newItem, item);
                    
                    // Add new listeners to the fresh clone
                    const newVideo = newItem.querySelector('video');
                    newItem.addEventListener('mouseenter', () => {
                        newVideo.play().catch(e => {});
                    });
                    newItem.addEventListener('mouseleave', () => {
                        newVideo.pause();
                        newVideo.currentTime = 0;
                    });
                    newItem.addEventListener('click', (e) => {
                        e.preventDefault();
                        openLightbox(newItem);
                    });
                } else {
                    // For images, just add click handler
                    const newItem = item.cloneNode(true);
                    item.parentNode.replaceChild(newItem, item);
                    newItem.addEventListener('click', (e) => {
                        e.preventDefault();
                        openLightbox(newItem);
                    });
                }
            });
        }
        
        // Initialize on page load
        initializeGridItems();
    </script>
</body>
</html>

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
  <title><?php echo htmlspecialchars($user['username']); ?> • Codegram</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="profile.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <div class="logo">
                <svg viewBox="0 0 24 24" class="camera" aria-hidden="true"><path d="M12 7a5 5 0 100 10 5 5 0 000-10z" fill="none" stroke="currentColor" stroke-width="1.2"/><rect x="2" y="3" width="20" height="18" rx="4" ry="4" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                <span class="brand">Codegram</span>
            </div>
            <div class="search">
                <input type="search" placeholder="Search" aria-label="Search" />
            </div>
        </div>
    </header>

    <main class="main">
        <div class="app-inner">
            <!-- Left vertical nav -->
            <nav class="left-nav" aria-label="Main navigation">
                <ul>
                    <li class="nav-item" data-key="home">
                        <a href="index.php" style="display:flex;align-items:center;gap:10px;width:100%;background:none;border:0;padding:8px;border-radius:8px;cursor:pointer;color:#222;text-decoration:none" aria-label="Home">
                            <svg viewBox="0 0 24 24" class="icon"><path d="M3 11.5L12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V11.5z" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
                            <span class="label">Home</span>
                        </a>
                    </li>
                    <li class="nav-item" data-key="explore">
                        <button aria-label="Explore">
                            <svg viewBox="0 0 24 24" class="icon"><path d="M12 2l3.1 6.3L22 9.2l-5 4.9L18.2 22 12 18.3 5.8 22 7 14.1 2 9.2l6.9-0.9L12 2z" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                            <span class="label">Explore</span>
                        </button>
                    </li>
                    <li class="nav-item" data-key="reels">
                        <button aria-label="Reels">
                            <svg viewBox="0 0 24 24" class="icon"><rect x="3" y="5" width="18" height="14" rx="2" ry="2" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M10 9l5 3-5 3V9z" fill="currentColor"/></svg>
                            <span class="label">Reels</span>
                        </button>
                    </li>
                    <li class="nav-item" data-key="messages">
                        <button aria-label="Messages">
                            <svg viewBox="0 0 24 24" class="icon"><path d="M21 6.5L12 13 3 6.5" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M3 7v9a2 2 0 0 0 2 2h11" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                            <span class="label">Messages</span>
                        </button>
                    </li>
                    <li class="nav-item" data-key="notifications">
                        <button aria-label="Notifications">
                            <svg viewBox="0 0 24 24" class="icon"><path d="M15 17H9a3 3 0 0 0 6 0z" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                            <span class="label">Notifications</span>
                        </button>
                    </li>
                    <li class="nav-item" data-key="create">
                        <a href="upload_post.php" style="display:flex;align-items:center;gap:10px;width:100%;background:none;border:0;padding:8px;border-radius:8px;cursor:pointer;color:#222;text-decoration:none" aria-label="Create">
                            <svg viewBox="0 0 24 24" class="icon"><rect x="3" y="3" width="18" height="18" rx="2" ry="2" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M12 7v10M7 12h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            <span class="label">Create</span>
                        </a>
                    </li>
                    <li class="nav-item active" data-key="profile">
                        <a href="profile.php" style="display:flex;align-items:center;gap:10px;width:100%;background:none;border:0;padding:8px;border-radius:8px;cursor:pointer;color:#222;text-decoration:none" aria-label="Profile">
                            <?php if ($user['profile_pic']): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" class="nav-avatar" style="width:28px;height:28px;border-radius:50%;object-fit:cover;">
                            <?php else: ?>
                                <div class="avatar nav-avatar"></div>
                            <?php endif; ?>
                            <span class="label">Profile</span>
                        </a>
                    </li>
                    <li class="nav-item" data-key="logout">
                        <a href="logout.php" style="display:flex;align-items:center;gap:10px;width:100%;background:none;border:0;padding:8px;border-radius:8px;cursor:pointer;color:#222;text-decoration:none" aria-label="Logout">
                            <svg viewBox="0 0 24 24" class="icon"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span class="label">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>

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
                        <button class="btn-settings">⚙️</button>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="stat">
                            <span class="stat-count"><?php echo $post_count; ?></span>
                            <span class="stat-label">posts</span>
                        </div>
                        <div class="stat">
                            <span class="stat-count">0</span>
                            <span class="stat-label">followers</span>
                        </div>
                        <div class="stat">
                            <span class="stat-count">0</span>
                            <span class="stat-label">following</span>
                        </div>
                    </div>
                    
                    <div class="profile-bio">
                        <p class="profile-name"><?php echo htmlspecialchars($user['display_name'] ?: $user['username']); ?></p>
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
                <button class="tab-btn" data-tab="saved">
                    <span class="tab-icon">🔖</span>
                    <span class="tab-label">SAVED</span>
                </button>
            </div>
            
            <!-- Tab Content -->
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
                                    <video src="<?php echo htmlspecialchars($post['media_path']); ?>" class="grid-media"></video>
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
            
            <div class="tab-content" id="saved-tab" style="display:none;">
                <div class="empty-state">
                    <div class="empty-icon">🔖</div>
                    <h2>Save</h2>
                    <p>Save photos and videos that you want to see again.</p>
                </div>
            </div>
                </div>
            </section>
        </div>
    </main>
    
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
    </script>
</body>
</html>

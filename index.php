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
  </head>
  <body>
    <header class="topbar">
      <div class="topbar-inner">
        <div class="logo"><span class="brand">Codegram</span></div>
        <div class="search"><input placeholder="Search"></div>
        <div class="topbar-right"><a href="logout.php">Logout</a></div>
      </div>
    </header>

    <main class="main">
      <div class="app-inner">
        <?php include __DIR__ . '/index.html'; /* reuse left-nav markup from index.html for now */ ?>
        <section class="layout">
          <section class="feed">
            <div style="margin:12px 0;display:flex;justify-content:space-between;align-items:center">
              <div>Welcome, <?php echo htmlspecialchars($me['username']); ?></div>
              <div><a href="upload_post.php">Create post</a> • <a href="profile.php?u=<?php echo urlencode($me['username']); ?>">My profile</a></div>
            </div>
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
  </body>
</html>

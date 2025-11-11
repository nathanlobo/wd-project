<?php
require_once __DIR__ . '/includes/db.php';
session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = trim($_POST['caption'] ?? '');
    if (empty($_FILES['media']['tmp_name'])) {
        $errors[] = 'Please choose a media file (image or video).';
    }

    if (empty($errors)) {
        $file = $_FILES['media'];
        $tmp = $file['tmp_name'];
        $mime = mime_content_type($tmp);
        $type = strpos($mime, 'video/') === 0 ? 'video' : 'image';

        $targetDir = __DIR__ . '/Media/uploads/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = 'post_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $targetDir . $name;
        if (!move_uploaded_file($tmp, $dest)) {
            $errors[] = 'Failed to move uploaded file.';
        } else {
            $media_path = 'Media/uploads/' . $name;
            $db = db_connect();
            $stmt = $db->prepare('INSERT INTO posts (user_id, caption, media_path, media_type) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('isss', $_SESSION['user_id'], $caption, $media_path, $type);
            if ($stmt->execute()) {
                $stmt->close(); $db->close();
                header('Location: index.php'); exit;
            } else {
                $errors[] = 'Failed to save post.';
            }
        }
    }
}

// If we get here show a simple form with errors
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Create post - Codegram</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <main style="max-width:640px;margin:40px auto;padding:20px;background:#fff;border:1px solid #e6e6e6;border-radius:6px">
    <h2>Create post</h2>
    <?php if ($errors): ?><div style="color:#b00020"><?php echo implode('<br>', array_map('htmlspecialchars',$errors)); ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <label>Media (image or video)<br><input type="file" name="media" accept="image/*,video/*" required></label><br><br>
      <label>Caption<br><textarea name="caption" rows="3" style="width:100%"></textarea></label><br><br>
      <button type="submit">Upload</button>
    </form>
    <p><a href="/Nathan/wd-project/">Back to feed</a></p>
  </main>
</body>
</html>

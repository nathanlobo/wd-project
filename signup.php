<?php
require_once __DIR__ . '/includes/db.php';
session_start();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $display = trim($_POST['display_name'] ?? '');

    if (!$username || !$email || !$password) {
        $errors[] = 'Username, email and password are required.';
    }

    if (empty($errors)) {
        $db = db_connect();
        // check uniqueness
        $stmt = $db->prepare('SELECT id FROM users WHERE username=? OR email=? LIMIT 1');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $errors[] = 'Username or email already taken.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $profile_pic = null;
        // optional profile pic upload
        if (!empty($_FILES['profile_pic']['tmp_name'])) {
            $targetDir = __DIR__ . '/Media/profiles/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $fileName = 'profile_' . time() . '_' . preg_replace('/[^a-z0-9_\-\.]/i','', $username) . '.' . $ext;
            $dest = $targetDir . $fileName;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
                $profile_pic = 'Media/profiles/' . $fileName;
            }
        }

        $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, display_name, profile_pic) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $username, $email, $hash, $display, $profile_pic);
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $stmt->close();
            $db->close();
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Failed to create account.';
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Sign up - Codegram</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
  <main style="max-width:420px;margin:40px auto;padding:24px;background:#fff;border:1px solid #e6e6e6;border-radius:6px">
    <h2>Create account</h2>
    <?php if ($errors): ?>
      <div style="color:#b00020;margin-bottom:12px"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <label>Username<br><input name="username" required></label><br><br>
      <label>Email<br><input name="email" type="email" required></label><br><br>
      <label>Password<br><input name="password" type="password" required></label><br><br>
      <label>Display name (optional)<br><input name="display_name"></label><br><br>
      <label>Profile picture (optional)<br><input name="profile_pic" type="file" accept="image/*"></label><br><br>
      <button type="submit">Sign up</button>
    </form>
    <p>Already have an account? <a href="login.php">Log in</a></p>
  </main>
</body>
</html>

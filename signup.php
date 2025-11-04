<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mail_config.php';
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

    // Detect if email verification columns exist
    $hasVerified = false;
    if ($result = $db->query("SHOW COLUMNS FROM users LIKE 'email_verified'")) {
      $hasVerified = $result->num_rows > 0;
      $result->close();
    }

    if ($hasVerified) {
      $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, display_name, profile_pic, email_verified) VALUES (?, ?, ?, ?, ?, 0)');
      $stmt->bind_param('sssss', $username, $email, $hash, $display, $profile_pic);
    } else {
      $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, display_name, profile_pic) VALUES (?, ?, ?, ?, ?)');
      $stmt->bind_param('sssss', $username, $email, $hash, $display, $profile_pic);
    }

    if ($stmt && $stmt->execute()) {
      $newUserId = $stmt->insert_id;
      $stmt->close();

      // If verification schema is present, go through OTP verification
      $hasCodesTable = false;
      if ($res2 = $db->query("SHOW TABLES LIKE 'email_verification_codes'")) {
        $hasCodesTable = $res2->num_rows > 0;
        $res2->close();
      }

      if ($hasVerified && $hasCodesTable) {
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes
        $stmt2 = $db->prepare('DELETE FROM email_verification_codes WHERE user_id = ?');
        $stmt2->bind_param('i', $newUserId);
        $stmt2->execute();
        $stmt2->close();
        $stmt2 = $db->prepare('INSERT INTO email_verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)');
        $stmt2->bind_param('iss', $newUserId, $code, $expires_at);
        $stmt2->execute();
        $stmt2->close();

        // Send email with OTP
        $displayName = $display ?: $username;
        send_otp_email($email, $displayName, $code);

        // Store pending verification session
        $_SESSION['pending_user_id'] = $newUserId;
        $_SESSION['pending_user_email'] = $email;
        $_SESSION['pending_user_name'] = $displayName;

        $db->close();
        header('Location: verify_email.php');
        exit;
      } else {
        // Fallback: log them in immediately (no verification schema)
        $_SESSION['user_id'] = $newUserId;
        $db->close();
        header('Location: profile.php');
        exit;
      }
    } else {
      if ($stmt) $stmt->close();
      $db->close();
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
  <link rel="stylesheet" href="auth.css">
  <link href="https://fonts.googleapis.com/css2?family=Grand+Hotel&display=swap" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="login-container">
        <h2>CodeGram</h2>
        <?php if ($errors): ?>
          <div class="error-message"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
          <div class="input-container">
            <input type="email" name="email" required placeholder=" ">
            <label for="email">Email</label>
          </div>
          <div class="input-container">
            <input type="text" name="username" required placeholder=" ">
            <label for="username">Username</label>
          </div>
          <div class="input-container">
            <input type="text" name="display_name" required placeholder=" ">
            <label for="display_name">Full name</label>
          </div>
          <div class="input-container">
            <input type="password" name="password" required placeholder=" ">
            <label for="password">Enter password</label>
          </div>
            <button type="submit">Sign up</button>
            <div class="signup-option">
                Already have an account? <a href="login.php" class="signup-link">Log in</a>
            </div>
        </form>
    </div>
</body>
</html>

<?php
require_once __DIR__ . '/includes/db.php';
session_start();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['user'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!$user || !$pass) $errors[] = 'Enter username/email and password.';

    if (empty($errors)) {
        $db = db_connect();
  $stmt = $db->prepare('SELECT id, password_hash, email, username, display_name, IFNULL(email_verified,1) as email_verified FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->bind_param('ss', $user, $user);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
      if (password_verify($pass, $row['password_hash'])) {
        if (!empty($row['email_verified'])) {
          $_SESSION['user_id'] = $row['id'];
          $stmt->close(); $db->close();
          header('Location: profile.php'); exit;
        } else {
          // Unverified: send to verification page
          $_SESSION['pending_user_id'] = $row['id'];
          $_SESSION['pending_user_email'] = $row['email'];
          $_SESSION['pending_user_name'] = $row['display_name'] ?: $row['username'];
          $stmt->close(); $db->close();
          header('Location: verify_email.php?resend=1'); exit;
        }
            } else {
                $errors[] = 'Invalid credentials.';
            }
        } else {
            $errors[] = 'Invalid credentials.';
        }
        $stmt->close();
        $db->close();
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login - Codegram</title>
  <link rel="stylesheet" href="auth.css">
  <link href="https://fonts.googleapis.com/css2?family=Grand+Hotel&display=swap" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="login-container">
        <h2>CodeGram</h2>
        <?php if ($errors): ?>
          <div class="error-message"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="input-container">
            <input type="text" name="user" required placeholder=" ">
            <label for="username">Username or email</label>
          </div>
          <div class="input-container">
            <input type="password" id="password" name="password" required placeholder=" ">
            <label for="password">Password</label>
          </div>
          <button type="submit">Login</button>
          <div class="password-options">
              <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
          </div>
          <div class="signup-option">
              Don't have an account? <a href="signup.php" class="signup-link">Register</a>
          </div>
        </form>
    </div>
</body>
</html>

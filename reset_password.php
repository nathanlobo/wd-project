<?php
require_once __DIR__ . '/includes/db.php';
session_start();

$errors = [];
$success = false;
$token = $_GET['token'] ?? '';
$valid_token = false;
$user_id = null;

// Verify token
if ($token) {
    $db = db_connect();
    $stmt = $db->prepare('SELECT user_id, expires_at FROM password_reset_tokens WHERE token = ? LIMIT 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();
    $token_data = $res->fetch_assoc();
    $stmt->close();
    
    if ($token_data) {
        // Check if token is expired
        if (strtotime($token_data['expires_at']) > time()) {
            $valid_token = true;
            $user_id = $token_data['user_id'];
        } else {
            $errors[] = 'This password reset link has expired. Please request a new one.';
        }
    } else {
        $errors[] = 'Invalid password reset link.';
    }
    
    $db->close();
} else {
    $errors[] = 'No reset token provided.';
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!$password || !$confirm_password) {
        $errors[] = 'Please fill in all fields.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        $db = db_connect();
        
        // Update password
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->bind_param('si', $hash, $user_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Delete the used token
            $stmt = $db->prepare('DELETE FROM password_reset_tokens WHERE token = ?');
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $stmt->close();
            
            $success = true;
        } else {
            $errors[] = 'Failed to reset password. Please try again.';
            $stmt->close();
        }
        
        $db->close();
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Reset Password - Codegram</title>
  <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="login-container">
        <h2>Reset Password</h2>
        
        <?php if ($success): ?>
          <div class="success-message">
              Your password has been reset successfully!
          </div>
          <p style="text-align: center; margin-top: 20px;">
              <a href="login.php" class="signup-link">Go to Login</a>
          </p>
        <?php elseif ($valid_token): ?>
          <p style="color: #666; margin-bottom: 20px;">Enter your new password below.</p>
          
          <?php if ($errors): ?>
            <div class="error-message"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
          <?php endif; ?>
          
          <form method="post">
              <div class="input-container">
                  <input type="password" name="password" required placeholder=" " minlength="6">
                  <label for="password">New Password</label>
              </div>
              <div class="input-container">
                  <input type="password" name="confirm_password" required placeholder=" " minlength="6">
                  <label for="confirm_password">Confirm Password</label>
              </div>
              <button type="submit">Reset Password</button>
          </form>
        <?php else: ?>
          <?php if ($errors): ?>
            <div class="error-message"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
          <?php endif; ?>
          <p style="text-align: center; margin-top: 20px;">
              <a href="forgot_password.php" class="signup-link">Request a new reset link</a>
          </p>
        <?php endif; ?>
    </div>
</body>
</html>

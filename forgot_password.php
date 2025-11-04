<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mail_config.php';
session_start();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (!$email) {
        $errors[] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (empty($errors)) {
        $db = db_connect();
        
        // Check if email exists
        $stmt = $db->prepare('SELECT id, username, display_name FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();
        
        if ($user) {
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
            
            // Delete any existing tokens for this user
            $stmt = $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?');
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $stmt->close();
            
            // Insert new token
            $stmt = $db->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
            $stmt->bind_param('iss', $user['id'], $token, $expires_at);
            
            if ($stmt->execute()) {
                $stmt->close();
                
                // Create reset link
                $reset_link = BASE_URL . '/reset_password.php?token=' . $token;
                
                // Send email
                $user_name = $user['display_name'] ?: $user['username'];
                if (send_password_reset_email($email, $user_name, $reset_link)) {
                    $success = true;
                } else {
                    $errors[] = 'Failed to send email. Please try again later.';
                }
            } else {
                $errors[] = 'An error occurred. Please try again.';
                $stmt->close();
            }
        } else {
            // Don't reveal if email exists or not (security practice)
            // Show success message anyway
            $success = true;
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
  <title>Forgot Password - Codegram</title>
  <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="login-container">
        <h2>Forgot Password</h2>
        <p style="color: #666; margin-bottom: 20px;">Enter your email address and we'll send you a link to reset your password.</p>
        
        <?php if ($success): ?>
          <div class="success-message">
              If an account exists with that email, you will receive a password reset link shortly. Please check your inbox.
          </div>
          <p style="text-align: center; margin-top: 20px;">
              <a href="login.php" class="signup-link">Back to Login</a>
          </p>
        <?php else: ?>
          <?php if ($errors): ?>
            <div class="error-message"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
          <?php endif; ?>
          
          <form method="post">
              <div class="input-container">
                  <input type="email" name="email" required placeholder=" ">
                  <label for="email">Email Address</label>
              </div>
              <button type="submit">Send Reset Link</button>
              <div class="signup-option">
                  Remember your password? <a href="login.php" class="signup-link">Log in</a>
              </div>
          </form>
        <?php endif; ?>
    </div>
</body>
</html>

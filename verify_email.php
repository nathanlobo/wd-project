<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mail_config.php';
session_start();

$errors = [];
$info = '';
$success = false;

// Determine user to verify
$user_id = $_SESSION['pending_user_id'] ?? null;
$email = $_SESSION['pending_user_email'] ?? null;
$name = $_SESSION['pending_user_name'] ?? 'there';

if (!$user_id) {
    // Also allow verify via querystring if provided (fallback)
    $user_id = isset($_GET['uid']) ? intval($_GET['uid']) : null;
}

if (!$user_id) {
    header('Location: signup.php');
    exit;
}

$db = db_connect();

// Fetch email if missing
if (!$email) {
    $stmt = $db->prepare('SELECT email, display_name, username, email_verified FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($u = $res->fetch_assoc()) {
        $email = $u['email'];
        $name = $u['display_name'] ?: $u['username'];
        if (!empty($u['email_verified'])) {
            $_SESSION['user_id'] = $user_id;
            header('Location: profile.php');
            exit;
        }
    }
    $stmt->close();
}

// Resend flow
if (isset($_GET['resend'])) {
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes

    // Remove previous codes
    $stmt = $db->prepare('DELETE FROM email_verification_codes WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();

    // Insert new
    $stmt = $db->prepare('INSERT INTO email_verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)');
    $stmt->bind_param('iss', $user_id, $code, $expires_at);
    $stmt->execute();
    $stmt->close();

    if (send_otp_email($email, $name, $code)) {
        $info = 'A new verification code has been sent to ' . htmlspecialchars($email) . '.';
    } else {
        $errors[] = 'Failed to send verification email. Please try again later.';
    }
}

// Verify flow
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    if (!$code) {
        $errors[] = 'Please enter the verification code.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare('SELECT id, expires_at FROM email_verification_codes WHERE user_id = ? AND code = ? LIMIT 1');
        $stmt->bind_param('is', $user_id, $code);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if ($row) {
            if (strtotime($row['expires_at']) >= time()) {
                // Mark verified
                $stmt = $db->prepare('UPDATE users SET email_verified = 1, email_verified_at = NOW() WHERE id = ?');
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $stmt->close();
                // Clean code
                $stmt = $db->prepare('DELETE FROM email_verification_codes WHERE user_id = ?');
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $stmt->close();

                $_SESSION['user_id'] = $user_id; // log them in
                unset($_SESSION['pending_user_id'], $_SESSION['pending_user_email'], $_SESSION['pending_user_name']);
                $success = true;
            } else {
                $errors[] = 'This code has expired. Please request a new one.';
            }
        } else {
            $errors[] = 'Invalid code. Please check and try again.';
        }
    }
}

$db->close();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Verify Email - CodeGram</title>
  <link rel="stylesheet" href="auth.css">
</head>
<body>
  <div class="login-container">
    <h2>Verify your email</h2>
    <?php if ($success): ?>
      <div class="success-message">Your email has been verified. Redirecting to your profile...</div>
      <script>setTimeout(function(){ window.location.href = 'profile.php'; }, 1200);</script>
    <?php else: ?>
      <?php if ($info): ?><div class="success-message"><?php echo $info; ?></div><?php endif; ?>
      <?php if ($errors): ?><div class="error-message"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div><?php endif; ?>
      <p style="color:#666;margin-bottom:16px">We sent a 6-digit code to <strong><?php echo htmlspecialchars($email); ?></strong>. Enter it below to verify your account.</p>
      <form method="post">
        <div class="input-container">
          <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required placeholder=" ">
          <label for="code">6-digit code</label>
        </div>
        <button type="submit">Verify</button>
        <div class="signup-option" style="margin-top:14px">
          Didn't get it? <a class="signup-link" href="verify_email.php?resend=1">Resend code</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>

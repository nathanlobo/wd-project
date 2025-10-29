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
        $stmt = $db->prepare('SELECT id, password_hash FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->bind_param('ss', $user, $user);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if (password_verify($pass, $row['password_hash'])) {
                $_SESSION['user_id'] = $row['id'];
                $stmt->close(); $db->close();
                header('Location: index.php'); exit;
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
  <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
  <main style="max-width:420px;margin:40px auto;padding:24px;background:#fff;border:1px solid #e6e6e6;border-radius:6px">
    <h2>Log in</h2>
    <?php if ($errors): ?>
      <div style="color:#b00020;margin-bottom:12px"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
    <?php endif; ?>
    <form method="post">
      <label>Username or email<br><input name="user" required></label><br><br>
      <label>Password<br><input name="password" type="password" required></label><br><br>
      <button type="submit">Log in</button>
    </form>
    <p>Don't have an account? <a href="signup.php">Sign up</a></p>
  </main>
</body>
</html>

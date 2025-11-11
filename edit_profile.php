<?php
session_start();
require_once __DIR__ . '/includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = db_connect();
$errors = [];
$success = false;

// Fetch current user data
$stmt = $db->prepare('SELECT id, username, email, display_name, profile_pic, bio FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $current_pic = $user['profile_pic'];
    
    // Validation
    if (!$username) {
        $errors[] = 'Username is required.';
    }
    
    // Check username uniqueness if changed
    if ($username !== $user['username']) {
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $stmt->bind_param('si', $username, $_SESSION['user_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $errors[] = 'Username already taken.';
        }
        $stmt->close();
    }
    
    // Handle profile picture upload
    $new_pic = $current_pic;
    if (!empty($_FILES['profile_pic']['tmp_name'])) {
        $targetDir = __DIR__ . '/Media/profiles/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Only JPG, PNG, and GIF images are allowed.';
        } else {
            $fileName = 'profile_' . time() . '_' . preg_replace('/[^a-z0-9_\-\.]/i', '', $username) . '.' . $ext;
            $dest = $targetDir . $fileName;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
                // Delete old profile pic if exists
                if ($current_pic && file_exists(__DIR__ . '/' . $current_pic)) {
                    unlink(__DIR__ . '/' . $current_pic);
                }
                $new_pic = 'Media/profiles/' . $fileName;
            } else {
                $errors[] = 'Failed to upload profile picture.';
            }
        }
    }
    
    // Handle profile pic removal
    if (isset($_POST['remove_pic']) && $current_pic) {
        if (file_exists(__DIR__ . '/' . $current_pic)) {
            unlink(__DIR__ . '/' . $current_pic);
        }
        $new_pic = null;
    }
    
    // Update database if no errors
    if (empty($errors)) {
        $stmt = $db->prepare('UPDATE users SET username = ?, display_name = ?, bio = ?, profile_pic = ? WHERE id = ?');
        $stmt->bind_param('ssssi', $username, $display_name, $bio, $new_pic, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $stmt->close();
            $db->close();
            header('Location: profile.php');
            exit;
        } else {
            $errors[] = 'Failed to update profile.';
        }
        $stmt->close();
    }
}

$db->close();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Edit Profile - Codegram</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="profile.css">
  <style>
    .edit-profile-container {
        max-width: 700px;
        margin: 40px auto;
        background: white;
        border: 1px solid #dbdbdb;
        border-radius: 8px;
    }
    
    .edit-header {
        padding: 20px;
        border-bottom: 1px solid #dbdbdb;
        text-align: center;
        font-weight: 600;
        font-size: 18px;
    }
    
    .edit-form {
        padding: 20px;
    }
    
    .form-group {
        display: flex;
        align-items: flex-start;
        margin-bottom: 25px;
        gap: 30px;
    }
    
    .form-label {
        flex: 0 0 150px;
        text-align: right;
        padding-top: 8px;
        font-weight: 600;
        color: #262626;
    }
    
    .form-input-wrapper {
        flex: 1;
    }
    
    .form-input,
    .form-textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #dbdbdb;
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
    }
    
    .form-textarea {
        resize: vertical;
        min-height: 80px;
    }
    
    .form-input:focus,
    .form-textarea:focus {
        outline: none;
        border-color: #0095f6;
    }
    
    .form-hint {
        font-size: 12px;
        color: #8e8e8e;
        margin-top: 6px;
    }
    
    .profile-pic-edit {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .current-pic {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #dbdbdb;
    }
    
    .pic-placeholder {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(45deg, #405de6, #833ab4, #e1306c);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        font-weight: 600;
    }
    
    .pic-controls {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .file-input-wrapper {
        position: relative;
        display: inline-block;
    }
    
    .file-input-wrapper input[type="file"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .file-input-label {
        display: inline-block;
        padding: 6px 16px;
        background: #0095f6;
        color: white;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: background 0.2s;
    }
    
    .file-input-label:hover {
        background: #0082d9;
    }
    
    .btn-remove-pic {
        padding: 6px 16px;
        background: transparent;
        color: #ed4956;
        border: none;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        padding: 20px;
        border-top: 1px solid #dbdbdb;
    }
    
    .btn-cancel,
    .btn-save {
        padding: 8px 24px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-cancel {
        background: transparent;
        border: 1px solid #dbdbdb;
        color: #262626;
    }
    
    .btn-cancel:hover {
        background: #fafafa;
    }
    
    .btn-save {
        background: #0095f6;
        border: none;
        color: white;
    }
    
    .btn-save:hover {
        background: #0082d9;
    }
    
    .success-banner {
        background: #d4edda;
        color: #155724;
        padding: 12px 20px;
        border-bottom: 1px solid #c3e6cb;
        text-align: center;
        font-size: 14px;
    }
    
    .error-banner {
        background: #f8d7da;
        color: #b00020;
        padding: 12px 20px;
        border-bottom: 1px solid #f5c6cb;
        font-size: 14px;
    }
    
    @media (max-width: 735px) {
        .form-group {
            flex-direction: column;
            gap: 10px;
        }
        
        .form-label {
            text-align: left;
            flex: none;
        }
        
        .edit-profile-container {
            border: none;
            border-radius: 0;
        }
    }
  </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-inner">
            <a class="logo" href="/Nathan/wd-project/">
                <span class="brand">Codegram</span>
            </a>
            <div class="icons">
                <a href="profile" class="icon" title="Profile">👤</a>
                <a href="logout.php" class="icon" title="Logout">🚪</a>
            </div>
        </div>
    </div>

    <main class="profile-main">
        <div class="edit-profile-container">
            <div class="edit-header">
                Edit Profile
            </div>
            
            <?php if ($success): ?>
                <div class="success-banner">
                    ✓ Profile updated successfully! <a href="profile" style="color:#155724;text-decoration:underline">View profile</a>
                </div>
            <?php endif; ?>
            
            <?php if ($errors): ?>
                <div class="error-banner">
                    <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data" class="edit-form">
                <div class="form-group">
                    <label class="form-label">Profile Photo</label>
                    <div class="form-input-wrapper">
                        <div class="profile-pic-edit">
                            <?php if ($user['profile_pic']): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" class="current-pic">
                            <?php else: ?>
                                <div class="pic-placeholder">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div class="pic-controls">
                                <div class="file-input-wrapper">
                                    <input type="file" name="profile_pic" id="profile_pic" accept="image/*">
                                    <label for="profile_pic" class="file-input-label">Change Photo</label>
                                </div>
                                <?php if ($user['profile_pic']): ?>
                                    <button type="submit" name="remove_pic" class="btn-remove-pic" onclick="return confirm('Remove profile picture?')">Remove Photo</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <div class="form-input-wrapper">
                        <input type="text" name="username" id="username" class="form-input" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        <div class="form-hint">Your unique username</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="display_name">Name</label>
                    <div class="form-input-wrapper">
                        <input type="text" name="display_name" id="display_name" class="form-input" value="<?php echo htmlspecialchars($user['display_name'] ?? ''); ?>">
                        <div class="form-hint">Your display name (can be your real name)</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="bio">Bio</label>
                    <div class="form-input-wrapper">
                        <textarea name="bio" id="bio" class="form-textarea" maxlength="150"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        <div class="form-hint">Max 150 characters</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <div class="form-input-wrapper">
                        <input type="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <div class="form-hint">Email cannot be changed</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="profile" class="btn-cancel" style="text-decoration:none;display:inline-flex;align-items:center">Cancel</a>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>

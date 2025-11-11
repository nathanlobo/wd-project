<?php
require_once __DIR__ . '/includes/db.php';
session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$current_user = current_user();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = trim($_POST['caption'] ?? '');
    $post_type = $_POST['post_type'] ?? 'post'; // 'post' or 'codea'
    
    if (empty($_FILES['media']['tmp_name'])) {
        $errors[] = 'Please choose a media file (image or video).';
    }

    if (empty($errors)) {
        $file = $_FILES['media'];
        $tmp = $file['tmp_name'];
        $mime = mime_content_type($tmp);
        $type = strpos($mime, 'video/') === 0 ? 'video' : 'image';

        $targetDir = __DIR__ . '/Media/' . ($post_type === 'codea' ? 'videos/' : 'uploads/');
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = $post_type . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $targetDir . $name;
        
        if (!move_uploaded_file($tmp, $dest)) {
            $errors[] = 'Failed to move uploaded file.';
        } else {
            $media_path = 'Media/' . ($post_type === 'codea' ? 'videos/' : 'uploads/') . $name;
            $db = db_connect();
            
            if ($post_type === 'codea' && $type === 'video') {
                // Insert as Codea
                $stmt = $db->prepare('INSERT INTO codeas (user_id, video_path, caption) VALUES (?, ?, ?)');
                $stmt->bind_param('iss', $_SESSION['user_id'], $media_path, $caption);
            } else {
                // Insert as regular post
                $stmt = $db->prepare('INSERT INTO posts (user_id, caption, media_path, media_type) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('isss', $_SESSION['user_id'], $caption, $media_path, $type);
            }
            
            if ($stmt->execute()) {
                $success = true;
                $stmt->close();
                $db->close();
            } else {
                $errors[] = 'Failed to save post.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create - Codegram</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    * {
      box-sizing: border-box;
    }
    
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      padding: 20px;
        margin: 0;
    }
    
      .create-topbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 60px;
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      }
    
      .create-topbar .logo {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 24px;
        font-weight: 700;
        color: #667eea;
      }
    
      .create-topbar svg {
        width: 32px;
        height: 32px;
        stroke: #667eea;
      }
    
    .create-container {
      background: white;
      margin: 24px auto;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      max-width: 900px;
      width: 100%;
      overflow: hidden;
      animation: slideIn 0.4s ease;
    }
    
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .create-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 30px;
      text-align: center;
    }
    
    .create-header h1 {
      margin: 0 0 10px 0;
      font-size: 32px;
      font-weight: 700;
    }
    
    .create-header p {
      margin: 0;
      opacity: 0.9;
      font-size: 16px;
    }
    
    .create-body {
      padding: 40px;
    }
    
    .post-type-selector {
      display: flex;
      gap: 16px;
      margin-bottom: 30px;
    }
    
    .type-option {
      flex: 1;
      padding: 20px;
      border: 2px solid #e6e6e6;
      border-radius: 12px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .type-option:hover {
      border-color: #667eea;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
    }
    
    .type-option.active {
      border-color: #667eea;
      background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    }
    
    .type-option .icon {
      font-size: 48px;
      margin-bottom: 10px;
    }
    
    .type-option .label {
      font-weight: 600;
      font-size: 16px;
      display: block;
      margin-bottom: 5px;
    }
    
    .type-option .desc {
      font-size: 13px;
      color: #666;
    }
    
    .upload-zone {
      border: 3px dashed #e6e6e6;
      border-radius: 16px;
      padding: 60px 20px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
      margin-bottom: 24px;
      position: relative;
      overflow: hidden;
    }
    
    .upload-zone:hover {
      border-color: #667eea;
      background: rgba(102, 126, 234, 0.05);
    }
    
    .upload-zone.drag-over {
      border-color: #667eea;
      background: rgba(102, 126, 234, 0.1);
    }
    
    .upload-zone input[type="file"] {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      opacity: 0;
      cursor: pointer;
    }
    
    .upload-icon {
      font-size: 64px;
      margin-bottom: 16px;
    }
    
    .upload-text {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 8px;
      color: #333;
    }
    
    .upload-hint {
      font-size: 14px;
      color: #666;
    }
    
    .preview-container {
      display: none;
      margin-bottom: 24px;
      border-radius: 16px;
      overflow: hidden;
      position: relative;
      background: #000;
    }
    
    .preview-container.active {
      display: block;
    }
    
    .preview-media {
      width: 100%;
      max-height: 400px;
      object-fit: contain;
    }
    
    .preview-remove {
      position: absolute;
      top: 12px;
      right: 12px;
      background: rgba(0,0,0,0.7);
      color: white;
      border: none;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      font-size: 20px;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .preview-remove:hover {
      background: rgba(0,0,0,0.9);
      transform: rotate(90deg);
    }
    
    .form-group {
      margin-bottom: 24px;
    }
    
    .form-label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      color: #333;
      font-size: 15px;
    }
    
    .form-textarea {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e6e6e6;
      border-radius: 12px;
      font-size: 15px;
      font-family: inherit;
      resize: vertical;
      transition: border-color 0.3s;
    }
    
    .form-textarea:focus {
      outline: none;
      border-color: #667eea;
    }
    
    .form-actions {
      display: flex;
      gap: 12px;
    }
    
    .btn {
      flex: 1;
      padding: 16px;
      border: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }
    
    .btn-primary:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }
    
    .btn-secondary {
      background: #f0f0f0;
      color: #333;
    }
    
    .btn-secondary:hover {
      background: #e0e0e0;
    }
    
    .alert {
      padding: 16px;
      border-radius: 12px;
      margin-bottom: 20px;
      animation: slideIn 0.3s ease;
    }
    
    .alert-error {
      background: #fee;
      color: #c00;
      border: 1px solid #fcc;
    }
    
    .alert-success {
      background: #efe;
      color: #060;
      border: 1px solid #cfc;
    }
    
    .success-animation {
      text-align: center;
      padding: 40px;
    }
    
    .success-icon {
      font-size: 80px;
      animation: bounce 0.6s ease;
    }
    
    @keyframes bounce {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.2); }
    }
    
    .success-title {
      font-size: 24px;
      font-weight: 700;
      margin: 20px 0 10px 0;
    }
    
    .success-text {
      color: #666;
      margin-bottom: 30px;
    }
  </style>
    /* Standard content area width inside layout */
    .create-wrapper {
      max-width: 760px;
      width: 100%;
    }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="logo">
        <svg viewBox="0 0 24 24" class="camera" aria-hidden="true"><path d="M12 7a5 5 0 100 10 5 5 0 000-10z" fill="none" stroke="currentColor" stroke-width="1.2"/><rect x="2" y="3" width="20" height="18" rx="4" ry="4" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
        <span class="brand">Codegram</span>
      </div>
      <div style="flex:1"></div>
    </div>
  </header>

  <main class="main">
    <div class="app-inner">
      <?php include __DIR__ . '/left-nav.php'; ?>
      <section class="layout">
        <div class="create-wrapper">
          <div class="create-container">
    <div class="create-header">
      <h1>✨ Create Something Amazing</h1>
      <p>Share your moments with the world</p>
    </div>
    
    <div class="create-body">
      <?php if ($success): ?>
        <div class="success-animation">
          <div class="success-icon">🎉</div>
          <div class="success-title">Posted Successfully!</div>
          <div class="success-text">Your content is now live and ready to be discovered.</div>
          <div class="form-actions">
            <a href="/Nathan/wd-project/" class="btn btn-primary" style="text-decoration:none;display:block">View Feed</a>
            <button onclick="location.reload()" class="btn btn-secondary">Create Another</button>
          </div>
        </div>
      <?php else: ?>
        <?php if ($errors): ?>
          <div class="alert alert-error">
            <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
          </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data" id="createForm">
          <div class="post-type-selector">
            <label class="type-option active" data-type="post">
              <input type="radio" name="post_type" value="post" checked style="display:none">
              <div class="icon">📸</div>
              <span class="label">Post</span>
              <span class="desc">Share photos & videos</span>
            </label>
            <label class="type-option" data-type="codea">
              <input type="radio" name="post_type" value="codea" style="display:none">
              <div class="icon">🎬</div>
              <span class="label">Codea</span>
              <span class="desc">Short video content</span>
            </label>
          </div>
          
          <div class="upload-zone" id="uploadZone">
            <input type="file" name="media" id="mediaInput" accept="image/*,video/*" required>
            <div class="upload-icon">☁️</div>
            <div class="upload-text">Drop your file here or click to browse</div>
            <div class="upload-hint">Supports images and videos</div>
          </div>
          
          <div class="preview-container" id="previewContainer">
            <img id="previewImage" class="preview-media" style="display:none">
            <video id="previewVideo" class="preview-media" controls style="display:none"></video>
            <button type="button" class="preview-remove" onclick="removePreview()">×</button>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="caption">Caption</label>
            <textarea name="caption" id="caption" rows="4" class="form-textarea" placeholder="Write a caption..."></textarea>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Publish</button>
            <a href="/Nathan/wd-project/" class="btn btn-secondary" style="text-decoration:none;display:flex;align-items:center;justify-content:center">Cancel</a>
          </div>
        </form>
      <?php endif; ?>
    </div>
          </div>
        </div>
      </section>
    </div>
  </main>

  <script>
    const mediaInput = document.getElementById('mediaInput');
    const uploadZone = document.getElementById('uploadZone');
    const previewContainer = document.getElementById('previewContainer');
    const previewImage = document.getElementById('previewImage');
    const previewVideo = document.getElementById('previewVideo');
    const submitBtn = document.getElementById('submitBtn');
    
    // Type selector
    document.querySelectorAll('.type-option').forEach(opt => {
      opt.addEventListener('click', () => {
        document.querySelectorAll('.type-option').forEach(o => o.classList.remove('active'));
        opt.classList.add('active');
      });
    });
    
    // File input change
    mediaInput.addEventListener('change', handleFileSelect);
    
    // Drag and drop
    uploadZone.addEventListener('dragover', (e) => {
      e.preventDefault();
      uploadZone.classList.add('drag-over');
    });
    
    uploadZone.addEventListener('dragleave', () => {
      uploadZone.classList.remove('drag-over');
    });
    
    uploadZone.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadZone.classList.remove('drag-over');
      const files = e.dataTransfer.files;
      if (files.length) {
        mediaInput.files = files;
        handleFileSelect();
      }
    });
    
    function handleFileSelect() {
      const file = mediaInput.files[0];
      if (!file) return;
      
      const reader = new FileReader();
      reader.onload = (e) => {
        const isVideo = file.type.startsWith('video/');
        
        if (isVideo) {
          previewVideo.src = e.target.result;
          previewVideo.style.display = 'block';
          previewImage.style.display = 'none';
        } else {
          previewImage.src = e.target.result;
          previewImage.style.display = 'block';
          previewVideo.style.display = 'none';
        }
        
        uploadZone.style.display = 'none';
        previewContainer.classList.add('active');
        submitBtn.disabled = false;
      };
      reader.readAsDataURL(file);
    }
    
    function removePreview() {
      mediaInput.value = '';
      previewImage.src = '';
      previewVideo.src = '';
      uploadZone.style.display = 'block';
      previewContainer.classList.remove('active');
      submitBtn.disabled = true;
    }
  </script>
</body>
</html>

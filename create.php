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
  <script src="theme.js"></script>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="theme.css">
  <style>
    .create-container {
      max-width: 600px;
      margin: 24px auto;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 8px;
      overflow: hidden;
    }
    
    .create-header {
      padding: 20px 24px;
      border-bottom: 1px solid var(--border);
    }
    
    .create-header h2 {
      margin: 0;
      font-size: 20px;
      font-weight: 600;
    }
    
    .create-body {
      padding: 24px;
    }
    
    .post-type-selector {
      display: flex;
      gap: 12px;
      margin-bottom: 24px;
    }
    
    .type-option {
      flex: 1;
      padding: 16px;
      border: 2px solid var(--border);
      border-radius: 8px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
      background: var(--card);
    }
    
    .type-option:hover {
      border-color: #4949FF;
    }
    
    .type-option.active {
      border-color: #0000FF;
      background: linear-gradient(135deg, rgba(0, 0, 255, 0.05) 0%, rgba(31, 31, 255, 0.05) 20%, rgba(73, 121, 255, 0.05) 40%, rgba(120, 121, 255, 0.05) 60%, rgba(163, 163, 255, 0.05) 80%, rgba(191, 191, 255, 0.05) 100%);
    }
    
    .type-option .icon {
      font-size: 36px;
      margin-bottom: 8px;
    }
    
    .type-option .label {
      font-weight: 600;
      font-size: 14px;
      display: block;
      margin-bottom: 4px;
    }
    
    .type-option .desc {
      font-size: 12px;
      color: var(--muted);
    }
    
    .upload-zone {
      border: 2px dashed var(--border);
      border-radius: 8px;
      padding: 40px 20px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
      margin-bottom: 20px;
      position: relative;
      background: var(--input-bg);
    }
    
    .upload-zone:hover {
      border-color: #4949FF;
      background: linear-gradient(135deg, rgba(0, 0, 255, 0.03) 0%, rgba(31, 31, 255, 0.03) 20%, rgba(73, 121, 255, 0.03) 40%, rgba(120, 121, 255, 0.03) 60%, rgba(163, 163, 255, 0.03) 80%, rgba(191, 191, 255, 0.03) 100%);
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
      font-size: 48px;
      margin-bottom: 12px;
    }
    
    .upload-text {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 6px;
      color: var(--text);
    }
    
    .upload-hint {
      font-size: 13px;
      color: var(--muted);
    }
    
    .preview-container {
      display: none;
      margin-bottom: 20px;
      border-radius: 8px;
      overflow: hidden;
      position: relative;
      background: #000;
      border: 1px solid var(--border);
    }
    
    .preview-container.active {
      display: block;
    }
    
    .preview-media {
      width: 100%;
      max-height: 400px;
      object-fit: contain;
      display: block;
    }
    
    .preview-remove {
      position: absolute;
      top: 8px;
      right: 8px;
      background: rgba(0, 0, 0, 0.7);
      color: white;
      border: none;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      font-size: 18px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.2s;
    }
    
    .preview-remove:hover {
      background: rgba(0, 0, 0, 0.9);
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      font-size: 14px;
      color: var(--text);
    }
    
    .form-textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 14px;
      font-family: inherit;
      resize: vertical;
      background: var(--input-bg);
      color: var(--text);
    }
    
    .form-textarea:focus {
      outline: none;
      border-color: #4949FF;
    }
    
    .form-actions {
      display: flex;
      gap: 12px;
    }
    
    .btn-create {
      flex: 1;
      padding: 12px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #0000FF 0%, #1F1FFF 20%, #4949FF 40%, #7879FF 60%, #A3A3FF 80%, #BFBFFF 100%);
      color: white;
    }
    
    .btn-primary:hover:not(:disabled) {
      opacity: 0.9;
    }
    
    .btn-primary:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    
    .btn-secondary {
      background: var(--input-bg);
      color: var(--text);
      border: 1px solid var(--border);
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .btn-secondary:hover {
      background: var(--hover-bg);
    }
    
    .alert {
      padding: 14px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
    }
    
    .alert-error {
      background: #fee;
      color: #c00;
      border: 1px solid #fdd;
    }
    
    .alert-success {
      background: #efe;
      color: #060;
      border: 1px solid #dfd;
    }
    
    .success-content {
      text-align: center;
      padding: 40px 20px;
    }
    
    .success-icon {
      font-size: 64px;
      margin-bottom: 16px;
    }
    
    .success-title {
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 8px;
    }
    
    .success-text {
      color: var(--muted);
      margin-bottom: 24px;
      font-size: 14px;
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
      <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme">
        <span class="theme-toggle-slider">🌙</span>
      </button>
    </div>
  </header>

  <main class="main">
    <div class="app-inner">
      <?php include __DIR__ . '/left-nav.php'; ?>
      <section class="layout">
        <div class="create-container">
          <div class="create-header">
            <h2>Create New Post</h2>
          </div>
          
          <div class="create-body">
            <?php if ($success): ?>
              <div class="success-content">
                <div class="success-icon">✅</div>
                <div class="success-title">Posted Successfully!</div>
                <div class="success-text">Your content is now live.</div>
                <div class="form-actions">
                  <a href="/Nathan/wd-project/" class="btn-create btn-primary">View Feed</a>
                  <button onclick="location.reload()" class="btn-create btn-secondary">Create Another</button>
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
                    <span class="desc">Photos & videos</span>
                  </label>
                  <label class="type-option" data-type="codea">
                    <input type="radio" name="post_type" value="codea" style="display:none">
                    <div class="icon">🎬</div>
                    <span class="label">Codea</span>
                    <span class="desc">Short videos</span>
                  </label>
                </div>
                
                <div class="upload-zone" id="uploadZone">
                  <input type="file" name="media" id="mediaInput" accept="image/*,video/*" required>
                  <div class="upload-icon">📁</div>
                  <div class="upload-text">Choose a file or drag it here</div>
                  <div class="upload-hint">Images and videos supported</div>
                </div>
                
                <div class="preview-container" id="previewContainer">
                  <img id="previewImage" class="preview-media" style="display:none">
                  <video id="previewVideo" class="preview-media" controls style="display:none"></video>
                  <button type="button" class="preview-remove" onclick="removePreview()">×</button>
                </div>
                
                <div class="form-group">
                  <label class="form-label" for="caption">Caption</label>
                  <textarea name="caption" id="caption" rows="3" class="form-textarea" placeholder="Write a caption..."></textarea>
                </div>
                
                <div class="form-actions">
                  <button type="submit" class="btn-create btn-primary" id="submitBtn" disabled>Share</button>
                  <a href="/Nathan/wd-project/" class="btn-create btn-secondary">Cancel</a>
                </div>
              </form>
            <?php endif; ?>
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
      uploadZone.style.borderColor = 'var(--accent)';
    });
    
    uploadZone.addEventListener('dragleave', () => {
      uploadZone.style.borderColor = '';
    });
    
    uploadZone.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadZone.style.borderColor = '';
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

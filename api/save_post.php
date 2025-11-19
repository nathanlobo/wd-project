<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? 0;
$type = $_POST['type'] ?? 'post'; // 'post' or 'codea'

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

$db = db_connect();

// Create saved_posts table if it doesn't exist
$db->query("CREATE TABLE IF NOT EXISTS saved_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    post_type VARCHAR(10) DEFAULT 'post',
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_save (user_id, post_id, post_type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Check if already saved
$stmt = $db->prepare('SELECT id FROM saved_posts WHERE user_id = ? AND post_id = ? AND post_type = ?');
$stmt->bind_param('iis', $user_id, $post_id, $type);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();
$stmt->close();

if ($existing) {
    // Unsave
    $stmt = $db->prepare('DELETE FROM saved_posts WHERE user_id = ? AND post_id = ? AND post_type = ?');
    $stmt->bind_param('iis', $user_id, $post_id, $type);
    $stmt->execute();
    $stmt->close();
    $db->close();
    
    echo json_encode(['success' => true, 'saved' => false, 'message' => 'Post unsaved']);
} else {
    // Save
    $stmt = $db->prepare('INSERT INTO saved_posts (user_id, post_id, post_type) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $user_id, $post_id, $type);
    $success = $stmt->execute();
    $stmt->close();
    $db->close();
    
    if ($success) {
        echo json_encode(['success' => true, 'saved' => true, 'message' => 'Post saved']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save post']);
    }
}

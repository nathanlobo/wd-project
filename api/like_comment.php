<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$comment_id = $data['comment_id'] ?? null;
$type = $data['type'] ?? 'post'; // 'post' or 'codea'

if (!$comment_id) {
    echo json_encode(['success' => false, 'error' => 'Missing comment_id']);
    exit;
}

$db = db_connect();

// Determine table based on type
$likes_table = ($type === 'codea') ? 'codeas_comment_likes' : 'comment_likes';

// Create table if it doesn't exist
if ($type === 'codea') {
    $db->query("CREATE TABLE IF NOT EXISTS codeas_comment_likes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        comment_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (comment_id, user_id),
        INDEX idx_comment_id (comment_id),
        INDEX idx_user_id (user_id)
    )");
} else {
    $db->query("CREATE TABLE IF NOT EXISTS comment_likes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        comment_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (comment_id, user_id),
        INDEX idx_comment_id (comment_id),
        INDEX idx_user_id (user_id)
    )");
}

// Check if already liked
$stmt = $db->prepare("SELECT id FROM $likes_table WHERE comment_id = ? AND user_id = ?");
$stmt->bind_param('ii', $comment_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$already_liked = $result->num_rows > 0;
$stmt->close();

if ($already_liked) {
    // Unlike
    $stmt = $db->prepare("DELETE FROM $likes_table WHERE comment_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $comment_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    $db->close();
    echo json_encode(['success' => true, 'liked' => false]);
} else {
    // Like
    $stmt = $db->prepare("INSERT INTO $likes_table (comment_id, user_id) VALUES (?, ?)");
    $stmt->bind_param('ii', $comment_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    $db->close();
    echo json_encode(['success' => true, 'liked' => true]);
}

<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$codea_id = $data['codea_id'] ?? null;

if (!$codea_id) {
    echo json_encode(['success' => false, 'error' => 'Missing codea_id']);
    exit;
}

$db = db_connect();

// Check if already liked
$stmt = $db->prepare('SELECT id FROM codeas_likes WHERE codea_id = ? AND user_id = ?');
$stmt->bind_param('ii', $codea_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$already_liked = $result->num_rows > 0;
$stmt->close();

if ($already_liked) {
    // Unlike
    $stmt = $db->prepare('DELETE FROM codeas_likes WHERE codea_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $codea_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    
    // Decrement count
    $db->query("UPDATE codeas SET likes_count = likes_count - 1 WHERE id = $codea_id");
} else {
    // Like
    $stmt = $db->prepare('INSERT INTO codeas_likes (codea_id, user_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $codea_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    
    // Increment count
    $db->query("UPDATE codeas SET likes_count = likes_count + 1 WHERE id = $codea_id");
}

// Get updated count
$result = $db->query("SELECT likes_count FROM codeas WHERE id = $codea_id");
$codea = $result->fetch_assoc();

$db->close();

echo json_encode([
    'success' => true,
    'liked' => !$already_liked,
    'likes_count' => $codea['likes_count']
]);

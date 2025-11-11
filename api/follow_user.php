<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;

if (!$user_id || $user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'Invalid user_id']);
    exit;
}

$db = db_connect();

// Check if already following
$stmt = $db->prepare('SELECT id FROM follows WHERE follower_id = ? AND following_id = ?');
$stmt->bind_param('ii', $_SESSION['user_id'], $user_id);
$stmt->execute();
$result = $stmt->get_result();
$already_following = $result->num_rows > 0;
$stmt->close();

if ($already_following) {
    // Unfollow
    $stmt = $db->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
    $stmt->bind_param('ii', $_SESSION['user_id'], $user_id);
    $stmt->execute();
    $stmt->close();
} else {
    // Follow
    $stmt = $db->prepare('INSERT INTO follows (follower_id, following_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $_SESSION['user_id'], $user_id);
    $stmt->execute();
    $stmt->close();
}

$db->close();

echo json_encode([
    'success' => true,
    'following' => !$already_following
]);

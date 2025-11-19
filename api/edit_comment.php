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
$comment = trim($data['comment'] ?? '');
$type = $data['type'] ?? 'post'; // 'post' or 'codea'

if (!$comment_id || !$comment) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

$db = db_connect();

// Determine table based on type
$table = ($type === 'codea') ? 'codeas_comments' : 'comments';

// Check if user owns the comment
$stmt = $db->prepare("SELECT user_id FROM $table WHERE id = ?");
$stmt->bind_param('i', $comment_id);
$stmt->execute();
$result = $stmt->get_result();
$commentData = $result->fetch_assoc();
$stmt->close();

if (!$commentData || $commentData['user_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    $db->close();
    exit;
}

// Update the comment
$stmt = $db->prepare("UPDATE $table SET comment = ? WHERE id = ?");
$stmt->bind_param('si', $comment, $comment_id);

if ($stmt->execute()) {
    $stmt->close();
    $db->close();
    echo json_encode(['success' => true]);
} else {
    $stmt->close();
    $db->close();
    echo json_encode(['success' => false, 'error' => 'Failed to update comment']);
}

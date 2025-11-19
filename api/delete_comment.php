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
$table = ($type === 'codea') ? 'codeas_comments' : 'comments';

// Check if user owns the comment
$stmt = $db->prepare("SELECT user_id FROM $table WHERE id = ?");
$stmt->bind_param('i', $comment_id);
$stmt->execute();
$result = $stmt->get_result();
$comment = $result->fetch_assoc();
$stmt->close();

if (!$comment || $comment['user_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    $db->close();
    exit;
}

// Delete the comment
$stmt = $db->prepare("DELETE FROM $table WHERE id = ?");
$stmt->bind_param('i', $comment_id);

if ($stmt->execute()) {
    // Decrement comment count for codeas
    if ($type === 'codea') {
        $db->query("UPDATE codeas SET comments_count = GREATEST(0, comments_count - 1)");
    }
    
    $stmt->close();
    $db->close();
    echo json_encode(['success' => true]);
} else {
    $stmt->close();
    $db->close();
    echo json_encode(['success' => false, 'error' => 'Failed to delete comment']);
}

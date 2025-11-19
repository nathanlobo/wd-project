<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$db = db_connect();

// Handle POST - Add comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $post_id = $data['post_id'] ?? null;
    $comment = trim($data['comment'] ?? '');
    
    if (!$post_id || !$comment) {
        echo json_encode(['success' => false, 'error' => 'Missing data']);
        exit;
    }
    
    $stmt = $db->prepare('INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $post_id, $_SESSION['user_id'], $comment);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Get post owner to send notification
        $stmt = $db->prepare('SELECT user_id FROM posts WHERE id = ?');
        $stmt->bind_param('i', $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $post = $result->fetch_assoc();
        $stmt->close();
        
        // Send notification to post owner (if not commenting on own post)
        if ($post && $post['user_id'] != $_SESSION['user_id']) {
            $message = 'commented on your post';
            $stmt = $db->prepare('INSERT INTO notifications (user_id, from_user_id, type, reference_id, message, created_at) VALUES (?, ?, "comment", ?, ?, NOW())');
            $stmt->bind_param('iiis', $post['user_id'], $_SESSION['user_id'], $post_id, $message);
            $stmt->execute();
            $stmt->close();
        }
        
        $db->close();
        echo json_encode(['success' => true]);
    } else {
        $stmt->close();
        $db->close();
        echo json_encode(['success' => false, 'error' => 'Failed to add comment']);
    }
    exit;
}

// Handle GET - Fetch comments
$post_id = $_GET['post_id'] ?? null;

if (!$post_id) {
    echo json_encode(['success' => false, 'error' => 'Missing post_id']);
    exit;
}

$stmt = $db->prepare('SELECT c.*, u.username, u.profile_pic 
                      FROM comments c
                      JOIN users u ON c.user_id = u.id
                      WHERE c.post_id = ?
                      ORDER BY c.created_at DESC');
$stmt->bind_param('i', $post_id);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();

echo json_encode([
    'success' => true,
    'comments' => $comments
]);

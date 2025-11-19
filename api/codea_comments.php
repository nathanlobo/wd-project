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
    $codea_id = $data['codea_id'] ?? null;
    $comment = trim($data['comment'] ?? '');
    
    if (!$codea_id || !$comment) {
        echo json_encode(['success' => false, 'error' => 'Missing data']);
        exit;
    }
    
    $stmt = $db->prepare('INSERT INTO codeas_comments (codea_id, user_id, comment) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $codea_id, $_SESSION['user_id'], $comment);
    $stmt->execute();
    $stmt->close();
    
    // Increment comment count
    $db->query("UPDATE codeas SET comments_count = comments_count + 1 WHERE id = $codea_id");
    
    // Get codea owner to send notification
    $stmt = $db->prepare('SELECT user_id FROM codeas WHERE id = ?');
    $stmt->bind_param('i', $codea_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $codea = $result->fetch_assoc();
    $stmt->close();
    
    // Send notification to codea owner (if not commenting on own codea)
    if ($codea && $codea['user_id'] != $_SESSION['user_id']) {
        $message = 'commented on your codea';
        $stmt = $db->prepare('INSERT INTO notifications (user_id, from_user_id, type, reference_id, message, created_at) VALUES (?, ?, "comment", ?, ?, NOW())');
        $stmt->bind_param('iiis', $codea['user_id'], $_SESSION['user_id'], $codea_id, $message);
        $stmt->execute();
        $stmt->close();
    }
    
    $db->close();
    echo json_encode(['success' => true]);
    exit;
}

// Handle GET - Fetch comments
$codea_id = $_GET['codea_id'] ?? null;

if (!$codea_id) {
    echo json_encode(['success' => false, 'error' => 'Missing codea_id']);
    exit;
}

$stmt = $db->prepare('SELECT cc.*, u.username, u.profile_pic 
                      FROM codeas_comments cc
                      JOIN users u ON cc.user_id = u.id
                      WHERE cc.codea_id = ?
                      ORDER BY cc.created_at DESC');
$stmt->bind_param('i', $codea_id);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();

echo json_encode([
    'success' => true,
    'comments' => $comments
]);

<?php
require_once __DIR__ . '/../includes/db.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }

$messageId = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;
$emoji = isset($_POST['emoji']) ? trim($_POST['emoji']) : '';

if ($messageId <= 0 || $emoji === '') {
    http_response_code(400); echo json_encode(['error'=>'bad_request']); exit;
}

$db = db_connect();

// Check if already reacted - if yes, update; if no, insert
$stmt = $db->prepare('SELECT id FROM message_reactions WHERE message_id=? AND user_id=?');
$stmt->bind_param('ii', $messageId, $_SESSION['user_id']);
$stmt->execute(); $res=$stmt->get_result();
if ($res->fetch_row()) {
    // Update existing
    $stmt->close();
    $stmt = $db->prepare('UPDATE message_reactions SET emoji=? WHERE message_id=? AND user_id=?');
    $stmt->bind_param('sii', $emoji, $messageId, $_SESSION['user_id']);
    $stmt->execute(); $stmt->close();
} else {
    // Insert new
    $stmt->close();
    $stmt = $db->prepare('INSERT INTO message_reactions (message_id, user_id, emoji) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $messageId, $_SESSION['user_id'], $emoji);
    $stmt->execute(); $stmt->close();
}

$db->close();
echo json_encode(['ok'=>true]);

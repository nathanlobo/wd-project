<?php
require_once __DIR__ . '/../includes/db.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }

$userId = (int)$_SESSION['user_id'];
$notifId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($notifId <= 0) {
    // Mark all as read
    $db = db_connect();
    $stmt = $db->prepare('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
    $db->close();
    echo json_encode(['ok'=>true]);
} else {
    // Mark specific as read
    $db = db_connect();
    $stmt = $db->prepare('UPDATE notifications SET read_at=NOW() WHERE id=? AND user_id=?');
    $stmt->bind_param('ii', $notifId, $userId);
    $stmt->execute();
    $stmt->close();
    $db->close();
    echo json_encode(['ok'=>true]);
}

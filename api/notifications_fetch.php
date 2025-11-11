<?php
require_once __DIR__ . '/../includes/db.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }

$userId = (int)$_SESSION['user_id'];
$since = isset($_GET['since']) ? $_GET['since'] : null;

$db = db_connect();

if ($since) {
    $stmt = $db->prepare('SELECT n.*, u.username, u.profile_pic FROM notifications n JOIN users u ON n.from_user_id=u.id WHERE n.user_id=? AND n.created_at > ? ORDER BY n.created_at DESC LIMIT 50');
    $stmt->bind_param('is', $userId, $since);
} else {
    $stmt = $db->prepare('SELECT n.*, u.username, u.profile_pic FROM notifications n JOIN users u ON n.from_user_id=u.id WHERE n.user_id=? ORDER BY n.created_at DESC LIMIT 50');
    $stmt->bind_param('i', $userId);
}

$stmt->execute();
$res = $stmt->get_result();
$notifications = [];
while ($row = $res->fetch_assoc()) $notifications[] = $row;
$stmt->close();

// Get unread count
$stmt = $db->prepare('SELECT COUNT(*) as unread FROM notifications WHERE user_id=? AND read_at IS NULL');
$stmt->bind_param('i', $userId);
$stmt->execute();
$r = $stmt->get_result();
$unread = $r->fetch_assoc()['unread'];
$stmt->close();

$db->close();
echo json_encode(['notifications'=>$notifications, 'unread'=>$unread]);

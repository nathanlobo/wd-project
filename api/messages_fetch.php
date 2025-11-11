<?php
require_once __DIR__ . '/../includes/db.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }

$userId = (int)$_SESSION['user_id'];
$otherId = isset($_GET['u']) ? (int)$_GET['u'] : 0;
$since   = isset($_GET['since']) ? $_GET['since'] : null; // ISO or mysql datetime

if ($otherId <= 0) { echo json_encode(['messages'=>[]]); exit; }

$db = db_connect();
// validate other user
$stmt = $db->prepare('SELECT id FROM users WHERE id=? LIMIT 1');
$stmt->bind_param('i',$otherId); $stmt->execute(); $res=$stmt->get_result();
if (!$res->fetch_row()) { $stmt->close(); $db->close(); echo json_encode(['messages'=>[]]); exit; }
$stmt->close();

if ($since) {
    $stmt = $db->prepare('SELECT id,sender_id,receiver_id,message,created_at FROM messages WHERE ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)) AND created_at > ? ORDER BY created_at ASC');
    $stmt->bind_param('iiiis',$userId,$otherId,$otherId,$userId,$since);
} else {
    $stmt = $db->prepare('SELECT id,sender_id,receiver_id,message,created_at FROM messages WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?) ORDER BY created_at ASC');
    $stmt->bind_param('iiii',$userId,$otherId,$otherId,$userId);
}
$stmt->execute(); $res=$stmt->get_result();
$msgs=[]; while($row=$res->fetch_assoc()) $msgs[]=$row; $stmt->close();

// mark as read
$stmt=$db->prepare('UPDATE messages SET read_at=NOW() WHERE sender_id=? AND receiver_id=? AND read_at IS NULL');
$stmt->bind_param('ii',$otherId,$userId); $stmt->execute(); $stmt->close();

$db->close();
echo json_encode(['messages'=>$msgs]);

<?php
require_once __DIR__ . '/../includes/db.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }

$messageId = isset($_GET['message_id']) ? (int)$_GET['message_id'] : 0;
if ($messageId <= 0) { echo json_encode(['reactions'=>[]]); exit; }

$db = db_connect();
$stmt = $db->prepare('SELECT emoji, COUNT(*) as count FROM message_reactions WHERE message_id=? GROUP BY emoji');
$stmt->bind_param('i', $messageId);
$stmt->execute(); $res=$stmt->get_result();
$reactions = [];
while ($row = $res->fetch_assoc()) $reactions[] = $row;
$stmt->close();
$db->close();

echo json_encode(['reactions'=>$reactions]);

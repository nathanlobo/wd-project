<?php
require_once __DIR__ . '/../includes/db.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }

$input = $_POST;
$otherId = isset($input['other_id']) ? (int)$input['other_id'] : 0;
$text = isset($input['message']) ? trim($input['message']) : '';
if ($otherId <= 0 || $text === '') { http_response_code(400); echo json_encode(['error'=>'bad_request']); exit; }

$db = db_connect();
// validate other user
$stmt = $db->prepare('SELECT id FROM users WHERE id=? LIMIT 1');
$stmt->bind_param('i',$otherId); $stmt->execute(); $res=$stmt->get_result();
if (!$res->fetch_row()) { $stmt->close(); $db->close(); http_response_code(404); echo json_encode(['error'=>'not_found']); exit; }
$stmt->close();

$stmt = $db->prepare('INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)');
$stmt->bind_param('iis', $_SESSION['user_id'], $otherId, $text);
if ($stmt->execute()) {
    $id = $stmt->insert_id; $stmt->close();
    
    // Create notification for receiver
    $stmt2 = $db->prepare('SELECT username FROM users WHERE id=?');
    $stmt2->bind_param('i', $_SESSION['user_id']);
    $stmt2->execute(); $r=$stmt2->get_result(); $sender=$r->fetch_assoc(); $stmt2->close();
    $notifMsg = 'sent you a message';
    $stmt2 = $db->prepare('INSERT INTO notifications (user_id, type, from_user_id, reference_id, message) VALUES (?, "message", ?, ?, ?)');
    $stmt2->bind_param('iiis', $otherId, $_SESSION['user_id'], $id, $notifMsg);
    $stmt2->execute(); $stmt2->close();
    
    // fetch inserted row to return
    $st = $db->prepare('SELECT id,sender_id,receiver_id,message,created_at FROM messages WHERE id=?');
    $st->bind_param('i',$id); $st->execute(); $r=$st->get_result(); $row=$r->fetch_assoc(); $st->close();
    $db->close();
    echo json_encode(['ok'=>true,'message'=>$row]);
} else {
    $stmt->close(); $db->close(); http_response_code(500); echo json_encode(['error'=>'insert_failed']);
}

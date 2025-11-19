<?php
require_once __DIR__ . '/../includes/db.php';
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid method']);
    exit;
}
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid notification id']);
    exit;
}
$db = db_connect();
$stmt = $db->prepare('DELETE FROM notifications WHERE id=? AND user_id=?');
$stmt->bind_param('ii', $id, $_SESSION['user_id']);
$stmt->execute();
$stmt->close();
$db->close();
echo json_encode(['success' => true]);

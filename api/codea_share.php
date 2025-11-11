<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$codea_id = $data['codea_id'] ?? null;

if (!$codea_id) {
    echo json_encode(['success' => false, 'error' => 'Missing codea_id']);
    exit;
}

$db = db_connect();

// Increment share count
$db->query("UPDATE codeas SET shares_count = shares_count + 1 WHERE id = $codea_id");

$db->close();

echo json_encode(['success' => true]);

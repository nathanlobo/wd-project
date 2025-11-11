<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 1) {
    echo json_encode(['success' => true, 'users' => []]);
    exit;
}

$db = db_connect();

// Instagram-like search: prioritize exact username matches, then partial matches, then display_name matches
$searchPattern = '%' . $query . '%';
$stmt = $db->prepare('
    SELECT id, username, display_name, profile_pic,
           CASE 
               WHEN username = ? THEN 1
               WHEN username LIKE ? THEN 2
               WHEN display_name LIKE ? THEN 3
               ELSE 4
           END as priority
    FROM users 
    WHERE (username LIKE ? OR display_name LIKE ?)
    AND id != ?
    ORDER BY priority ASC, username ASC
    LIMIT 10
');

$stmt->bind_param('sssssi', $query, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $_SESSION['user_id']);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();

echo json_encode([
    'success' => true,
    'users' => $users
]);

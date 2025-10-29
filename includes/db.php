<?php
// Simple MySQLi connection helper. Update constants below for your environment.
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'codegram');

function db_connect() {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_errno) {
        error_log('DB connect error: ' . $mysqli->connect_error);
        die('Database connection failed. Check configuration.');
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

// helper: fetch current user by session id
function current_user() {
    if (!session_id()) session_start();
    if (empty($_SESSION['user_id'])) return null;
    $db = db_connect();
    $stmt = $db->prepare('SELECT id, username, display_name, profile_pic FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();
    $db->close();
    return $user ?: null;
}

?>

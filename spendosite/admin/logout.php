<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

// Regenerate session ID before destroying to prevent session fixation
session_regenerate_id(true);

// Log admin logout
if (isset($_SESSION['admin_id'])) {
    $stmt = $pdo->prepare("INSERT INTO admin_login_logs (admin_id, ip_address, success, logout_time) 
                          VALUES (?, ?, 1, NOW())");
    $stmt->execute([$_SESSION['admin_id'], $_SERVER['REMOTE_ADDR']]);
}

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php?logout=success');
exit();
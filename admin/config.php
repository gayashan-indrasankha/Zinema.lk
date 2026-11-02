<?php
require_once __DIR__ . '/../includes/environment.php';
recovery_browser_policy();
session_save_path(RECOVERY_SESSION_PATH);

// Set lifetime to 100 years (3153600000 seconds)
$lifetime = 3153600000;
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params($lifetime);

// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuration is explicit in includes/environment.php.

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) recovery_config_error('database connection');
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    recovery_config_error('database connection');
}

// ========================================
// AUTO-LOGIN FROM "REMEMBER ME" COOKIE
// ========================================
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
    $userId = intval($_COOKIE['remember_user']);
    $token = $_COOKIE['remember_token'];
    
    $stmt = $conn->prepare("SELECT id, username, remember_token FROM users WHERE id = ? AND remember_token = ?");
    $stmt->bind_param("is", $userId, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
    } else {
        // Invalid cookie - clear it
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('remember_user', '', time() - 3600, '/');
    }
    $stmt->close();
}

// Check if admin is logged in
function check_login() {
    if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}
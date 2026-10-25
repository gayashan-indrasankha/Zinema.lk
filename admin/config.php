<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/auth.php';
// Service adapters authenticate with their own shared key, never browser cookies.
if (!defined('WHATSAPP_API')) require_browser_csrf();

// Configuration is explicit in includes/environment.php.

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) recovery_config_error('database connection');
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    recovery_config_error('database connection');
}

// Older sessions must not retain access after verification is revoked.
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare('SELECT is_verified FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $currentUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$currentUser || (int)$currentUser['is_verified'] !== 1) {
        unset($_SESSION['user_id'], $_SESSION['username']);
        clear_remember_cookies();
    }
}

// ========================================
// AUTO-LOGIN FROM "REMEMBER ME" COOKIE
// ========================================
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
    $userId = intval($_COOKIE['remember_user']);
    $token = is_string($_COOKIE['remember_token']) ? hash('sha256', $_COOKIE['remember_token']) : ''; 
    
    $stmt = $conn->prepare("SELECT id, username, remember_token FROM users WHERE id = ? AND remember_token = ? AND is_verified = 1 AND remember_expires_at > NOW()");
    $stmt->bind_param("is", $userId, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        establish_user_session($user);
    } else {
        // Invalid cookie - clear it
        clear_remember_cookies();
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

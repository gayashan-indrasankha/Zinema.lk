<?php
// Set lifetime to 100 years (3153600000 seconds)
$lifetime = 3153600000;
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params($lifetime);

// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ===================
// DATABASE CONFIGURATION
// ===================

define('DB_HOST', 'localhost');
define('DB_USER', 'zinexxio_dbuser');
define('DB_PASS', 'gaiya2080546');
define('DB_NAME', 'zinexxio_cinedrive');

// Base URL for the application
define('BASE_URL', 'https://zinema.lk');

// ===================
// EMAIL CONFIGURATION (Gmail SMTP)
// ===================
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'zinema.lkteam@gmail.com');  // Your Gmail address
define('MAIL_PASSWORD', 'hstm moow egya aiab');   // Your Gmail App Password (16 chars)
define('MAIL_FROM_NAME', 'Zinema.lk');
define('MAIL_FROM_EMAIL', 'zinema.lkteam@gmail.com'); // Same as MAIL_USERNAME

// ===================
// GOOGLE OAUTH CONFIGURATION
// ===================
define('GOOGLE_CLIENT_ID', '267732612600-kqt8ek89p4gtdebkucih58o6n49p2e74.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', BASE_URL . '/google-callback.php');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// CRITICAL: Set UTF-8 encoding for Sinhala/Unicode support
$conn->set_charset("utf8mb4");

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
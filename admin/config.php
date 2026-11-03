<?php
require_once __DIR__ . '/../includes/environment.php';
recovery_browser_policy();
session_save_path(RECOVERY_SESSION_PATH);

// Sessions last 30 days; the cookie is hidden from JavaScript and not sent on cross-site requests
$lifetime = 30 * 24 * 60 * 60;
ini_set('session.gc_maxlifetime', $lifetime);
ini_set('session.use_strict_mode', 1);
session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);

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

// Admins are signed out after 8 hours without activity
define('ADMIN_IDLE_TIMEOUT', 8 * 60 * 60);

// Check if admin is logged in (every admin page calls this; public pages never do)
function check_login() {
    if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
    if (isset($_SESSION['admin_last_seen']) && time() - $_SESSION['admin_last_seen'] > ADMIN_IDLE_TIMEOUT) {
        unset($_SESSION['admin_id'], $_SESSION['admin_last_seen'], $_SESSION['admin_csrf']);
        header('Location: login.php');
        exit;
    }
    $_SESSION['admin_last_seen'] = time();
    admin_csrf_protect();
}

// One CSRF token per admin session
function admin_csrf_token() {
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

// Reject state-changing admin requests (POST forms and ?delete= links) that lack the session's
// CSRF token, then add the token to every POST form and delete link on the page.
function admin_csrf_protect() {
    $token = admin_csrf_token();
    $changes_state = $_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['delete']) || isset($_GET['delete_episode']);
    if ($changes_state) {
        $sent = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($sent) || !hash_equals($token, $sent)) {
            http_response_code(403);
            exit('Security check failed. Please go back, refresh the page and try again.');
        }
    }
    ob_start(function ($html) use ($token) {
        $safe = htmlspecialchars($token, ENT_QUOTES);
        $html = preg_replace('~<form\b[^>]*\bmethod\s*=\s*["\']?post\b[^>]*>~i', '$0<input type="hidden" name="csrf_token" value="' . $safe . '">', $html);
        return preg_replace('~(href\s*=\s*["\'][^"\']*\?(?:delete|delete_episode)=[0-9]+)~i', '$1&amp;csrf_token=' . $safe, $html);
    });
}

// Accept only real images (by extension and by content) for admin cover/thumbnail uploads.
// Returns an error message, or '' when the file is fine.
function admin_image_upload_error(array $file) {
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return 'Please upload an image file (jpg, png, webp or gif).';
    }
    $info = @getimagesize($file['tmp_name'] ?? '');
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
        return 'The uploaded file is not a valid image.';
    }
    return '';
}
<?php
require_once __DIR__ . '/admin/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); header('Allow: POST'); exit('POST required.');
}
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare('UPDATE users SET remember_token = NULL, remember_expires_at = NULL WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']); $stmt->execute(); $stmt->close();
}
clear_remember_cookies();
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $params['path'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
header('Location: ' . BASE_URL . '/index.php', true, 303);

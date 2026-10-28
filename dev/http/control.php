<?php
// Installed only in the development image, outside the application document root.
$key = trim(file_get_contents('/run/secrets/control_key'));
if (!hash_equals($key, $_SERVER['HTTP_X_TEST_KEY'] ?? '')) { http_response_code(403); exit; }
require_once '/var/www/html/includes/environment.php';
if (APP_ENV !== 'development' || DB_HOST !== '127.0.0.1' || DB_NAME !== 'zinema_recovery_development' || $_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(403); exit; }
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($db->query('SELECT DATABASE()')->fetch_row()[0] !== DB_NAME) { http_response_code(403); exit; }
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';
$result = [];
if (in_array($action, ['user','expire_verification','expire_reset'], true)) {
    $email = $input['email'] ?? '';
    if (!is_string($email) || !preg_match('/^[a-z0-9_+.-]+@example\.invalid$/iD', $email)) { http_response_code(400); exit; }
    $sql = match ($action) {
        'user' => 'SELECT id, username, is_verified, google_id FROM users WHERE email=?',
        'expire_verification' => 'UPDATE users SET verification_expires=DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE email=?',
        'expire_reset' => 'UPDATE password_resets p JOIN users u ON p.user_id=u.id SET p.expires_at=DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE u.email=?',
    };
    $stmt = $db->prepare($sql); $stmt->bind_param('s', $email); $stmt->execute();
    $result = $action === 'user' ? ($stmt->get_result()->fetch_assoc() ?: []) : ['success'=>true];
} elseif ($action === 'catalogue') {
    $table = $input['table'] ?? '';
    if (!in_array($table, ['movies','series','episodes','collections','trailers'], true)) { http_response_code(400); exit; }
    $title = $input['title'] ?? '';
    if (!is_string($title) || !str_starts_with($title, 'Local Test ')) { http_response_code(400); exit; }
    $column = $table === 'episodes' ? 'thumb_image' : ($table === 'trailers' ? 'video_url' : 'cover_image');
    $stmt = $db->prepare("SELECT id, $column AS path FROM $table WHERE title=? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $title); $stmt->execute(); $result = $stmt->get_result()->fetch_assoc() ?: [];
} elseif (in_array($action, ['token_status','expire_token','age_claim'], true)) {
    $token = $input['token'] ?? '';
    if (!is_string($token) || !preg_match('/^T[A-Z0-9]{11}$/D', $token)) { http_response_code(400); exit; }
    $sql = match ($action) {
        'token_status' => 'SELECT claim_state, is_used, expires_at > NOW() AS unexpired, TIMESTAMPDIFF(SECOND,NOW(),expires_at) AS remaining_seconds FROM whatsapp_tokens WHERE token=?',
        'expire_token' => 'UPDATE whatsapp_tokens SET expires_at=DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE token=?',
        'age_claim' => 'UPDATE whatsapp_tokens SET claimed_at=DATE_SUB(NOW(), INTERVAL 1 DAY), expires_at=expires_at WHERE token=?',
    };
    $stmt = $db->prepare($sql); $stmt->bind_param('s', $token); $stmt->execute();
    $result = $action === 'token_status' ? ($stmt->get_result()->fetch_assoc() ?: []) : ['success'=>true];
} elseif ($action === 'token') {
    $token = $input['token'] ?? '';
    if (!is_string($token) || !preg_match('/^T[A-Z0-9]{11}$/D', $token)) { http_response_code(400); exit; }
    $active = !empty($input['active']) ? 1 : 0;
    $minutes = !empty($input['expired']) ? -10 : 10;
    $bot = (int)($input['bot_id'] ?? 1);
    $stmt = $db->prepare("INSERT INTO whatsapp_tokens(token,content_type,content_id,message_id,is_active,expires_at,assigned_bot_id) VALUES(?,'movie',1,'fictional-message',?,DATE_ADD(NOW(),INTERVAL ? MINUTE),?)");
    $stmt->bind_param('siii', $token, $active, $minutes, $bot); $stmt->execute();
    $result = ['id'=>$db->insert_id];
} else { http_response_code(400); exit; }
header('Content-Type: application/json'); header('Cache-Control: no-store'); echo json_encode($result);

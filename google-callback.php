<?php
require_once __DIR__ . '/includes/environment.php';
recovery_require_adapter('OAUTH');
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/includes/oauth.php';
header('Cache-Control: no-store');
$flow = oauth_consume_flow($_GET['state'] ?? null);
if (!$flow) oauth_fail('state');
if (isset($_GET['error'])) oauth_fail('denied');
$code = $_GET['code'] ?? '';
if (!is_string($code) || $code === '' || strlen($code) > 2048) oauth_fail('code');
$tokens = oauth_http_json(OAUTH_TOKEN_URL, ['code' => $code, 'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET, 'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code', 'code_verifier' => $flow['verifier']]);
$access = $tokens['access_token'] ?? null;
if (!is_string($access) || $access === '' || strlen($access) > 4096) oauth_fail('exchange');
$identity = oauth_http_json(OAUTH_USERINFO_URL, null, $access);
$id = $identity['sub'] ?? $identity['id'] ?? null;
$email = $identity['email'] ?? null;
$verified = ($identity['email_verified'] ?? $identity['verified_email'] ?? false) === true;
if (!$verified || !is_string($id) || $id === '' || strlen($id) > 255 || !is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) oauth_fail('identity');
try {
    $conn->begin_transaction();
    $stmt = $conn->prepare('SELECT id, username, email, google_id, is_verified FROM users WHERE google_id = ? OR email = ? FOR UPDATE');
    $stmt->bind_param('ss', $id, $email); $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    if (count($users) > 1) { $conn->rollback(); oauth_fail('conflict'); }
    if ($users) {
        $user = $users[0];
        if ((int)$user['is_verified'] !== 1) { $conn->rollback(); oauth_fail('identity'); }
        if ($user['google_id'] && !hash_equals($user['google_id'], $id)) { $conn->rollback(); oauth_fail('conflict'); }
        if (!$user['google_id']) {
            if (!isset($_SESSION['user_id']) || (int)$flow['link_user_id'] !== (int)$user['id'] || (int)$_SESSION['user_id'] !== (int)$user['id']) { $conn->rollback(); oauth_fail('link_required'); }
            $stmt = $conn->prepare('UPDATE users SET google_id = ? WHERE id = ? AND (google_id IS NULL OR google_id = \'\')');
            $stmt->bind_param('si', $id, $user['id']); $stmt->execute(); $stmt->close();
        }
    } else {
        $username = 'oauth_' . bin2hex(random_bytes(6));
        $stmt = $conn->prepare("INSERT INTO users(username,email,password_hash,is_verified,google_id) VALUES(?,?,'',1,?)");
        $stmt->bind_param('sss', $username, $email, $id); $stmt->execute();
        $user = ['id' => $conn->insert_id, 'username' => $username]; $stmt->close();
    }
    $conn->commit();
    establish_user_session($user);
    header('Location: ' . BASE_URL . '/profile.php', true, 303);
} catch (Throwable $e) {
    $conn->rollback(); oauth_fail('unavailable');
}

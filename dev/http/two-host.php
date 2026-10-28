<?php
// Private fixture control, development image only. No arbitrary SQL or real media.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || getenv('ZINEMA_TWO_HOST_FIXTURES') !== '1' ||
    !hash_equals(trim(file_get_contents('/run/secrets/control_key')), $_SERVER['HTTP_X_TEST_KEY'] ?? '')) {
    http_response_code(403); exit;
}
require_once '/var/www/html/includes/environment.php';
if (!recovery_local_delivery_fixture()) { http_response_code(403); exit; }
// The private fixture key above is the service credential; no browser session.
define('WHATSAPP_API',true);
require_once '/var/www/html/admin/config.php';
require_once '/var/www/html/includes/whatsapp_token.php';
$identity=$conn->query('SELECT DATABASE(),@@port')->fetch_row();
if ($identity[0] !== 'zinema_recovery_development' || (int)$identity[1] !== 3306) { http_response_code(403); exit; }
$input=json_decode(file_get_contents('php://input',false,null,0,4097),true);
$action=is_array($input) ? ($input['action'] ?? '') : '';
$bot=910000001;
try {
    if ($action === 'issue') {
        $id=filter_var($input['movie'] ?? null,FILTER_VALIDATE_INT);
        $stmt=$conn->prepare("SELECT id FROM movies WHERE id=? AND title LIKE 'Local Test Product %'");
        $stmt->bind_param('i',$id); $stmt->execute();
        if (!$stmt->get_result()->fetch_row()) throw new RuntimeException();
        // Independent fictional peers keep unrelated phase admission counters intact.
        $_SERVER['REMOTE_ADDR']='127.0.0.'.random_int(2,250);
        $result=createWhatsAppToken($conn,$id,'movie',10);
    } elseif ($action === 'status') {
        $token=$input['token'] ?? '';
        if (!is_string($token) || !preg_match('/^[A-Z0-9]{12}$/D',$token)) throw new RuntimeException();
        $stmt=$conn->prepare('SELECT claim_state,is_used FROM whatsapp_tokens WHERE token=? AND assigned_bot_id=?');
        $stmt->bind_param('si',$token,$bot); $stmt->execute(); $result=$stmt->get_result()->fetch_assoc() ?: [];
    } elseif (in_array($action,['stale','future'],true)) {
        $time=$action === 'stale' ? 'DATE_SUB(NOW(),INTERVAL 3 MINUTE)' : 'DATE_ADD(NOW(),INTERVAL 10 MINUTE)';
        $conn->query("UPDATE bot_health_status SET last_heartbeat=$time WHERE bot_id=910000001");
        $result=['success'=>true];
    } elseif ($action === 'count') {
        $result=['tokens'=>(int)$conn->query('SELECT COUNT(*) FROM whatsapp_tokens WHERE assigned_bot_id=910000001')->fetch_row()[0]];
    } else throw new RuntimeException();
    header('Content-Type: application/json'); header('Cache-Control: no-store');
    echo json_encode($result);
} catch (Throwable $error) { http_response_code(400); echo '{"error":"fictional_control_unavailable"}'; }

<?php
require_once __DIR__.'/client_ip.php';
function password_attempt($db,$scope,$identity,$verify) {
    $ip=recovery_client_ip();$lock='zinema-password-'.hash('sha256',$ip);
    // MySQL lock names are limited to 64 characters.
    $lock=substr($lock,0,64);
    $stmt=$db->prepare('SELECT GET_LOCK(?,5)');$stmt->bind_param('s',$lock);$stmt->execute();
    if ((int)$stmt->get_result()->fetch_row()[0]!==1) throw new RuntimeException('Password service busy');
    try {
        $stmt=$db->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address=? AND successful=0 AND attempt_time>DATE_SUB(NOW(),INTERVAL 15 MINUTE)');
        $stmt->bind_param('s',$ip);$stmt->execute();
        if ((int)$stmt->get_result()->fetch_row()[0]>=20) {
            http_response_code(429);header('Retry-After: 900');
            return ['success'=>false,'rate_limited'=>true,'message'=>'Too many sign-in attempts. Please try again later.'];
        }
        $result=$verify();$successful=($result['success'] ?? false) ? 1 : 0;
        // Do not persist raw account identifiers in the attempt ledger.
        $account=hash('sha256',$scope.':'.(is_string($identity) ? strtolower(trim($identity)) : 'invalid'));
        $stmt=$db->prepare('INSERT INTO login_attempts(ip_address,username,successful) VALUES(?,?,?)');
        $stmt->bind_param('ssi',$ip,$account,$successful);$stmt->execute();
        return $result;
    } finally {
        $stmt=$db->prepare('SELECT RELEASE_LOCK(?)');$stmt->bind_param('s',$lock);$stmt->execute();
    }
}

<?php
// Executed only on the exact owned CLI development target. Fictional peers only.
ini_set('display_errors','0');require_once __DIR__.'/operations.php';
try {
    [$db,$settings]=operator_context();
    require_once '/var/www/html/includes/auth.php';
    require_once '/var/www/html/includes/client_ip.php';
    require_once '/var/www/html/includes/media_url.php';
    require_once '/var/www/html/includes/rate_limiter.php';
    $rows=[];
    $check=static function($name,$passed) use(&$rows){$rows[]=['name'=>$name,'passed'=>(bool)$passed];};
    foreach (['https://www.facebook.com/watch/?v=123','https://fb.watch/fictional/'] as $value) $check('Facebook accepted host '.parse_url($value,PHP_URL_HOST),valid_facebook_url($value));
    foreach (['https://facebook.com.example.invalid/a','https://example.invalid/?facebook.com','http://facebook.com/a','https://user@facebook.com/a','https://facebook.com:444/a','https://facebook.com\\@example.invalid/a'] as $index=>$value) $check('Facebook deceptive/unsafe URL case '.$index,!valid_facebook_url($value));
    $check('Untrusted proxy headers cannot replace peer',recovery_client_ip(['REMOTE_ADDR'=>'198.51.100.1','HTTP_X_REAL_IP'=>'127.0.0.1','HTTP_X_FORWARDED_FOR'=>'127.0.0.1'],'')==='198.51.100.1');
    $check('Explicit trusted edge can supply one real client address',recovery_client_ip(['REMOTE_ADDR'=>'127.0.0.1','HTTP_X_REAL_IP'=>'198.51.100.2'],'127.0.0.1')==='198.51.100.2');
    $check('Proxy chains are not guessed',recovery_client_ip(['REMOTE_ADDR'=>'127.0.0.1','HTTP_X_REAL_IP'=>'198.51.100.2, 127.0.0.1'],'127.0.0.1')==='127.0.0.1');
    // Random documentation-only IPv6 peers make repeated runs independent; no real address is contacted.
    $_SERVER['REMOTE_ADDR']='2001:db8:'.bin2hex(random_bytes(2)).':'.bin2hex(random_bytes(2)).'::1';
    $allowed=0;$blocked=0;
    for($i=0;$i<24;$i++) {
        $result=authenticate_password($db,'throttle-unit@example.invalid','fictional-invalid');
        if (!empty($result['rate_limited'])) $blocked++;else $allowed++;
    }
    $check('Password failures are bounded across shared verification policy',$allowed===20 && $blocked===4);
    $admin=password_attempt($db,'admin','fictional',static fn()=>['success'=>true]);
    $check('Admin/member failures share the peer admission limit',!empty($admin['rate_limited']));
    $_SERVER['REMOTE_ADDR']='2001:db8:'.bin2hex(random_bytes(2)).':'.bin2hex(random_bytes(2)).'::2';
    $_SERVER['HTTP_X_FORWARDED_FOR']='127.0.0.1';$_SERVER['HTTP_X_REAL_IP']='127.0.0.1';
    // Session-local table shadows settings; tests never edit the site's real policy.
    $db->query('CREATE TEMPORARY TABLE site_settings(setting_key varchar(100) PRIMARY KEY,setting_value text,description varchar(255),updated_at timestamp DEFAULT CURRENT_TIMESTAMP)');
    require_once '/var/www/html/includes/settings.php';
    $check('Invalid token/rate tuning values cannot be saved',!setSetting($db,'rate_limit_per_minute','0') && !setSetting($db,'rate_limit_per_hour','-1') && !setSetting($db,'token_expiry_minutes','181'));
    $check('Valid token lifetime is persisted in the temporary policy',setSetting($db,'token_expiry_minutes','15') && getSetting($db,'token_expiry_minutes')==='15');
    setSetting($db,'rate_limit_per_minute','3');
    $limiter=new RateLimiter($db);$allowed=0;
    for($i=0;$i<12;$i++) if($limiter->checkLimit('fictional_probe')['allowed']) $allowed++;
    $check('Token limiter enforces configured minute budget despite spoofed loopback headers',$allowed===3);
    require_once '/var/www/html/includes/session.php';
    $_SERVER['REQUEST_METHOD']='GET';$_SESSION['admin_id']=2147483647;
    require_once '/var/www/html/admin/config.php';
    $check('A nonexistent administrator cannot retain session access',!isset($_SESSION['admin_id']));
    session_destroy(); // Only this CLI-created fictional session, never a browser session.
    $result=['tests'=>$rows,'summary'=>['total'=>count($rows),'passed'=>count(array_filter($rows,static fn($r)=>$r['passed']))]];
    $result['summary']['failed']=$result['summary']['total']-$result['summary']['passed'];
    echo json_encode($result).PHP_EOL;exit($result['summary']['failed'] ? 1 : 0);
}catch(Throwable $e){echo json_encode(['success'=>false,'error'=>'local_security_checks_unavailable','category'=>get_class($e),'code'=>$e->getCode(),'file'=>basename($e->getFile()),'line'=>$e->getLine()]).PHP_EOL;exit(1);}

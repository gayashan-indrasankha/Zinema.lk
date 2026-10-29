<?php
/** Private native-demo bootstrap. Never installed in the public application. */
function native_demo_plain($path) {
    $normal=str_replace('\\','/',$path);
    if (is_link($path) || str_replace('\\','/',realpath($path) ?: '')!==$normal) return false;
    for ($p=$path;dirname($p)!==$p;$p=dirname($p)) if (is_link($p)) return false;
    return true;
}
function native_demo_bootstrap($home) {
    $root=str_replace('\\','/',dirname(__DIR__,2));$home=str_replace('\\','/',$home);
    if (dirname($home)!==$root.'/.runtime' || !preg_match('/^audit-[0-9]{8}-[0-9]{6}$/D',basename($home)) || !native_demo_plain($home)) throw new RuntimeException();
    $path=$home.'/native-config.json';
    if (!native_demo_plain($path) || !is_file($path) || filesize($path)>16384) throw new RuntimeException();
    $c=json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR);
    $owner=json_decode(file_get_contents($home.'/ownership.json'),true,16,JSON_THROW_ON_ERROR);
    if (($c['schema'] ?? null)!==1 || ($c['external_connections'] ?? null)!==false || ($c['publishing'] ?? null)!==false ||
        !preg_match('/^zinema-native-[a-f0-9]{12}$/D',$c['owner'] ?? '') || ($owner['native_owner'] ?? '')!==$c['owner']) throw new RuntimeException();
    $e=$c['environment'] ?? [];
    if (($e['MAIL_HOST'] ?? null)!=='127.0.0.1' || !in_array($e['MAIL_PORT'] ?? null,['1025','11025'],true)) throw new RuntimeException();
    $fixed=['APP_ENV'=>'test','BASE_URL'=>'http://127.0.0.1:8088','DB_HOST'=>'127.0.0.1','DB_PORT'=>'3307',
        'DB_NAME'=>'zinema_recovery_test','DB_USER'=>'zinema_recovery_test','ALLOW_LIVE_INTEGRATIONS'=>'false',
        'MAIL_MODE'=>'local','MAIL_HOST'=>'127.0.0.1','MAIL_PORT'=>$e['MAIL_PORT'],'MAIL_USERNAME'=>'local-sink','MAIL_PASSWORD'=>'local-unused',
        'MAIL_FROM_EMAIL'=>'demo@example.invalid','MAIL_FROM_NAME'=>'Zinema Local Demo','OAUTH_MODE'=>'local',
        'GOOGLE_CLIENT_ID'=>'zinema-local','GOOGLE_REDIRECT_URI'=>'http://127.0.0.1:8088/google-callback.php',
        'OAUTH_AUTHORIZE_URL'=>'http://127.0.0.1:9081/authorize','OAUTH_TOKEN_URL'=>'http://127.0.0.1:9081/token','OAUTH_USERINFO_URL'=>'http://127.0.0.1:9081/userinfo',
        'WHATSAPP_MODE'=>'local','MEDIA_MODE'=>'disabled','PAYMENT_MODE'=>'disabled','EXTRACTOR_MODE'=>'local',
        'FB_VIDEO_API_URL'=>'http://127.0.0.1:3334','ZINEMA_NATIVE_FIXTURES'=>'1','TRUSTED_PROXY_IPS'=>'',
        'SESSION_SAVE_PATH'=>$home.'/sessions','UPLOAD_QUOTA_MODE'=>'native','UPLOAD_STORAGE_STATE'=>$home.'/native/storage',
        'UPLOAD_STORAGE_OWNER'=>$c['owner'],'UPLOAD_MAX_BYTES'=>'268435456','UPLOAD_MAX_FILES'=>'1000',
        'UPLOAD_QUARANTINE_MAX_BYTES'=>'67108864','UPLOAD_QUARANTINE_MAX_FILES'=>'500'];
    foreach ($fixed as $key=>$value) if (($e[$key] ?? null)!==$value) throw new RuntimeException();
    foreach (['WHATSAPP_API_KEY','EXTRACTOR_API_KEY','GOOGLE_CLIENT_SECRET'] as $key)
        if (!is_string($e[$key] ?? null) || !preg_match('/^[a-f0-9]{64}$/D',$e[$key])) throw new RuntimeException();
    if (!preg_match('/^[a-f0-9]{48}$/D',$e['DB_PASS'] ?? '') || !preg_match('/^[a-f0-9]{64}$/D',$c['control_key'] ?? '')) throw new RuntimeException();
    foreach (['web','web/uploads','native','native/storage','native/bot','sessions','db'] as $dir) if (!native_demo_plain($home.'/'.$dir)) throw new RuntimeException();
    if (PHP_SAPI!=='cli' && ($_SERVER['REMOTE_ADDR'] ?? '')!=='127.0.0.1') throw new RuntimeException();
    // No inherited environment flag can activate a production/provider branch.
    foreach ($fixed+array_intersect_key($e,array_flip(['DB_PASS','WHATSAPP_API_KEY','EXTRACTOR_API_KEY','GOOGLE_CLIENT_SECRET'])) as $key=>$value) putenv($key.'='.$value);
    foreach (['ZINEMA_TWO_HOST_FIXTURES','ZINEMA_PROMOTION_FIXTURES','ZINEMA_EXTRACTOR_FIXTURES'] as $key) putenv($key.'=0');
    $live=false;$liveFile=$home.'/native/whatsapp-live.json';
    if (file_exists($liveFile)) {
        if (!native_demo_plain($liveFile) || filesize($liveFile)>16384) throw new RuntimeException();
        $a=json_decode(file_get_contents($liveFile),true,16,JSON_THROW_ON_ERROR);
        if (($a['schema'] ?? null)!==1 || ($a['owner'] ?? '')!==$c['owner'] || ($a['mode'] ?? '')!=='owner-local-whatsapp' || ($a['liveWhatsApp'] ?? null)!==true || ($a['production'] ?? null)!==false || ($a['publishing'] ?? null)!==false || ($a['automaticJobs'] ?? null)!==false || !preg_match('/^[0-9]{8,15}$/D',$a['accountPhone'] ?? '') || ($a['recipients'] ?? [])!==[$a['accountPhone']] || ($a['maxAttachmentBytes'] ?? null)!==20971520 || ($a['sourceRoot'] ?? '')!=='D:\\Zinema.lk\\whatsapp-bot-deploy') throw new RuntimeException();
        $live=true;
    }
    define('ZINEMA_NATIVE_CONTEXT',['root'=>$root,'home'=>$home,'public'=>$home.'/web','owner'=>$c['owner'],'environment'=>$e,'control_key'=>$c['control_key'],'real_whatsapp'=>$live]);
}
function native_demo_db() {
    if (!defined('ZINEMA_NATIVE_CONTEXT')) throw new RuntimeException();
    require_once ZINEMA_NATIVE_CONTEXT['public'].'/includes/environment.php';
    if (!recovery_native_fixture()) throw new RuntimeException();
    $db=new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME,DB_PORT);$db->set_charset('utf8mb4');
    $row=$db->query('SELECT DATABASE(),@@port,@@datadir')->fetch_row();
    if ($row[0]!==DB_NAME || (int)$row[1]!==3307 || strtolower(rtrim(str_replace('\\','/',$row[2]),'/'))!==strtolower(ZINEMA_NATIVE_CONTEXT['home'].'/db')) throw new RuntimeException();
    return $db;
}

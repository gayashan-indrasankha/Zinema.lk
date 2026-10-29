<?php
/** Private configuration adapter. No application/database/network bootstrap here. */
function stellar_plain_directory($path) {
    if (!is_string($path) || strpos($path, "\0")!==false || !is_dir($path) || is_link($path)) return false;
    $real=str_replace('\\','/',realpath($path) ?: '');
    if ($real!==str_replace('\\','/',$path)) return false;
    for ($part=$path; dirname($part)!==$part; $part=dirname($part)) if (is_link($part)) return false;
    return true;
}
function stellar_runtime_context($c,$private,$public,$remote=null) {
    $deny=static function(){throw new RuntimeException('staging_configuration_denied');};
    $private=str_replace('\\','/',$private);$public=str_replace('\\','/',$public);
    if (!is_array($c) || ($c['version'] ?? null)!==1 || ($c['purpose'] ?? '')!=='private-staging-runtime' ||
        ($c['enabled'] ?? null)!==true || ($c['production_access'] ?? null)!==false || ($c['publishing'] ?? null)!==false) $deny();
    if (!stellar_plain_directory($private) || !stellar_plain_directory($public)) $deny();
    $a=str_replace('\\','/',$private).'/';$b=str_replace('\\','/',$public).'/';
    if (str_starts_with($a,$b) || str_starts_with($b,$a)) $deny();
    $fixture=($c['mode'] ?? '')==='local-fixture';
    if (!$fixture && ($c['mode'] ?? '')!=='private-staging') $deny();
    $env=$c['environment'] ?? []; $base=$env['BASE_URL'] ?? ''; $parsed=is_string($base)?parse_url($base):false;
    if (!$parsed || isset($parsed['user'],$parsed['pass']) || isset($parsed['query']) || isset($parsed['fragment']) ||
        isset($parsed['user']) || isset($parsed['pass']) || !empty($parsed['path']) ||
        ($parsed['scheme'] ?? '')!==($fixture?'http':'https')) $deny();
    if ($fixture && (($parsed['host'] ?? '')!=='127.0.0.1' || ($env['APP_ENV'] ?? '')!=='test' ||
        ($env['DB_HOST'] ?? '')!=='127.0.0.1' || ($env['DB_NAME'] ?? '')!=='zinema_recovery_test' ||
        !str_starts_with($env['DB_USER'] ?? '','zinema_recovery_'))) $deny();
    if (!$fixture && (($env['APP_ENV'] ?? '')!=='production' || ($env['DB_HOST'] ?? '')!=='localhost' ||
        !preg_match('/^[a-z0-9]{1,16}_[a-z0-9_]{1,32}$/D',$env['DB_NAME'] ?? '') ||
        !preg_match('/^[a-z0-9]{1,16}_[a-z0-9_]{1,32}$/D',$env['DB_USER'] ?? '') ||
        !preg_match('/^[a-z0-9.-]+$/D',$parsed['host'] ?? '') || str_ends_with($parsed['host'] ?? '', '.invalid'))) $deny();
    if (($env['ALLOW_LIVE_INTEGRATIONS'] ?? null)!=='false' || ($env['TRUSTED_PROXY_IPS'] ?? null)!=='') $deny();
    foreach (['MAIL','OAUTH','WHATSAPP','MEDIA','PAYMENT'] as $name) if (($env[$name.'_MODE'] ?? '')!=='disabled') $deny();
    if (!in_array($env['EXTRACTOR_MODE'] ?? '',['disabled','account-queue'],true) || ($env['UPLOAD_QUOTA_MODE'] ?? '')!=='account') $deny();
    foreach (['ZINEMA_TWO_HOST_FIXTURES','ZINEMA_PROMOTION_FIXTURES','ZINEMA_EXTRACTOR_FIXTURES'] as $name)
        if (($env[$name] ?? '')!=='0') $deny();
    foreach (['DB_PASS','DB_PORT','SESSION_SAVE_PATH','UPLOAD_STORAGE_STATE','UPLOAD_STORAGE_OWNER','UPLOAD_MAX_BYTES','UPLOAD_MAX_FILES','UPLOAD_QUARANTINE_MAX_BYTES','UPLOAD_QUARANTINE_MAX_FILES'] as $key)
        if (!is_string($env[$key] ?? null) || $env[$key]==='' || str_starts_with($env[$key],'REPLACE_') || str_contains($env[$key],"\0")) $deny();
    if (!ctype_digit($env['DB_PORT']) || (int)$env['DB_PORT']<1 || (int)$env['DB_PORT']>65535 || (!$fixture && $env['DB_PORT']!=='3306')) $deny();
    if ($env['SESSION_SAVE_PATH']!==$private.'/sessions' || $env['UPLOAD_STORAGE_STATE']!==$private.'/storage' ||
        !preg_match('/^zinema-account-[a-f0-9]{16}$/D',$env['UPLOAD_STORAGE_OWNER'])) $deny();
    foreach (['sessions','storage','extractor'] as $dir) if (!stellar_plain_directory($private.'/'.$dir)) $deny();
    if (function_exists('posix_geteuid')) foreach ([$private,$private.'/sessions',$private.'/storage',$private.'/extractor'] as $dir)
        if (fileowner($dir)!==posix_geteuid() || (fileperms($dir)&0077)!==0) $deny();
    if (!stellar_plain_directory($public.'/uploads')) $deny();
    $clients=$c['allowed_clients'] ?? null;
    if (!is_array($clients) || !$clients || count($clients)>16) $deny();
    foreach ($clients as $client) if (!is_string($client) || !filter_var($client,FILTER_VALIDATE_IP) || ($fixture && $client!=='127.0.0.1')) $deny();
    if ($remote!==null && !in_array($remote,$clients,true)) $deny();
    $key=$c['extractor_worker_key'] ?? '';
    if (!is_string($key) || !preg_match('/^[a-f0-9]{64}$/D',$key)) $deny();
    return ['mode'=>$c['mode'],'private'=>str_replace('\\','/',$private),'public'=>str_replace('\\','/',$public),'environment'=>$env,'worker_key'=>$key];
}
function stellar_apply_context($context) {
    if (defined('ZINEMA_STELLAR_CONTEXT')) throw new RuntimeException('staging_configuration_denied');
    // Only this allowlist is injected; unknown inherited adapter/fixture flags cannot select a live path.
    foreach ($context['environment'] as $key=>$value) {
        if (!preg_match('/^[A-Z][A-Z0-9_]+$/D',$key) || !is_string($value)) throw new RuntimeException('staging_configuration_denied');
        if (in_array($key,['APP_ENV','BASE_URL','ALLOW_LIVE_INTEGRATIONS','TRUSTED_PROXY_IPS','DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS','SESSION_SAVE_PATH','MAIL_MODE','OAUTH_MODE','WHATSAPP_MODE','MEDIA_MODE','PAYMENT_MODE','EXTRACTOR_MODE','UPLOAD_QUOTA_MODE','UPLOAD_STORAGE_STATE','UPLOAD_STORAGE_OWNER','UPLOAD_MAX_BYTES','UPLOAD_MAX_FILES','UPLOAD_QUARANTINE_MAX_BYTES','UPLOAD_QUARANTINE_MAX_FILES','ZINEMA_TWO_HOST_FIXTURES','ZINEMA_PROMOTION_FIXTURES','ZINEMA_EXTRACTOR_FIXTURES'],true)) putenv($key.'='.$value);
    }
    define('ZINEMA_STELLAR_CONTEXT',$context);
}

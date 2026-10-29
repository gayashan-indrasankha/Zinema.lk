<?php
if (!defined('ZINEMA_NATIVE_CONTEXT')) {http_response_code(404);exit;}
require_once ZINEMA_NATIVE_CONTEXT['public'].'/includes/environment.php';
if (!recovery_native_fixture()) {http_response_code(404);exit;}
require_once ZINEMA_NATIVE_CONTEXT['public'].'/admin/config.php';
$nativeIdentity=$conn->query('SELECT DATABASE(),@@port,@@datadir')->fetch_row();
if ($nativeIdentity[0]!==DB_NAME || (int)$nativeIdentity[1]!==3307 || strtolower(rtrim(str_replace('\\','/',$nativeIdentity[2]),'/'))!==strtolower(ZINEMA_NATIVE_CONTEXT['home'].'/db')) {http_response_code(503);exit('Local fixture identity unavailable.');}
header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
function native_admin() {
    if (empty($_SESSION['admin_id'])) {header('Location: /admin/login.php',true,303);exit;}
}
function native_call($route,$input=[]) {
    $mail=$route==='mail';
    if (!$mail && !in_array($route,['status','control','deliver'],true)) throw new RuntimeException();
    $url=$mail?'http://127.0.0.1:9081/_test/outbox':'http://127.0.0.1:9090/'.$route;
    $curl=curl_init($url);$raw='';
    try {
        curl_setopt_array($curl,[CURLOPT_PROXY=>'',CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT_MS=>1000,CURLOPT_TIMEOUT_MS=>5000,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json',($mail?'X-Test-Key: ':'X-Local-Control: ').ZINEMA_NATIVE_CONTEXT['control_key']],
            CURLOPT_WRITEFUNCTION=>static function($handle,$chunk)use(&$raw){if(strlen($raw)+strlen($chunk)>1048576)return 0;$raw.=$chunk;return strlen($chunk);}]);
        if (!$mail) curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($input)]);
        if (curl_exec($curl)===false) throw new RuntimeException();
        $status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);$value=json_decode($raw,true,16,JSON_THROW_ON_ERROR);
        if (!is_array($value)) throw new RuntimeException();
        return [$status,$value];
    } finally {curl_close($curl);}
}
function native_escape($value) {return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
function native_page($title) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.native_escape($title).'</title><style>body{font:16px/1.5 system-ui;background:#101526;color:#edf2ff;margin:0}main{max-width:980px;margin:auto;padding:24px}a{color:#b8c8ff}nav{display:flex;gap:18px;flex-wrap:wrap}section{background:#1c2540;border-radius:12px;padding:18px;margin:20px 0}button,a.button{background:#5872df;border:0;border-radius:7px;padding:10px 15px;color:white;cursor:pointer}input{padding:10px}pre{white-space:pre-wrap;overflow-wrap:anywhere}video{max-width:100%;width:420px}table{width:100%;border-collapse:collapse}td,th{text-align:left;border-bottom:1px solid #44516e;padding:8px;overflow-wrap:anywhere}</style></head><body><main><h1>'.native_escape($title).'</h1><nav><a href="/movies">Website</a><a href="/admin/index.php">Administrator</a><a href="/admin/manage_shots.php">Extractor</a><a href="/admin/whatsapp-bot-tracking.php">Bot tracking</a><a href="/__local/index.php">Local console</a><a href="/__local/inbox.php">Fake inbox</a></nav><p>'.(!empty(ZINEMA_NATIVE_CONTEXT['real_whatsapp'])?'Owner-approved real WhatsApp connection. Mail, OAuth and Facebook remain local substitutes.':'Local substitutes only. No real mail, OAuth, Facebook or WhatsApp account is contacted.').'</p>';
}

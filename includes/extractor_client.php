<?php
require_once __DIR__.'/media_url.php';
final class ExtractorFailure extends RuntimeException {
    public $httpStatus;
    public function __construct($code,$status=502) { parent::__construct($code);$this->httpStatus=$status; }
}
function extractor_transport($destination,$body,$key,$unixSocket=null) {
    if (!function_exists('curl_init')) throw new ExtractorFailure('extractor_configuration',503);
    $stream=curl_init($destination);$raw='';
    try {
        curl_setopt_array($stream,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json','X-Extractor-Key: '.$key],
            CURLOPT_PROXY=>'',CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT_MS=>1000,CURLOPT_TIMEOUT_MS=>4000,
            CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTP|CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION=>static function($ch,$chunk) use (&$raw) {if(strlen($raw)+strlen($chunk)>8192)return 0;$raw.=$chunk;return strlen($chunk);}]);
        if ($unixSocket!==null) curl_setopt($stream,CURLOPT_UNIX_SOCKET_PATH,$unixSocket);
        if (curl_exec($stream)===false || stripos(curl_getinfo($stream,CURLINFO_CONTENT_TYPE) ?: '', 'application/json')!==0) throw new ExtractorFailure('extractor_unavailable');
        return [(int)curl_getinfo($stream,CURLINFO_HTTP_CODE),$raw];
    } finally {curl_close($stream);}
}
function extractor_request($action,$source=null,$transport=null) {
    if (!in_array($action,['health','extract'],true)) throw new ExtractorFailure('invalid_action',400);
    if ($action==='extract' && !valid_facebook_url($source)) throw new ExtractorFailure('invalid_source',422);
    // Both branches are original fixtures only; neither enables an upstream adapter.
    $private=EXTRACTOR_MODE==='private-fixture';$socket=null;
    if ($private) {
        $socket='/run/zinema/extractor.sock';$keyFile='/run/secrets/extractor_key';
        if(APP_ENV!=='production'||getenv('ALLOW_LIVE_INTEGRATIONS')!=='false'||getenv('ZINEMA_PROMOTION_FIXTURES')!=='1'||FB_VIDEO_API_URL!=='http://localhost'||BASE_URL!=='https://127.0.0.1:8443'||is_link($socket)||realpath(dirname($socket))!=='/run/zinema'||is_link($keyFile)||!is_file($keyFile)||filesize($keyFile)>128)throw new ExtractorFailure('extractor_configuration',503);
        if(@filetype($socket)!=='socket')throw new ExtractorFailure('extractor_unavailable');
        if((@fileperms($socket)&7)!==0)throw new ExtractorFailure('extractor_configuration',503);
        $key=trim(file_get_contents($keyFile));
    }else {
        if (EXTRACTOR_MODE!=='local' || !in_array(APP_ENV,['development','test'],true)) throw new ExtractorFailure('extractor_disabled',503);
        $key=getenv('EXTRACTOR_API_KEY') ?: '';
        if (!preg_match('~^http://127\.0\.0\.1:[0-9]{1,5}$~D',FB_VIDEO_API_URL)) throw new ExtractorFailure('extractor_configuration',503);
    }
    if(!preg_match('/^[a-f0-9]{64}$/D',$key))throw new ExtractorFailure('extractor_configuration',503);
    $payload=json_encode($action==='health' ? [] : ['url'=>$source],JSON_THROW_ON_ERROR);
    [$status,$raw]=($transport ?? 'extractor_transport')(FB_VIDEO_API_URL.($action==='health'?'/health':'/api/extract'),$payload,$key,$socket);
    if ($status===429) throw new ExtractorFailure('extractor_busy',429);
    if ($status!==200) throw new ExtractorFailure('extractor_unavailable');
    try {$data=json_decode($raw,true,16,JSON_THROW_ON_ERROR);} catch (Throwable $e) {throw new ExtractorFailure('extractor_unavailable');}
    if (!is_array($data) || ($data['success'] ?? null)!==true || ($data['mode'] ?? '')!==EXTRACTOR_MODE) throw new ExtractorFailure('extractor_unavailable');
    if ($action==='health') {
        if (!is_numeric($data['uptime'] ?? null) || $data['uptime']<0) throw new ExtractorFailure('extractor_unavailable');
        return ['success'=>true,'mode'=>EXTRACTOR_MODE,'uptime'=>(int)$data['uptime'],'version'=>'local-contract-1'];
    }
    $url=$data['url'] ?? null;$media=is_string($url) && strlen($url)<=4096 ? parse_url($url) : false;$public=parse_url(BASE_URL);
    if (!$media || isset($media['user']) || isset($media['pass']) || isset($media['query']) || isset($media['fragment']) ||
        ($media['scheme'] ?? '')!==$public['scheme'] || ($media['host'] ?? '')!==$public['host'] || ($media['port'] ?? 80)!==($public['port'] ?? 80) ||
        !(!$private && ($media['path'] ?? '')==='/__dev/extractor-media.php' || preg_match('~^/uploads/(?:trailers/)?[a-f0-9]{32}\.mp4$~D',$media['path'] ?? ''))) throw new ExtractorFailure('extractor_unavailable');
    return ['success'=>true,'mode'=>EXTRACTOR_MODE,'url'=>$url,'method'=>'local-fixture','duration_ms'=>0];
}

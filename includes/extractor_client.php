<?php
require_once __DIR__.'/media_url.php';
final class ExtractorFailure extends RuntimeException {
    public $httpStatus;
    public function __construct($code,$status=502) { parent::__construct($code);$this->httpStatus=$status; }
}
function extractor_transport($destination,$body,$key) {
    if (!function_exists('curl_init')) throw new ExtractorFailure('extractor_configuration',503);
    $stream=curl_init($destination);$raw='';
    try {
        curl_setopt_array($stream,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json','X-Extractor-Key: '.$key],
            CURLOPT_PROXY=>'',CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT_MS=>1000,CURLOPT_TIMEOUT_MS=>4000,
            CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTP|CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION=>static function($ch,$chunk) use (&$raw) {if(strlen($raw)+strlen($chunk)>8192)return 0;$raw.=$chunk;return strlen($chunk);}]);
        if (curl_exec($stream)===false || stripos(curl_getinfo($stream,CURLINFO_CONTENT_TYPE) ?: '', 'application/json')!==0) throw new ExtractorFailure('extractor_unavailable');
        return [(int)curl_getinfo($stream,CURLINFO_HTTP_CODE),$raw];
    } finally {curl_close($stream);}
}
function extractor_request($action,$source=null,$transport=null) {
    if (!in_array($action,['health','extract'],true)) throw new ExtractorFailure('invalid_action',400);
    if ($action==='extract' && !valid_facebook_url($source)) throw new ExtractorFailure('invalid_source',422);
    // This phase grants local fake access only, never production or live extraction.
    if (EXTRACTOR_MODE!=='local' || !in_array(APP_ENV,['development','test'],true)) throw new ExtractorFailure('extractor_disabled',503);
    $base=parse_url(FB_VIDEO_API_URL);$key=getenv('EXTRACTOR_API_KEY') ?: '';
    if (!$base || !preg_match('~^http://127\.0\.0\.1:[0-9]{1,5}$~D',FB_VIDEO_API_URL) || !preg_match('/^[a-f0-9]{64}$/D',$key)) throw new ExtractorFailure('extractor_configuration',503);
    $payload=json_encode($action==='health' ? [] : ['url'=>$source],JSON_THROW_ON_ERROR);
    [$status,$raw]=($transport ?? 'extractor_transport')(FB_VIDEO_API_URL.($action==='health'?'/health':'/api/extract'),$payload,$key);
    if ($status===429) throw new ExtractorFailure('extractor_busy',429);
    if ($status!==200) throw new ExtractorFailure('extractor_unavailable');
    try {$data=json_decode($raw,true,16,JSON_THROW_ON_ERROR);} catch (Throwable $e) {throw new ExtractorFailure('extractor_unavailable');}
    if (!is_array($data) || ($data['success'] ?? null)!==true || ($data['mode'] ?? '')!=='local') throw new ExtractorFailure('extractor_unavailable');
    if ($action==='health') {
        if (!is_numeric($data['uptime'] ?? null) || $data['uptime']<0) throw new ExtractorFailure('extractor_unavailable');
        return ['success'=>true,'mode'=>'local','uptime'=>(int)$data['uptime'],'version'=>'local-contract-1'];
    }
    $url=$data['url'] ?? null;$media=is_string($url) && strlen($url)<=4096 ? parse_url($url) : false;$public=parse_url(BASE_URL);
    if (!$media || isset($media['user']) || isset($media['pass']) || isset($media['query']) || isset($media['fragment']) ||
        ($media['scheme'] ?? '')!==$public['scheme'] || ($media['host'] ?? '')!==$public['host'] || ($media['port'] ?? 80)!==($public['port'] ?? 80) ||
        !(($media['path'] ?? '')==='/__dev/extractor-media.php' || preg_match('~^/uploads/(?:trailers/)?[a-f0-9]{32}\.mp4$~D',$media['path'] ?? ''))) throw new ExtractorFailure('extractor_unavailable');
    return ['success'=>true,'mode'=>'local','url'=>$url,'method'=>'local-fixture','duration_ms'=>0];
}

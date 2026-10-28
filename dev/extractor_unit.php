<?php
if (PHP_SAPI!=='cli') exit(1);
define('APP_ENV','test');define('EXTRACTOR_MODE','local');define('FB_VIDEO_API_URL','http://127.0.0.1:3334');define('BASE_URL','http://127.0.0.1:8088');
putenv('EXTRACTOR_API_KEY='.bin2hex(random_bytes(32)));
require_once '/var/www/html/includes/extractor_client.php';
$rows=[];
function record($name,$passed) {global $rows;$rows[]=['name'=>$name,'passed'=>(bool)$passed];}
function rejected($run,$code) {try{$run();return false;}catch(ExtractorFailure $e){return $e->getMessage()===$code;}}
foreach ([['health',null],['extract','https://facebook.com/reel/fictional']] as [$action,$url]) {
    $response=['success'=>true,'mode'=>'local','uptime'=>10,'url'=>'http://127.0.0.1:8088/__dev/extractor-media.php','private'=>'fictional-private'];
    $result=extractor_request($action,$url,static function($destination,$body,$key) use($response) {return [200,json_encode($response)];});
    record('Client projects only permitted '.$action.' fields',!isset($result['private']) && $result['success']);
}
foreach ([['status',503,'{}'],['redirect',302,'{}'],['invalid JSON',200,'{'],['wrong mode',200,'{"success":true,"mode":"live"}'],['wrong success type',200,'{"success":1,"mode":"local"}']] as [$name,$status,$raw])
    record('Client rejects '.$name,rejected(static fn()=>extractor_request('health',null,static fn()=>[$status,$raw]),'extractor_unavailable'));
record('Client preserves busy classification',rejected(static fn()=>extractor_request('health',null,static fn()=>[429,'{}']),'extractor_busy'));
foreach (['https://example.invalid/a.mp4','http://127.0.0.1:9081/authorize','http://user@127.0.0.1:8088/uploads/a.mp4','http://127.0.0.1:8088/__dev/control.php','http://127.0.0.1:8088/uploads/../admin/login.php'] as $url)
    record('Client rejects unsafe media result '.count($rows),rejected(static fn()=>extractor_request('extract','https://facebook.com/f',static fn()=>[200,json_encode(['success'=>true,'mode'=>'local','url'=>$url])]),'extractor_unavailable'));
putenv('EXTRACTOR_API_KEY=wrong');record('Missing/invalid private credential fails before transport',rejected(static fn()=>extractor_request('health',null,static function(){throw new LogicException();}),'extractor_configuration'));
$result=['scope'=>'Pure local private-client validation; no transport/provider used','tests'=>$rows,'summary'=>['total'=>count($rows),'passed'=>count(array_filter($rows,fn($r)=>$r['passed'])),'failed'=>count(array_filter($rows,fn($r)=>!$r['passed']))]];
echo json_encode($result).PHP_EOL;exit($result['summary']['failed']?1:0);

<?php
if (PHP_SAPI!=='cli') exit(1);
foreach (['DB_PASS'=>'db_password','GOOGLE_CLIENT_SECRET'=>'oauth_secret','EXTRACTOR_API_KEY'=>'extractor_key'] as $name=>$file) putenv($name.'='.trim(file_get_contents('/run/secrets/'.$file)));
require_once '/var/www/html/includes/environment.php';
if (APP_ENV!=='development' || DB_NAME!=='zinema_recovery_development' || EXTRACTOR_MODE!=='local' || getenv('ZINEMA_EXTRACTOR_FIXTURES')!=='1') exit(1);
require_once '/var/www/html/includes/extractor_client.php';
$rows=[];$key=getenv('EXTRACTOR_API_KEY');
function probe($path) {global $key;return extractor_transport('http://127.0.0.1:3335/'.$path,'{}',$key);}
foreach (['oversize','html','timeout'] as $case) {
    $start=microtime(true);$denied=false;try{probe($case);}catch(ExtractorFailure $e){$denied=$e->getMessage()==='extractor_unavailable';}
    $rows[]=['name'=>'Transport rejects '.$case,'passed'=>$denied && ($case!=='timeout' || microtime(true)-$start<4.8)];
}
$redirect=probe('redirect');$counts=json_decode(probe('counts')[1],true);
$rows[]=['name'=>'Transport does not follow redirects or forward its key','passed'=>$redirect[0]===302 && $counts['followed']===0];
$result=['scope'=>'Deterministic loopback faults only; no provider request','tests'=>$rows,'summary'=>['total'=>count($rows),'passed'=>count(array_filter($rows,fn($r)=>$r['passed'])),'failed'=>count(array_filter($rows,fn($r)=>!$r['passed']))]];
echo json_encode($result).PHP_EOL;exit($result['summary']['failed']?1:0);

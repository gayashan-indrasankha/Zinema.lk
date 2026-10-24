<?php
declare(strict_types=1);
// Only the owned test router binds these services. No application config, DB or providers.
require_once __DIR__.'/fixtures.php';
use Zinema\Identity\{Account,Principal,NativeSession,SessionState,Authentication,Recovery,Abuse,Audit,Authorization,Csrf,Limiter,SecurityFailure,Password};
if (PHP_SAPI!=='cli-server' || ($_SERVER['REMOTE_ADDR']??'')!=='127.0.0.1') { http_response_code(404);exit; }
$root=realpath(getenv('ZL19_FIXTURE_ROOT')?:'');
if (!$root || !str_starts_with(basename($root),'zinema-zl19-http-') || is_link($root)) { http_response_code(503);exit; }
$origin=getenv('ZL19_FIXTURE_ORIGIN')?:'';
$url=parse_url($origin);
if (($url['host']??'')!=='127.0.0.1' || ($url['scheme']??'')!=='http' || ($_SERVER['HTTP_HOST']??'')!=='127.0.0.1:'.($url['port']??'')) { http_response_code(403);exit; }
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
header('Content-Type: application/json');header('Cache-Control: private, no-store');header('Referrer-Policy: no-referrer');
if ($path==='/health') { echo '{"ready":true}';exit; }
$allowed=['/form','/register','/login','/logout','/me','/admin','/account','/reset/request','/reset/consume','/verify/request','/verify/consume','/change'];
if (!in_array($path,$allowed,true)) { http_response_code(404);echo '{"error":"NOT_FOUND"}';exit; }
// This file lock serializes test adapter state only, not production database semantics.
$lock=fopen($root.'/state.json','c+');if (!$lock || !flock($lock,LOCK_EX)) { http_response_code(503);exit; }
$data=json_decode(stream_get_contents($lock),true,32,JSON_THROW_ON_ERROR);
$store=new FixtureStore();$clock=new FixtureClock();$clock->time=$data['now'];
foreach ($data['accounts'] as $a) $store->accounts[$a['id']]=new Account(...$a);
$store->tokens=$data['tokens'];$store->events=$data['events'];
$mail=new FixtureMail();$mail->messages=$data['mail'];
$limiter=new class($data['limits']) implements Limiter {
    public function __construct(public array $buckets) {}
    public function take(string $key,int $now):void {
        foreach ($this->buckets as $id=>$b) if ($now>=$b['until']) unset($this->buckets[$id]);
        if (!isset($this->buckets[$key])) { if(count($this->buckets)>=128)throw new SecurityFailure('RATE_LIMITED',429);$this->buckets[$key]=['count'=>0,'until'=>$now+300]; }
        if($this->buckets[$key]['count']>=5)throw new SecurityFailure('RATE_LIMITED',429);
        $this->buckets[$key]['count']++;
    }
};
try {
    NativeSession::start($origin,'test','127.0.0.1',false,$root.'/sessions');
    $method=$_SERVER['REQUEST_METHOD'];
    $read=in_array($path,['/form','/me','/admin','/account'],true);
    if ($read && $method!=='GET') throw new SecurityFailure('METHOD_NOT_ALLOWED',405);
    $input=[];
    if (!$read) {
        if ((int)($_SERVER['CONTENT_LENGTH']??0)>16384) throw new SecurityFailure('REQUEST_TOO_LARGE',413);
        Csrf::mutation($method,$_SERVER['HTTP_ORIGIN']??'',$origin,$_SESSION,$_SERVER['HTTP_X_CSRF_TOKEN']??null,$clock->time);
        if (strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE']??'')[0]))!=='application/json') throw new SecurityFailure('INVALID_REQUEST',400);
        $body=file_get_contents('php://input',false,null,0,16385);
        if (strlen($body)>16384) throw new SecurityFailure('REQUEST_TOO_LARGE',413);
        try { $input=json_decode($body,true,16,JSON_THROW_ON_ERROR); } catch (Throwable) { throw new SecurityFailure('INVALID_REQUEST',400); }
        if (!is_array($input) || !str_starts_with(ltrim($body),'{')) throw new SecurityFailure('INVALID_REQUEST',400);
    }
    $passwords=new Password();$abuse=new Abuse($limiter,hex2bin($data['key']));$audit=new Audit($store,$clock);
    $auth=new Authentication($store,$passwords,$clock,$abuse,$audit);$recovery=new Recovery($store,$passwords,$clock,$abuse,$audit,$mail);
    if (!isset($_SESSION['identity'])) $_SESSION['identity']=[];
    $principal=SessionState::resolve($_SESSION['identity'],$store,$clock->time);
    $result=match ($path) {
        '/form'=>['csrf'=>Csrf::token($_SESSION,$clock->time)],
        '/register'=>$auth->register($input['email']??null,$input['password']??null,'127.0.0.1'),
        '/login'=>(function()use($auth,$input,$clock){$p=$auth->login($input['email']??null,$input['password']??null,'127.0.0.1');NativeSession::establish($p,$clock->time);return ['authenticated'=>true];})(),
        '/logout'=>(function()use($store,$audit,$principal){if($principal)$store->transaction(fn()=>$audit->record('logout','allowed',$principal->id));NativeSession::logout();return ['authenticated'=>false];})(),
        '/me'=>$principal?['subject'=>$principal->id]:throw new SecurityFailure('AUTHENTICATION_REQUIRED',401),
        '/admin','/account'=>(function()use($store,$path,$principal,$clock){if(!$principal)throw new SecurityFailure('AUTHENTICATION_REQUIRED',401);(new Authorization($store))->require($principal,$path==='/admin'?'admin.enter':'account.read',$path==='/account'?($_GET['owner']??null):null,$_SESSION['identity']['binding'],$clock->time);return ['allowed'=>true];})(),
        '/reset/request','/verify/request'=>$recovery->request($path==='/reset/request'?'reset':'verify',$input['email']??null,'127.0.0.1'),
        '/reset/consume','/verify/consume'=>['accepted'=>$recovery->consume($path==='/reset/consume'?'reset':'verify',$input['token']??null,$input['password']??null,'127.0.0.1')],
        '/change'=>(function()use($auth,$principal,$input){if(!$principal)throw new SecurityFailure('AUTHENTICATION_REQUIRED',401);$auth->change($principal,$input['old']??null,$input['password']??null,'127.0.0.1');return ['accepted'=>true];})(),
    };
    echo json_encode($result,JSON_THROW_ON_ERROR);
} catch (SecurityFailure $e) { http_response_code($e->status);echo json_encode(['error'=>$e->reason],JSON_THROW_ON_ERROR); }
catch (Throwable) { http_response_code(503);echo '{"error":"IDENTITY_UNAVAILABLE"}'; }
finally {
    $data['accounts']=array_values(array_map(fn($a)=>get_object_vars($a),$store->accounts));
    $data['tokens']=$store->tokens;$data['events']=$store->events;$data['mail']=$mail->messages;$data['limits']=$limiter->buckets;
    rewind($lock);ftruncate($lock,0);fwrite($lock,json_encode($data,JSON_THROW_ON_ERROR));fflush($lock);flock($lock,LOCK_UN);fclose($lock);
}

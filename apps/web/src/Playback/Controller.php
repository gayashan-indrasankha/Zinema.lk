<?php
declare(strict_types=1);
namespace Zinema\Playback;
use Zinema\Catalogue\{ApiFailure,Context,ContextProvider,Controller as Json,FixedWindowLimiter,Limiter,MissingContext,Response,UnconfiguredRepository,Access,EntitlementPolicy};
use Zinema\Content\{Clock,UtcClock,UnverifiedCatalogue,Validation};
final class Controller {
    public function __construct(private Service $service,private ContextProvider $contexts,private Limiter $limiter,private Clock $clock,private ?string $origin=null){
        if($origin!==null&&!preg_match('~^https://[a-z0-9.-]+(?::[0-9]+)?$|^http://127\.0\.0\.1:[0-9]+$~D',$origin))throw new \InvalidArgumentException('TRUSTED_ORIGIN_REQUIRED');
    }
    private function context():Context{
        $c=$this->contexts->resolve();if($c===null)throw new ApiFailure(503,'PLAYBACK_NOT_CONFIGURED');
        $this->limiter->consume($c,$this->clock->now());
        if(!$c->territoryVerified||!in_array($c->territory,Validation::TERRITORIES,true)||($c->verifiedAge!==null&&($c->verifiedAge<0||$c->verifiedAge>120))||($c->subject!==null&&!preg_match('/^[a-f0-9]{32}$/D',$c->subject)))throw new ApiFailure(503,'CONTEXT_UNAVAILABLE');
        if($c->subject!==null&&!$c->active)throw new ApiFailure(401,'AUTHENTICATION_REQUIRED');return $c;
    }
    public function handle(string $route,string $method,array $query=[],string $body='',array $headers=[],string $cookie=''):Response{
        $request=bin2hex(random_bytes(16));$extra=[];
        $base=['Cache-Control'=>'private, no-store','Vary'=>'Authorization, Cookie','X-Content-Type-Options'=>'nosniff','Cross-Origin-Resource-Policy'=>'same-origin','Referrer-Policy'=>'no-referrer','X-Request-ID'=>$request];
        try{
            if(!in_array($route,['link','details','session','media'],true))throw new ApiFailure(404,'CONTENT_UNAVAILABLE');
            if($method!==($route==='session'?'POST':'GET'))throw new ApiFailure(405,'METHOD_NOT_ALLOWED');
            if(strlen($body)>1024||strlen(http_build_query($query))>4096)throw new ApiFailure(400,'INVALID_REQUEST');
            if(($headers['sec-fetch-site']??'')==='cross-site')throw new ApiFailure(403,'ORIGIN_DENIED');
            if($route==='session'){
                if($this->origin===null)throw new ApiFailure(503,'PLAYBACK_NOT_CONFIGURED');
                if(($headers['origin']??null)!==$this->origin||($headers['x-zinema-playback']??null)!=='1'||strtolower(trim(explode(';',$headers['content-type']??'')[0]))!=='application/json')throw new ApiFailure(403,'ORIGIN_DENIED');
                if($query!==[])throw new ApiFailure(400,'INVALID_REQUEST');
                try{$input=json_decode($body,true,4,JSON_THROW_ON_ERROR);}catch(\Throwable){throw new ApiFailure(400,'INVALID_REQUEST');}
                if(!is_array($input))throw new ApiFailure(400,'INVALID_REQUEST');
            }else{
                if(isset($headers['origin'])&&$headers['origin']!==$this->origin)throw new ApiFailure(403,'ORIGIN_DENIED');
                $input=$query;
            }
            $context=$this->context();
            if($route==='session'){
                $issued=$this->service->issue($input,$context);$secure=str_starts_with($this->origin,'https:')?'; Secure':'';
                $extra['Set-Cookie']='zinema_playback='.$issued['cookie'].'; Path=/api/playback/; Max-Age=60; HttpOnly; SameSite=Strict'.$secure;
                $data=$issued['descriptor'];
            }elseif($route==='details')$data=$this->service->details($input,$context);
            elseif($route==='link'){
                if(array_keys($input)!==['shot_id']||!is_string($input['shot_id']))throw new ApiFailure(400,'INVALID_REQUEST');
                $data=$this->service->link($input['shot_id'],$context);
            }else{
                if(array_keys($input)!==['file']||!is_string($input['file']))throw new ApiFailure(400,'INVALID_REQUEST');
                $file=$this->service->media($cookie,$input['file'],$context);$bytes=$file['bytes'];$status=200;$extra['Accept-Ranges']='bytes';
                if(isset($headers['range'])&&!str_contains($file['mime'],'mpegurl')){
                    $length=strlen($bytes);if(!preg_match('/^bytes=(\d*)-(\d*)$/D',$headers['range'],$m)||($m[1]===''&&$m[2]===''))throw new ApiFailure(416,'RANGE_INVALID');
                    $start=$m[1]===''?max(0,$length-(int)$m[2]):(int)$m[1];$end=$m[1]===''||$m[2]===''?$length-1:min($length-1,(int)$m[2]);
                    if($start>$end||$start>=$length)throw new ApiFailure(416,'RANGE_INVALID');$bytes=substr($bytes,$start,$end-$start+1);$status=206;$extra['Content-Range']='bytes '.$start.'-'.$end.'/'.$length;
                }
                return new Response($status,[...$base,...$extra,'Content-Type'=>$file['mime'],'Content-Length'=>(string)strlen($bytes)],$bytes);
            }
            $json=Json::encode(['data'=>$data,'requestId'=>$request]);if(strlen($json)>262144)throw new ApiFailure(503,'PLAYBACK_UNAVAILABLE');
            return new Response(200,[...$base,...$extra,'Content-Type'=>'application/json; charset=utf-8'],$json);
        }catch(\Throwable $e){
            $codes=['INVALID_REQUEST'=>400,'SESSION_REQUIRED'=>401,'AUTHENTICATION_REQUIRED'=>401,'ORIGIN_DENIED'=>403,'CONTENT_UNAVAILABLE'=>404,'METHOD_NOT_ALLOWED'=>405,'RANGE_INVALID'=>416,'RATE_LIMITED'=>429,'PLAYBACK_NOT_CONFIGURED'=>503,'PLAYBACK_UNAVAILABLE'=>503,'CATALOGUE_NOT_CONFIGURED'=>503,'CATALOGUE_UNAVAILABLE'=>503,'CONTEXT_UNAVAILABLE'=>503];
            $code=$e instanceof ApiFailure&&isset($codes[$e->reason])?$e->reason:'INTERNAL_ERROR';$status=$codes[$code]??500;
            if($status===405)$extra['Allow']=$route==='session'?'POST':'GET';if($status===429)$extra['Retry-After']=(string)max(1,min(3600,$e->retryAfter));unset($extra['Set-Cookie']);
            return new Response($status,[...$base,...$extra,'Content-Type'=>'application/json; charset=utf-8'],Json::encode(['error'=>['code'=>$code,'message'=>$status===404?'Content unavailable.':'Playback request unavailable.'],'requestId'=>$request]));
        }
    }
}
final class Factory {
    public static function unconfigured():Controller{
        $clock=new UtcClock();$entitlements=new EntitlementPolicy();$access=new Access(new UnverifiedCatalogue(),$entitlements,$clock);
        $service=new Service(new UnconfiguredRepository(),$access,$entitlements,$clock,new UnavailableProvider(),new SessionCodec(random_bytes(32)));
        return new Controller($service,new MissingContext(),new FixedWindowLimiter(),$clock);
    }
}
final class Http {
    public static function run(string $route,?Controller $controller=null):void{
        ini_set('display_errors','0');$headers=[];foreach(['CONTENT_TYPE'=>'content-type','HTTP_ORIGIN'=>'origin','HTTP_X_ZINEMA_PLAYBACK'=>'x-zinema-playback','HTTP_SEC_FETCH_SITE'=>'sec-fetch-site','HTTP_RANGE'=>'range'] as $k=>$v)if(isset($_SERVER[$k]))$headers[$v]=$_SERVER[$k];
        if((int)($_SERVER['CONTENT_LENGTH']??0)>1024)$body=str_repeat('x',1025);else $body=(string)file_get_contents('php://input',false,null,0,1025);
        $r=($controller??Factory::unconfigured())->handle($route,$_SERVER['REQUEST_METHOD']??'',$_GET,$body,$headers,(string)($_COOKIE['zinema_playback']??''));
        http_response_code($r->status);foreach($r->headers as $name=>$value)header($name.': '.$value);echo $r->body;
    }
}

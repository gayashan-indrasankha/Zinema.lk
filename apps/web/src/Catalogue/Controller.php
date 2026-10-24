<?php
declare(strict_types=1);
namespace Zinema\Catalogue;
use Zinema\Content\{Clock,UtcClock,UnverifiedCatalogue,Validation};

final class Response {
    public function __construct(public readonly int $status,public readonly array $headers,public readonly string $body) {}
}
final class Controller {
    public function __construct(private SearchService $service,private ContextProvider $contexts,private Limiter $limiter,private Clock $clock) {}
    public static function encode(array $data):string {
        return json_encode($data,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
    }
    public function handle(string $method,array $input,int $rawQueryBytes=0):Response {
        $requestId=bin2hex(random_bytes(16));$status=200;$extra=[];
        try{
            if($method!=='GET')throw new ApiFailure(405,'METHOD_NOT_ALLOWED');
            if($rawQueryBytes>4096)throw new ApiFailure(400,'INVALID_REQUEST');
            $context=$this->contexts->resolve();
            if($context!==null)$this->limiter->consume($context,$this->clock->now());
            $query=Query::parse($input);
            if($context===null)throw new ApiFailure(503,'CATALOGUE_NOT_CONFIGURED');
            if(!$context->territoryVerified||!in_array($context->territory,Validation::TERRITORIES,true)||
                ($context->subject!==null&&!preg_match('/^[a-f0-9]{32}$/D',$context->subject))||
                ($context->verifiedAge!==null&&($context->verifiedAge<0||$context->verifiedAge>120)))throw new ApiFailure(503,'CONTEXT_UNAVAILABLE');
            if($context->subject!==null&&!$context->active)throw new ApiFailure(401,'AUTHENTICATION_REQUIRED');
            $data=$query->id===null?$this->service->search($query,$context):$this->service->detail($query,$context);
            $body=self::encode(['data'=>$data,'requestId'=>$requestId]);
            if(strlen($body)>262144)throw new ApiFailure(503,'RESPONSE_LIMIT_EXCEEDED');
        }catch(\Throwable $error){
            $codes=['INVALID_REQUEST'=>400,'INVALID_CURSOR'=>400,'AUTHENTICATION_REQUIRED'=>401,'CONTENT_UNAVAILABLE'=>404,
                'METHOD_NOT_ALLOWED'=>405,'RATE_LIMITED'=>429,'CATALOGUE_NOT_CONFIGURED'=>503,'CONTEXT_UNAVAILABLE'=>503,
                'CATALOGUE_UNAVAILABLE'=>503,'SEARCH_BUDGET_EXCEEDED'=>503,'RESPONSE_LIMIT_EXCEEDED'=>503,'PLAYBACK_NOT_CONFIGURED'=>503];
            $code=$error instanceof ApiFailure&&isset($codes[$error->reason])?$error->reason:'INTERNAL_ERROR';
            $status=$codes[$code]??500;
            $messages=[400=>'Invalid catalogue request.',401=>'Authentication required.',404=>'Content unavailable.',
                405=>'Method not allowed.',429=>'Request limit exceeded.',500=>'Catalogue request failed.',503=>'Catalogue service unavailable.'];
            $body=self::encode(['error'=>['code'=>$code,'message'=>$messages[$status]??$messages[500]],'requestId'=>$requestId]);
            if($status===405)$extra['Allow']='GET';
            if($error instanceof ApiFailure&&$status===429)$extra['Retry-After']=(string)max(1,min(3600,$error->retryAfter));
        }
        return new Response($status,['Content-Type'=>'application/json; charset=utf-8','Cache-Control'=>'private, no-store',
            'Vary'=>'Authorization, Cookie','X-Content-Type-Options'=>'nosniff','X-Request-ID'=>$requestId,...$extra],$body);
    }
}
final class Factory {
    /** No configuration flag, request parameter or fixture fallback enables a public data source. */
    public static function unconfigured():Controller {
        $clock=new UtcClock();$access=new Access(new UnverifiedCatalogue(),new EntitlementPolicy(),$clock);
        $service=new SearchService(new UnconfiguredRepository(),$access,new CursorCodec(random_bytes(32)),$clock);
        return new Controller($service,new MissingContext(),new FixedWindowLimiter(),$clock);
    }
}

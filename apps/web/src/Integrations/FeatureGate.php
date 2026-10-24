<?php
declare(strict_types=1);
namespace Zinema\Integrations;
final class IntegrationDisabled extends \RuntimeException {public function __construct(){parent::__construct('Integration unavailable.');}}
final class FeatureGate {
    private static function definitions():array{
        static $policy=null;if($policy===null){$policy=json_decode((string)file_get_contents(dirname(__DIR__,4).'/config/integration-policy.json'),true,8,JSON_THROW_ON_ERROR);if(($policy['version']??null)!==1||!is_array($policy['features']??null))throw new IntegrationDisabled();}return $policy['features'];
    }
    public static function status(string $feature,?array $env=null):array{
        $definitions=self::definitions();if(!array_key_exists($feature,$definitions))throw new IntegrationDisabled();$d=$definitions[$feature];
        $get=static fn($key)=>$env===null?getenv($key):($env[$key]??false);
        $sim=$d['simulation']===true&&$get('APP_ENV')==='test'&&$get($d['flag'])==='local';
        return ['available'=>false,'state'=>$sim?'simulation_only':'disabled','message'=>$d['message']];
    }
    public static function assertLive(string $feature):void{self::status($feature);throw new IntegrationDisabled();}
    public static function requireLive(string $feature):void{if(PHP_SAPI==='cli')self::assertLive($feature);self::deny($feature);}
    public static function deny(string $feature):void{
        ini_set('display_errors','0');$s=self::status($feature);http_response_code(403);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');
        echo json_encode(['success'=>false,'available'=>false,'error'=>['code'=>'INTEGRATION_DISABLED','message'=>$s['message']]],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);exit(PHP_SAPI==='cli'?1:0);
    }
    public static function all():array{$result=[];foreach(array_keys(self::definitions()) as $name)$result[$name]=self::status($name);return $result;}
}

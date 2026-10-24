<?php
declare(strict_types=1);
namespace Zinema\Playback;
use Zinema\Catalogue\ApiFailure;

interface SourceProvider {
    /** Returns a verified ready asset digest, or null. No public origin. */
    public function digest():?string;
    /** Verified local-only bytes; manifests contain only protected same-origin references. */
    public function resource(string $name):array;
}
final class UnavailableProvider implements SourceProvider {
    public function digest():?string{return null;}
    public function resource(string $name):array{throw new ApiFailure(503,'PLAYBACK_UNAVAILABLE');}
}
final class SessionCodec {
    public function __construct(private string $key){if(strlen($key)<32)throw new \InvalidArgumentException('SESSION_KEY_REQUIRED');}
    private static function b64(string $s):string{return rtrim(strtr(base64_encode($s),'+/','-_'),'=');}
    public function issue(array $claims):string{$p=self::b64(json_encode($claims,JSON_THROW_ON_ERROR));return $p.'.'.self::b64(hash_hmac('sha256',$p,$this->key,true));}
    public function progressScope(string $binding):string{return substr(hash_hmac('sha256','progress:'.$binding,$this->key),0,32);}
    public function read(string $cookie,string $binding,int $now):array{
        if(strlen($cookie)>2048||!preg_match('/^([A-Za-z0-9_-]+)\.([A-Za-z0-9_-]{43})$/D',$cookie,$m)||!hash_equals(self::b64(hash_hmac('sha256',$m[1],$this->key,true)),$m[2]))throw new ApiFailure(401,'SESSION_REQUIRED');
        try{$p=json_decode((string)base64_decode(strtr($m[1],'-_','+/'),true),true,16,JSON_THROW_ON_ERROR);}catch(\Throwable){throw new ApiFailure(401,'SESSION_REQUIRED');}
        if(!is_array($p)||array_keys($p)!==['type','id','asset','digest','version','binding','issued','until']||$p['binding']!==$binding||!is_int($p['until'])||!is_int($p['issued'])||$p['issued']>$now||$p['until']<=$now||$p['until']>$p['issued']+60||$p['issued']>$p['until'])throw new ApiFailure(401,'SESSION_REQUIRED');
        return $p;
    }
}

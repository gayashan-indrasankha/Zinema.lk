<?php
declare(strict_types=1);
namespace Zinema\Identity;

/** Single-process/test limiter only. Capacity exhaustion denies, never evicts a live bucket. */
final class WindowLimiter implements Limiter {
    private array $buckets=[];
    public function __construct(private int $maximum=5,private int $seconds=300,private int $capacity=1024) {
        if ($maximum<1 || $seconds<1 || $capacity<1) throw new SecurityFailure('INVALID_CONFIGURATION',503);
    }
    public function take(string $key,int $now):void {
        if (!preg_match('/^[a-f0-9]{64}$/D',$key)) throw new SecurityFailure('LIMIT_UNAVAILABLE',503);
        foreach ($this->buckets as $id=>$b) if ($now>=$b['until']) unset($this->buckets[$id]);
        if (!isset($this->buckets[$key])) {
            if (count($this->buckets)>=$this->capacity) throw new SecurityFailure('RATE_LIMITED',429);
            $this->buckets[$key]=['start'=>$now,'until'=>$now+$this->seconds,'count'=>0];
        }
        $b=&$this->buckets[$key];
        if ($now<$b['start']) throw new SecurityFailure('LIMIT_UNAVAILABLE',503);
        if ($b['count']>=$this->maximum) throw new SecurityFailure('RATE_LIMITED',429);
        $b['count']++;
    }
}
final class Abuse {
    public function __construct(private Limiter $limiter,private string $key) {
        if (strlen($key)<32) throw new SecurityFailure('LIMIT_UNAVAILABLE',503);
    }
    public function take(string $action,string $email,string $network,int $now):void {
        if (!filter_var($network,FILTER_VALIDATE_IP)) throw new SecurityFailure('INVALID_REQUEST',400);
        // Pass the directly observed remote address; forwarded headers are not accepted here.
        foreach (['network:'.$network,'account:'.$email] as $dimension)
            $this->limiter->take(hash_hmac('sha256',$action.':'.$dimension,$this->key),$now);
    }
}
final class Csrf {
    public static function token(array &$session,int $now):string {
        if (!self::valid($session,$session['csrf']??null,$now)) {
            $session['csrf']=bin2hex(random_bytes(32));$session['csrfAt']=$now;
        }
        return $session['csrf'];
    }
    public static function valid(array $session,mixed $token,int $now):bool {
        return is_string($token) && preg_match('/^[a-f0-9]{64}$/D',$token)===1
            && isset($session['csrf'],$session['csrfAt']) && is_string($session['csrf']) && is_int($session['csrfAt'])
            && $now>=$session['csrfAt'] && $now-$session['csrfAt']<3600 && hash_equals($session['csrf'],$token);
    }
    public static function mutation(string $method,string $origin,string $trustedOrigin,array $session,mixed $token,int $now):void {
        if ($method!=='POST') throw new SecurityFailure('METHOD_NOT_ALLOWED',405);
        if ($origin!==$trustedOrigin || !self::valid($session,$token,$now)) throw new SecurityFailure('CSRF_REJECTED');
    }
}
final class Authorization {
    private const PERMISSIONS=['account.read','account.change','content.edit','content.review','content.publish','admin.enter','admin.users','admin.settings','admin.upload','audit.read'];
    public function __construct(private Store $store,private StepUp $mfa=new MissingStepUp()) {}
    public function require(Principal $principal,string $permission,?string $owner,string $binding,int $now):Account {
        $a=$this->store->byId($principal->id);
        if (!$a || !$a->active || !$a->verified || $a->epoch!==$principal->epoch
            || !in_array($permission,self::PERMISSIONS,true) || !in_array($permission,$a->permissions,true)) throw new SecurityFailure('ACCESS_DENIED');
        if (str_starts_with($permission,'account.') && $owner!==$a->id) throw new SecurityFailure('ACCESS_DENIED');
        // All non-account permissions require fresh server-bound MFA; client role/MFA flags are never arguments.
        if (!str_starts_with($permission,'account.') && !$this->mfa->verified($principal,$binding,$now)) throw new SecurityFailure('STEP_UP_REQUIRED');
        return $a;
    }
}

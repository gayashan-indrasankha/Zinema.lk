<?php
declare(strict_types=1);
namespace Zinema\Catalogue;

final class ApiFailure extends \RuntimeException {
    public function __construct(public readonly int $status,public readonly string $reason,public readonly int $retryAfter=0) { parent::__construct($reason); }
}
final class Context {
    public function __construct(public readonly ?string $subject,public readonly bool $active,
        public readonly ?string $territory,public readonly bool $territoryVerified,
        public readonly ?int $verifiedAge,public readonly string $quotaKey) {}
    public function fingerprint():string { return hash('sha256',json_encode([$this->subject,$this->active,$this->territory,$this->territoryVerified,$this->verifiedAge],JSON_THROW_ON_ERROR)); }
}
interface ContextProvider { public function resolve():?Context; }
final class MissingContext implements ContextProvider { public function resolve():?Context { return null; } }
interface AccessRules { public function rule(string $type,string $id,string $operation):?array; }
final class MissingRules implements AccessRules { public function rule(string $type,string $id,string $operation):?array { return null; } }
interface Grants {
    public function permits(string $subject,string $type,string $id,string $assetId,string $operation,string $territory,string $channel,\DateTimeImmutable $now):bool;
}
final class MissingGrants implements Grants {
    public function permits(string $subject,string $type,string $id,string $assetId,string $operation,string $territory,string $channel,\DateTimeImmutable $now):bool { return false; }
}
interface SearchRepository {
    /** Returns at most budget+1 candidates, never an unbounded catalogue. */
    public function candidates(Query $query,?array $position,int $budget):iterable;
    public function find(string $type,string $id):?array;
}
final class UnconfiguredRepository implements SearchRepository {
    public function candidates(Query $query,?array $position,int $budget):iterable { throw new ApiFailure(503,'CATALOGUE_NOT_CONFIGURED'); }
    public function find(string $type,string $id):?array { throw new ApiFailure(503,'CATALOGUE_NOT_CONFIGURED'); }
}
interface Limiter { public function consume(Context $context,\DateTimeImmutable $now):void; }
/** Bounded test/single-process implementation. Not a distributed production limit. */
final class FixedWindowLimiter implements Limiter {
    private array $buckets=[];
    public function __construct(private int $maximum=60,private int $seconds=60,private int $capacity=1024) {
        if ($maximum<1 || $seconds<1 || $capacity<1) throw new \InvalidArgumentException('INVALID_LIMITER_CONFIGURATION');
    }
    public function consume(Context $context,\DateTimeImmutable $now):void {
        if (!preg_match('/^[a-f0-9]{32,64}$/D',$context->quotaKey)) throw new ApiFailure(503,'CONTEXT_UNAVAILABLE');
        $time=$now->getTimestamp();
        foreach ($this->buckets as $key=>$bucket) if ($time>=$bucket['until']) unset($this->buckets[$key]);
        $key=$context->quotaKey;
        if (!isset($this->buckets[$key])) {
            if (count($this->buckets)>=$this->capacity) throw new ApiFailure(429,'RATE_LIMITED',$this->seconds);
            $this->buckets[$key]=['until'=>$time+$this->seconds,'count'=>0,'start'=>$time];
        }
        $bucket=&$this->buckets[$key];
        if ($time<$bucket['start']) throw new ApiFailure(503,'CONTEXT_UNAVAILABLE');
        if ($bucket['count']>=$this->maximum) throw new ApiFailure(429,'RATE_LIMITED',max(1,$bucket['until']-$time));
        $bucket['count']++;
    }
}

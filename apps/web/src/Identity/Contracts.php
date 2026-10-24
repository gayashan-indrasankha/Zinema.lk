<?php
declare(strict_types=1);
namespace Zinema\Identity;

final class SecurityFailure extends \RuntimeException {
    public function __construct(public readonly string $reason, public readonly int $status=403) { parent::__construct($reason); }
}
final class Account {
    /** Internal adapter DTO. Never serialize this object into an HTTP response. */
    public function __construct(public readonly string $id, public readonly string $email,
        public readonly string $passwordHash, public readonly bool $active, public readonly bool $verified,
        public readonly int $epoch, public readonly array $permissions=[]) {
        if (!preg_match('/^[a-f0-9]{32}$/D',$id) || $epoch<1) throw new SecurityFailure('IDENTITY_UNAVAILABLE',503);
    }
}
final class Principal {
    public function __construct(public readonly string $id, public readonly int $epoch) {}
}
interface Clock { public function now():int; }
final class SystemClock implements Clock { public function now():int { return time(); } }
/**
 * Adapter must serialize each transaction, enforce unique normalized email, lock
 * affected identities/tokens and roll back ALL account/token/audit mutations on
 * error. Consume must atomically delete a matching unexpired purpose-bound digest.
 * No production implementation is provided before verified identity DDL exists.
 */
interface Store {
    public function transaction(callable $operation):mixed;
    public function byEmail(string $email):?Account;
    public function byId(string $id):?Account;
    public function create(Account $account):void;
    public function replace(Account $account,int $expectedEpoch):void;
    public function issue(string $purpose,string $subject,string $digest,int $expires):void;
    public function consume(string $purpose,string $digest,int $now):?string;
    public function invalidateTokens(string $subject):void;
    public function append(array $event):void;
}
interface MailSink {
    /** Trusted adapter only. Raw token is delivered here, never to a public API response. */
    public function deliver(string $email,string $purpose,string $token):void;
}
interface Limiter { public function take(string $key,int $now):void; }
interface StepUp {
    /** Must resolve server-side MFA evidence bound to this session, identity and epoch. */
    public function verified(Principal $principal,string $sessionBinding,int $now):bool;
}
final class MissingStepUp implements StepUp {
    public function verified(Principal $principal,string $sessionBinding,int $now):bool { return false; }
}
final class Factory {
    public static function production():never { throw new SecurityFailure('IDENTITY_NOT_CONFIGURED',503); }
}

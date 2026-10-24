<?php
declare(strict_types=1);
namespace Zinema\Identity;

final class Recovery {
    public function __construct(private Store $store,private Password $passwords,private Clock $clock,
        private Abuse $abuse,private Audit $audit,private MailSink $mail) {}
    public function request(string $purpose,mixed $email,string $network):array {
        if (!in_array($purpose,['verify','reset'],true)) throw new SecurityFailure('INVALID_INPUT',400);
        $email=Authentication::email($email);$now=$this->clock->now();$this->abuse->take($purpose,$email,$network,$now);
        $delivery=$this->store->transaction(function() use($purpose,$email,$now) {
            $a=$this->store->byEmail($email);
            if (!$a || !$a->active || ($purpose==='verify' && $a->verified) || ($purpose==='reset' && !$a->verified)) return null;
            $raw=bin2hex(random_bytes(32));
            $this->store->issue($purpose,$a->id,hash('sha256',$raw),$now+($purpose==='reset'?1800:3600));
            $this->audit->record($purpose==='reset'?'reset.request':'verify','requested',null,$a->id);
            return [$email,$purpose,$raw];
        });
        // Delivery failures must not expose account existence. Durable outbox/retry is an unresolved adapter requirement.
        if ($delivery) { try { $this->mail->deliver(...$delivery); } catch (\Throwable) {} }
        return ['accepted'=>true];
    }
    public function consume(string $purpose,mixed $token,mixed $newPassword,string $network):bool {
        if (!in_array($purpose,['verify','reset'],true)) throw new SecurityFailure('INVALID_INPUT',400);
        $now=$this->clock->now();$this->abuse->take($purpose.'.consume','token',$network,$now);
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D',$token)) throw new SecurityFailure('RECOVERY_REJECTED',400);
        $hash=$purpose==='reset'?$this->passwords->hash($newPassword):null;
        return $this->store->transaction(function() use($purpose,$token,$now,$hash) {
            $id=$this->store->consume($purpose,hash('sha256',$token),$now);
            $a=$id?$this->store->byId($id):null;
            if (!$a || !$a->active || ($purpose==='reset' && !$a->verified)) throw new SecurityFailure('RECOVERY_REJECTED',400);
            $this->store->replace(new Account($a->id,$a->email,$hash??$a->passwordHash,$a->active,true,$a->epoch+1,$a->permissions),$a->epoch);
            // Revoke all sessions through epoch; verification does not silently sign in or produce MFA evidence.
            $this->store->invalidateTokens($a->id);$this->audit->record($purpose==='reset'?'reset.complete':'verify','allowed',null,$a->id);
            return true;
        });
    }
}

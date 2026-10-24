<?php
declare(strict_types=1);
namespace Zinema\Identity;

final class Authentication {
    private string $dummy;
    public function __construct(private Store $store,private Password $passwords,private Clock $clock,private Abuse $abuse,private Audit $audit) {
        $this->dummy=$passwords->hash(bin2hex(random_bytes(24)));
    }
    public static function email(mixed $email):string {
        // Proposed new adapter contract; existing collation/normalization is not changed.
        if (!is_string($email) || strlen($email)>254) throw new SecurityFailure('INVALID_INPUT',400);
        $email=strtolower(trim($email));
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || preg_match('/[^\x21-\x7e]/',$email)) throw new SecurityFailure('INVALID_INPUT',400);
        return $email;
    }
    public function register(mixed $email,mixed $password,string $network):array {
        $email=self::email($email);$this->abuse->take('register',$email,$network,$this->clock->now());
        $hash=$this->passwords->hash($password);
        $this->store->transaction(function() use($email,$hash) {
            if ($this->store->byEmail($email)) return;
            // No requested role/status/permission is accepted from the caller.
            $a=new Account(bin2hex(random_bytes(16)),$email,$hash,true,false,1,['account.read','account.change']);
            $this->store->create($a);$this->audit->record('register','requested',null,$a->id);
        });
        return ['accepted'=>true]; // Duplicate and new accounts have the same public shape; no auto-login.
    }
    public function login(mixed $email,mixed $password,string $network):Principal {
        $email=self::email($email);$this->abuse->take('login',$email,$network,$this->clock->now());
        return $this->store->transaction(function() use($email,$password) {
            $a=$this->store->byEmail($email);
            $ok=$this->passwords->verify($password,$a?->passwordHash??$this->dummy);
            if (!$ok || !$a || !$a->active || !$a->verified) { $this->audit->record('login','denied');return null; }
            if ($this->passwords->valid($password) && $this->passwords->needsRehash($a->passwordHash)) {
                $this->store->replace(new Account($a->id,$a->email,$this->passwords->hash($password),$a->active,$a->verified,$a->epoch,$a->permissions),$a->epoch);
            }
            $this->audit->record('login','allowed',$a->id,$a->id);
            return new Principal($a->id,$a->epoch);
        }) ?? throw new SecurityFailure('INVALID_CREDENTIALS',401);
    }
    public function change(Principal $p,mixed $old,mixed $new,string $network):void {
        $this->abuse->take('password.change',$p->id,$network,$this->clock->now());
        $this->store->transaction(function() use($p,$old,$new) {
            $a=$this->store->byId($p->id);
            if (!$a || !$a->active || !$a->verified || $a->epoch!==$p->epoch || !$this->passwords->verify($old,$a->passwordHash)) throw new SecurityFailure('INVALID_CREDENTIALS',401);
            $this->store->replace(new Account($a->id,$a->email,$this->passwords->hash($new),$a->active,$a->verified,$a->epoch+1,$a->permissions),$a->epoch);
            $this->store->invalidateTokens($a->id);$this->audit->record('password.change','allowed',$a->id,$a->id);
        });
    }
}

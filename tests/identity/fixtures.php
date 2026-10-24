<?php
declare(strict_types=1);
require_once __DIR__.'/../../apps/web/src/Identity/bootstrap.php';
use Zinema\Identity\{Account,Store,Clock,MailSink,SecurityFailure,Password,Authentication,Abuse,WindowLimiter,Audit,Recovery};

final class FixtureClock implements Clock { public int $time=1792800000;public function now():int { return $this->time; } }
/** Synthetic in-memory contract adapter, NOT a database schema or production repository. */
class FixtureStore implements Store {
    public array $accounts=[],$tokens=[],$events=[];
    public bool $failAudit=false,$failReplace=false;
    private bool $locked=false;
    public function transaction(callable $operation):mixed {
        if ($this->locked) throw new SecurityFailure('NESTED_TRANSACTION',503);
        $before=[$this->accounts,$this->tokens,$this->events];$this->locked=true;
        try { return $operation(); }
        catch (Throwable $e) { [$this->accounts,$this->tokens,$this->events]=$before;throw $e; }
        finally { $this->locked=false; }
    }
    private function locked():void { if (!$this->locked) throw new SecurityFailure('TRANSACTION_REQUIRED',503); }
    public function byEmail(string $email):?Account { foreach ($this->accounts as $a) if ($a->email===$email) return $a;return null; }
    public function byId(string $id):?Account { return $this->accounts[$id]??null; }
    public function create(Account $account):void {
        $this->locked();if ($this->byEmail($account->email) || isset($this->accounts[$account->id])) throw new SecurityFailure('IDENTITY_CONFLICT',409);
        $this->accounts[$account->id]=$account;
    }
    public function replace(Account $account,int $expectedEpoch):void {
        $this->locked();if ($this->failReplace) throw new SecurityFailure('STORAGE_UNAVAILABLE',503);
        if (($this->byId($account->id)?->epoch??0)!==$expectedEpoch) throw new SecurityFailure('IDENTITY_CONFLICT',409);
        $this->accounts[$account->id]=$account;
    }
    public function issue(string $purpose,string $subject,string $digest,int $expires):void {
        $this->locked();foreach ($this->tokens as $key=>$t) if ($t['subject']===$subject && $t['purpose']===$purpose) unset($this->tokens[$key]);
        $this->tokens[$digest]=compact('purpose','subject','expires');
    }
    public function consume(string $purpose,string $digest,int $now):?string {
        $this->locked();$t=$this->tokens[$digest]??null;
        if (!$t || $t['purpose']!==$purpose || $now>=$t['expires']) return null;
        unset($this->tokens[$digest]);return $t['subject'];
    }
    public function invalidateTokens(string $subject):void { $this->locked();foreach ($this->tokens as $key=>$t) if ($t['subject']===$subject) unset($this->tokens[$key]); }
    public function append(array $event):void { $this->locked();if ($this->failAudit) throw new SecurityFailure('AUDIT_UNAVAILABLE',503);$this->events[]=$event; }
}
final class FixtureMail implements MailSink {
    public array $messages=[];public bool $fail=false;
    public function deliver(string $email,string $purpose,string $token):void {
        if ($this->fail) throw new RuntimeException('SYNTHETIC_DELIVERY_FAILURE');
        $this->messages[]=compact('email','purpose','token');
    }
}
function world(int $maximum=50):array {
    $s=new FixtureStore();$p=new Password();$c=new FixtureClock();$mail=new FixtureMail();
    $abuse=new Abuse(new WindowLimiter($maximum,300),random_bytes(32));$audit=new Audit($s,$c);
    return [$s,$p,$c,$mail,new Authentication($s,$p,$c,$abuse,$audit),new Recovery($s,$p,$c,$abuse,$audit,$mail),$audit,$abuse];
}
function seed(FixtureStore $s,Password $p,string $id='aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',bool $verified=true,bool $active=true,array $permissions=['account.read','account.change']):Account {
    $a=new Account($id,'viewer-'.substr($id,0,1).'@example.invalid',$p->hash('fictional horse galaxy 47'),$active,$verified,1,$permissions);
    $s->transaction(fn()=>$s->create($a));return $a;
}
function check(bool $condition):void { if (!$condition) throw new RuntimeException('SYNTHETIC_ASSERTION_FAILED'); }
function denied(callable $fn,string $reason):void {
    try { $fn(); } catch (SecurityFailure $e) { check($e->reason===$reason);return; }
    throw new RuntimeException('EXPECTED_DENIAL');
}

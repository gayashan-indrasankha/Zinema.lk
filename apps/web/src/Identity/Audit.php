<?php
declare(strict_types=1);
namespace Zinema\Identity;

final class Audit {
    private const ACTIONS=['register','login','logout','verify','reset.request','reset.complete','password.change','access.denied','role.change','content.publish','upload'];
    public function __construct(private Store $store,private Clock $clock) {}
    public function record(string $action,string $result,?string $actor=null,?string $target=null):void {
        if (!in_array($action,self::ACTIONS,true) || !in_array($result,['allowed','denied','requested'],true)) throw new SecurityFailure('AUDIT_UNAVAILABLE',503);
        foreach ([$actor,$target] as $id) if ($id!==null && !preg_match('/^[a-f0-9]{32}$/D',$id)) throw new SecurityFailure('AUDIT_UNAVAILABLE',503);
        // Fixed schema: no arbitrary metadata, exception text, email, address or secrets.
        $this->store->append(['id'=>bin2hex(random_bytes(16)),'at'=>$this->clock->now(),'actor'=>$actor,'action'=>$action,'target'=>$target,'result'=>$result]);
    }
}

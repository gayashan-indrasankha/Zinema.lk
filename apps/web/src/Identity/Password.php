<?php
declare(strict_types=1);
namespace Zinema\Identity;

final class Password {
    private string $algorithm;
    private array $options;
    public function __construct() {
        $this->algorithm=defined('PASSWORD_ARGON2ID') && in_array('argon2id',password_algos(),true) ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $this->options=$this->algorithm===PASSWORD_BCRYPT ? ['cost'=>12] : ['memory_cost'=>65536,'time_cost'=>3,'threads'=>1];
    }
    public function valid(mixed $value):bool {
        if (!is_string($value) || strlen($value)>($this->algorithm===PASSWORD_BCRYPT ? 72 : 128) || str_contains($value,"\0")) return false;
        return preg_match_all('/./us',$value,$unused)!==false && count($unused[0])>=15;
    }
    public function hash(mixed $value):string {
        if (!$this->valid($value)) throw new SecurityFailure('INVALID_INPUT',400);
        return password_hash($value,$this->algorithm,$this->options);
    }
    public function verify(mixed $value,string $hash):bool {
        // Legacy source uses PHP password_hash. Actual deployed formats remain unverified.
        if (!is_string($value) || strlen($value)>128 || str_contains($value,"\0")) return false;
        $algo=password_get_info($hash)['algoName'];
        if (!in_array($algo,['bcrypt','argon2i','argon2id'],true) || ($algo==='bcrypt' && strlen($value)>72)) return false;
        return password_verify($value,$hash);
    }
    public function needsRehash(string $hash):bool { return password_needs_rehash($hash,$this->algorithm,$this->options); }
    public function algorithm():string { return $this->algorithm; }
}

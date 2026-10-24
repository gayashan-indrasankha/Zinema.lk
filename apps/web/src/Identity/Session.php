<?php
declare(strict_types=1);
namespace Zinema\Identity;

final class SessionState {
    public static function establish(Principal $p,int $now):array {
        return ['subject'=>$p->id,'epoch'=>$p->epoch,'created'=>$now,'seen'=>$now,'binding'=>bin2hex(random_bytes(32))];
    }
    public static function resolve(array &$state,Store $store,int $now):?Principal {
        foreach (['subject','epoch','created','seen','binding'] as $key) if (!array_key_exists($key,$state)) { $state=[];return null; }
        if (!is_string($state['subject']) || !is_int($state['epoch']) || !is_int($state['created']) || !is_int($state['seen'])
            || !is_string($state['binding']) || !preg_match('/^[a-f0-9]{64}$/D',$state['binding'])
            || $now<$state['seen'] || $state['seen']<$state['created'] || $now-$state['seen']>=1800 || $now-$state['created']>=28800) { $state=[];return null; }
        $a=$store->byId($state['subject']);
        if (!$a || !$a->active || !$a->verified || $a->epoch!==$state['epoch']) { $state=[];return null; }
        $state['seen']=$now;return new Principal($a->id,$a->epoch);
    }
}
/** Separate session namespace. Never automatically adopts legacy PHPSESSID/admin flags. */
final class NativeSession {
    public static function start(string $origin,string $environment,string $remote,bool $https,string $directory):void {
        if (session_status()!==PHP_SESSION_NONE || headers_sent()) throw new SecurityFailure('SESSION_UNAVAILABLE',503);
        $u=parse_url($origin);
        $local=in_array($environment,['development','test'],true) && ($u['scheme']??'')==='http'
            && ($u['host']??'')==='127.0.0.1' && $remote==='127.0.0.1';
        $secure=($u['scheme']??'')==='https' && $https;
        if (!$u || !isset($u['host']) || (!in_array($environment,['production','development','test'],true))
            || (!$local && !$secure) || isset($u['user']) || isset($u['pass']) || isset($u['query']) || isset($u['fragment'])
            || !is_dir($directory) || is_link($directory) || !is_writable($directory)) throw new SecurityFailure('SESSION_UNAVAILABLE',503);
        ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.use_trans_sid','0');
        ini_set('session.gc_maxlifetime','28800');session_name('ZL19SID');session_save_path($directory);
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
        if (!session_start()) throw new SecurityFailure('SESSION_UNAVAILABLE',503);
        header('Cache-Control: private, no-store');header('Referrer-Policy: no-referrer');
    }
    public static function establish(Principal $p,int $now):void {
        if (session_status()!==PHP_SESSION_ACTIVE || !session_regenerate_id(true)) throw new SecurityFailure('SESSION_UNAVAILABLE',503);
        $_SESSION=['identity'=>SessionState::establish($p,$now)];
    }
    public static function logout():void {
        if (session_status()!==PHP_SESSION_ACTIVE) throw new SecurityFailure('SESSION_UNAVAILABLE',503);
        $_SESSION=[];$p=session_get_cookie_params();
        setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>$p['path'],'secure'=>$p['secure'],'httponly'=>$p['httponly'],'samesite'=>$p['samesite']]);
        if (!session_destroy()) throw new SecurityFailure('SESSION_UNAVAILABLE',503);
    }
}

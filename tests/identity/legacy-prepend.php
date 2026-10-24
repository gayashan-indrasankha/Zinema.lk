<?php
declare(strict_types=1);
// Used only with a new owned synthetic directory. No database or environment bootstrap.
$root=realpath(getenv('ZL19_LEGACY_ROOT')?:'');
if (!$root || !str_starts_with(basename($root),'zinema-zl19-legacy-') || is_link($root)) exit(2);
chdir($root);
$_SERVER['REQUEST_METHOD']='POST';
$_POST=['username'=>'fictional_viewer','email'=>'viewer@example.invalid','password'=>'fictional horse galaxy 47','csrf_token'=>str_repeat('a',64)];
final class SyntheticResult {
    public int $num_rows=1;
    public function fetch_assoc():array { return ['id'=>101,'is_verified'=>0,'verification_expires'=>'2000-01-01 00:00:00']; }
}
final class SyntheticStatement {
    public function bind_param(string $types,mixed &...$values):void {}
    public function execute():bool { return true; }
    public function get_result():SyntheticResult { return new SyntheticResult(); }
    public function close():void {}
}
final class SyntheticConnection {
    public function prepare(string $sql):SyntheticStatement {
        if (!preg_match('/^SELECT id, is_verified, verification_expires FROM users WHERE email = \?$/D',$sql)) {
            fwrite(STDERR,'UNEXPECTED_DATABASE_OPERATION');exit(3);
        }
        return new SyntheticStatement();
    }
}
$conn=new SyntheticConnection();

<?php
declare(strict_types=1);
namespace Zinema\Playback;
use Zinema\Catalogue\ApiFailure;

/** Development fixture only, never installed by the public factory. */
final class LocalFixtureProvider implements SourceProvider {
    private string $root;private array $files;private string $hash;
    public function __construct(string $root){
        if(!in_array(PHP_SAPI,['cli','cli-server'],true))throw new ApiFailure(503,'PLAYBACK_UNAVAILABLE');
        $real=realpath($root);$private=realpath(dirname(__DIR__,4).'/.recovery-private');
        if($real===false||$private===false||!str_starts_with(str_replace('\\','/',$real).'/',str_replace('\\','/',$private).'/')||is_link($root))throw new ApiFailure(503,'PLAYBACK_UNAVAILABLE');
        $this->root=$real;$m=json_decode((string)file_get_contents($real.'/fixture.json'),true,16,JSON_THROW_ON_ERROR);
        if(($m['dataClass']??null)!=='synthetic-original'||!is_array($m['files']??null)||count($m['files'])>24||!isset($m['files']['master.m3u8']))throw new ApiFailure(503,'PLAYBACK_UNAVAILABLE');
        $this->files=$m['files'];foreach($this->files as $name=>$hash){
            if(!preg_match('/^[a-z][a-z0-9_-]{0,24}\.(m3u8|ts|vtt)$/D',$name)||!is_string($hash)||!preg_match('/^[a-f0-9]{64}$/D',$hash)||is_link($real.'/'.$name)||!is_file($real.'/'.$name)||filesize($real.'/'.$name)>8388608||hash_file('sha256',$real.'/'.$name)!==$hash)throw new ApiFailure(503,'PLAYBACK_UNAVAILABLE');
        }
        ksort($this->files);$this->hash=hash('sha256',json_encode($this->files,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    }
    public function digest():?string{return $this->hash;}
    public function resource(string $name):array{
        if(!isset($this->files[$name]))throw new ApiFailure(404,'CONTENT_UNAVAILABLE');
        $file=$this->root.'/'.$name;if(is_link($file)||hash_file('sha256',$file)!==$this->files[$name])throw new ApiFailure(503,'PLAYBACK_UNAVAILABLE');
        $bytes=(string)file_get_contents($file);$ext=pathinfo($name,PATHINFO_EXTENSION);
        if($ext==='m3u8'){
            if(!str_starts_with($bytes,"#EXTM3U\n"))throw new ApiFailure(503,'PLAYBACK_UNAVAILABLE');
            $link=function(string $ref):string{if(!isset($this->files[$ref]))throw new ApiFailure(503,'PLAYBACK_UNAVAILABLE');return 'media.php?file='.rawurlencode($ref);};
            $lines=explode("\n",$bytes);foreach($lines as &$line){$line=rtrim($line,"\r");if($line!==''&&$line[0]!=='#')$line=$link($line);elseif(str_contains($line,'URI=')){$n=0;$line=preg_replace_callback('/URI="([^"]+)"/',static function($m)use($link){return 'URI="'.$link($m[1]).'"';},$line,-1,$n);if($n!==1)throw new ApiFailure(503,'PLAYBACK_UNAVAILABLE');}}unset($line);$bytes=implode("\n",$lines);
        }
        return ['bytes'=>$bytes,'mime'=>match($ext){'m3u8'=>'application/vnd.apple.mpegurl','ts'=>'video/mp2t','vtt'=>'text/vtt; charset=utf-8'}];
    }
}

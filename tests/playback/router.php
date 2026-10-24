<?php
declare(strict_types=1);
// Isolated test server: only explicitly reviewed code paths. No legacy DB bootstrap.
ini_set('display_errors','0');$root=dirname(__DIR__,2);$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if($path==='/health'){echo 'ready';exit;}
$static=['/js/hls-player.js','/js/shot-link.js','/js/vendor/hls/hls.min.js','/css/hls-player.css'];
if(in_array($path,$static,true)){header('Content-Type: '.(str_ends_with($path,'.css')?'text/css':'application/javascript'));readfile($root.'/Zinema.lk Website'.$path);exit;}
if($path==='/movie-details.php'||$path==='/series-details.php'){$playbackType=$path==='/movie-details.php'?'movie':'series';require $root.'/apps/web/src/Playback/view.php';exit;}
if($path==='/shot-content.php'){require $root.'/Zinema.lk Website/shot-content.php';exit;}
if($path==='/'||$path==='/index.php'){
    require_once $root.'/apps/web/src/Playback/Navigation.php';$href=\Zinema\Playback\Navigation::shotHref(['id'=>'801','linked_content_type'=>'movie','linked_content_id'=>'501']);
    header("Content-Security-Policy: default-src 'self'");echo '<!doctype html><html lang="en"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Original synthetic Shots</title><h1>Original synthetic Shots fixture</h1><a style="display:inline-block;padding:16px" href="/'.$href.'">View fictional movie availability</a></html>';exit;
}
if(!preg_match('~^/api/playback/(link|details|session|media)\.php$~D',$path,$m)){http_response_code(404);exit;}
if(isset($_GET['public'])){unset($_GET['public']);require $root.'/Zinema.lk Website'.$path;exit;}
require __DIR__.'/fixtures.php';$state=json_decode((string)file_get_contents(getenv('ZL17_STATE')),true,64,JSON_THROW_ON_ERROR);$provider=new \Zinema\Playback\LocalFixtureProvider(getenv('ZL17_MEDIA'));
$w=playbackWorld($provider,$state);\Zinema\Playback\Http::run($m[1],$w['playbackController']);

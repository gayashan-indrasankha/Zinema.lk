<?php
declare(strict_types=1);
ini_set('display_errors','0');$root=dirname(__DIR__,2);$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if($path==='/health'){echo 'ready';exit;}
$routes=json_decode((string)file_get_contents(__DIR__.'/routes.json'),true,8,JSON_THROW_ON_ERROR);
if(in_array(ltrim($path,'/'),$routes,true)){$target=$root.'/Zinema.lk Website/'.$path;if(!is_file($target)){http_response_code(404);header('Content-Type: application/json');header('Cache-Control: private, no-store');echo '{"error":{"code":"RESOURCE_NOT_DEPLOYED"}}';exit;}require $target;exit;}
if($path==='/api/integrations/status.php'){require $root.'/Zinema.lk Website/api/integrations/status.php';exit;}
http_response_code(404);

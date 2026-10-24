<?php
declare(strict_types=1);
// Test server only: never included by the public entry or application factory.
if(str_starts_with($_SERVER['REQUEST_URI'],'/health')){header('Content-Type: application/json');echo '{"ready":true}';exit;}
if(str_starts_with($_SERVER['REQUEST_URI'],'/real')){require dirname(__DIR__,2).'/Zinema.lk Website/api/catalogue/v1.php';exit;}
if(!str_starts_with($_SERVER['REQUEST_URI'],'/fixture')){http_response_code(404);exit;}
require __DIR__.'/fixtures.php';
$records=json_decode(file_get_contents((string)getenv('ZL16_HTTP_RECORDS')),true,64,JSON_THROW_ON_ERROR);
$world=apiWorld(null,$records);
$response=$world['controller']->handle($_SERVER['REQUEST_METHOD'],$_GET,strlen($_SERVER['QUERY_STRING']??''));
http_response_code($response->status);foreach($response->headers as $key=>$value)header($key.': '.$value);echo $response->body;

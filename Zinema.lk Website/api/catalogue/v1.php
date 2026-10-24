<?php
declare(strict_types=1);
ini_set('display_errors','0');
require dirname(__DIR__,3).'/apps/web/src/Catalogue/bootstrap.php';
$response=\Zinema\Catalogue\Factory::unconfigured()->handle($_SERVER['REQUEST_METHOD']??'',$_GET,strlen($_SERVER['QUERY_STRING']??''));
http_response_code($response->status);
foreach($response->headers as $name=>$value)header($name.': '.$value);
echo $response->body;

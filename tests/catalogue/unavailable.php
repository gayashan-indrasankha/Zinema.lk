<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/apps/web/src/Catalogue/bootstrap.php';
try {
    $password=(string)getenv('ZL16_READER_PASSWORD');
    if(!preg_match('/^LOCAL_ONLY_[a-f0-9]{64}$/D',$password))throw new RuntimeException();
    $db=new PDO('mysql:host=db;dbname=zinema_recovery_test;charset=utf8mb4','zinema_catalogue_reader',$password,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>2]);
    $repo=new \Zinema\Catalogue\PdoSearchRepository($db);$repo->find('movie','502');
    echo '{"databaseReachable":true}';
}catch(Throwable $error){fwrite(STDERR,'{"error":"CATALOGUE_UNAVAILABLE"}');exit(1);}

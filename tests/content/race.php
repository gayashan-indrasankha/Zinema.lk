<?php
declare(strict_types=1);
require __DIR__.'/fixtures.php';
try {
    $id=\Zinema\Content\Validation::id(getenv('ZL15_RACE_ID')); $version=(int)getenv('ZL15_RACE_VERSION');
    $password=(string)getenv('ZL15_CONTENT_PASSWORD');check(preg_match('/^LOCAL_ONLY_[a-f0-9]{64}$/D',$password)===1);
    $db=new PDO('mysql:host=db;dbname=zinema_recovery_test;charset=utf8mb4','zinema_content_service',$password,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>2]);
    $repo=new \Zinema\Content\PdoRepository($db);$w=world($repo);$record=$repo->find($id);$metadata=$record['metadata'];
    $metadata['title']='Synthetic concurrent editor';$start=(float)getenv('ZL15_RACE_START');
    check($start>microtime(true)-10 && $start<microtime(true)+10);
    while(microtime(true)<$start)usleep(1000);
    $w['service']->execute($id,$version,'edit_metadata',$metadata);
    echo json_encode(['outcome'=>'saved']);
} catch (\Zinema\Content\ContentFailure $error) {
    if($error->reason==='VERSION_CONFLICT'){echo json_encode(['outcome'=>'VERSION_CONFLICT']);exit;}
    fwrite(STDERR,json_encode(['error'=>$error->reason]));exit(1);
} catch(Throwable $error){fwrite(STDERR,'{"error":"CONTENT_RACE_FAILED"}');exit(1);}

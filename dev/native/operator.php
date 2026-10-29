<?php
if (PHP_SAPI!=='cli' || !defined('ZINEMA_NATIVE_CONTEXT')) exit(1);
try {
    $db=native_demo_db();$c=ZINEMA_NATIVE_CONTEXT;
    require_once $c['public'].'/includes/upload_storage.php';
    require_once $c['public'].'/includes/operator_operations.php';
    $raw=stream_get_contents(STDIN,16385);
    if (strlen($raw)>16384) throw new RuntimeException();
    $input=json_decode($raw,true,16,JSON_THROW_ON_ERROR);
    echo json_encode(operator_dispatch($db,local_storage_settings(),$input),JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $e) {fwrite(STDERR,"Native operator request refused.\n");exit(1);}

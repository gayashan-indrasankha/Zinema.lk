<?php
// Private CLI-only operations. This is never a website endpoint or a cron installation.
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require __DIR__.'/bootstrap.php';
try {
    $context=ZINEMA_STELLAR_CONTEXT;
    require_once $context['public'].'/includes/environment.php';
    require_once $context['public'].'/includes/upload_storage.php';
    require_once $context['public'].'/includes/operator_operations.php';
    $input=json_decode(stream_get_contents(STDIN,4097),true,8,JSON_THROW_ON_ERROR);
    if (!is_array($input) || !in_array($input['action'] ?? '',['initialize','inventory','storage-review','storage-history','storage-quarantine','storage-restore','storage-recover'],true)) throw new RuntimeException();
    $settings=local_storage_settings();
    if ($input['action']==='initialize') {
        if (array_keys($input)!==['action']) throw new RuntimeException();
        initialize_local_upload_storage();$result=['success'=>true,'initialized'=>true];
    } else {
        $db=new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME,DB_PORT);$db->set_charset('utf8mb4');
        $identity=$db->query('SELECT DATABASE(),@@port')->fetch_row();
        if ($identity[0]!==DB_NAME || (int)$identity[1]!==DB_PORT) throw new RuntimeException();
        $result=operator_dispatch($db,$settings,$input);$db->close();
    }
    echo json_encode($result,JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $error) {fwrite(STDERR,"Private staging operation refused; review sanitized configuration and evidence.\n");exit(1);}

<?php
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require __DIR__.'/bootstrap.php';
try {
    require_once ZINEMA_STELLAR_CONTEXT['public'].'/includes/environment.php';
    require_once ZINEMA_STELLAR_CONTEXT['public'].'/includes/extractor_client.php';
    require_once ZINEMA_STELLAR_CONTEXT['public'].'/includes/extractor_queue.php';
    $input=json_decode(stream_get_contents(STDIN,2049),true,8,JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new RuntimeException();
    echo json_encode(account_extractor_queue()->review($input),JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $error) {fwrite(STDERR,"Extractor review refused; preserve held work and review evidence.\n");exit(1);}

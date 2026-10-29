<?php
require __DIR__.'/common.php';native_admin();
if (empty(ZINEMA_NATIVE_CONTEXT['real_whatsapp'])) {http_response_code(404);exit;}
try {
    [$status,$bot]=native_call('status');
    if (empty($bot['pairingAvailable'])) throw new RuntimeException();
    $file=ZINEMA_NATIVE_CONTEXT['home'].'/native/real-whatsapp/pairing.svg';
    if (!native_demo_plain($file) || filesize($file)>262144) throw new RuntimeException();
    header('Content-Type: image/svg+xml');header("Content-Security-Policy: default-src 'none'; style-src 'none'; sandbox");readfile($file);
} catch(Throwable $e){http_response_code(404);}

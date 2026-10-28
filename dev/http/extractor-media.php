<?php
// Original non-sensitive demonstration clip; installed only in the local test image.
require_once '/var/www/html/includes/environment.php';
if (APP_ENV!=='development' || EXTRACTOR_MODE!=='local' || getenv('ZINEMA_EXTRACTOR_FIXTURES')!=='1') {http_response_code(404);exit;}
$clip=base64_decode(file_get_contents('/opt/zinema/dev/fixtures/motion-demo.mp4.b64'),true);
header('Content-Type: video/mp4');header('Cache-Control: no-store');header('Content-Length: '.strlen($clip));echo $clip;

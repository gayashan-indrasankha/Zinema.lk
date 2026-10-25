<?php
// Loopback-only fixture server. No operator endpoint, arbitrary PHP path or public port.
if (getenv('ZINEMA_OPERATOR_FIXTURES') !== '1' || getenv('APP_ENV') !== 'development' ||
    !hash_equals(trim(file_get_contents('/run/secrets/control_key')), $_SERVER['HTTP_X_TEST_KEY'] ?? '')) {
    http_response_code(403); exit;
}
$path = parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if (!in_array($path,['/admin/login.php','/admin/movies.php'],true)) { http_response_code(404); exit; }
foreach (['HTTP_X_FIXTURE_MAX_BYTES'=>'UPLOAD_MAX_BYTES','HTTP_X_FIXTURE_MAX_FILES'=>'UPLOAD_MAX_FILES'] as $header=>$key) {
    if (isset($_SERVER[$header])) {
        if (!ctype_digit($_SERVER[$header]) || strlen($_SERVER[$header])>10) { http_response_code(400); exit; }
        putenv($key.'='.$_SERVER[$header]);
    }
}
chdir('/var/www/html/admin');
require '/var/www/html'.$path;

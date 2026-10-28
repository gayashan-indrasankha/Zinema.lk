<?php
// Install only outside public_html. Distributed candidate has no runtime.php config.
// A separately reviewed private runtime-config.php is required; never use a request path.
$configuration=__DIR__.'/runtime-config.php';
if (is_file($configuration) && !is_link($configuration) && filesize($configuration)<=16384 &&
    (!function_exists('posix_geteuid') || (fileowner($configuration)===posix_geteuid() && (fileperms($configuration)&0077)===0))) {
    try {
        require_once __DIR__.'/runtime.php';
        $public=dirname(__DIR__,2).'/public_html';
        stellar_apply_context(stellar_runtime_context(require $configuration,__DIR__,$public,
            PHP_SAPI==='cli' ? null : ($_SERVER['REMOTE_ADDR'] ?? '')));
        return;
    } catch (Throwable $error) { /* No paths, credentials or exception text in responses. */ }
}
http_response_code(503);header('Content-Type: text/plain; charset=UTF-8');header('Cache-Control: no-store');
exit('Private staging candidate is disabled.');

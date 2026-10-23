<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ob_start();
register_shutdown_function(function () {
    $output = ob_get_clean();
    echo json_encode(['http' => http_response_code() ?: 200, 'output' => $output], JSON_THROW_ON_ERROR);
});
require dirname(__DIR__, 2) . '/infra/runtime/health.php';
if (($argv[1] ?? '') === 'config') {
    require dirname(__DIR__, 2) . '/Zinema.lk Website/includes/environment.php';
    echo 'configuration_valid';
} else {
    [$code, $body] = zinema_health_response($argv[1] ?? 'live', fn() => ($argv[2] ?? '') === 'available');
    http_response_code($code);
    echo json_encode($body, JSON_THROW_ON_ERROR);
}

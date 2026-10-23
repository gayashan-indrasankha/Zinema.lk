<?php
// CLI-only characterization: no application bootstrap, database, session or network.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$module = $argv[1] ?? '';
if ($module === '') {
    fwrite(STDERR, "A helper path is required.\n");
    exit(1);
}
ob_start();
require_once $module;
$includeOutput = ob_get_clean();
$functions = [
    'renderMobileBannerAd', 'renderDesktopBannerAd', 'renderRectangleAd',
    'renderResponsiveBannerAd', 'getSmartLinkUrl', 'renderSocialBar',
    'renderPopunder', 'renderNativeBanner'
];
$contracts = [];
foreach ($functions as $name) {
    $reflection = new ReflectionFunction($name);
    ob_start();
    $value = $name();
    $output = ob_get_clean();
    $contracts[$name] = [
        'parameters' => $reflection->getNumberOfParameters(),
        'requiredParameters' => $reflection->getNumberOfRequiredParameters(),
        'value' => $value,
        'output' => $output
    ];
}
ob_start();
require_once $module;
$repeatOutput = ob_get_clean();
echo json_encode([
    'includeOutput' => $includeOutput,
    'repeatOutput' => $repeatOutput,
    'adsEnabled' => ADS_ENABLED,
    'smartLink' => SMART_LINK_URL,
    'functions' => $contracts,
    'sessionStarted' => session_status() === PHP_SESSION_ACTIVE,
    'headers' => headers_list(),
    'includedFileCount' => count(get_included_files())
], JSON_THROW_ON_ERROR);

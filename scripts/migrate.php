<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/database/Runner.php';
try {
    // File/order/checksum validation happens before any database connection.
    $migrations = \Zinema\Database\Runner::load(dirname(__DIR__) . '/database');
    $settings = \Zinema\Database\Runner::settings();
    $db = \Zinema\Database\Runner::connect($settings);
    echo json_encode(\Zinema\Database\Runner::apply($db, $migrations), JSON_THROW_ON_ERROR) . "\n";
} catch (Throwable $error) {
    $code = $error instanceof \Zinema\Database\MigrationFailure ? $error->reason : 'MIGRATION_UNAVAILABLE';
    fwrite(STDERR, json_encode(['error' => $code, 'detailsWithheld' => true]) . "\n");
    exit(1);
}

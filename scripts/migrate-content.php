<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/database/ContentMigrations.php';
try {
    $migrations=\Zinema\Content\ContentMigrations::load(dirname(__DIR__));
    $db=\Zinema\Database\Runner::connect(\Zinema\Database\Runner::settings());
    echo json_encode(\Zinema\Content\ContentMigrations::apply($db,$migrations),JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $error) {
    $code=$error instanceof \Zinema\Database\MigrationFailure ? $error->reason : 'CONTENT_MIGRATION_FAILED';
    fwrite(STDERR,json_encode(['error'=>$code,'valuesRedacted'=>true]).PHP_EOL); exit(1);
}

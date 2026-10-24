<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/database/ContentMigrations.php';
try {
    \Zinema\Content\ContentMigrations::load($argv[1]);
    echo '{"loaded":true}';
} catch (Throwable $error) {
    $reason=$error instanceof \Zinema\Database\MigrationFailure ? $error->reason : 'MANIFEST_INVALID';
    echo json_encode(['error'=>$reason]);
}

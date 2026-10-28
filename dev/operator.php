<?php
ini_set('display_errors','0');
require_once __DIR__ . '/operations.php';
try {
    if (PHP_SAPI !== 'cli') throw new LocalOperationError('cli_only');
    $raw = stream_get_contents(STDIN,8193);
    if (strlen($raw) > 8192) throw new LocalOperationError('invalid_request');
    $input = json_decode($raw,true,32,JSON_THROW_ON_ERROR);
    [$db,$settings] = operator_context();
    $response = operator_dispatch($db,$settings,$input);
} catch (Throwable $error) {
    $known = $error instanceof LocalOperationError || $error instanceof LocalStorageError;
    $response = ['success'=>false,'error'=>$known ? $error->getMessage() : 'operation_unavailable'];
}
echo json_encode($response,JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(($response['success'] ?? false) ? 0 : 1);

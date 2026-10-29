<?php
// Independent worker boundary: no administrator cookies, browser key or provider fetch.
header('Content-Type: application/json');header('Cache-Control: no-store');
try {
    if (!defined('ZINEMA_STELLAR_CONTEXT')) {http_response_code(503);echo '{"success":false,"error":"extractor_disabled"}';exit;}
    $key=$_SERVER['HTTP_X_EXTRACTOR_WORKER_KEY'] ?? '';
    if (!is_string($key) || !preg_match('/^[a-f0-9]{64}$/D',$key) || !hash_equals(ZINEMA_STELLAR_CONTEXT['worker_key'],$key)) {
        http_response_code(403);echo '{"success":false,"error":"worker_required"}';exit;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') {http_response_code(405);header('Allow: POST');exit('{"success":false,"error":"method_not_allowed"}');}
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0)>2048) {http_response_code(413);exit('{"success":false,"error":"request_too_large"}');}
    require_once __DIR__.'/../includes/environment.php';
    require_once __DIR__.'/../includes/extractor_client.php';
    require_once __DIR__.'/../includes/extractor_queue.php';
    $raw=file_get_contents('php://input',false,null,0,2049);
    if (strlen($raw)>2048) throw new ExtractorFailure('request_too_large',413);
    $input=json_decode($raw,true,8,JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new ExtractorFailure('invalid_request',400);
    echo json_encode(account_extractor_queue()->worker($input),JSON_UNESCAPED_SLASHES);
} catch (ExtractorFailure $error) {http_response_code($error->httpStatus);echo json_encode(['success'=>false,'error'=>$error->getMessage()]);}
catch (Throwable $error) {http_response_code(400);echo '{"success":false,"error":"invalid_request"}';}

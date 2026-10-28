<?php
// Reject oversized/unsolicited JSON before the shared CSRF helper reads the body.
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0)>2048) {http_response_code(413);header('Content-Type: application/json');echo '{"success":false,"error":"request_too_large"}';exit;}
if (($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && !isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {http_response_code(403);header('Content-Type: application/json');echo '{"success":false,"error":"csrf"}';exit;}
require_once __DIR__.'/config.php';
header('Content-Type: application/json');header('Cache-Control: no-store');
if (empty($_SESSION['admin_id'])) {http_response_code(403);echo json_encode(['success'=>false,'error'=>'administrator_required']);exit;}
if ($_SERVER['REQUEST_METHOD']!=='POST') {http_response_code(405);header('Allow: POST');echo json_encode(['success'=>false,'error'=>'method_not_allowed']);exit;}
require_once __DIR__.'/../includes/extractor_client.php';
try {
    $input=json_decode(file_get_contents('php://input',false,null,0,2049),true,8,JSON_THROW_ON_ERROR);
    if (!is_array($input) || !is_string($input['action'] ?? null) || array_diff(array_keys($input),['action','url','job_id','csrf_token'])) throw new ExtractorFailure('invalid_request',400);
    echo json_encode(extractor_request($input['action'],$input['action']==='status'?($input['job_id'] ?? null):($input['url'] ?? null)),JSON_UNESCAPED_SLASHES);
} catch (ExtractorFailure $e) {http_response_code($e->httpStatus);echo json_encode(['success'=>false,'error'=>$e->getMessage()]);}
catch (Throwable $e) {http_response_code(400);echo json_encode(['success'=>false,'error'=>'invalid_request']);}

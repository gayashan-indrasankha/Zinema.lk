<?php
// Authenticated metadata only. No files, recipient data or session payloads.
define('WHATSAPP_API',true);
require_once __DIR__.'/config.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') sendResponse(['success'=>false,'error'=>'method_not_allowed'],405);
validateApiRequest();
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0)>4096) sendResponse(['success'=>false,'error'=>'heartbeat_too_large'],413);
$raw=file_get_contents('php://input',false,null,0,4097);
if (strlen($raw)>4096) sendResponse(['success'=>false,'error'=>'heartbeat_too_large'],413);
$input=json_decode($raw,true);
if (!is_array($input) || array_is_list($input)) sendResponse(['success'=>false,'error'=>'invalid_heartbeat'],400);
$integer=static function($value,$max) {
    return (is_int($value) || (is_string($value) && preg_match('/^(0|[1-9][0-9]*)$/D',$value))) &&
        filter_var($value,FILTER_VALIDATE_INT)!==false && (int)$value>=0 && (int)$value<=$max;
};
$botId=$input['bot_id'] ?? null; $queue=$input['queue_size'] ?? 0;
$uptime=$input['uptime_seconds'] ?? 0; $status=$input['status'] ?? 'online';
$phone=$input['bot_phone'] ?? null; $disk=$input['disk_usage_mb'] ?? 0; $memory=$input['memory_usage_mb'] ?? 0;
if (!$integer($botId,2147483647) || (int)$botId<1 || !$integer($queue,1000000) || !$integer($uptime,2147483647) ||
    !in_array($status,['online','offline'],true) ||
    ($phone!==null && (!is_string($phone) || !preg_match('/^[0-9]{5,20}$/D',$phone))) ||
    !is_numeric($disk) || !is_finite((float)$disk) || (float)$disk<0 || (float)$disk>1099511627776 ||
    !is_numeric($memory) || !is_finite((float)$memory) || (float)$memory<0 || (float)$memory>1099511627776)
    sendResponse(['success'=>false,'error'=>'invalid_heartbeat'],400);
$botId=(int)$botId; $queue=(int)$queue; $uptime=(int)$uptime; $disk=(float)$disk; $memory=(float)$memory;
try {
    $stmt=$conn->prepare("INSERT INTO bot_health_status
        (bot_id,status,queue_size,disk_usage_mb,memory_usage_mb,uptime_seconds,bot_phone,last_heartbeat,created_at,updated_at)
        VALUES(?,?,?,?,?,?,?,NOW(),NOW(),NOW()) ON DUPLICATE KEY UPDATE
        status=VALUES(status),queue_size=VALUES(queue_size),disk_usage_mb=VALUES(disk_usage_mb),
        memory_usage_mb=VALUES(memory_usage_mb),uptime_seconds=VALUES(uptime_seconds),
        bot_phone=COALESCE(VALUES(bot_phone),bot_phone),last_heartbeat=NOW(),error_message=NULL,updated_at=NOW()");
    $stmt->bind_param('isiddis',$botId,$status,$queue,$disk,$memory,$uptime,$phone);
    $stmt->execute(); sendResponse(['success'=>true,'message'=>'Heartbeat received']);
} catch (Throwable $error) { sendResponse(['success'=>false,'error'=>'heartbeat_unavailable'],503); }

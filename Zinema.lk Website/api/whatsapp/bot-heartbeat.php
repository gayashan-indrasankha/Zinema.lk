<?php
/**
 * Bot Heartbeat API
 * Receives health status from bot instances
 */

define('WHATSAPP_API', true);
require_once 'config.php';
validateApiRequest();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['bot_id'])) {
    sendResponse(['success' => false, 'error' => 'Bot ID required'], 400);
}

$botId = (int)$input['bot_id'];
$queueSize = isset($input['queue_size']) ? (int)$input['queue_size'] : 0;
$diskUsageMb = isset($input['disk_usage_mb']) ? (float)$input['disk_usage_mb'] : 0;
$memoryUsageMb = isset($input['memory_usage_mb']) ? (float)$input['memory_usage_mb'] : 0;
$uptimeSeconds = isset($input['uptime_seconds']) ? (int)$input['uptime_seconds'] : 0;
$status = isset($input['status']) ? $input['status'] : 'online';
$botPhone = isset($input['bot_phone']) ? $input['bot_phone'] : null;

try {
    // First check if bot entry exists
    $checkQuery = "SELECT bot_id FROM bot_health_status WHERE bot_id = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, 'i', $botId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $exists = mysqli_num_rows($checkResult) > 0;
    mysqli_stmt_close($checkStmt);
    
    if ($exists) {
        // Update existing record
        $sql = "UPDATE bot_health_status SET 
                    status = ?,
                    queue_size = ?,
                    disk_usage_mb = ?, 
                    memory_usage_mb = ?,
                    uptime_seconds = ?,
                    bot_phone = COALESCE(?, bot_phone),
                    last_heartbeat = NOW(),
                    error_message = NULL,
                    updated_at = NOW()
                WHERE bot_id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'siddisi', $status, $queueSize, $diskUsageMb, $memoryUsageMb, $uptimeSeconds, $botPhone, $botId);
    } else {
        // Insert new record
        $sql = "INSERT INTO bot_health_status 
                (bot_id, status, queue_size, disk_usage_mb, memory_usage_mb, uptime_seconds, bot_phone, last_heartbeat, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'isiddis', $botId, $status, $queueSize, $diskUsageMb, $memoryUsageMb, $uptimeSeconds, $botPhone);
    }
    
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    sendResponse(['success' => true, 'message' => 'Heartbeat received']);
} catch (Exception $e) {
    sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
?>

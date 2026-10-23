<?php
/**
 * Update Daily Stats API
 * Updates daily statistics for bots
 */

define('WHATSAPP_API', true);
require_once 'config.php';
validateApiRequest();

$input = json_decode(file_get_contents('php://input'), true);

$botId = (int)($input['bot_id'] ?? 0);
$successful = (int)($input['successful'] ?? 0);
$failed = (int)($input['failed'] ?? 0);

if (!$botId) {
    echo json_encode(['success' => false, 'error' => 'Bot ID required']);
    exit;
}

try {
    $sql = "UPDATE bot_health_status 
            SET total_requests_today = total_requests_today + ?,
                successful_forwards_today = successful_forwards_today + ?,
                failed_forwards_today = failed_forwards_today + ?
            WHERE bot_id = ?";
    
    $totalRequests = $successful + $failed;
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'iiii', $totalRequests, $successful, $failed, $botId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

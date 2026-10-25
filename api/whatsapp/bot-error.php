<?php
/**
 * Bot Error Reporting API
 * Receives and logs bot errors
 */

define('WHATSAPP_API', true);
require_once 'config.php';
validateApiRequest();

$input = json_decode(file_get_contents('php://input'), true);

$botId = (int)($input['bot_id'] ?? 0 );
$errorMessage = $input['error_message'] ?? null;

if (!$botId || !$errorMessage) {
    echo json_encode(['success' => false, 'error' => 'Bot ID and error message required']);
    exit;
}

try {
    // Update bot status with error
    $sql = "UPDATE bot_health_status 
            SET status = 'error',
                error_message = ?,
                updated_at = NOW()
            WHERE bot_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $errorMessage, $botId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Create system alert
    $alertSql = "INSERT INTO system_alerts 
                 (alert_type, severity, message, bot_id)
                 VALUES ('error', 'critical', ?, ?)";
    
    $alertStmt = mysqli_prepare($conn, $alertSql);
    mysqli_stmt_bind_param($alertStmt, 'si', $errorMessage, $botId);
    mysqli_stmt_execute($alertStmt);
    mysqli_stmt_close($alertStmt);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

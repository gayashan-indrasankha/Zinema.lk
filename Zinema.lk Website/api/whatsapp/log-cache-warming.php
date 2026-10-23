<?php
/**
 * Log Cache Warming API
 * Logs cache warming operations
 */

define('WHATSAPP_API', true);
require_once 'config.php';
validateApiRequest();

$input = json_decode(file_get_contents('php://input'), true);

$botId = (int)($input['bot_id'] ?? 0);
$contentType = $input['content_type'] ?? null;
$contentId = (int)($input['content_id'] ?? 0);
$partNumber = isset($input['part_number']) ? (int)$input['part_number'] : null;
$success = isset($input['success']) ? (bool)$input['success'] : true;
$errorMessage = $input['error_message'] ?? null;

if (!$botId || !$contentType || !$contentId) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $sql = "INSERT INTO cache_warming_log 
            (bot_id, content_type, content_id, part_number, success, error_message)
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'isiiss', 
        $botId, $contentType, $contentId, $partNumber, $success, $errorMessage);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

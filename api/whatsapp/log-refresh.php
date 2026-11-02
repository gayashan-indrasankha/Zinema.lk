<?php
/**
 * WhatsApp API Bridge - Log Refresh Activity
 * Logs media refresh activity for cache warming
 * 
 * POST /api/whatsapp/log-refresh.php
 * Body: { "message_id": "...", "file_name": "...", "file_type": "video" }
 */

define('WHATSAPP_API', true);
require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Validate API key
validateApiRequest();

// Get input
$input = getJsonInput();
$messageId = isset($input['message_id']) ? trim($input['message_id']) : '';
$fileName = isset($input['file_name']) ? trim($input['file_name']) : '';
$fileType = isset($input['file_type']) ? trim($input['file_type']) : '';

if (empty($messageId)) {
    sendResponse(['error' => 'message_id is required'], 400);
}

logApiRequest('log-refresh', ['message_id' => $messageId]);

try {
    $stmt = $conn->prepare("INSERT INTO media_refresh_log (message_id, file_name, file_type, refresh_count) 
                           VALUES (?, ?, ?, 1) 
                           ON DUPLICATE KEY UPDATE 
                           last_refreshed = CURRENT_TIMESTAMP, 
                           refresh_count = refresh_count + 1");
    $stmt->bind_param("sss", $messageId, $fileName, $fileType);
    
    if ($stmt->execute()) {
        sendResponse(['success' => true]);
    } else {
        sendResponse(['success' => false, 'error' => 'Failed to log refresh'], 500);
    }
    
    $stmt->close();
} catch (Exception $e) {
    sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
?>
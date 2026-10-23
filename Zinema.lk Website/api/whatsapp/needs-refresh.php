<?php
/**
 * WhatsApp API Bridge - Check if Media Needs Refresh
 * Checks if a media file needs to be refreshed based on last refresh time
 * 
 * POST /api/whatsapp/needs-refresh.php
 * Body: { "message_id": "..." }
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

if (empty($messageId)) {
    sendResponse(['needs_refresh' => true]);
}

logApiRequest('needs-refresh', ['message_id' => $messageId]);

try {
    $stmt = $conn->prepare("SELECT last_refreshed FROM media_refresh_log WHERE message_id = ?");
    $stmt->bind_param("s", $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(['needs_refresh' => true]);
    } else {
        $row = $result->fetch_assoc();
        $lastRefreshed = strtotime($row['last_refreshed']);
        $hoursSince = (time() - $lastRefreshed) / 3600;
        sendResponse(['needs_refresh' => $hoursSince > 48]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    sendResponse(['needs_refresh' => true, 'error' => $e->getMessage()]);
}
?>
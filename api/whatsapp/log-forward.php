<?php
/**
 * WhatsApp API Bridge - Log Forward Activity
 * Logs forwarding activity (success/failure)
 * 
 * POST /api/whatsapp/log-forward.php
 * Body: { "token_id": 123, "user_phone": "94...", "user_chat_id": "...", "status": "success", "error_message": null }
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
$tokenId = isset($input['token_id']) ? (int)$input['token_id'] : null;
$userPhone = isset($input['user_phone']) ? trim($input['user_phone']) : '';
$userChatId = isset($input['user_chat_id']) ? trim($input['user_chat_id']) : '';
$status = isset($input['status']) ? trim($input['status']) : 'pending';
$errorMessage = isset($input['error_message']) ? trim($input['error_message']) : null;

// Validate required fields
if (empty($userPhone) || empty($userChatId)) {
    sendResponse(['error' => 'user_phone and user_chat_id are required'], 400);
}

// Validate status
if (!in_array($status, ['success', 'failed', 'pending'])) {
    sendResponse(['error' => 'Invalid status. Must be: success, failed, or pending'], 400);
}

logApiRequest('log-forward', ['token_id' => $tokenId, 'status' => $status]);

// Insert log entry
// Handle null token_id (for failed lookups)
if ($tokenId && $tokenId > 0) {
    $stmt = $conn->prepare("INSERT INTO whatsapp_forward_logs (token_id, user_phone, user_chat_id, status, error_message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $tokenId, $userPhone, $userChatId, $status, $errorMessage);
} else {
    // Log without token_id foreign key
    $stmt = $conn->prepare("INSERT INTO whatsapp_forward_logs (token_id, user_phone, user_chat_id, status, error_message) VALUES (NULL, ?, ?, ?, ?)");
    $stmt->bind_param("ssss", $userPhone, $userChatId, $status, $errorMessage);
}

if ($stmt->execute()) {
    sendResponse([
        'success' => true,
        'log_id' => $stmt->insert_id
    ]);
} else {
    sendResponse([
        'success' => false,
        'error' => 'Failed to log activity'
    ], 500);
}

$stmt->close();
?>

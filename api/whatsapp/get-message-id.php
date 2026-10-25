<?php
/**
 * WhatsApp API Bridge - Get Message ID
 * Gets WhatsApp message ID for a specific content
 * 
 * POST /api/whatsapp/get-message-id.php
 * Body: { "content_type": "movie", "content_id": 63 }
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
$contentType = isset($input['content_type']) ? trim($input['content_type']) : '';
$contentId = isset($input['content_id']) ? (int)$input['content_id'] : 0;

// Validate required fields
if (empty($contentType) || $contentId <= 0) {
    sendResponse(['error' => 'content_type and content_id are required'], 400);
}

if (!in_array($contentType, ['movie', 'episode'])) {
    sendResponse(['error' => 'Invalid content_type. Must be: movie or episode'], 400);
}

logApiRequest('get-message-id', ['content_type' => $contentType, 'content_id' => $contentId]);

// Query message ID
$stmt = $conn->prepare("SELECT message_id, file_name FROM whatsapp_message_ids WHERE content_type = ? AND content_id = ?");
$stmt->bind_param("si", $contentType, $contentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    sendResponse([
        'found' => false,
        'message' => 'No message ID configured for this content'
    ]);
}

$data = $result->fetch_assoc();
$stmt->close();

sendResponse([
    'found' => true,
    'message_id' => $data['message_id'],
    'file_name' => $data['file_name']
]);
?>

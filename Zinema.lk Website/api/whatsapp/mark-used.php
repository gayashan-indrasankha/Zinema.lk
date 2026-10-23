<?php
/**
 * WhatsApp API Bridge - Mark Token as Used
 * Marks a token as used immediately when processing starts
 * 
 * POST /api/whatsapp/mark-used.php
 * Body: { "token_id": 123 }
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
$tokenId = isset($input['token_id']) ? (int)$input['token_id'] : 0;

if ($tokenId <= 0) {
    sendResponse(['error' => 'Valid token_id is required'], 400);
}

logApiRequest('mark-used', ['token_id' => $tokenId]);

// Update token to mark as used
$stmt = $conn->prepare("UPDATE whatsapp_tokens SET is_used = 1, used_at = NOW() WHERE id = ? AND is_used = 0");
$stmt->bind_param("i", $tokenId);
$stmt->execute();

$affectedRows = $stmt->affected_rows;
$stmt->close();

if ($affectedRows > 0) {
    sendResponse([
        'success' => true,
        'message' => 'Token marked as used'
    ]);
} else {
    sendResponse([
        'success' => false,
        'message' => 'Token already used or not found'
    ]);
}
?>

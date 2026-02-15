<?php
/**
 * WhatsApp API Bridge - Add Message ID
 * Adds or updates a WhatsApp message ID for content
 * 
 * POST /api/whatsapp/add-message-id.php
 * Body: { "content_type": "movie", "content_id": 89, "message_id": "...", "file_name": "..." }
 * 
 * For movie parts:
 * Body: { "content_type": "movie", "content_id": 89, "message_id": "...", "part_number": 1, "file_name": "..." }
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
$messageId = isset($input['message_id']) ? trim($input['message_id']) : '';
$fileName = isset($input['file_name']) ? trim($input['file_name']) : null;
$partNumber = isset($input['part_number']) ? (int)$input['part_number'] : 0;
$fileSize = isset($input['file_size']) ? (int)$input['file_size'] : null;

// Validate required fields
if (empty($contentType) || $contentId <= 0 || empty($messageId)) {
    sendResponse(['error' => 'content_type, content_id, and message_id are required'], 400);
}

if (!in_array($contentType, ['movie', 'episode'])) {
    sendResponse(['error' => 'Invalid content_type. Must be: movie or episode'], 400);
}

logApiRequest('add-message-id', [
    'content_type' => $contentType, 
    'content_id' => $contentId,
    'part_number' => $partNumber
]);

// If part_number is provided, insert into appropriate parts table
if ($partNumber > 0 && in_array($contentType, ['movie', 'episode'])) {
    // Determine which table to use based on content type
    $tableName = $contentType === 'movie' ? 'movie_parts' : 'episode_parts';
    $idColumn = $contentType === 'movie' ? 'movie_id' : 'episode_id';
    
    $stmt = $conn->prepare(
        "INSERT INTO {$tableName} ({$idColumn}, part_number, message_id, file_name, file_size) 
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE message_id = ?, file_name = ?, file_size = ?, updated_at = NOW()"
    );
    $stmt->bind_param("iississi", 
        $contentId, $partNumber, $messageId, $fileName, $fileSize,
        $messageId, $fileName, $fileSize
    );
    
    if ($stmt->execute()) {
        $isNew = $stmt->affected_rows === 1;
        sendResponse([
            'success' => true,
            'action' => $isNew ? 'created' : 'updated',
            'type' => 'part',
            'content_type' => $contentType,
            'content_id' => $contentId,
            'part_number' => $partNumber,
            'message_id' => $messageId,
            'file_name' => $fileName
        ]);
    } else {
        sendResponse([
            'success' => false,
            'error' => 'Failed to save ' . $contentType . ' part'
        ], 500);
    }
} else {
    // Standard behavior - insert into whatsapp_message_ids
    $stmt = $conn->prepare(
        "INSERT INTO whatsapp_message_ids (content_type, content_id, message_id, file_name) 
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE message_id = ?, file_name = ?, updated_at = NOW()"
    );
    $stmt->bind_param("sissss", $contentType, $contentId, $messageId, $fileName, $messageId, $fileName);
    
    if ($stmt->execute()) {
        $isNew = $stmt->affected_rows === 1;
        sendResponse([
            'success' => true,
            'action' => $isNew ? 'created' : 'updated',
            'type' => 'full',
            'content_type' => $contentType,
            'content_id' => $contentId,
            'message_id' => $messageId,
            'file_name' => $fileName
        ]);
    } else {
        sendResponse([
            'success' => false,
            'error' => 'Failed to save message ID'
        ], 500);
    }
}

$stmt->close();
?>

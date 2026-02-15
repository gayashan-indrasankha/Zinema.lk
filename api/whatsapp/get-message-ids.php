<?php
/**
 * WhatsApp API Bridge - Get Message IDs
 * Returns all message IDs for a specific content item
 * 
 * POST /api/whatsapp/get-message-ids.php
 * Body: { "content_type": "movie", "content_id": 92 }
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
$contentType = isset($input['content_type']) ? strtolower(trim($input['content_type'])) : '';
$contentId = isset($input['content_id']) ? (int)$input['content_id'] : 0;

if (empty($contentType) || $contentId <= 0) {
    sendResponse(['error' => 'content_type and content_id are required'], 400);
}

if (!in_array($contentType, ['movie', 'episode'])) {
    sendResponse(['error' => 'content_type must be movie or episode'], 400);
}

logApiRequest('get-message-ids', ['content_type' => $contentType, 'content_id' => $contentId]);

$results = [];

// Get main message ID from whatsapp_message_ids
$stmt = $conn->prepare("SELECT message_id, file_name FROM whatsapp_message_ids WHERE content_type = ? AND content_id = ?");
$stmt->bind_param("si", $contentType, $contentId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $results[] = [
        'part_number' => null,
        'message_id' => $row['message_id'],
        'file_name' => $row['file_name']
    ];
}
$stmt->close();

// Get parts message IDs
if ($contentType === 'movie') {
    $stmt = $conn->prepare("SELECT part_number, message_id, file_name FROM movie_parts WHERE movie_id = ? ORDER BY part_number");
    $stmt->bind_param("i", $contentId);
} else {
    $stmt = $conn->prepare("SELECT part_number, message_id, file_name FROM episode_parts WHERE episode_id = ? ORDER BY part_number");
    $stmt->bind_param("i", $contentId);
}

$stmt->execute();
$partsResult = $stmt->get_result();
while ($row = $partsResult->fetch_assoc()) {
    $results[] = [
        'part_number' => (int)$row['part_number'],
        'message_id' => $row['message_id'],
        'file_name' => $row['file_name']
    ];
}
$stmt->close();

sendResponse([
    'success' => true,
    'content_type' => $contentType,
    'content_id' => $contentId,
    'data' => $results,
    'count' => count($results)
]);
?>

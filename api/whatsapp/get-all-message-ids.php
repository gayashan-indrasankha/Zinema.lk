<?php
/**
 * WhatsApp API Bridge - Get All Message IDs
 * Returns ALL message IDs from the database for health checking
 * 
 * POST /api/whatsapp/get-all-message-ids.php
 * Body: { }
 */

define('WHATSAPP_API', true);
require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Validate API key
validateApiRequest();

logApiRequest('get-all-message-ids', []);

$results = [];

// Get all main message IDs from whatsapp_message_ids
$stmt = $conn->prepare("SELECT content_type, content_id, message_id, file_name FROM whatsapp_message_ids ORDER BY content_type, content_id");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $results[] = [
        'content_type' => $row['content_type'],
        'content_id' => (int)$row['content_id'],
        'part_number' => null,
        'message_id' => $row['message_id'],
        'file_name' => $row['file_name']
    ];
}
$stmt->close();

// Get all movie parts
$stmt = $conn->prepare("SELECT 'movie' as content_type, movie_id as content_id, part_number, message_id, file_name FROM movie_parts ORDER BY movie_id, part_number");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $results[] = [
        'content_type' => $row['content_type'],
        'content_id' => (int)$row['content_id'],
        'part_number' => (int)$row['part_number'],
        'message_id' => $row['message_id'],
        'file_name' => $row['file_name']
    ];
}
$stmt->close();

// Get all episode parts
$stmt = $conn->prepare("SELECT 'episode' as content_type, episode_id as content_id, part_number, message_id, file_name FROM episode_parts ORDER BY episode_id, part_number");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $results[] = [
        'content_type' => $row['content_type'],
        'content_id' => (int)$row['content_id'],
        'part_number' => (int)$row['part_number'],
        'message_id' => $row['message_id'],
        'file_name' => $row['file_name']
    ];
}
$stmt->close();

sendResponse([
    'success' => true,
    'data' => $results,
    'count' => count($results)
]);
?>

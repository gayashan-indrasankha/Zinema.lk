<?php
/**
 * WhatsApp API Bridge - Get Movie Parts
 * Returns all parts for a movie if they exist
 * 
 * GET /api/whatsapp/get-movie-parts.php?movie_id=90
 */

define('WHATSAPP_API', true);
require_once 'config.php';

// Allow both GET and POST
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $movieId = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateApiRequest();
    $input = getJsonInput();
    $movieId = isset($input['movie_id']) ? (int)$input['movie_id'] : 0;
} else {
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Validate
if ($movieId <= 0) {
    sendResponse(['error' => 'movie_id is required'], 400);
}

// Get all parts for this movie
$stmt = $conn->prepare(
    "SELECT id, part_number, message_id, file_name, file_size, created_at 
     FROM movie_parts 
     WHERE movie_id = ? 
     ORDER BY part_number ASC"
);
$stmt->bind_param("i", $movieId);
$stmt->execute();
$result = $stmt->get_result();

$parts = [];
while ($row = $result->fetch_assoc()) {
    $parts[] = [
        'id' => (int)$row['id'],
        'part_number' => (int)$row['part_number'],
        'message_id' => $row['message_id'],
        'file_name' => $row['file_name'],
        'file_size' => $row['file_size'] ? (int)$row['file_size'] : null,
        'file_size_formatted' => $row['file_size'] ? formatBytes($row['file_size']) : null
    ];
}

$stmt->close();

sendResponse([
    'success' => true,
    'movie_id' => $movieId,
    'has_parts' => count($parts) > 0,
    'total_parts' => count($parts),
    'parts' => $parts
]);

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>

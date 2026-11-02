<?php
/**
 * WhatsApp API Bridge - Validate Token
 * Validates a token and returns its data
 * 
 * POST /api/whatsapp/validate-token.php
 * Body: { "token": "ABC123XYZ789" }
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
$token = isset($input['token']) ? strtoupper(trim($input['token'])) : '';

if (empty($token)) {
    sendResponse(['error' => 'Token is required'], 400);
}

logApiRequest('validate-token', ['token' => $token]);

// Query token with validation - use MySQL NOW() to check expiration to avoid timezone issues
$sql = "SELECT 
            t.id, 
            t.token, 
            t.content_type, 
            t.content_id, 
            t.message_id,
            t.part_number,
            t.is_used, 
            t.is_active, 
            t.expires_at,
            t.created_at,
            m.message_id as content_message_id,
            m.file_name,
            mp.message_id as part_message_id,
            mp.file_name as part_file_name,
            ep.message_id as episode_part_message_id,
            ep.file_name as episode_part_file_name,
            CASE WHEN t.expires_at < NOW() THEN 1 ELSE 0 END as is_expired
        FROM whatsapp_tokens t
        LEFT JOIN whatsapp_message_ids m ON t.content_type = m.content_type AND t.content_id = m.content_id
        LEFT JOIN movie_parts mp ON t.content_type = 'movie' AND t.content_id = mp.movie_id AND t.part_number = mp.part_number
        LEFT JOIN episode_parts ep ON t.content_type = 'episode' AND t.content_id = ep.episode_id AND t.part_number = ep.part_number
        WHERE t.token = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    sendResponse([
        'valid' => false,
        'error' => 'not_found',
        'message' => 'Token not found'
    ]);
}

$tokenData = $result->fetch_assoc();
$stmt->close();

// Check if token is active
if (!$tokenData['is_active']) {
    sendResponse([
        'valid' => false,
        'error' => 'inactive',
        'message' => 'Token is no longer active',
        'data' => $tokenData
    ]);
}

// Check if token has been used
if ($tokenData['is_used']) {
    sendResponse([
        'valid' => false,
        'error' => 'already_used',
        'message' => 'Token has already been used',
        'data' => $tokenData
    ]);
}

// Check if token has expired (using MySQL NOW() comparison from query)
if ($tokenData['is_expired']) {
    sendResponse([
        'valid' => false,
        'error' => 'expired',
        'message' => 'Token has expired',
        'data' => $tokenData
    ]);
}

// Determine which Message ID to use
// Priority: 
// 1. Manually set ID on the token itself (rare override)
// 2. Part-specific ID (if it's a part token) - check both movie and episode parts
// 3. Main content ID (fallback / full movie/episode)
$messageId = $tokenData['message_id']; // Token override

if (!$messageId && !empty($tokenData['part_message_id'])) {
    $messageId = $tokenData['part_message_id'];
    // Also use the part filename if available
    if (!empty($tokenData['part_file_name'])) {
        $tokenData['file_name'] = $tokenData['part_file_name'];
    }
}

if (!$messageId && !empty($tokenData['episode_part_message_id'])) {
    $messageId = $tokenData['episode_part_message_id'];
    // Also use the episode part filename if available
    if (!empty($tokenData['episode_part_file_name'])) {
        $tokenData['file_name'] = $tokenData['episode_part_file_name'];
    }
}

if (!$messageId) {
    $messageId = $tokenData['content_message_id'];
}

if (!$messageId && $tokenData['content_type'] === 'episode' && !empty($tokenData['part_number'])) {
    // Fallback: Direct query to episode_parts (in case JOIN failed)
    $stmt = $conn->prepare("SELECT message_id, file_name FROM episode_parts WHERE episode_id = ? AND part_number = ?");
    $stmt->bind_param("ii", $tokenData['content_id'], $tokenData['part_number']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $messageId = $row['message_id'];
        if (!empty($row['file_name'])) {
            $tokenData['file_name'] = $row['file_name'];
        }
    }
    $stmt->close();
}

if (!$messageId) {
    sendResponse([
        'valid' => false,
        'error' => 'no_message_id',
        'message' => 'No video configured for this content',
        'data' => $tokenData
    ]);
}

// Get content title
$title = null;
if ($tokenData['content_type'] === 'movie') {
    $titleStmt = $conn->prepare("SELECT title FROM movies WHERE id = ?");
    $titleStmt->bind_param("i", $tokenData['content_id']);
    $titleStmt->execute();
    $titleResult = $titleStmt->get_result();
    if ($titleResult->num_rows > 0) {
        $title = $titleResult->fetch_assoc()['title'];
    }
    $titleStmt->close();
} else if ($tokenData['content_type'] === 'episode') {
    $titleStmt = $conn->prepare("SELECT CONCAT(s.title, ' - ', e.title) as title 
                                  FROM episodes e 
                                  LEFT JOIN series s ON e.series_id = s.id 
                                  WHERE e.id = ?");
    $titleStmt->bind_param("i", $tokenData['content_id']);
    $titleStmt->execute();
    $titleResult = $titleStmt->get_result();
    if ($titleResult->num_rows > 0) {
        $title = $titleResult->fetch_assoc()['title'];
    }
    $titleStmt->close();
}

// Return valid token data
sendResponse([
    'valid' => true,
    'data' => [
        'id' => (int)$tokenData['id'],
        'token' => $tokenData['token'],
        'content_type' => $tokenData['content_type'],
        'content_id' => (int)$tokenData['content_id'],
        'part_number' => $tokenData['part_number'] ? (int)$tokenData['part_number'] : null,
        'message_id' => $messageId,
        'file_name' => $tokenData['file_name'],
        'title' => $title,
        'expires_at' => $tokenData['expires_at']
    ]
]);
?>

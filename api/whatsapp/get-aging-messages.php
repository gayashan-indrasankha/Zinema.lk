<?php
/**
 * Get messages older than X days that need refreshing
 * Prevents "Download failed" by identifying content that will expire soon
 */

require_once __DIR__ . '/../../admin/config.php';

header('Content-Type: application/json');

// Validate API key
$headers = getallheaders();
$apiKey = $headers['X-Api-Key'] ?? '';

if ($apiKey !== 'zinema-whatsapp-bot-2024') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid API key']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$daysOld = $input['days_old'] ?? 10;

try {
    $messages = [];
    
    // Get aging movie parts (uploaded more than X days ago)
    $stmt = $conn->prepare("
        SELECT 'movie' as content_type, movie_id as content_id, part_number, message_id, updated_at 
        FROM movie_parts 
        WHERE message_id IS NOT NULL 
        AND updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY updated_at ASC
        LIMIT 100
    ");
    $stmt->bind_param("i", $daysOld);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
    
    // Get aging episode parts
    $stmt = $conn->prepare("
        SELECT 'episode' as content_type, episode_id as content_id, part_number, message_id, updated_at 
        FROM episode_parts 
        WHERE message_id IS NOT NULL 
        AND updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY updated_at ASC
        LIMIT 100
    ");
    $stmt->bind_param("i", $daysOld);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
    
    // Get aging movies (full files, not parts)
    $stmt = $conn->prepare("
        SELECT 'movie' as content_type, id as content_id, 0 as part_number, message_id, updated_at 
        FROM movies 
        WHERE message_id IS NOT NULL 
        AND updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY updated_at ASC
        LIMIT 50
    ");
    $stmt->bind_param("i", $daysOld);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
    
    // Get aging episodes (full files, not parts)
    $stmt = $conn->prepare("
        SELECT 'episode' as content_type, id as content_id, 0 as part_number, message_id, updated_at 
        FROM episodes 
        WHERE message_id IS NOT NULL 
        AND updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY updated_at ASC
        LIMIT 50
    ");
    $stmt->bind_param("i", $daysOld);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'days_threshold' => $daysOld,
        'count' => count($messages),
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

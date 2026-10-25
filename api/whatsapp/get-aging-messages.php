<?php
/**
 * Get messages older than X days that need refreshing
 * Prevents "Download failed" by identifying content that will expire soon
 */

define('WHATSAPP_API', true);
require_once __DIR__ . '/config.php';
validateApiRequest();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse(['success' => false, 'error' => 'POST required'], 405);

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$daysOld = $input['days_old'] ?? 10;

try {
    $messages = [];
    
    $stmt = $conn->prepare("SELECT content_type, content_id, part_number, message_id, updated_at
        FROM v_delivery_files WHERE message_id IS NOT NULL
        AND updated_at < DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY updated_at ASC LIMIT 300");
    $stmt->bind_param('i', $daysOld); $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $messages[] = $row;
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

<?php
/**
 * Log Cache Warming API
 * Logs cache warming operations
 */

define('WHATSAPP_API', true);
require_once 'config.php';
validateApiRequest();

$input = json_decode(file_get_contents('php://input'), true);

$botId = (int)($input['bot_id'] ?? 0);
$contentType = $input['content_type'] ?? null;
$contentId = (int)($input['content_id'] ?? 0);
$partNumber = isset($input['part_number']) ? (int)$input['part_number'] : null;
$success = isset($input['success']) ? (int)(bool)$input['success'] : 1;
$errorMessage = $input['error_message'] ?? null;

if (!$botId || !$contentType || !$contentId) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cache_warming_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bot_id INT NOT NULL,
        content_type ENUM('movie', 'episode') NOT NULL,
        content_id INT NOT NULL,
        part_number INT DEFAULT NULL,
        warmed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        file_size_mb DECIMAL(10,2) DEFAULT NULL,
        success BOOLEAN DEFAULT TRUE,
        error_message TEXT DEFAULT NULL,
        INDEX idx_bot_id (bot_id),
        INDEX idx_warmed_at (warmed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $sql = "INSERT INTO cache_warming_log 
            (bot_id, content_type, content_id, part_number, success, error_message)
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'isiiis', 
        $botId, $contentType, $contentId, $partNumber, $success, $errorMessage);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

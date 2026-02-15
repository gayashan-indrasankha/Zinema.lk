<?php
/**
 * Update Cache Message ID API
 * Updates the cache message ID for a content item
 */

define('WHATSAPP_API', true);
require_once 'config.php';
validateApiRequest();

$input = json_decode(file_get_contents('php://input'), true);

$contentType = $input['content_type'] ?? null;
$contentId = (int)($input['content_id'] ?? 0);
$partNumber = isset($input['part_number']) ? (int)$input['part_number'] : null;
$cacheMessageId = $input['cache_message_id'] ?? null;

if (!$contentType || !$contentId || !$cacheMessageId) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Update movie_message_ids table
    if ($contentType === 'movie') {
        if ($partNumber) {
            $sql = "UPDATE movie_message_ids 
                    SET cache_message_id = ?, 
                        message_id_updated_at = NOW()
                    WHERE movie_id = ? AND part_number = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'sii', $cacheMessageId, $contentId, $partNumber);
        } else {
            $sql = "UPDATE movie_message_ids 
                    SET cache_message_id = ?, 
                        message_id_updated_at = NOW()
                    WHERE movie_id = ? AND part_number IS NULL";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'si', $cacheMessageId, $contentId);
        }
        
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        
        echo json_encode(['success' => true, 'affected_rows' => $affected]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unsupported content type']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

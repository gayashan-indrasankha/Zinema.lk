<?php
/**
 * Update Forward Statistics API
 * Records forward statistics and cache hit/miss data
 */

define('WHATSAPP_API', true);
require_once 'config.php';
validateApiRequest();

$input = json_decode(file_get_contents('php://input'), true);

$tokenId = (int)($input['token_id'] ?? 0);
$hitType = $input['hit_type'] ?? 'cache_miss'; // 'cache_hit' or 'cache_miss'

if (!$tokenId) {
    echo json_encode(['success' => false, 'error' => 'Token ID required']);
    exit;
}

try {
    // Get token details
    $tokenQuery = "SELECT content_type, content_id, part_number FROM movie_message_ids 
                   WHERE id = ?";
    $stmt = mysqli_prepare($conn, $tokenQuery);
    mysqli_stmt_bind_param($stmt, 'i', $tokenId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $tokenData = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$tokenData) {
        echo json_encode(['success' => false, 'error' => 'Token not found']);
        exit;
    }
    
    // Update movie_message_ids counters
    $isCacheHit = ($hitType === 'cache_hit');
    
    if ($isCacheHit) {
        $sql = "UPDATE movie_message_ids 
                SET cache_hit_count = cache_hit_count + 1,
                    forward_count = forward_count + 1,
                    last_forwarded_at = NOW()
                WHERE id = ?";
    } else {
        $sql = "UPDATE movie_message_ids 
                SET cache_miss_count = cache_miss_count + 1,
                    forward_count = forward_count + 1,
                    last_forwarded_at = NOW()
                WHERE id = ?";
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $tokenId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Also update bot_statistics table
    // (This would typically be done via stored procedure for accuracy)
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

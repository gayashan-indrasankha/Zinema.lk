<?php
/**
 * Get Popular Files API
 * Returns list of popular content for cache warming
 */

define('WHATSAPP_API', true);
require_once 'config.php';
validateApiRequest();

$input = json_decode(file_get_contents('php://input'), true);

$limit = isset($input['limit']) ? (int)$input['limit'] : 50;
$botId = isset($input['bot_id']) ? (int)$input['bot_id'] : null;

try {
    // Get popular content from view
    $sql = "SELECT 
                content_type,
                content_id,
                part_number,
                total_forwards,
                cache_hit_rate,
                total_data_saved_mb,
                local_file_path,
                cache_message_id,
                hours_since_cache_update,
                cache_status
            FROM v_popular_content
            WHERE local_file_path IS NOT NULL
            ORDER BY total_forwards DESC
            LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $files = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $files[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'success' => true,
        'files' => $files,
        'count' => count($files)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

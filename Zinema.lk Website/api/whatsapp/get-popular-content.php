<?php
/**
 * Get Popular Content API
 * Returns most requested videos for cache warming
 */

require_once 'config.php';

// Verify API key
if (!verifyApiKey()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = getApiInput();
$limit = isset($input['limit']) ? intval($input['limit']) : 50;

// Limit to max 100
$limit = min($limit, 100);

try {
    // Query popular content based on token usage
    // Get movies and episodes that have been requested most in the last 7 days
    $sql = "SELECT 
        wt.content_type,
        wt.content_id,
        wt.part_number,
        wt.message_id,
        CASE 
            WHEN wt.content_type = 'movie' THEN m.title
            WHEN wt.content_type = 'episode' THEN CONCAT(s.title, ' - ', e.title)
            ELSE 'Unknown'
        END as title,
        COUNT(wt.id) as request_count,
        MAX(wt.created_at) as last_requested
    FROM whatsapp_tokens wt
    LEFT JOIN movies m ON wt.content_type = 'movie' AND wt.content_id = m.id
    LEFT JOIN episodes e ON wt.content_type = 'episode' AND wt.content_id = e.id
    LEFT JOIN tv_series s ON e.series_id = s.id
    WHERE wt.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND wt.message_id IS NOT NULL
    GROUP BY wt.content_type, wt.content_id, wt.part_number
    ORDER BY request_count DESC, last_requested DESC
    LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $content = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $content[] = [
            'content_type' => $row['content_type'],
            'content_id' => (int)$row['content_id'],
            'part_number' => $row['part_number'] ? (int)$row['part_number'] : null,
            'message_id' => $row['message_id'],
            'title' => $row['title'],
            'request_count' => (int)$row['request_count'],
            'last_requested' => $row['last_requested']
        ];
    }
    
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'success' => true,
        'data' => $content,
        'count' => count($content)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

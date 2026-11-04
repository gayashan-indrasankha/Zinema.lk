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
    // Only split files have a cache column; a whole file keeps its main message ID.
    $tables = ['movie' => ['movie_parts', 'movie_id'], 'episode' => ['episode_parts', 'episode_id']];
    if (!isset($tables[$contentType])) {
        echo json_encode(['success' => false, 'error' => 'Unsupported content type']);
        exit;
    }
    if (!$partNumber) {
        echo json_encode(['success' => true, 'affected_rows' => 0, 'stored' => false]);
        exit;
    }

    [$table, $idColumn] = $tables[$contentType];
    $stmt = mysqli_prepare($conn, "UPDATE $table SET cache_message_id = ? WHERE $idColumn = ? AND part_number = ?");
    mysqli_stmt_bind_param($stmt, 'sii', $cacheMessageId, $contentId, $partNumber);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode(['success' => true, 'affected_rows' => $affected, 'stored' => $affected > 0]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

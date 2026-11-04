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
    $stmt = mysqli_prepare($conn, "SELECT content_type, content_id, part_number FROM whatsapp_tokens WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $tokenId);
    mysqli_stmt_execute($stmt);
    $tokenData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$tokenData) {
        echo json_encode(['success' => false, 'error' => 'Token not found']);
        exit;
    }

    $isCacheHit = ($hitType === 'cache_hit') ? 1 : 0;
    $isCacheMiss = 1 - $isCacheHit;
    $partNumber = $tokenData['part_number'];

    $stmt = mysqli_prepare($conn, "UPDATE content_forward_stats
        SET total_forwards = total_forwards + 1, cache_hits = cache_hits + ?, cache_misses = cache_misses + ?, last_forwarded_at = NOW()
        WHERE content_type = ? AND content_id = ? AND part_number <=> ?");
    mysqli_stmt_bind_param($stmt, 'iisii', $isCacheHit, $isCacheMiss, $tokenData['content_type'], $tokenData['content_id'], $partNumber);
    mysqli_stmt_execute($stmt);
    $updated = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($updated === 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO content_forward_stats
            (content_type, content_id, part_number, total_forwards, cache_hits, cache_misses, last_forwarded_at)
            VALUES (?, ?, ?, 1, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, 'siiii', $tokenData['content_type'], $tokenData['content_id'], $partNumber, $isCacheHit, $isCacheMiss);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

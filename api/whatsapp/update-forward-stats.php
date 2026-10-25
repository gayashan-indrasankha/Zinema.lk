<?php
define('WHATSAPP_API', true);
require_once __DIR__ . '/config.php';
validateApiRequest();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse(['success' => false, 'error' => 'POST required'], 405);
$input = getJsonInput();
$tokenId = (int)($input['token_id'] ?? 0);
$hit = $input['hit_type'] ?? '';
if ($tokenId < 1 || !in_array($hit, ['cache_hit','cache_miss'], true)) sendResponse(['success' => false, 'error' => 'Invalid statistics request'], 400);
$stmt = $conn->prepare('SELECT content_type, content_id, COALESCE(part_number,0) AS part_number FROM whatsapp_tokens WHERE id = ?');
$stmt->bind_param('i', $tokenId); $stmt->execute();
$token = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$token) sendResponse(['success' => false, 'error' => 'Token not found'], 404);
$type = $token['content_type']; $id = (int)$token['content_id']; $part = (int)$token['part_number'];
$hitCount = $hit === 'cache_hit' ? 1 : 0; $missCount = 1 - $hitCount;
if ($part > 0) {
    $table = $type === 'movie' ? 'movie_parts' : 'episode_parts';
    $column = $type === 'movie' ? 'movie_id' : 'episode_id';
    $stmt = $conn->prepare("UPDATE {$table} SET forward_count=forward_count+1, cache_hit_count=cache_hit_count+?, cache_miss_count=cache_miss_count+?, last_forwarded_at=NOW() WHERE {$column}=? AND part_number=?");
    $stmt->bind_param('iiii', $hitCount, $missCount, $id, $part);
} else {
    $stmt = $conn->prepare('UPDATE whatsapp_message_ids SET forward_count=forward_count+1, cache_hit_count=cache_hit_count+?, cache_miss_count=cache_miss_count+?, last_forwarded_at=NOW() WHERE content_type=? AND content_id=? AND part_number=0');
    $stmt->bind_param('iisi', $hitCount, $missCount, $type, $id);
}
$stmt->execute(); $stmt->close();
$stmt = $conn->prepare('INSERT INTO bot_statistics(content_type,content_id,part_number,total_forwards,cache_hits,cache_misses,last_forwarded_at) VALUES(?,?,?,1,?,?,NOW()) ON DUPLICATE KEY UPDATE total_forwards=total_forwards+1,cache_hits=cache_hits+VALUES(cache_hits),cache_misses=cache_misses+VALUES(cache_misses),last_forwarded_at=NOW()');
$stmt->bind_param('siiii', $type, $id, $part, $hitCount, $missCount); $stmt->execute();
sendResponse(['success' => true]);

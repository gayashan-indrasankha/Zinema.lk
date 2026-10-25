<?php
define('WHATSAPP_API', true);
require_once __DIR__ . '/config.php';
validateApiRequest();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse(['success' => false, 'error' => 'POST required'], 405);
$input = getJsonInput();
$type = $input['content_type'] ?? '';
$id = (int)($input['content_id'] ?? 0);
$part = (int)($input['part_number'] ?? 0);
$message = $input['cache_message_id'] ?? '';
if (!in_array($type, ['movie','episode'], true) || $id < 1 || $part < 0 || !is_string($message) || $message === '') sendResponse(['success' => false, 'error' => 'Invalid content mapping'], 400);
if ($part > 0) {
    $table = $type === 'movie' ? 'movie_parts' : 'episode_parts';
    $column = $type === 'movie' ? 'movie_id' : 'episode_id';
    $stmt = $conn->prepare("UPDATE {$table} SET cache_message_id = ?, message_id_updated_at = NOW() WHERE {$column} = ? AND part_number = ?");
    $stmt->bind_param('sii', $message, $id, $part);
} else {
    $stmt = $conn->prepare('UPDATE whatsapp_message_ids SET cache_message_id = ?, message_id_updated_at = NOW() WHERE content_type = ? AND content_id = ? AND part_number = 0');
    $stmt->bind_param('ssi', $message, $type, $id);
}
$stmt->execute();
sendResponse(['success' => true, 'affected_rows' => $stmt->affected_rows]);

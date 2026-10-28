<?php
define('WHATSAPP_API', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/token-data.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse(['error'=>'Method not allowed'], 405);
validateApiRequest();
$input = getJsonInput();
$action = $input['action'] ?? 'claim';
$owner = $input['recipient_hash'] ?? '';
$bot = filter_var($input['bot_id'] ?? null, FILTER_VALIDATE_INT);
if (!is_string($owner) || !preg_match('/^[a-f0-9]{64}$/D', $owner) || !$bot || $bot < 1)
    sendResponse(['success'=>false,'error'=>'invalid_owner'], 400);
if ($action === 'claim') {
    $token = is_string($input['token'] ?? null) ? strtoupper(trim($input['token'])) : '';
    if (!preg_match('/^[A-Z0-9]{12}$/D', $token)) sendResponse(['success'=>false,'error'=>'invalid_token'], 400);
    $data = token_validation_data($conn, $token);
    if (($data['valid'] ?? false) !== true) sendResponse(['success'=>false,'error'=>$data['error'] ?? 'invalid_token']);
    $receipt = bin2hex(random_bytes(32));
    $hash = hash('sha256', $receipt);
    $stmt = $conn->prepare("UPDATE whatsapp_tokens SET claim_hash=?, claim_owner=?, claim_state='claimed', claimed_at=NOW(), expires_at=expires_at WHERE token=? AND assigned_bot_id=? AND claim_hash IS NULL AND is_used=0 AND is_active=1 AND expires_at>NOW()");
    $stmt->bind_param('sssi', $hash, $owner, $token, $bot);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) sendResponse(['success'=>false,'error'=>'unavailable']);
    sendResponse(['success'=>true,'receipt'=>$receipt,'data'=>$data['data']]);
}
$id = filter_var($input['token_id'] ?? null, FILTER_VALIDATE_INT);
$receipt = $input['receipt'] ?? '';
if (!$id || $id < 1 || !is_string($receipt) || !preg_match('/^[a-f0-9]{64}$/D', $receipt))
    sendResponse(['success'=>false,'error'=>'invalid_claim'], 400);
$hash = hash('sha256', $receipt);
// An ambiguous send is never made available by a timer. Operators must reconcile it.
$transition = match ($action) {
    'begin' => "claim_state='sending' WHERE claim_state='claimed' AND is_active=1 AND expires_at>NOW()",
    'complete' => "claim_state='completed', is_used=1, used_at=NOW() WHERE claim_state='sending'",
    'fail' => "claim_state='uncertain' WHERE claim_state='sending'",
    default => null,
};
if ($transition === null) sendResponse(['success'=>false,'error'=>'invalid_action'], 400);
$stmt = $conn->prepare("UPDATE whatsapp_tokens SET expires_at=expires_at, $transition AND id=? AND claim_hash=? AND claim_owner=? AND assigned_bot_id=? AND is_used=0");
$stmt->bind_param('issi', $id, $hash, $owner, $bot);
$stmt->execute();
sendResponse(['success'=>$stmt->affected_rows === 1]);

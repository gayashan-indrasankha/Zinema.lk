<?php
define('WHATSAPP_API', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/token-data.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse(['error'=>'Method not allowed'], 405);
validateApiRequest();
$input = getJsonInput();
$token = is_string($input['token'] ?? null) ? strtoupper(trim($input['token'])) : '';
if (!preg_match('/^[A-Z0-9]{12}$/D', $token)) sendResponse(['valid'=>false,'error'=>'invalid_token'], 400);
sendResponse(token_validation_data($conn, $token));

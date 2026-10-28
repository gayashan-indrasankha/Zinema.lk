<?php
// Legacy callers cannot consume a token without the delivery claim protocol.
define('WHATSAPP_API', true);
require_once __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse(['error'=>'Method not allowed'], 405);
validateApiRequest();
sendResponse(['success'=>false,'error'=>'claim_protocol_required']);

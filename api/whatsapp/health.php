<?php
define('WHATSAPP_API',true);
require_once __DIR__.'/config.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') sendResponse(['error'=>'Method not allowed'],405);
validateApiRequest();
$conn->query('SELECT id FROM whatsapp_tokens LIMIT 0');
sendResponse(['success'=>true,'service'=>'zinema-bridge','claim_protocol'=>'claim-begin-complete-v1']);

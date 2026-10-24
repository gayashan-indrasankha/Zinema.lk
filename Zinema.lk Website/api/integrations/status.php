<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/integrations.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);header('Allow: GET');echo '{"error":{"code":"METHOD_NOT_ALLOWED"}}';exit;}
echo json_encode(['features'=>\Zinema\Integrations\FeatureGate::all()],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);

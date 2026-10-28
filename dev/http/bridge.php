<?php
// Allowlisted SQL-only test adapter. The application's normal bridge stays disabled.
if (!hash_equals(trim(file_get_contents('/run/secrets/control_key')), $_SERVER['HTTP_X_TEST_KEY'] ?? '') || getenv('APP_ENV') !== 'development' || getenv('ALLOW_LIVE_INTEGRATIONS') !== 'false') { http_response_code(403); exit; }
$endpoint = $_GET['endpoint'] ?? '';
if (!in_array($endpoint, ['claim-token','validate-token','mark-used'], true)) { http_response_code(404); exit; }
putenv('WHATSAPP_MODE=local');
putenv('WHATSAPP_API_KEY=' . trim(file_get_contents('/run/secrets/adapter_key')));
require '/var/www/html/api/whatsapp/' . $endpoint . '.php';

<?php
require_once __DIR__ . '/includes/environment.php';
recovery_require_adapter('OAUTH');
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/includes/oauth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }
$flow = oauth_start_flow();
$parameters = ['client_id' => GOOGLE_CLIENT_ID, 'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code', 'scope' => 'email profile', 'state' => $flow['state'],
    'code_challenge' => $flow['challenge'], 'code_challenge_method' => 'S256'];
header('Cache-Control: no-store');
header('Location: ' . OAUTH_AUTHORIZE_URL . '?' . http_build_query($parameters), true, 302);

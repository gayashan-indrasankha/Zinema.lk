<?php
require_once '/var/www/html/includes/oauth.php';
$results = [];
function expect($name, $passed) { global $results; $results[] = ['name'=>$name,'passed'=>(bool)$passed]; }
$_SESSION = ['user_id'=>42];
$flow = oauth_start_flow(); $state = $flow['state'];
$stored = $_SESSION['oauth_flows'][$state];
expect('PKCE challenge matches a private session verifier', $flow['challenge'] === rtrim(strtr(base64_encode(hash('sha256', $stored['verifier'], true)), '+/', '-_'), '='));
expect('Linking records the initiating account', $stored['link_user_id'] === 42);
expect('A valid state is consumed once', oauth_consume_flow($state) !== null && oauth_consume_flow($state) === null);
$flow = oauth_start_flow();
expect('Expired state is rejected and consumed', oauth_consume_flow($flow['state'], time()+601) === null && oauth_consume_flow($flow['state']) === null);
$flow = oauth_start_flow();
expect('Future-issued state is rejected', oauth_consume_flow($flow['state'], time()-1) === null);
expect('Malformed state is rejected', oauth_consume_flow(['invalid']) === null);
for ($i=0; $i<12; $i++) oauth_start_flow();
expect('Outstanding flows are bounded', count($_SESSION['oauth_flows']) === 5);
echo json_encode(['tests'=>$results,'passed'=>count(array_filter($results, fn($r)=>$r['passed'])),'total'=>count($results)]) . PHP_EOL;
exit(count(array_filter($results, fn($r)=>!$r['passed'])) ? 1 : 0);

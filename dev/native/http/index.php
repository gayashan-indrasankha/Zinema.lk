<?php
require __DIR__.'/common.php';native_admin();
$live=!empty(ZINEMA_NATIVE_CONTEXT['real_whatsapp']);$label=$live?'WhatsApp bot':'Simulator';
$notice='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!in_array($_POST['action'] ?? '',['online','offline'],true)) {http_response_code(400);exit('Invalid local action.');}
    try {native_call('control',['action'=>$_POST['action']]);$notice=$label.' state updated.';}catch(Throwable $e){$notice=$label.' unavailable.';}
}
try {[$status,$bot]=native_call('status');}catch(Throwable $e){$status=503;$bot=['online'=>false,'outbox'=>[],'held'=>[]];}
native_page('Whole local system');
echo '<section><h2>Services</h2><p>Website/PHP/database, local SMTP sink, fake OAuth, keyed fb-video-api fixture and '.($live?'real Baileys WhatsApp transport.':'Node WhatsApp simulator.').'</p><p>'.native_escape($label).': <strong id="bot-status">'.($status===200 && ($bot['online'] ?? false)?'Online':'Offline').'</strong></p>';
if ($live) echo '<p>Connection: '.native_escape($bot['connection'] ?? 'unavailable').'. <a href="pairing.php">Link approved WhatsApp account</a> · <a href="self-test.php">Original clip test to approved owner</a></p>';
echo '<p>'.native_escape($notice).'</p><form method="post">';csrf_token_field();
echo '<button name="action" value="online">'.native_escape($label).' online</button> <button name="action" value="offline">'.native_escape($label).' offline</button></form><p><a href="/whatsapp-watch.php?id=1">Request original demo movie</a> · <a href="/whatsapp-watch.php?id=1&amp;type=episode">Request original demo episode</a></p></section>';
echo '<section><h2>'.($live?'WhatsApp submissions':'Simulated deliveries').'</h2><p>'.($live?'Original generated clips only, capped at 20 MiB, to the approved owner only. Submission acknowledgement does not prove recipient delivery or reading.':'Only original generated test patterns. These acknowledgements do not represent real WhatsApp delivery.').'</p><table><tr><th>Item</th><th>Bytes</th><th>Result</th></tr>';
foreach ($bot['outbox'] ?? [] as $item) echo '<tr><td>'.native_escape($item['content_type'].' '.$item['content_id']).'</td><td>'.native_escape($item['bytes']).'</td><td>'.($live?'Submitted through WhatsApp':'Simulated attachment acknowledged').'</td></tr>';
echo '</table><p>Held work: '.count($bot['held'] ?? []).'. Held or uncertain work is not replayed after restart.</p></section><section><h2>Local operations</h2><p>Uploads have a 256 MiB fixture quota and separate 64 MiB recoverable quarantine. Use the native CLI operator for inventory and reviewed cleanup/reconciliation. Mail and OAuth are fictional; signup uses .invalid addresses only. Payments remain disabled.</p></section></main></body></html>';

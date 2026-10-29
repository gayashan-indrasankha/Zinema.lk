<?php
require __DIR__.'/common.php';native_admin();
if (empty(ZINEMA_NATIVE_CONTEXT['real_whatsapp'])) {http_response_code(404);exit;}
try {[$status,$bot]=native_call('status');}catch(Throwable $e){$bot=['connection'=>'unavailable'];}
if (empty($bot['online'])) header('Refresh: 10');
native_page('Link your WhatsApp account');
echo '<section><p>On the approved phone, open WhatsApp → Linked devices → Link a device and scan below. This links the existing phone account; it does not register a new phone number.</p><p>Connection: <strong>'.native_escape($bot['connection'] ?? 'unavailable').'</strong></p>';
if (!empty($bot['pairingAvailable'])) echo '<img src="/__local/pairing-image.php" width="360" height="360" style="max-width:100%;height:auto" alt="Private WhatsApp linking QR code">';
echo '<p>Only the configured owner account is accepted. Automatic group jobs are disabled. <a href="pairing.php">Refresh pairing status</a></p></section></main></body></html>';

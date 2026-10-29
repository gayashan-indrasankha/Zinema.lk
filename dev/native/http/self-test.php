<?php
require __DIR__.'/common.php';native_admin();
if (empty(ZINEMA_NATIVE_CONTEXT['real_whatsapp'])) {http_response_code(404);exit;}
$message='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        [$status,$bot]=native_call('status');
        if (empty($bot['online'])) throw new RuntimeException();
        require_once ZINEMA_NATIVE_CONTEXT['public'].'/includes/whatsapp_token.php';
        $token=createWhatsAppToken($conn,1,'movie',10);
        if (!is_array($token) || empty($token['token'])) throw new RuntimeException();
        [$status,$result]=native_call('deliver',['token'=>$token['token']]);
        $message=!empty($result['success'])?'Original clip submitted to the approved owner. Check WhatsApp for delivery.':'Submission unavailable or held. Do not retry automatically; review local status.';
    } catch(Throwable $e){$message='Bot offline or submission unconfirmed. Check pairing/status before trying again.';}
}
native_page('Test the approved WhatsApp account');
echo '<section><p>Send one small original generated test clip to the approved owner account only. No other recipient, group or scheduled job is enabled.</p><p>'.native_escape($message).'</p><form method="post">';csrf_token_field();echo '<button>Send original clip to approved owner</button></form></section></main></body></html>';

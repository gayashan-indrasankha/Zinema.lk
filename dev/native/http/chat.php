<?php
require __DIR__.'/common.php';
if (!empty(ZINEMA_NATIVE_CONTEXT['real_whatsapp'])) {native_admin();http_response_code(409);exit('Use the approved owner test page at /__local/self-test.php.');}
$token=$_SERVER['REQUEST_METHOD']==='POST'?($_POST['token'] ?? ''):($_GET['token'] ?? '');
if (!is_string($token) || !preg_match('/^[A-Z0-9]{12}$/D',$token)) {http_response_code(400);exit('Open a demo movie or episode delivery link first.');}
$result=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {[$status,$result]=native_call('deliver',['token'=>$token]);}
    catch(Throwable $e){$result=['success'=>false,'error'=>'result_unconfirmed_do_not_retry'];}
}
native_page('Local WhatsApp simulator');
echo '<section><p>This replaces the external WhatsApp handoff for the native demo. The token is sent only to the local simulator. No phone or real recipient is used.</p>';
if ($result) {
    echo '<h2 id="delivery-result">'.(($result['success'] ?? false)?'Simulated delivery complete':'Delivery unavailable or held').'</h2>';
    echo '<p>'.(($result['success'] ?? false)?'The real local claim/begin/complete protocol recorded one fictional acknowledgement.':'No automatic retry was made. Review local status before requesting again.').'</p>';
    if ($result['success'] ?? false) {
        $clip='trailers/'.substr(hash('sha256',ZINEMA_NATIVE_CONTEXT['owner'].'clip'),0,32).'.mp4';
        echo '<video controls muted src="/uploads/'.native_escape($clip).'"></video><p>Original demonstration clip representing the simulated attachment.</p>';
    }
} else {
    echo '<form method="post">';csrf_token_field();echo '<input type="hidden" name="token" value="'.native_escape($token).'"><button>Send to local simulator</button></form>';
}
echo '</section></main></body></html>';

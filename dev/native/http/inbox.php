<?php
require __DIR__.'/common.php';native_admin();native_page('Fake mail inbox');
echo '<section><p>Messages exist only in this local sink. Refresh after signup or password reset. No real mailbox receives them.</p>';
try {
    [$status,$value]=native_call('mail');
    if ($status!==200) throw new RuntimeException();
    foreach ($value['messages'] ?? [] as $message) {
        echo '<article><h2>'.native_escape($message['subject'] ?? 'Fictional message').'</h2><p>'.native_escape(implode(', ',$message['to'])).'</p>';
        foreach ($message['links'] ?? [] as $link) if (is_string($link) && preg_match('~^http://127\.0\.0\.1:8088/(?:verify-email|reset-password)\.php\?token=[a-zA-Z0-9]+$~D',$link)) echo '<p><a class="mail-action" href="'.native_escape($link).'">'.(str_contains($link,'verify-email')?'Verify fictional account':'Reset fictional password').'</a></p>';
        echo '</article>';
    }
} catch(Throwable $e){echo '<p>Local inbox is unavailable.</p>';}
echo '</section></main></body></html>';

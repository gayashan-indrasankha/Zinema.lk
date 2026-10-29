<?php
/** Local-only operator functions. No browser endpoint or delivery transport. */
require_once __DIR__.'/../includes/operator_operations.php';

function operator_context($bootstrap = false) {
    if (PHP_SAPI !== 'cli' || getenv('APP_ENV') !== 'development' || getenv('ALLOW_LIVE_INTEGRATIONS') !== 'false' ||
        getenv('DB_HOST') !== '127.0.0.1' || getenv('DB_NAME') !== 'zinema_recovery_development' ||
        getenv('DB_PORT') !== '3306' || getenv('UPLOAD_QUOTA_MODE') !== 'local') throw new LocalOperationError('local_target_denied');
    foreach (['WHATSAPP','MEDIA','EXTRACTOR','PAYMENT'] as $adapter)
        if (getenv($adapter.'_MODE') !== 'disabled') throw new LocalOperationError('integrations_must_be_disabled');
    foreach (['DB_PASS'=>'db_password','GOOGLE_CLIENT_SECRET'=>'oauth_secret'] as $key=>$file) {
        $value = @file_get_contents('/run/secrets/'.$file);
        if (!is_string($value) || trim($value) === '') throw new LocalOperationError('local_configuration_unavailable');
        putenv($key.'='.trim($value));
    }
    require_once '/var/www/html/includes/environment.php';
    require_once '/var/www/html/includes/upload_storage.php';
    $settings = local_storage_settings();
    if (!$settings) throw new LocalOperationError('local_storage_required');
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $db->set_charset('utf8mb4');
    $identity = $db->query('SELECT DATABASE(), @@port')->fetch_row();
    if ($identity[0] !== 'zinema_recovery_development' || (int)$identity[1] !== 3306) throw new LocalOperationError('local_target_denied');
    if ($bootstrap) initialize_local_upload_storage();
    storage_check_owner($settings);
    return [$db, $settings];
}

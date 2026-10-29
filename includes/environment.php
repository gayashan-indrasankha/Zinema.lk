<?php
// Pure configuration: no DB, session, file creation, dotenv loading or network I/O.
function recovery_config_error($key) {
    http_response_code(503);
    exit('Recovery configuration unavailable: ' . $key . '.');
}
function recovery_env($key, $default = '') {
    $value = getenv($key);
    return $value === false ? $default : trim($value);
}
function recovery_required($key) {
    $value = recovery_env($key);
    if ($value === '' || strpos($value, 'REPLACE_') === 0) recovery_config_error($key);
    return $value;
}
function recovery_url($key, $local) {
    $value = recovery_required($key);
    $url = parse_url($value);
    if (!$url || !isset($url['host'], $url['scheme']) || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) recovery_config_error($key);
    if ($local ? ($url['host'] !== '127.0.0.1' || $url['scheme'] !== 'http') : ($url['scheme'] !== 'https')) recovery_config_error($key);
    return rtrim($value, '/');
}
function recovery_mode($name) {
    $mode = recovery_env($name . '_MODE', 'disabled');
    if (!in_array($mode, ['disabled', 'local', 'live', 'private-fixture', 'account-queue'], true)) recovery_config_error($name . '_MODE');
    if ($mode==='account-queue' && ($name!=='EXTRACTOR' || !defined('ZINEMA_STELLAR_CONTEXT') || recovery_env('ALLOW_LIVE_INTEGRATIONS')!=='false')) recovery_config_error($name . '_MODE');
    if ($mode==='private-fixture' && ($name!=='EXTRACTOR' || APP_ENV!=='production' || recovery_env('ALLOW_LIVE_INTEGRATIONS')!=='false' || recovery_env('ZINEMA_PROMOTION_FIXTURES')!=='1')) recovery_config_error($name . '_MODE');
    if ($mode === 'live' && (APP_ENV !== 'production' || recovery_env('ALLOW_LIVE_INTEGRATIONS') !== 'true')) recovery_config_error($name . '_MODE');
    return $mode;
}
define('APP_ENV', recovery_required('APP_ENV'));
if (!in_array(APP_ENV, ['development', 'test', 'production'], true)) recovery_config_error('APP_ENV');
define('BASE_URL', recovery_url('BASE_URL', APP_ENV !== 'production'));
define('DB_HOST', recovery_required('DB_HOST'));
define('DB_PORT', (int)recovery_env('DB_PORT', '3306'));
if (DB_PORT < 1 || DB_PORT > 65535) recovery_config_error('DB_PORT');
define('DB_USER', recovery_required('DB_USER'));
define('DB_PASS', recovery_required('DB_PASS'));
define('DB_NAME', recovery_required('DB_NAME'));
if (APP_ENV !== 'production' && (DB_HOST !== '127.0.0.1' || DB_NAME !== 'zinema_recovery_' . APP_ENV || strpos(DB_USER, 'zinema_recovery_') !== 0)) recovery_config_error('local database target');
define('RECOVERY_SESSION_PATH', recovery_required('SESSION_SAVE_PATH'));
if (!preg_match('~^(?:[A-Za-z]:[/\\\\]|/)~', RECOVERY_SESSION_PATH) || preg_match('~(?:^|[/\\\\])(?:whatsapp-session|\.wwebjs_auth)(?:[/\\\\]|$)~i', RECOVERY_SESSION_PATH)) recovery_config_error('SESSION_SAVE_PATH');
foreach (['MAIL', 'OAUTH', 'WHATSAPP', 'MEDIA', 'EXTRACTOR', 'PAYMENT'] as $adapter) define($adapter . '_MODE', recovery_mode($adapter));
define('MAIL_HOST', MAIL_MODE === 'disabled' ? '' : recovery_required('MAIL_HOST'));
if (MAIL_MODE === 'local' && MAIL_HOST !== '127.0.0.1') recovery_config_error('MAIL_HOST');
define('MAIL_PORT', MAIL_MODE === 'disabled' ? 0 : (int)recovery_required('MAIL_PORT'));
if (MAIL_MODE !== 'disabled' && (MAIL_PORT < 1 || MAIL_PORT > 65535)) recovery_config_error('MAIL_PORT');
foreach (['MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_EMAIL', 'MAIL_FROM_NAME'] as $key) define($key, MAIL_MODE === 'disabled' ? '' : recovery_required($key));
foreach (['GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET'] as $key) define($key, OAUTH_MODE === 'disabled' ? '' : recovery_required($key));
define('GOOGLE_REDIRECT_URI', OAUTH_MODE === 'disabled' ? '' : recovery_url('GOOGLE_REDIRECT_URI', OAUTH_MODE === 'local'));
foreach (['OAUTH_AUTHORIZE_URL', 'OAUTH_TOKEN_URL', 'OAUTH_USERINFO_URL'] as $key) define($key, OAUTH_MODE === 'disabled' ? '' : recovery_url($key, OAUTH_MODE === 'local'));
define('WHATSAPP_API_KEY', WHATSAPP_MODE === 'disabled' ? '' : recovery_required('WHATSAPP_API_KEY'));
define('STREAM_SIGNING_SECRET', MEDIA_MODE === 'disabled' ? '' : recovery_required('STREAM_SIGNING_SECRET'));
define('STREAM_BASE_URL', MEDIA_MODE === 'disabled' ? '' : recovery_url('STREAM_BASE_URL', MEDIA_MODE === 'local'));
define('FB_VIDEO_API_URL', EXTRACTOR_MODE === 'account-queue' ? '' : (EXTRACTOR_MODE === 'private-fixture' ? 'http://localhost' : (EXTRACTOR_MODE === 'disabled' ? BASE_URL . '/__recovery_disabled_extractor' : recovery_url('FB_VIDEO_API_URL', EXTRACTOR_MODE === 'local'))));
foreach (['PAYMENT_APP_ID', 'PAYMENT_APP_SECRET'] as $key) define($key, PAYMENT_MODE === 'disabled' ? '' : recovery_required($key));
define('PAYMENT_API_URL', PAYMENT_MODE === 'disabled' ? '' : recovery_url('PAYMENT_API_URL', PAYMENT_MODE === 'local'));
// Legacy payment handlers stay disabled independently of these future adapter settings.
function recovery_require_adapter($name) {
    if (constant($name . '_MODE') === 'disabled') recovery_config_error($name . ' integration disabled');
}
// Explicit fictional two-host rehearsal only. Never a request-controlled live gate.
function recovery_local_delivery_fixture() {
    return APP_ENV === 'development' && WHATSAPP_MODE === 'local' &&
        recovery_env('ZINEMA_TWO_HOST_FIXTURES') === '1' &&
        recovery_env('ALLOW_LIVE_INTEGRATIONS') === 'false' &&
        BASE_URL === 'http://127.0.0.1:8088' && DB_HOST === '127.0.0.1' &&
        DB_PORT === 3306 && DB_NAME === 'zinema_recovery_development';
}
function recovery_browser_policy() {
    if (APP_ENV !== 'production' || defined('ZINEMA_STELLAR_CONTEXT')) {
        // Cached legacy CDN URLs and browser storage cannot bypass this local policy.
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: http://127.0.0.1:*; media-src 'self' http://127.0.0.1:*; connect-src 'self' http://127.0.0.1:*; frame-src 'self' http://127.0.0.1:*; font-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
    }
}

<?php
require_once __DIR__ . '/environment.php';
recovery_browser_policy();

if (session_status() === PHP_SESSION_NONE) {
    session_save_path(RECOVERY_SESSION_PATH);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '7200');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => parse_url(BASE_URL, PHP_URL_SCHEME) === 'https',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

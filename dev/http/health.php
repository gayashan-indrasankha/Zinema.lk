<?php
require_once '/var/www/html/includes/environment.php';
try {
    if (APP_ENV !== 'development' || DB_HOST !== '127.0.0.1' || DB_NAME !== 'zinema_recovery_development') throw new RuntimeException();
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $db->query('SELECT id FROM users LIMIT 0');
    echo 'ok';
} catch (Throwable $e) {
    http_response_code(503);
}

<?php
require_once __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); header('Allow: POST'); exit('POST required.');
}
unset($_SESSION['admin_id']);
session_regenerate_id(true);
header('Location: login.php', true, 303);

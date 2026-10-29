<?php
// CLI-only, read-only version/module inventory. Never phpinfo(), env or DB access.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
$extensions=[];
foreach (['mysqli','pdo_mysql','gd','fileinfo','openssl','curl','mbstring','json','session'] as $name)
    $extensions[$name]=extension_loaded($name);
echo json_encode([
    'purpose'=>'Private staging capability inventory, not deployment approval',
    'php_version'=>PHP_VERSION,'sapi'=>PHP_SAPI,'integer_bytes'=>PHP_INT_SIZE,
    'extensions'=>$extensions,'curl_unix_socket_option_defined'=>defined('CURLOPT_UNIX_SOCKET_PATH'),
    'web_handler_and_user_ini_effect'=>'not_verified',
    'private_socket_permissions_and_managed_node_adapter'=>'not_verified',
    'security_patch_status'=>'not_verified','database_connection_attempted'=>false,
    'files_written'=>false,'external_connections'=>false,'activation_enabled'=>false,
],JSON_PRETTY_PRINT).PHP_EOL;

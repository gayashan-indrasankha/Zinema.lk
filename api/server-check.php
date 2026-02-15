<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$info = [
    'current_file' => __FILE__,
    'current_dir' => __DIR__,
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'not set',
    'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'not set',
    'php_version' => phpversion(),
];

// Check if config exists in various locations
$possible_configs = [
    __DIR__ . '/../admin/config.php',
    '../admin/config.php',
    $_SERVER['DOCUMENT_ROOT'] . '/admin/config.php',
    dirname(__DIR__) . '/admin/config.php',
];

$info['config_checks'] = [];
foreach ($possible_configs as $path) {
    $info['config_checks'][$path] = file_exists($path) ? 'EXISTS' : 'NOT FOUND';
}

// List files in parent directory
$parent_dir = dirname(__DIR__);
if (is_dir($parent_dir)) {
    $info['parent_dir_contents'] = scandir($parent_dir);
}

// Check admin folder
$admin_dir = $parent_dir . '/admin';
if (is_dir($admin_dir)) {
    $info['admin_dir_contents'] = scandir($admin_dir);
}

echo json_encode($info, JSON_PRETTY_PRINT);
?>
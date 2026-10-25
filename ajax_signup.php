<?php
header('Content-Type: application/json');
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/includes/registration.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required.']); exit;
}
try {
    echo json_encode(register_password_user($conn, $_POST['username'] ?? '', $_POST['email'] ?? '', $_POST['password'] ?? ''));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again later.']);
}

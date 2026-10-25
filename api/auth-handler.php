<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../includes/registration.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required.']); exit;
}
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON.']); exit;
}
try {
    if (($input['action'] ?? '') === 'login') {
        $result = authenticate_password($conn, $input['email'] ?? '', $input['password'] ?? '');
    } elseif (($input['action'] ?? '') === 'register') {
        if (($input['password'] ?? '') !== ($input['password_confirm'] ?? null)) {
            $result = ['success' => false, 'message' => 'Passwords do not match.'];
        } else {
            $result = register_password_user($conn, $input['username'] ?? '', $input['email'] ?? '', $input['password'] ?? '');
        }
    } else { $result = ['success' => false, 'message' => 'Invalid action.']; }
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to process the account request.']);
}

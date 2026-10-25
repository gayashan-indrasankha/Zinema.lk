<?php
header('Content-Type: application/json');
require_once __DIR__ . '/admin/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required.']); exit;
}
try {
    $result = authenticate_password($conn, $_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($result['success'] && !empty($_POST['remember'])) {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = time() + 30 * 86400;
        $expiryDate = date('Y-m-d H:i:s', $expires);
        $id = $_SESSION['user_id'];
        $stmt = $conn->prepare('UPDATE users SET remember_token = ?, remember_expires_at = ? WHERE id = ?');
        $stmt->bind_param('ssi', $hash, $expiryDate, $id);
        $stmt->execute(); $stmt->close();
        setcookie('remember_token', $token, remember_cookie_options($expires));
        setcookie('remember_user', (string)$id, remember_cookie_options($expires));
    }
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to sign in. Please try again later.']);
}

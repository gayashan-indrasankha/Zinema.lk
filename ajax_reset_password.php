<?php
/**
 * AJAX Handler: Reset Password
 * Validates token and updates user password
 */

header('Content-Type: application/json');
require_once 'admin/config.php';
require_once 'includes/csrf_helper.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate CSRF token
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
}

// Get data
$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate inputs
if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reset token']);
    exit;
}

if (empty($password) || strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

// Validate token
$stmt = $conn->prepare("
    SELECT pr.id, pr.user_id, pr.expires_at, pr.used 
    FROM password_resets pr 
    WHERE pr.token = ?
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid reset token']);
    exit;
}

$resetData = $result->fetch_assoc();
$stmt->close();

// Check if already used
if ($resetData['used']) {
    echo json_encode(['success' => false, 'message' => 'This reset link has already been used']);
    exit;
}

// Check if expired
if (strtotime($resetData['expires_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'This reset link has expired']);
    exit;
}

// Hash new password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Start transaction
$conn->begin_transaction();

try {
    // Update user password
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $passwordHash, $resetData['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update password');
    }
    $stmt->close();
    
    // Mark token as used
    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
    $stmt->bind_param("i", $resetData['id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to invalidate token');
    }
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Password reset successfully! Redirecting to login...']);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}

$conn->close();

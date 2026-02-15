<?php
/**
 * AJAX Handler: Forgot Password
 * Sends password reset email with secure token
 */

header('Content-Type: application/json');
require_once 'admin/config.php';
require_once 'includes/csrf_helper.php';
require_once 'includes/mailer.php';

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

// Get and validate email
$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your email address']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Don't reveal if email exists or not (security)
    echo json_encode(['success' => true, 'message' => 'If an account exists with this email, you will receive a reset link shortly.']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Generate secure token
$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Delete any existing tokens for this user
$stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$stmt->close();

// Insert new token
$stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user['id'], $token, $expires_at);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    exit;
}
$stmt->close();

// Build reset link
$resetLink = BASE_URL . '/reset-password.php?token=' . $token;

// Send email
$emailResult = sendPasswordResetEmail($email, $user['username'], $resetLink);

if ($emailResult['success']) {
    echo json_encode(['success' => true, 'message' => 'If an account exists with this email, you will receive a reset link shortly.']);
} else {
    // Log the error but show generic message to user
    error_log("Password reset email failed for user {$user['id']}: " . $emailResult['message']);
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
}

$conn->close();

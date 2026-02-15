<?php
/**
 * AJAX Handler: Resend Verification Email
 * Sends a new verification email to unverified users
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

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

// Find user
$stmt = $conn->prepare("SELECT id, username, is_verified FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Don't reveal if email exists
    echo json_encode(['success' => true, 'message' => 'If an account exists with this email, a verification link has been sent.']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Check if already verified
if ($user['is_verified']) {
    echo json_encode(['success' => false, 'message' => 'This email is already verified. You can login now.']);
    exit;
}

// Generate new token
$verification_token = bin2hex(random_bytes(32));
$verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

// Update user with new token
$stmt = $conn->prepare("UPDATE users SET verification_token = ?, verification_expires = ? WHERE id = ?");
$stmt->bind_param("ssi", $verification_token, $verification_expires, $user['id']);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    exit;
}
$stmt->close();

// Send verification email
$verifyLink = BASE_URL . '/verify-email.php?token=' . $verification_token;
$emailResult = sendVerificationEmail($email, $user['username'], $verifyLink);

if ($emailResult['success']) {
    echo json_encode(['success' => true, 'message' => 'Verification email sent! Please check your inbox.']);
} else {
    error_log("Resend verification email failed for user {$user['id']}: " . $emailResult['message']);
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
}

$conn->close();

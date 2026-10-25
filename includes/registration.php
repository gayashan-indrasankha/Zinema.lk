<?php
require_once __DIR__ . '/mailer.php';

function register_password_user($conn, $username, $email, $password) {
    if (!is_string($username) || !preg_match('/^[a-zA-Z0-9_]{3,20}$/D', $username) ||
        !is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !is_string($password) || strlen($password) < 6) {
        return ['success' => false, 'message' => 'Enter a valid username, email and password of at least 6 characters.'];
    }
    if (MAIL_MODE === 'disabled') return ['success' => false, 'message' => 'Registration is unavailable while verification email is disabled.'];
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
    $stmt->bind_param('ss', $email, $username);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if ($exists) return ['success' => false, 'message' => 'Account already exists. Sign in or request a new verification email.'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 86400);
    $stmt = $conn->prepare('INSERT INTO users (username, email, password_hash, is_verified, verification_token, verification_expires) VALUES (?, ?, ?, 0, ?, ?)');
    $stmt->bind_param('sssss', $username, $email, $hash, $token, $expires);
    $stmt->execute();
    $stmt->close();
    $delivery = sendVerificationEmail($email, $username, BASE_URL . '/verify-email.php?token=' . $token);
    if (!$delivery['success']) {
        // Preserve pending accounts; resending can recover delivery without data loss.
        return ['success' => false, 'account_created' => true, 'requires_verification' => true, 'message' => 'Account pending, but verification email could not be sent. Request a new verification email later.'];
    }
    return ['success' => true, 'requires_verification' => true, 'message' => 'Account created. Check your email to verify your account before signing in.'];
}

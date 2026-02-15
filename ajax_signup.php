<?php
/**
 * AJAX Signup Handler
 * Processes signup requests with email verification
 */

header('Content-Type: application/json');

require_once 'admin/config.php';
require_once 'includes/csrf_helper.php';
require_once 'includes/mailer.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Validate username format
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Username must be 3-20 characters, alphanumeric and underscore only.']);
    exit;
}

try {
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id, is_verified, verification_expires FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Database error occurred");
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $existingUser = $result->fetch_assoc();
        $stmt->close();
        
        // Check if the existing account is unverified AND expired
        if (isset($existingUser['is_verified']) && $existingUser['is_verified'] == 0) {
            // Check if verification has expired (24 hours passed)
            if ($existingUser['verification_expires'] && strtotime($existingUser['verification_expires']) < time()) {
                // Delete the expired unverified account - allow real owner to register
                $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $deleteStmt->bind_param("i", $existingUser['id']);
                $deleteStmt->execute();
                $deleteStmt->close();
                // Continue with registration
            } else {
                // Unverified but not expired yet - don't allow
                echo json_encode(['success' => false, 'message' => 'Email already registered but not verified. Please check your inbox or wait 24 hours to try again.']);
                exit;
            }
        } else {
            // Verified account exists
            echo json_encode(['success' => false, 'message' => 'Email already registered. Please login or use a different email.']);
            exit;
        }
    } else {
        $stmt->close();
    }
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Username already taken. Please choose a different username.']);
        exit;
    }
    $stmt->close();
    
    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate verification token
    $verification_token = bin2hex(random_bytes(32));
    $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Insert new user into database (is_verified = 0 by default)
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, is_verified, verification_token, verification_expires, created_at) VALUES (?, ?, ?, 0, ?, ?, NOW())");
    $stmt->bind_param("sssss", $username, $email, $password_hash, $verification_token, $verification_expires);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        $stmt->close();
        
        // Send verification email
        $verifyLink = BASE_URL . '/verify-email.php?token=' . $verification_token;
        $emailResult = sendVerificationEmail($email, $username, $verifyLink);
        
        if ($emailResult['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Account created! Please check your email to verify your account.',
                'requires_verification' => true
            ]);
        } else {
            // Account created but email failed - log error but show success
            error_log("Verification email failed for user {$user_id}: " . $emailResult['message']);
            echo json_encode([
                'success' => true,
                'message' => 'Account created! Please check your email to verify your account.',
                'requires_verification' => true
            ]);
        }
        exit;
    } else {
        $stmt->close();
        throw new Exception("Failed to create account");
    }
    
} catch (Exception $e) {
    error_log("Signup Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed. Please try again later.'
    ]);
    exit;
}

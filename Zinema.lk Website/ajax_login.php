<?php
/**
 * AJAX Login Handler
 * Processes login requests with email verification check
 */

header('Content-Type: application/json');

require_once 'admin/config.php';
require_once 'includes/csrf_helper.php';

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

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

try {
    // Query to get user by email (including verification status)
    $stmt = $conn->prepare("SELECT id, username, email, password_hash, is_verified FROM users WHERE email = ?");
    
    if (!$stmt) {
        throw new Exception("Database error occurred");
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            
            // Check if email is verified
            if (isset($user['is_verified']) && $user['is_verified'] == 0) {
                $stmt->close();
                echo json_encode([
                    'success' => false,
                    'message' => 'Please verify your email before logging in. Check your inbox for the verification link.',
                    'unverified' => true,
                    'email' => $email
                ]);
                exit;
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Handle "Remember Me" - set persistent cookie
            $remember = isset($_POST['remember']) && $_POST['remember'];
            if ($remember) {
                // Generate secure token
                $rememberToken = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 days
                
                // Store token in database
                $updateStmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $updateStmt->bind_param("si", $rememberToken, $user['id']);
                $updateStmt->execute();
                $updateStmt->close();
                
                // Set cookie (secure, httponly)
                setcookie('remember_token', $rememberToken, [
                    'expires' => $expires,
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                setcookie('remember_user', $user['id'], [
                    'expires' => $expires,
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
            
            $stmt->close();
            
            // Return success with redirect URL
            echo json_encode([
                'success' => true,
                'message' => 'Login successful!',
                'redirect' => 'profile.php'
            ]);
            exit;
        } else {
            // Incorrect password
            $stmt->close();
            echo json_encode([
                'success' => false,
                'message' => 'Incorrect password. Please try again.'
            ]);
            exit;
        }
    } else {
        // Email not found
        echo json_encode([
            'success' => false,
            'message' => 'No account found with this email address.'
        ]);
        exit;
    }
    
} catch (Exception $e) {
    error_log("Login Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
    exit;
}

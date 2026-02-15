<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../includes/jwt-helper.php';
require_once __DIR__ . '/../includes/mobile_detect.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// ========================================
// MOBILE APP: JWT-based login
// ========================================
if ($action === 'mobile-login') {
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id, username, email, password_hash, subscription_expiry_date FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            // Generate JWT token
            $token = jwt_encode([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email']
            ]);
            
            $is_subscribed = isSubscribed($conn, $user['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful!',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'is_subscribed' => $is_subscribed,
                    'subscription_expiry' => $user['subscription_expiry_date']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }

} elseif ($action === 'mobile-register') {
    $username = $input['username'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $password_confirm = $input['password_confirm'] ?? '';
    
    if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    if ($password !== $password_confirm) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }

    // Check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or Email already taken.']);
        exit;
    }

    // Create new user
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password_hash);

    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;
        $token = jwt_encode([
            'user_id' => $new_user_id,
            'username' => $username,
            'email' => $email
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Registration successful!',
            'token' => $token,
            'user' => [
                'id' => $new_user_id,
                'username' => $username,
                'email' => $email,
                'is_subscribed' => false,
                'subscription_expiry' => null
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }

} elseif ($action === 'verify-token') {
    // Verify JWT token is still valid
    $token = $input['token'] ?? '';
    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => 'Token required.']);
        exit;
    }

    $payload = jwt_decode($token);
    if ($payload && isset($payload['user_id'])) {
        $is_subscribed = isSubscribed($conn, $payload['user_id']);
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $payload['user_id'],
                'username' => $payload['username'],
                'email' => $payload['email'],
                'is_subscribed' => $is_subscribed
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Token expired or invalid.']);
    }

// ========================================
// WEB: Session-based login (existing)
// ========================================
} elseif ($action === 'login') {
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            echo json_encode([
                'success' => true, 
                'message' => 'Login successful!',
                'username' => $user['username']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }
    
} elseif ($action === 'register') {
    $username = $input['username'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $password_confirm = $input['password_confirm'] ?? '';
    
    if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }
    
    if ($password !== $password_confirm) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or Email already taken.']);
        exit;
    }
    
    // Create new user
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password_hash);
    
    if ($stmt->execute()) {
        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['username'] = $username;
        echo json_encode([
            'success' => true, 
            'message' => 'Registration successful!',
            'username' => $username
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

$conn->close();
?>
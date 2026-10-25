<?php
/**
 * CSRF Protection Helper Functions
 * Provides token generation and validation for form security
 */

/**
 * Generate a CSRF token and store it in the session
 * @return string The generated CSRF token
 */
function generate_csrf_token() {
    if (session_status() == PHP_SESSION_NONE) {
        require_once __DIR__ . '/session.php';
    }
    
    // Generate cryptographically secure random token
    $token = bin2hex(random_bytes(32));
    
    // Store in session
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

/**
 * Validate a CSRF token
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function validate_csrf_token($token) {
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) return false;
    if (session_status() == PHP_SESSION_NONE) {
        require_once __DIR__ . '/session.php';
    }
    
    // Check if token exists in session
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check if token has expired (1 hour expiration)
    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    // Validate token using timing-safe comparison
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    
    return true;
}

/**
 * Get the current CSRF token or generate a new one
 * @return string The CSRF token
 */
function get_csrf_token() {
    if (session_status() == PHP_SESSION_NONE) {
        require_once __DIR__ . '/session.php';
    }
    
    // Return existing token if valid
    if (isset($_SESSION['csrf_token']) && isset($_SESSION['csrf_token_time'])) {
        if (time() - $_SESSION['csrf_token_time'] <= 3600) {
            return $_SESSION['csrf_token'];
        }
    }
    
    // Generate new token if none exists or expired
    return generate_csrf_token();
}

/**
 * Output a hidden CSRF token input field
 */
function csrf_token_field() {
    $token = get_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function request_csrf_token() {
    if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) return $_SERVER['HTTP_X_CSRF_TOKEN'];
    if (isset($_POST['csrf_token'])) return $_POST['csrf_token'];
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === 0) {
        $body = json_decode(file_get_contents('php://input'), true);
        return is_array($body) ? ($body['csrf_token'] ?? '') : '';
    }
    return '';
}

function require_browser_csrf() {
    if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD', 'OPTIONS'], true)) return;
    if (!validate_csrf_token(request_csrf_token())) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'status' => 'error', 'error' => 'csrf', 'message' => 'Invalid security token. Please refresh and try again.']);
        exit;
    }
}

function csrf_meta_tags() {
    echo '<meta name="csrf-token" content="' . htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    echo '<script src="' . htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') . '/assets/js/security.js"></script>';
}

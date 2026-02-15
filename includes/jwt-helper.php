<?php
/**
 * Simple JWT Helper for Zinema.lk Mobile App
 * Uses HMAC-SHA256 — no external library needed
 */

// JWT Secret Key — change this to a random string in production
define('JWT_SECRET', 'ZinemaLk_M0b1le_JWT_S3cret_K3y_2026!@#$');
define('JWT_EXPIRY', 86400 * 30); // 30 days

/**
 * Base64 URL encode (JWT-safe)
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 URL decode
 */
function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}

/**
 * Create a JWT token
 * 
 * @param array $payload Data to encode
 * @return string JWT token
 */
function jwt_encode($payload) {
    $header = ['typ' => 'JWT', 'alg' => 'HS256'];
    
    // Add standard claims
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRY;
    
    $header_encoded = base64url_encode(json_encode($header));
    $payload_encoded = base64url_encode(json_encode($payload));
    
    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", JWT_SECRET, true);
    $signature_encoded = base64url_encode($signature);
    
    return "$header_encoded.$payload_encoded.$signature_encoded";
}

/**
 * Decode and verify a JWT token
 * 
 * @param string $token JWT token
 * @return array|false Decoded payload or false if invalid
 */
function jwt_decode($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    
    [$header_encoded, $payload_encoded, $signature_encoded] = $parts;
    
    // Verify signature
    $expected_sig = base64url_encode(
        hash_hmac('sha256', "$header_encoded.$payload_encoded", JWT_SECRET, true)
    );
    
    if (!hash_equals($expected_sig, $signature_encoded)) {
        return false;
    }
    
    $payload = json_decode(base64url_decode($payload_encoded), true);
    if (!$payload) return false;
    
    // Check expiry
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

/**
 * Extract JWT from Authorization header
 * 
 * @return string|false Token or false
 */
function get_bearer_token() {
    $headers = null;
    
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    
    if ($headers && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
    }
    
    // Also check query param (fallback for WebView)
    if (isset($_GET['token'])) {
        return $_GET['token'];
    }
    
    return false;
}

/**
 * Authenticate request using JWT
 * Returns user data or false
 * 
 * @return array|false User data or false
 */
function authenticate_mobile_request() {
    $token = get_bearer_token();
    if (!$token) return false;
    
    $payload = jwt_decode($token);
    if (!$payload || !isset($payload['user_id'])) return false;
    
    return $payload;
}
?>

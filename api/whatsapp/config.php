<?php
/**
 * WhatsApp API Bridge - Configuration
 * This file contains security settings and database connection
 */

// Prevent direct access
if (!defined('WHATSAPP_API')) {
    http_response_code(403);
    die(json_encode(['error' => 'Direct access not allowed']));
}

// Explicit adapter boundary; disabled bridge never reaches DB bootstrap.
require_once __DIR__ . '/../../includes/environment.php';
recovery_require_adapter('WHATSAPP');
define('API_SECRET_KEY', WHATSAPP_API_KEY);

// Rate limiting settings
define('RATE_LIMIT_REQUESTS', 100); // Max requests per minute
define('RATE_LIMIT_WINDOW', 60);    // Window in seconds

// Include main config for database connection
require_once dirname(dirname(dirname(__FILE__))) . '/admin/config.php';

/**
 * Validate API request authentication
 */
function validateApiRequest() {
    // Get API key from header (case-insensitive)
    $headers = getallheaders();
    
    // Convert all header names to lowercase for case-insensitive matching
    $headersLower = array();
    foreach ($headers as $key => $value) {
        $headersLower[strtolower($key)] = $value;
    }
    
    $apiKey = isset($headersLower['x-api-key']) ? $headersLower['x-api-key'] : '';
    
    // Also check Authorization header
    if (empty($apiKey) && isset($headersLower['authorization'])) {
        $apiKey = str_replace('Bearer ', '', $headersLower['authorization']);
    }
    
    if ($apiKey !== API_SECRET_KEY) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }
    
    if (defined('LOCAL_MEDIA_REFERENCE_WRITE') && getenv('UPLOAD_QUOTA_MODE') === 'local') {
        require_once __DIR__ . '/../../includes/upload_storage.php';
        try {
            $db=$GLOBALS['conn'];$gate=storage_reference_acquire($db);
            register_shutdown_function(static function() use ($db,$gate) { try { storage_reference_release($db,$gate); } catch (Throwable $ignored) {} });
            storage_validate_reference_values(local_storage_settings(),getJsonInput());
        } catch (Throwable $error) { sendResponse(['success'=>false,'error'=>'upload_reference_unavailable'],409); }
    }
    return true;
}

/**
 * Send JSON response
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get JSON input from request body
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?: [];
}

/**
 * Log API request for debugging
 */
function logApiRequest($endpoint, $data = []) {
    // Uncomment for debugging
    // error_log("WhatsApp API: $endpoint - " . json_encode($data));
}
?>

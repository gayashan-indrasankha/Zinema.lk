<?php
/**
 * WhatsApp API Bridge - Get Refresh Statistics
 * Returns statistics about media refresh activity
 * 
 * POST /api/whatsapp/refresh-stats.php
 * Body: {}
 */

define('WHATSAPP_API', true);
require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Validate API key
validateApiRequest();

logApiRequest('refresh-stats', []);

try {
    $stmt = $conn->query("SELECT COUNT(*) as total_files, SUM(refresh_count) as total_refreshes FROM media_refresh_log");
    $row = $stmt->fetch_assoc();
    
    sendResponse([
        'total_files' => (int)($row['total_files'] ?? 0),
        'total_refreshes' => (int)($row['total_refreshes'] ?? 0)
    ]);
} catch (Exception $e) {
    sendResponse([
        'total_files' => 0, 
        'total_refreshes' => 0, 
        'error' => $e->getMessage()
    ], 500);
}
?>
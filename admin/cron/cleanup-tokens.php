<?php
/**
 * Token Cleanup Cron Job
 * Deletes expired and unused tokens from the database
 * 
 * Setup on Namecheap cPanel:
 * 1. Go to cPanel > Cron Jobs
 * 2. Add: php /home/YOUR_USERNAME/public_html/admin/cron/cleanup-tokens.php
 * 3. Schedule: Every hour (0 * * * *)
 */

// Allow CLI execution only (security)
if (php_sapi_name() !== 'cli' && !defined('CRON_RUN')) {
    // Also allow HTTP with secret key for testing
    $cronKey = $_GET['key'] ?? '';
    if ($cronKey !== 'ZINEMA_CRON_SECRET_2024') {
        http_response_code(403);
        die('Access denied');
    }
}

// Include database connection
require_once dirname(dirname(__DIR__)) . '/admin/config.php';

// Set execution time limit
set_time_limit(300);

// Start cleanup
$startTime = microtime(true);
$log = [];

try {
    // 1. Delete expired unused tokens (older than expiry time)
    $sql1 = "DELETE FROM whatsapp_tokens WHERE expires_at < NOW() AND is_used = 0";
    $result1 = mysqli_query($conn, $sql1);
    $deletedExpired = mysqli_affected_rows($conn);
    $log[] = "Deleted $deletedExpired expired unused tokens";

    // 2. Delete very old used tokens (older than 7 days) to prevent table bloat
    $sql2 = "DELETE FROM whatsapp_tokens WHERE is_used = 1 AND used_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $result2 = mysqli_query($conn, $sql2);
    $deletedOld = mysqli_affected_rows($conn);
    $log[] = "Deleted $deletedOld old used tokens (>7 days)";

    // 3. Delete orphaned tokens (no message_id and older than 1 hour)
    $sql3 = "DELETE FROM whatsapp_tokens WHERE message_id IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR) AND is_used = 0";
    $result3 = mysqli_query($conn, $sql3);
    $deletedOrphaned = mysqli_affected_rows($conn);
    $log[] = "Deleted $deletedOrphaned orphaned tokens";

    // 4. Get current token stats
    $statsResult = mysqli_query($conn, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_used = 1 THEN 1 ELSE 0 END) as used,
        SUM(CASE WHEN is_used = 0 AND expires_at > NOW() THEN 1 ELSE 0 END) as active
        FROM whatsapp_tokens");
    $stats = mysqli_fetch_assoc($statsResult);
    $log[] = "Current tokens: {$stats['total']} total, {$stats['used']} used, {$stats['active']} active";

    $success = true;
    $message = "Cleanup completed successfully";

} catch (Exception $e) {
    $success = false;
    $message = "Error: " . $e->getMessage();
    $log[] = $message;
}

$executionTime = round((microtime(true) - $startTime) * 1000, 2);
$log[] = "Execution time: {$executionTime}ms";

// Output results
$output = [
    'success' => $success,
    'message' => $message,
    'deleted' => [
        'expired' => $deletedExpired ?? 0,
        'old_used' => $deletedOld ?? 0,
        'orphaned' => $deletedOrphaned ?? 0
    ],
    'stats' => $stats ?? [],
    'log' => $log,
    'timestamp' => date('Y-m-d H:i:s')
];

// Log to file (for debugging)
$logFile = __DIR__ . '/cleanup.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . json_encode($output) . "\n", FILE_APPEND);

// Output JSON if HTTP request, or plain text for CLI
if (php_sapi_name() === 'cli') {
    echo implode("\n", $log) . "\n";
} else {
    header('Content-Type: application/json');
    echo json_encode($output, JSON_PRETTY_PRINT);
}
?>

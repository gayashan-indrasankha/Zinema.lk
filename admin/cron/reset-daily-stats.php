<?php
/**
 * Daily Stats Reset Cron Job
 * Resets daily counters for bot health status at midnight
 * 
 * Setup on Namecheap cPanel:
 * 1. Go to cPanel > Cron Jobs
 * 2. Add: php /home/YOUR_USERNAME/public_html/admin/cron/reset-daily-stats.php
 * 3. Schedule: Once per day at midnight (0 0 * * *)
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

// Start reset
$startTime = microtime(true);
$log = [];

try {
    // Get yesterday's totals before reset (for logging)
    $statsResult = mysqli_query($conn, "SELECT 
        bot_id,
        total_requests_today,
        successful_forwards_today,
        failed_forwards_today
        FROM bot_health_status
        ORDER BY bot_id");
    
    $yesterdayStats = [];
    while ($row = mysqli_fetch_assoc($statsResult)) {
        $yesterdayStats[] = $row;
        $log[] = "Bot #{$row['bot_id']}: {$row['total_requests_today']} requests, {$row['successful_forwards_today']} success, {$row['failed_forwards_today']} failed";
    }
    
    // Reset all daily counters
    $resetSql = "UPDATE bot_health_status SET 
        total_requests_today = 0,
        successful_forwards_today = 0,
        failed_forwards_today = 0,
        updated_at = NOW()";
    
    $result = mysqli_query($conn, $resetSql);
    $affectedRows = mysqli_affected_rows($conn);
    
    $log[] = "Reset daily stats for $affectedRows bot(s)";
    
    // Also clean up old rate_limits entries (if table exists)
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'rate_limits'");
    if (mysqli_num_rows($tableCheck) > 0) {
        mysqli_query($conn, "DELETE FROM rate_limits WHERE last_request_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $cleanedRateLimits = mysqli_affected_rows($conn);
        $log[] = "Cleaned $cleanedRateLimits old rate limit records";
    } else {
        $log[] = "Rate limits table not created yet (will be created on first use)";
    }
    
    $success = true;
    $message = "Daily stats reset completed";

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
    'bots_reset' => $affectedRows ?? 0,
    'yesterday_stats' => $yesterdayStats ?? [],
    'log' => $log,
    'timestamp' => date('Y-m-d H:i:s')
];

// Log to file (for debugging)
$logFile = __DIR__ . '/daily-reset.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . json_encode($output) . "\n", FILE_APPEND);

// Output JSON if HTTP request, or plain text for CLI
if (php_sapi_name() === 'cli') {
    echo implode("\n", $log) . "\n";
} else {
    header('Content-Type: application/json');
    echo json_encode($output, JSON_PRETTY_PRINT);
}
?>

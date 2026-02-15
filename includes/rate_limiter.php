<?php
/**
 * Rate Limiter Class
 * Prevents spam by limiting requests per IP address
 */

class RateLimiter {
    private $conn;
    private $tableName = 'rate_limits';
    
    // Configuration
    private $maxTokensPerMinute = 10;  // Max token requests per minute
    private $maxTokensPerHour = 50;    // Max token requests per hour
    private $blockDurationMinutes = 30; // How long to block after exceeding limits
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->ensureTableExists();
    }
    
    /**
     * Create rate_limits table if it doesn't exist
     */
    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            action_type VARCHAR(50) NOT NULL DEFAULT 'token_request',
            request_count INT DEFAULT 1,
            first_request_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_request_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            blocked_until TIMESTAMP NULL,
            INDEX idx_ip_action (ip_address, action_type),
            INDEX idx_blocked (blocked_until)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        mysqli_query($this->conn, $sql);
    }
    
    /**
     * Get client IP address
     */
    public function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Check if request is allowed
     * Returns: ['allowed' => bool, 'reason' => string, 'retry_after' => int]
     */
    public function checkLimit($actionType = 'token_request') {
        $ip = $this->getClientIP();
        
        // Skip rate limiting for localhost/development
        if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'])) {
            return ['allowed' => true, 'reason' => 'localhost'];
        }
        
        // Check if currently blocked
        $blockCheck = $this->isBlocked($ip, $actionType);
        if ($blockCheck['blocked']) {
            return [
                'allowed' => false,
                'reason' => 'You have been temporarily blocked due to too many requests.',
                'retry_after' => $blockCheck['retry_after']
            ];
        }
        
        // Get current request counts
        $counts = $this->getRequestCounts($ip, $actionType);
        
        // Check per-minute limit
        if ($counts['per_minute'] >= $this->maxTokensPerMinute) {
            return [
                'allowed' => false,
                'reason' => 'Too many requests. Please wait a moment.',
                'retry_after' => 60
            ];
        }
        
        // Check per-hour limit
        if ($counts['per_hour'] >= $this->maxTokensPerHour) {
            // Block for longer period
            $this->blockIP($ip, $actionType, $this->blockDurationMinutes);
            return [
                'allowed' => false,
                'reason' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $this->blockDurationMinutes * 60
            ];
        }
        
        // Request allowed - record it
        $this->recordRequest($ip, $actionType);
        
        return ['allowed' => true, 'reason' => 'ok'];
    }
    
    /**
     * Check if IP is blocked
     */
    private function isBlocked($ip, $actionType) {
        $stmt = mysqli_prepare($this->conn, 
            "SELECT blocked_until FROM rate_limits 
             WHERE ip_address = ? AND action_type = ? AND blocked_until > NOW()
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 'ss', $ip, $actionType);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $retryAfter = strtotime($row['blocked_until']) - time();
            return ['blocked' => true, 'retry_after' => max(0, $retryAfter)];
        }
        
        return ['blocked' => false];
    }
    
    /**
     * Get request counts for IP
     */
    private function getRequestCounts($ip, $actionType) {
        // Count requests in last minute
        $stmt1 = mysqli_prepare($this->conn,
            "SELECT COUNT(*) as cnt FROM rate_limits 
             WHERE ip_address = ? AND action_type = ? 
             AND last_request_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)"
        );
        mysqli_stmt_bind_param($stmt1, 'ss', $ip, $actionType);
        mysqli_stmt_execute($stmt1);
        $result1 = mysqli_stmt_get_result($stmt1);
        $perMinute = mysqli_fetch_assoc($result1)['cnt'] ?? 0;
        
        // Count requests in last hour
        $stmt2 = mysqli_prepare($this->conn,
            "SELECT COUNT(*) as cnt FROM rate_limits 
             WHERE ip_address = ? AND action_type = ? 
             AND last_request_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        mysqli_stmt_bind_param($stmt2, 'ss', $ip, $actionType);
        mysqli_stmt_execute($stmt2);
        $result2 = mysqli_stmt_get_result($stmt2);
        $perHour = mysqli_fetch_assoc($result2)['cnt'] ?? 0;
        
        return ['per_minute' => $perMinute, 'per_hour' => $perHour];
    }
    
    /**
     * Record a request
     */
    private function recordRequest($ip, $actionType) {
        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO rate_limits (ip_address, action_type, request_count, first_request_at) 
             VALUES (?, ?, 1, NOW())"
        );
        mysqli_stmt_bind_param($stmt, 'ss', $ip, $actionType);
        mysqli_stmt_execute($stmt);
    }
    
    /**
     * Block an IP for specified duration
     */
    private function blockIP($ip, $actionType, $durationMinutes) {
        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO rate_limits (ip_address, action_type, blocked_until) 
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))
             ON DUPLICATE KEY UPDATE blocked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE)"
        );
        mysqli_stmt_bind_param($stmt, 'ssii', $ip, $actionType, $durationMinutes, $durationMinutes);
        mysqli_stmt_execute($stmt);
    }
    
    /**
     * Clean up old rate limit records (call from cron)
     */
    public static function cleanup($conn) {
        // Delete records older than 2 hours
        mysqli_query($conn, 
            "DELETE FROM rate_limits WHERE last_request_at < DATE_SUB(NOW(), INTERVAL 2 HOUR) AND blocked_until IS NULL"
        );
        // Delete expired blocks
        mysqli_query($conn,
            "DELETE FROM rate_limits WHERE blocked_until IS NOT NULL AND blocked_until < NOW()"
        );
    }
}
?>

CREATE TABLE rate_limits (
 id INT AUTO_INCREMENT PRIMARY KEY,
 ip_address VARCHAR(45) NOT NULL,
 action_type VARCHAR(50) NOT NULL DEFAULT 'token_request',
 request_count INT DEFAULT 1,
 first_request_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 last_request_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 blocked_until TIMESTAMP NULL,
 INDEX idx_ip_action (ip_address, action_type),
 INDEX idx_blocked (blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

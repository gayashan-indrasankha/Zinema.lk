-- =====================================================
-- Ultimate Multi-Bot System - Database Migration
-- =====================================================
-- This migration adds support for:
-- 1. Local file storage
-- 2. Cache message IDs
-- 3. Multi-bot load balancing
-- 4. Statistics and tracking
-- 5. Admin health monitoring
-- =====================================================

-- Step 1: Update movie_message_ids table
ALTER TABLE movie_message_ids 
ADD COLUMN local_file_path VARCHAR(512) DEFAULT NULL COMMENT 'Path to locally stored media file',
ADD COLUMN cache_message_id VARCHAR(255) DEFAULT NULL COMMENT 'Message ID in cache group for quick forwarding',
ADD COLUMN last_forwarded_at TIMESTAMP NULL COMMENT 'Last time this file was successfully forwarded',
ADD COLUMN message_id_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When cache_message_id was last updated',
ADD COLUMN forward_count INT DEFAULT 0 COMMENT 'Total number of times this file was forwarded',
ADD COLUMN cache_hit_count INT DEFAULT 0 COMMENT 'Number of times forwarded from hot cache',
ADD COLUMN cache_miss_count INT DEFAULT 0 COMMENT 'Number of times had to re-upload',
ADD COLUMN file_size_mb DECIMAL(10,2) DEFAULT NULL COMMENT 'File size in megabytes',
ADD COLUMN bot_instance_id INT DEFAULT 1 COMMENT 'Which bot instance managed this file',
ADD INDEX idx_last_forwarded (last_forwarded_at),
ADD INDEX idx_forward_count (forward_count),
ADD INDEX idx_bot_instance (bot_instance_id);

-- Step 2: Update whatsapp_tokens table for load balancing
ALTER TABLE whatsapp_tokens
ADD COLUMN assigned_bot_id INT DEFAULT 1 COMMENT 'Which bot this token is assigned to (1-5)',
ADD INDEX idx_assigned_bot (assigned_bot_id);

-- Step 3: Create bot_statistics table for tracking
CREATE TABLE IF NOT EXISTS bot_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type ENUM('movie', 'episode') NOT NULL,
    content_id INT NOT NULL,
    part_number INT DEFAULT NULL,
    total_forwards INT DEFAULT 0,
    cache_hits INT DEFAULT 0,
    cache_misses INT DEFAULT 0,
    last_forwarded_at TIMESTAMP NULL,
    total_data_saved_mb DECIMAL(12,2) DEFAULT 0 COMMENT 'Estimated data saved by caching',
    avg_forward_time_sec DECIMAL(6,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_content (content_type, content_id, part_number),
    INDEX idx_popularity (total_forwards DESC),
    INDEX idx_last_forwarded (last_forwarded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Statistics for content forwarding performance';

-- Step 4: Create bot_health_status table for monitoring
CREATE TABLE IF NOT EXISTS bot_health_status (
    bot_id INT PRIMARY KEY COMMENT 'Bot instance ID (1-5)',
    bot_phone VARCHAR(20) NOT NULL COMMENT 'WhatsApp phone number',
    status ENUM('online', 'offline', 'error', 'starting') DEFAULT 'offline',
    last_heartbeat TIMESTAMP NULL COMMENT 'Last time bot reported it was alive',
    queue_size INT DEFAULT 0 COMMENT 'Current number of requests in queue',
    total_requests_today INT DEFAULT 0,
    successful_forwards_today INT DEFAULT 0,
    failed_forwards_today INT DEFAULT 0,
    uptime_seconds INT DEFAULT 0,
    cache_group_id VARCHAR(100) DEFAULT NULL,
    storage_path VARCHAR(512) DEFAULT NULL,
    disk_usage_mb DECIMAL(12,2) DEFAULT 0,
    memory_usage_mb DECIMAL(10,2) DEFAULT 0,
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Real-time health status of all bot instances';

-- Step 5: Insert initial bot instances
INSERT INTO bot_health_status (bot_id, bot_phone, status, storage_path) VALUES
(1, '+94XXXXX1', 'offline', 'd:/001/whatapp bot/bot-media'),
(2, '+94XXXXX2', 'offline', 'd:/001/whatapp bot/bot-media'),
(3, '+94XXXXX3', 'offline', 'd:/001/whatapp bot/bot-media'),
(4, '+94XXXXX4', 'offline', 'd:/001/whatapp bot/bot-media'),
(5, '+94XXXXX5', 'offline', 'd:/001/whatapp bot/bot-media')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Step 6: Create cache_warming_log table
CREATE TABLE IF NOT EXISTS cache_warming_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bot_id INT NOT NULL,
    content_type ENUM('movie', 'episode') NOT NULL,
    content_id INT NOT NULL,
    part_number INT DEFAULT NULL,
    warmed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_size_mb DECIMAL(10,2) DEFAULT NULL,
    success BOOLEAN DEFAULT TRUE,
    error_message TEXT DEFAULT NULL,
    INDEX idx_bot_id (bot_id),
    INDEX idx_warmed_at (warmed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Log of cache warming operations';

-- Step 7: Create system_alerts table
CREATE TABLE IF NOT EXISTS system_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type ENUM('disk_space', 'bot_offline', 'queue_overload', 'cache_miss_high', 'error') NOT NULL,
    severity ENUM('info', 'warning', 'critical') DEFAULT 'warning',
    message TEXT NOT NULL,
    bot_id INT DEFAULT NULL,
    is_resolved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    INDEX idx_unresolved (is_resolved, created_at),
    INDEX idx_bot_id (bot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='System alerts and notifications';

-- Step 8: Create file_cleanup_queue table (for automated cleanup)
CREATE TABLE IF NOT EXISTS file_cleanup_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type ENUM('movie', 'episode') NOT NULL,
    content_id INT NOT NULL,
    part_number INT DEFAULT NULL,
    local_file_path VARCHAR(512) NOT NULL,
    marked_for_deletion_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason VARCHAR(255) DEFAULT NULL COMMENT 'Why marked for deletion',
    deleted_at TIMESTAMP NULL,
    INDEX idx_pending (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Queue for files to be cleaned up';

-- Step 9: Create backup_log table (for tracking backups)
CREATE TABLE IF NOT EXISTS backup_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_type ENUM('full', 'incremental') NOT NULL,
    backup_path VARCHAR(512) NOT NULL,
    file_count INT DEFAULT 0,
    total_size_mb DECIMAL(12,2) DEFAULT 0,
    status ENUM('in_progress', 'completed', 'failed') DEFAULT 'in_progress',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    error_message TEXT DEFAULT NULL,
    INDEX idx_status (status, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Backup operation log';

-- Step 10: Create view for popular content
CREATE OR REPLACE VIEW v_popular_content AS
SELECT 
    bs.content_type,
    bs.content_id,
    bs.part_number,
    bs.total_forwards,
    bs.cache_hits,
    bs.cache_misses,
    ROUND((bs.cache_hits / NULLIF(bs.total_forwards, 0)) * 100, 2) as cache_hit_rate,
    bs.total_data_saved_mb,
    bs.last_forwarded_at,
    mmid.local_file_path,
    mmid.cache_message_id,
    mmid.message_id_updated_at,
    TIMESTAMPDIFF(HOUR, mmid.message_id_updated_at, NOW()) as hours_since_cache_update,
    CASE 
        WHEN TIMESTAMPDIFF(HOUR, mmid.message_id_updated_at, NOW()) < 6 THEN 'hot'
        WHEN TIMESTAMPDIFF(HOUR, mmid.message_id_updated_at, NOW()) < 96 THEN 'warm'
        ELSE 'cold'
    END as cache_status
FROM bot_statistics bs
LEFT JOIN movie_message_ids mmid 
    ON bs.content_type = 'movie' 
    AND bs.content_id = mmid.movie_id 
    AND (bs.part_number IS NULL OR bs.part_number = mmid.part_number)
ORDER BY bs.total_forwards DESC;

-- Step 11: Create stored procedure for bot heartbeat
DELIMITER //
CREATE PROCEDURE sp_bot_heartbeat(
    IN p_bot_id INT,
    IN p_queue_size INT,
    IN p_disk_usage_mb DECIMAL(12,2),
    IN p_memory_usage_mb DECIMAL(10,2)
)
BEGIN
    DECLARE v_now TIMESTAMP;
    SET v_now = NOW();
    
    UPDATE bot_health_status 
    SET 
        status = 'online',
        last_heartbeat = v_now,
        queue_size = p_queue_size,
        disk_usage_mb = p_disk_usage_mb,
        memory_usage_mb = p_memory_usage_mb,
        error_message = NULL,
        updated_at = v_now
    WHERE bot_id = p_bot_id;
    
    -- Check for alerts
    IF p_disk_usage_mb > 1500000 THEN -- 1.5TB
        INSERT INTO system_alerts (alert_type, severity, message, bot_id)
        VALUES ('disk_space', 'warning', 'Disk usage exceeds 1.5TB', p_bot_id);
    END IF;
    
    IF p_queue_size > 100 THEN
        INSERT INTO system_alerts (alert_type, severity, message, bot_id)
        VALUES ('queue_overload', 'warning', CONCAT('Queue size: ', p_queue_size), p_bot_id);
    END IF;
END//
DELIMITER ;

-- Step 12: Create stored procedure for recording forwards
DELIMITER //
CREATE PROCEDURE sp_record_forward(
    IN p_content_type VARCHAR(10),
    IN p_content_id INT,
    IN p_part_number INT,
    IN p_was_cache_hit BOOLEAN,
    IN p_forward_time_sec DECIMAL(6,2),
    IN p_file_size_mb DECIMAL(10,2)
)
BEGIN
    DECLARE v_data_saved DECIMAL(12,2);
    
    -- Calculate data saved (if cache hit, we saved download+upload)
    SET v_data_saved = IF(p_was_cache_hit, p_file_size_mb * 2, 0);
    
    -- Update or insert statistics
    INSERT INTO bot_statistics 
        (content_type, content_id, part_number, total_forwards, cache_hits, cache_misses, 
         total_data_saved_mb, avg_forward_time_sec, last_forwarded_at)
    VALUES 
        (p_content_type, p_content_id, p_part_number, 1, 
         IF(p_was_cache_hit, 1, 0), IF(p_was_cache_hit, 0, 1),
         v_data_saved, p_forward_time_sec, NOW())
    ON DUPLICATE KEY UPDATE
        total_forwards = total_forwards + 1,
        cache_hits = cache_hits + IF(p_was_cache_hit, 1, 0),
        cache_misses = cache_misses + IF(p_was_cache_hit, 0, 1),
        total_data_saved_mb = total_data_saved_mb + v_data_saved,
        avg_forward_time_sec = ((avg_forward_time_sec * total_forwards) + p_forward_time_sec) / (total_forwards + 1),
        last_forwarded_at = NOW();
END//
DELIMITER ;

-- Step 13: Create function to get next available bot
DELIMITER //
CREATE FUNCTION fn_get_available_bot() RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_bot_id INT;
    
    -- Get bot with smallest queue that's online
    SELECT bot_id INTO v_bot_id
    FROM bot_health_status
    WHERE status = 'online'
      AND TIMESTAMPDIFF(SECOND, last_heartbeat, NOW()) < 60
    ORDER BY queue_size ASC, total_requests_today ASC
    LIMIT 1;
    
    -- If no bot is available, return 1 as fallback
    IF v_bot_id IS NULL THEN
        SET v_bot_id = 1;
    END IF;
    
    RETURN v_bot_id;
END//
DELIMITER ;

-- =====================================================
-- Migration Complete
-- =====================================================
-- Next steps:
-- 1. Update .env files for each bot instance
-- 2. Create bot-media directory structure
-- 3. Update bot code to use new schema
-- 4. Deploy admin tracking dashboard
-- =====================================================

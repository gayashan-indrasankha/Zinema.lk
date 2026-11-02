-- WhatsApp Video Forwarder Database Schema
-- Run this in your existing database (zinexxio_cinedrive)
-- Note: CREATE DATABASE removed for shared hosting compatibility

-- Table to store video tokens and their corresponding message IDs
CREATE TABLE IF NOT EXISTS video_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(100) NOT NULL UNIQUE,
    message_id VARCHAR(255) NOT NULL COMMENT 'WhatsApp Message ID from storage group',
    file_name VARCHAR(500) DEFAULT NULL COMMENT 'Original file name for reference',
    file_size BIGINT DEFAULT NULL COMMENT 'File size in bytes',
    description TEXT DEFAULT NULL COMMENT 'Optional description',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    download_count INT DEFAULT 0 COMMENT 'Track how many times this was forwarded',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Soft delete flag',
    INDEX idx_token (token),
    INDEX idx_message_id (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to log all forwarding activities
CREATE TABLE IF NOT EXISTS forward_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_id INT NOT NULL,
    user_phone VARCHAR(50) NOT NULL COMMENT 'Phone number who requested',
    user_chat_id VARCHAR(255) NOT NULL COMMENT 'WhatsApp Chat ID',
    status ENUM('success', 'failed', 'pending') DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    forwarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (token_id) REFERENCES video_tokens(id) ON DELETE CASCADE,
    INDEX idx_user_phone (user_phone),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data (optional - remove in production)
-- INSERT INTO video_tokens (token, message_id, file_name, description) VALUES
-- ('MOVIE001', 'true_1234567890123456789@g.us_ABC123DEF456', 'Avengers_Endgame_2019.mp4', 'Avengers Endgame Full Movie'),
-- ('MOVIE002', 'true_1234567890123456789@g.us_XYZ789GHI012', 'Inception_2010.mp4', 'Inception Full Movie');

-- Table to track media file refresh history (Auto Refresher optimization)
CREATE TABLE IF NOT EXISTS media_refresh_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id VARCHAR(255) NOT NULL UNIQUE COMMENT 'WhatsApp Message ID from storage group',
    file_name VARCHAR(500) DEFAULT NULL COMMENT 'File name for reference',
    file_type ENUM('video', 'document', 'image', 'audio', 'other') DEFAULT 'video',
    file_size BIGINT DEFAULT NULL COMMENT 'File size in bytes (optional)',
    last_refreshed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    refresh_count INT DEFAULT 1 COMMENT 'How many times this file was refreshed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_message_id (message_id),
    INDEX idx_last_refreshed (last_refreshed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


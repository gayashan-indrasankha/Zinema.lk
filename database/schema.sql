-- Canonical local schema v4, MariaDB 10.4+. Structure only, no data.

-- Validated in the isolated recovery fixture. Not an in-place migration.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `value` int(11) DEFAULT NULL COMMENT 'e.g., watch duration in seconds',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `backup_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_type` enum('full','incremental') NOT NULL,
  `backup_path` varchar(512) NOT NULL,
  `file_count` int(11) DEFAULT 0,
  `total_size_mb` decimal(12,2) DEFAULT 0.00,
  `status` enum('in_progress','completed','failed') DEFAULT 'in_progress',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`,`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Backup operation log';

CREATE TABLE `bot_health_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bot_id` int(11) NOT NULL,
  `bot_phone` varchar(20) DEFAULT NULL,
  `status` enum('online','offline') NOT NULL DEFAULT 'offline',
  `queue_size` int(11) DEFAULT 0,
  `disk_usage_mb` decimal(10,2) DEFAULT 0.00,
  `memory_usage_mb` decimal(10,2) DEFAULT 0.00,
  `uptime_seconds` int(11) DEFAULT 0,
  `last_heartbeat` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_requests_today` int(11) NOT NULL DEFAULT 0,
  `successful_forwards_today` int(11) NOT NULL DEFAULT 0,
  `failed_forwards_today` int(11) NOT NULL DEFAULT 0,
  `cache_group_id` varchar(255) DEFAULT NULL,
  `storage_path` varchar(512) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bot_id` (`bot_id`),
  KEY `idx_status` (`status`),
  KEY `idx_last_heartbeat` (`last_heartbeat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bot_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` enum('movie','episode') NOT NULL,
  `content_id` int(11) NOT NULL,
  `part_number` int(11) NOT NULL DEFAULT 0,
  `total_forwards` int(11) DEFAULT 0,
  `cache_hits` int(11) DEFAULT 0,
  `cache_misses` int(11) DEFAULT 0,
  `last_forwarded_at` timestamp NULL DEFAULT NULL,
  `total_data_saved_mb` decimal(12,2) DEFAULT 0.00 COMMENT 'Estimated data saved by caching',
  `avg_forward_time_sec` decimal(6,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_content` (`content_type`,`content_id`,`part_number`),
  KEY `idx_popularity` (`total_forwards`),
  KEY `idx_last_forwarded` (`last_forwarded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Statistics for content forwarding performance';

CREATE TABLE `cache_warming_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bot_id` int(11) NOT NULL,
  `content_type` enum('movie','episode') NOT NULL,
  `content_id` int(11) NOT NULL,
  `part_number` int(11) DEFAULT NULL,
  `warmed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_size_mb` decimal(10,2) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 1,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bot_id` (`bot_id`),
  KEY `idx_warmed_at` (`warmed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log of cache warming operations';

CREATE TABLE `collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `genre` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `distribution_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(100) NOT NULL,
  `group_jid` varchar(100) DEFAULT NULL COMMENT 'WhatsApp group JID e.g. 123456@g.us',
  `invite_link` varchar(255) DEFAULT NULL,
  `member_count` int(11) NOT NULL DEFAULT 0,
  `max_members` int(11) NOT NULL DEFAULT 1024,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_full` tinyint(1) NOT NULL DEFAULT 0,
  `priority` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `episode_parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `episode_id` int(11) NOT NULL,
  `part_number` int(11) NOT NULL DEFAULT 1,
  `message_id` varchar(255) DEFAULT NULL COMMENT 'WhatsApp storage group message ID',
  `cache_message_id` varchar(255) DEFAULT NULL COMMENT 'WhatsApp cache group message ID',
  `file_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `local_file_path` varchar(512) DEFAULT NULL,
  `last_forwarded_at` timestamp NULL DEFAULT NULL,
  `message_id_updated_at` timestamp NULL DEFAULT NULL,
  `forward_count` int(11) NOT NULL DEFAULT 0,
  `cache_hit_count` int(11) NOT NULL DEFAULT 0,
  `cache_miss_count` int(11) NOT NULL DEFAULT 0,
  `bot_instance_id` int(11) NOT NULL DEFAULT 1,
  `file_size` bigint(20) DEFAULT NULL,
  `file_size_mb` decimal(10,2) DEFAULT NULL,
  `last_refreshed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `episode_part` (`episode_id`,`part_number`),
  KEY `idx_episode_id` (`episode_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `episodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `series_id` int(11) NOT NULL,
  `season_number` int(11) NOT NULL,
  `episode_number` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `video_url` longtext NOT NULL COMMENT 'Direct streaming URL (e.g., drive streamer URL)',
  `views` int(11) DEFAULT 0,
  `thumb_image` varchar(255) DEFAULT NULL,
  `air_date` date DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'Duration in minutes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `series_id` (`series_id`),
  KEY `idx_series_season` (`series_id`,`season_number`),
  CONSTRAINT `episodes_ibfk_1` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `file_cleanup_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` enum('movie','episode') NOT NULL,
  `content_id` int(11) NOT NULL,
  `part_number` int(11) DEFAULT NULL,
  `local_file_path` varchar(512) NOT NULL,
  `marked_for_deletion_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` varchar(255) DEFAULT NULL COMMENT 'Why marked for deletion',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pending` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Queue for files to be cleaned up';

CREATE TABLE `forward_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token_id` int(11) NOT NULL,
  `user_phone` varchar(50) NOT NULL COMMENT 'Phone number who requested',
  `user_chat_id` varchar(255) NOT NULL COMMENT 'WhatsApp Chat ID',
  `status` enum('success','failed','pending') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `forwarded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `token_id` (`token_id`),
  KEY `idx_user_phone` (`user_phone`),
  KEY `idx_status` (`status`),
  CONSTRAINT `forward_logs_ibfk_1` FOREIGN KEY (`token_id`) REFERENCES `video_tokens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL COMMENT 'IP address of the login attempt (supports IPv4 and IPv6)',
  `username` varchar(100) DEFAULT NULL COMMENT 'Username or email attempted',
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When the attempt occurred',
  `successful` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether the login was successful',
  PRIMARY KEY (`id`),
  KEY `idx_login_peer_time` (`ip_address`,`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks login attempts for rate limiting and security monitoring';

CREATE TABLE `media_refresh_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` varchar(255) NOT NULL COMMENT 'WhatsApp Message ID from storage group',
  `file_name` varchar(500) DEFAULT NULL COMMENT 'File name for reference',
  `file_type` enum('video','document','image','audio','other') DEFAULT 'video',
  `file_size` bigint(20) DEFAULT NULL COMMENT 'File size in bytes (optional)',
  `last_refreshed` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `refresh_count` int(11) DEFAULT 1 COMMENT 'How many times this file was refreshed',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_id` (`message_id`),
  KEY `idx_message_id` (`message_id`),
  KEY `idx_last_refreshed` (`last_refreshed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `movie_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_movie_comments_movie` (`movie_id`),
  KEY `fk_movie_comment_user` (`user_id`),
  KEY `fk_movie_comment_parent` (`parent_id`),
  CONSTRAINT `fk_movie_comment_movie` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_movie_comment_parent` FOREIGN KEY (`parent_id`) REFERENCES `movie_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_movie_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `movie_parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) NOT NULL,
  `part_number` int(11) NOT NULL DEFAULT 1,
  `message_id` varchar(255) DEFAULT NULL COMMENT 'WhatsApp storage group message ID',
  `cache_message_id` varchar(255) DEFAULT NULL COMMENT 'WhatsApp cache group message ID',
  `file_name` varchar(255) DEFAULT NULL,
  `file_size_mb` decimal(10,2) DEFAULT NULL,
  `last_refreshed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `local_file_path` varchar(512) DEFAULT NULL,
  `last_forwarded_at` timestamp NULL DEFAULT NULL,
  `message_id_updated_at` timestamp NULL DEFAULT NULL,
  `forward_count` int(11) NOT NULL DEFAULT 0,
  `cache_hit_count` int(11) NOT NULL DEFAULT 0,
  `cache_miss_count` int(11) NOT NULL DEFAULT 0,
  `bot_instance_id` int(11) NOT NULL DEFAULT 1,
  `file_size` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `movie_part` (`movie_id`,`part_number`),
  KEY `idx_movie_id` (`movie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `movies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `release_date` varchar(50) DEFAULT NULL,
  `genre` varchar(255) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT 0.0,
  `cover_image` varchar(255) DEFAULT NULL,
  `video_url` longtext DEFAULT NULL COMMENT 'Direct streaming URL (e.g., drive streamer URL)',
  `collection_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `views` int(11) DEFAULT 0,
  `language_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duration` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collection_id` (`collection_id`),
  CONSTRAINT `movies_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `action_type` varchar(50) NOT NULL DEFAULT 'token_request',
  `request_count` int(11) DEFAULT 1,
  `first_request_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_request_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `blocked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_action` (`ip_address`,`action_type`),
  KEY `idx_blocked` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `series` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `release_date` varchar(50) DEFAULT NULL,
  `genre` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `language_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shot_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `shot_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_shot_id` (`shot_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `shot_comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shot_comments_ibfk_2` FOREIGN KEY (`shot_id`) REFERENCES `shots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shot_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `shot_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`user_id`,`shot_id`),
  KEY `idx_shot_id` (`shot_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `shot_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shot_likes_ibfk_2` FOREIGN KEY (`shot_id`) REFERENCES `shots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `fb_share_url` varchar(512) DEFAULT NULL,
  `shot_video_file` longtext NOT NULL,
  `linked_content_type` enum('movie','series','collection') NOT NULL,
  `linked_content_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_content_type` (`linked_content_type`),
  KEY `idx_content_id` (`linked_content_id`),
  KEY `idx_admin` (`admin_id`),
  CONSTRAINT `shots_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_type` enum('disk_space','bot_offline','queue_overload','cache_miss_high','error') NOT NULL,
  `severity` enum('info','warning','critical') DEFAULT 'warning',
  `message` text NOT NULL,
  `bot_id` int(11) DEFAULT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_unresolved` (`is_resolved`,`created_at`),
  KEY `idx_bot_id` (`bot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System alerts and notifications';

CREATE TABLE `trailer_favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trailer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_trailer_favorite` (`trailer_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `trailer_favorites_ibfk_1` FOREIGN KEY (`trailer_id`) REFERENCES `trailers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trailer_favorites_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `trailer_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trailer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_trailer_like` (`trailer_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `trailer_likes_ibfk_1` FOREIGN KEY (`trailer_id`) REFERENCES `trailers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trailer_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `trailer_shares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trailer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `platform` varchar(50) NOT NULL,
  `shared_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `trailer_id` (`trailer_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `trailer_shares_ibfk_1` FOREIGN KEY (`trailer_id`) REFERENCES `trailers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trailer_shares_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `trailer_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trailer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_trailer_recent` (`trailer_id`,`viewed_at`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `trailer_views_ibfk_1` FOREIGN KEY (`trailer_id`) REFERENCES `trailers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trailer_views_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `trailers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `video_url` text NOT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_trailer_movie` (`movie_id`),
  CONSTRAINT `fk_trailer_movie` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `shot_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_shot` (`user_id`,`shot_id`),
  KEY `shot_id` (`shot_id`),
  CONSTRAINT `user_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_favorites_ibfk_2` FOREIGN KEY (`shot_id`) REFERENCES `shots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `shot_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_view` (`user_id`,`shot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `verification_expires` datetime DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `msisdn` varchar(20) DEFAULT NULL COMMENT 'Users phone number for Ideamart',
  `subscription_expiry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `remember_token` varchar(64) DEFAULT NULL,
  `remember_expires_at` datetime DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `msisdn` (`msisdn`),
  KEY `idx_verification_token` (`verification_token`),
  KEY `idx_google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `video_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(100) NOT NULL,
  `message_id` varchar(255) NOT NULL COMMENT 'WhatsApp Message ID from storage group',
  `file_name` varchar(500) DEFAULT NULL COMMENT 'Original file name for reference',
  `file_size` bigint(20) DEFAULT NULL COMMENT 'File size in bytes',
  `description` text DEFAULT NULL COMMENT 'Optional description',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `download_count` int(11) DEFAULT 0 COMMENT 'Track how many times this was forwarded',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Soft delete flag',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_token` (`token`),
  KEY `idx_message_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `whatsapp_forward_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token_id` int(11) DEFAULT NULL COMMENT 'Reference to whatsapp_tokens',
  `user_phone` varchar(50) NOT NULL COMMENT 'Phone number who requested',
  `user_chat_id` varchar(255) NOT NULL COMMENT 'WhatsApp Chat ID',
  `status` enum('success','failed','pending') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `forwarded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token_id` (`token_id`),
  KEY `idx_user_phone` (`user_phone`),
  KEY `idx_status` (`status`),
  CONSTRAINT `whatsapp_forward_logs_ibfk_1` FOREIGN KEY (`token_id`) REFERENCES `whatsapp_tokens` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `whatsapp_message_ids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` enum('movie','episode') NOT NULL,
  `content_id` int(11) NOT NULL,
  `message_id` varchar(255) NOT NULL COMMENT 'WhatsApp Message ID from storage group',
  `file_name` varchar(500) DEFAULT NULL COMMENT 'Original file name for reference',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `part_number` int(11) NOT NULL DEFAULT 0,
  `local_file_path` varchar(512) DEFAULT NULL,
  `last_forwarded_at` timestamp NULL DEFAULT NULL,
  `message_id_updated_at` timestamp NULL DEFAULT NULL,
  `forward_count` int(11) NOT NULL DEFAULT 0,
  `cache_hit_count` int(11) NOT NULL DEFAULT 0,
  `cache_miss_count` int(11) NOT NULL DEFAULT 0,
  `bot_instance_id` int(11) NOT NULL DEFAULT 1,
  `file_size` bigint(20) DEFAULT NULL,
  `file_size_mb` decimal(10,2) DEFAULT NULL,
  `cache_message_id` varchar(255) DEFAULT NULL,
  `last_refreshed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_content_part` (`content_type`,`content_id`,`part_number`),
  KEY `idx_message_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `whatsapp_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(12) NOT NULL COMMENT 'Unique 12-character alphanumeric token',
  `content_type` enum('movie','episode') NOT NULL COMMENT 'Type of content',
  `content_id` int(11) NOT NULL COMMENT 'ID of the movie or episode',
  `message_id` varchar(255) DEFAULT NULL COMMENT 'WhatsApp Message ID from storage group (set by admin)',
  `is_used` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether token has been used',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Soft delete flag',
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Token expiration time (10 minutes from creation)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL COMMENT 'When the token was used',
  `part_number` int(11) DEFAULT NULL,
  `assigned_bot_id` int(11) NOT NULL DEFAULT 1,
  `claim_hash` char(64) DEFAULT NULL,
  `claim_owner` char(64) DEFAULT NULL,
  `claim_state` enum('claimed','sending','uncertain','completed') DEFAULT NULL,
  `claimed_at` timestamp NULL DEFAULT NULL,
  `claim_revision` bigint unsigned NOT NULL DEFAULT 0,
  `reconciled_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_content` (`content_type`,`content_id`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_is_used` (`is_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `delivery_reconciliations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token_id` int(11) NOT NULL,
  `observed_revision` bigint unsigned NOT NULL,
  `previous_state` enum('claimed','sending','uncertain') NOT NULL,
  `outcome` enum('delivered','not_delivered') NOT NULL,
  `actor` varchar(48) NOT NULL,
  `evidence_ref` varchar(80) NOT NULL,
  `workers_stopped` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `one_reconciliation_per_token` (`token_id`),
  CONSTRAINT `reconciliation_token_fk` FOREIGN KEY (`token_id`) REFERENCES `whatsapp_tokens` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;

CREATE SQL SECURITY INVOKER VIEW `v_delivery_files` AS select `whatsapp_message_ids`.`content_type` AS `content_type`,`whatsapp_message_ids`.`content_id` AS `content_id`,0 AS `part_number`,`whatsapp_message_ids`.`message_id` AS `message_id`,`whatsapp_message_ids`.`cache_message_id` AS `cache_message_id`,`whatsapp_message_ids`.`local_file_path` AS `local_file_path`,`whatsapp_message_ids`.`message_id_updated_at` AS `message_id_updated_at`,`whatsapp_message_ids`.`updated_at` AS `updated_at`,`whatsapp_message_ids`.`forward_count` AS `forward_count`,`whatsapp_message_ids`.`cache_hit_count` AS `cache_hit_count`,`whatsapp_message_ids`.`cache_miss_count` AS `cache_miss_count` from `whatsapp_message_ids` where `whatsapp_message_ids`.`part_number` = 0 union all select 'movie' AS `movie`,`movie_parts`.`movie_id` AS `movie_id`,`movie_parts`.`part_number` AS `part_number`,`movie_parts`.`message_id` AS `message_id`,`movie_parts`.`cache_message_id` AS `cache_message_id`,`movie_parts`.`local_file_path` AS `local_file_path`,`movie_parts`.`message_id_updated_at` AS `message_id_updated_at`,`movie_parts`.`updated_at` AS `updated_at`,`movie_parts`.`forward_count` AS `forward_count`,`movie_parts`.`cache_hit_count` AS `cache_hit_count`,`movie_parts`.`cache_miss_count` AS `cache_miss_count` from `movie_parts` union all select 'episode' AS `episode`,`episode_parts`.`episode_id` AS `episode_id`,`episode_parts`.`part_number` AS `part_number`,`episode_parts`.`message_id` AS `message_id`,`episode_parts`.`cache_message_id` AS `cache_message_id`,`episode_parts`.`local_file_path` AS `local_file_path`,`episode_parts`.`message_id_updated_at` AS `message_id_updated_at`,`episode_parts`.`updated_at` AS `updated_at`,`episode_parts`.`forward_count` AS `forward_count`,`episode_parts`.`cache_hit_count` AS `cache_hit_count`,`episode_parts`.`cache_miss_count` AS `cache_miss_count` from `episode_parts`;

CREATE SQL SECURITY INVOKER VIEW `v_popular_content` AS select `bs`.`content_type` AS `content_type`,`bs`.`content_id` AS `content_id`,`bs`.`part_number` AS `part_number`,`bs`.`total_forwards` AS `total_forwards`,`bs`.`cache_hits` AS `cache_hits`,`bs`.`cache_misses` AS `cache_misses`,round(`bs`.`cache_hits` / nullif(`bs`.`total_forwards`,0) * 100,2) AS `cache_hit_rate`,`bs`.`total_data_saved_mb` AS `total_data_saved_mb`,`bs`.`last_forwarded_at` AS `last_forwarded_at`,`f`.`local_file_path` AS `local_file_path`,`f`.`cache_message_id` AS `cache_message_id`,`f`.`message_id_updated_at` AS `message_id_updated_at`,timestampdiff(HOUR,`f`.`message_id_updated_at`,current_timestamp()) AS `hours_since_cache_update`,case when timestampdiff(HOUR,`f`.`message_id_updated_at`,current_timestamp()) < 6 then 'hot' when timestampdiff(HOUR,`f`.`message_id_updated_at`,current_timestamp()) < 96 then 'warm' else 'cold' end AS `cache_status` from (`bot_statistics` `bs` left join `v_delivery_files` `f` on(`bs`.`content_type` = `f`.`content_type` and `bs`.`content_id` = `f`.`content_id` and `bs`.`part_number` = `f`.`part_number`));

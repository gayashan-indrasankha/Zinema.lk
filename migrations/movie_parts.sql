-- Movie Parts System Migration
-- Run this SQL to add support for multi-part movie downloads

-- Table to store movie parts (each part has its own WhatsApp message ID)
CREATE TABLE IF NOT EXISTS `movie_parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) NOT NULL COMMENT 'Foreign key to movies table',
  `part_number` int(11) NOT NULL COMMENT 'Part number (1, 2, 3, etc.)',
  `message_id` varchar(255) NOT NULL COMMENT 'WhatsApp Message ID for this part',
  `file_name` varchar(500) DEFAULT NULL COMMENT 'Part file name for reference',
  `file_size` bigint(20) DEFAULT NULL COMMENT 'File size in bytes',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_movie_part` (`movie_id`, `part_number`),
  KEY `idx_movie_id` (`movie_id`),
  KEY `idx_message_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add part_number column to whatsapp_tokens table
-- NULL means it's for the whole movie (backward compatible)
-- A number means it's for a specific part
ALTER TABLE `whatsapp_tokens` 
ADD COLUMN IF NOT EXISTS `part_number` int(11) DEFAULT NULL COMMENT 'Part number if multi-part download' AFTER `message_id`;

-- Add index for faster part lookups
ALTER TABLE `whatsapp_tokens` 
ADD INDEX IF NOT EXISTS `idx_part_number` (`part_number`);

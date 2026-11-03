-- Trailers System Migration
-- Tables used by includes/TrailerManager.php, trailers.php, api/trailers.php and admin/trailers.php

CREATE TABLE IF NOT EXISTS `trailers` (
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

CREATE TABLE IF NOT EXISTS `trailer_favorites` (
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

CREATE TABLE IF NOT EXISTS `trailer_likes` (
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

CREATE TABLE IF NOT EXISTS `trailer_shares` (
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

CREATE TABLE IF NOT EXISTS `trailer_views` (
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

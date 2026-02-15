-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 23, 2025 at 10:38 PM
-- Server version: 11.4.9-MariaDB-cll-lve
-- PHP Version: 8.3.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `zinexxio_cinedrive`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`) VALUES
(4, 'gayas', '$2y$10$kbqPIyaA12ZcScKUZu4sZueUlNsVGNMairlDOblFL2c9eb.WcZSzK');

-- --------------------------------------------------------

--
-- Table structure for table `analytics`
--

CREATE TABLE `analytics` (
  `id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `value` int(11) DEFAULT NULL COMMENT 'e.g., watch duration in seconds',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `analytics`
--

INSERT INTO `analytics` (`id`, `event_type`, `value`, `timestamp`) VALUES
(3352, 'page_view', NULL, '2025-12-15 08:10:05'),
(3692, 'page_view', NULL, '2025-12-19 19:58:04'),
(3693, 'page_view', NULL, '2025-12-19 19:58:11'),
(3694, 'page_view', NULL, '2025-12-20 20:41:47'),
(3695, 'page_view', NULL, '2025-12-20 20:42:01'),
(3696, 'page_view', NULL, '2025-12-20 22:25:29'),
(3697, 'page_view', NULL, '2025-12-20 22:25:29'),
(3698, 'page_view', NULL, '2025-12-20 22:25:30'),
(3699, 'page_view', NULL, '2025-12-20 22:27:03'),
(3700, 'page_view', NULL, '2025-12-20 22:29:50'),
(3701, 'page_view', NULL, '2025-12-20 22:30:17'),
(3702, 'page_view', NULL, '2025-12-20 22:30:53'),
(3703, 'page_view', NULL, '2025-12-20 22:31:26'),
(3704, 'page_view', NULL, '2025-12-20 22:31:40'),
(3705, 'page_view', NULL, '2025-12-20 22:38:22'),
(3706, 'page_view', NULL, '2025-12-20 22:39:30'),
(3707, 'page_view', NULL, '2025-12-20 22:40:10'),
(3708, 'page_view', NULL, '2025-12-20 22:40:21'),
(3709, 'page_view', NULL, '2025-12-20 22:40:23'),
(3710, 'page_view', NULL, '2025-12-20 23:11:30'),
(3711, 'page_view', NULL, '2025-12-20 23:11:38'),
(3712, 'page_view', NULL, '2025-12-20 23:11:56'),
(3713, 'page_view', NULL, '2025-12-20 23:13:27'),
(3714, 'page_view', NULL, '2025-12-20 23:13:27'),
(3715, 'page_view', NULL, '2025-12-20 23:13:34'),
(3716, 'page_view', NULL, '2025-12-20 23:13:45'),
(3717, 'page_view', NULL, '2025-12-20 23:13:52'),
(3718, 'page_view', NULL, '2025-12-20 23:14:06'),
(3719, 'page_view', NULL, '2025-12-21 07:26:43'),
(3720, 'page_view', NULL, '2025-12-21 07:26:46'),
(3721, 'page_view', NULL, '2025-12-21 07:27:02'),
(3722, 'page_view', NULL, '2025-12-21 07:27:08'),
(3723, 'page_view', NULL, '2025-12-21 07:27:10'),
(3724, 'page_view', NULL, '2025-12-21 07:31:42'),
(3725, 'page_view', NULL, '2025-12-21 07:31:43'),
(3726, 'page_view', NULL, '2025-12-21 07:31:44'),
(3727, 'page_view', NULL, '2025-12-21 07:31:46'),
(3728, 'page_view', NULL, '2025-12-21 07:31:47'),
(3729, 'page_view', NULL, '2025-12-21 12:00:51'),
(3730, 'page_view', NULL, '2025-12-21 12:00:57'),
(3731, 'page_view', NULL, '2025-12-21 12:00:59'),
(3732, 'page_view', NULL, '2025-12-21 12:01:26'),
(3733, 'page_view', NULL, '2025-12-21 12:01:51'),
(3734, 'page_view', NULL, '2025-12-21 12:02:58'),
(3735, 'page_view', NULL, '2025-12-21 12:02:59'),
(3736, 'page_view', NULL, '2025-12-21 12:03:01'),
(3737, 'page_view', NULL, '2025-12-21 12:03:09'),
(3738, 'page_view', NULL, '2025-12-21 12:03:43'),
(3739, 'page_view', NULL, '2025-12-21 12:03:47'),
(3740, 'page_view', NULL, '2025-12-21 12:04:31'),
(3741, 'page_view', NULL, '2025-12-21 12:04:48'),
(3742, 'page_view', NULL, '2025-12-21 13:02:17'),
(3743, 'page_view', NULL, '2025-12-21 13:02:25'),
(3744, 'page_view', NULL, '2025-12-21 13:02:26'),
(3745, 'page_view', NULL, '2025-12-21 13:02:27'),
(3746, 'page_view', NULL, '2025-12-21 13:02:28'),
(3747, 'page_view', NULL, '2025-12-21 13:02:29'),
(3748, 'page_view', NULL, '2025-12-21 13:02:32'),
(3749, 'page_view', NULL, '2025-12-21 13:23:37'),
(3750, 'page_view', NULL, '2025-12-21 13:24:45'),
(3751, 'page_view', NULL, '2025-12-21 13:27:00'),
(3752, 'page_view', NULL, '2025-12-21 13:27:02'),
(3753, 'page_view', NULL, '2025-12-21 13:43:42'),
(3754, 'page_view', NULL, '2025-12-21 13:43:55'),
(3755, 'page_view', NULL, '2025-12-21 13:44:04'),
(3756, 'page_view', NULL, '2025-12-21 13:44:09'),
(3757, 'page_view', NULL, '2025-12-21 13:44:14'),
(3758, 'page_view', NULL, '2025-12-21 13:53:15'),
(3759, 'page_view', NULL, '2025-12-21 13:53:20'),
(3760, 'page_view', NULL, '2025-12-21 13:53:27'),
(3761, 'page_view', NULL, '2025-12-21 13:53:38'),
(3762, 'page_view', NULL, '2025-12-21 13:53:48'),
(3763, 'page_view', NULL, '2025-12-21 13:53:49'),
(3764, 'page_view', NULL, '2025-12-21 13:53:51'),
(3765, 'page_view', NULL, '2025-12-21 13:53:55'),
(3766, 'page_view', NULL, '2025-12-21 14:10:11'),
(3767, 'page_view', NULL, '2025-12-21 14:10:20'),
(3768, 'page_view', NULL, '2025-12-21 14:10:23'),
(3769, 'page_view', NULL, '2025-12-21 14:10:40'),
(3770, 'page_view', NULL, '2025-12-21 14:14:41'),
(3771, 'page_view', NULL, '2025-12-21 14:17:13'),
(3772, 'page_view', NULL, '2025-12-21 14:17:30'),
(3773, 'page_view', NULL, '2025-12-21 14:18:02'),
(3774, 'page_view', NULL, '2025-12-21 14:24:04'),
(3775, 'page_view', NULL, '2025-12-22 13:12:59'),
(3776, 'page_view', NULL, '2025-12-22 13:13:12'),
(3777, 'page_view', NULL, '2025-12-22 13:14:23'),
(3778, 'page_view', NULL, '2025-12-23 07:01:07'),
(3779, 'page_view', NULL, '2025-12-23 07:01:14'),
(3780, 'page_view', NULL, '2025-12-23 07:02:29'),
(3781, 'page_view', NULL, '2025-12-23 07:02:36'),
(3782, 'page_view', NULL, '2025-12-23 07:02:45'),
(3783, 'page_view', NULL, '2025-12-23 07:02:54'),
(3784, 'page_view', NULL, '2025-12-23 07:02:56'),
(3785, 'page_view', NULL, '2025-12-23 07:03:02'),
(3786, 'page_view', NULL, '2025-12-23 07:03:03'),
(3787, 'page_view', NULL, '2025-12-23 07:06:15'),
(3788, 'page_view', NULL, '2025-12-23 07:06:18'),
(3789, 'page_view', NULL, '2025-12-23 07:06:19'),
(3790, 'page_view', NULL, '2025-12-23 07:06:20'),
(3791, 'page_view', NULL, '2025-12-23 07:06:25'),
(3792, 'page_view', NULL, '2025-12-23 07:06:27'),
(3793, 'page_view', NULL, '2025-12-23 07:06:28'),
(3794, 'page_view', NULL, '2025-12-23 07:06:33'),
(3795, 'page_view', NULL, '2025-12-23 07:06:34'),
(3796, 'page_view', NULL, '2025-12-23 07:06:37'),
(3797, 'page_view', NULL, '2025-12-23 07:06:40'),
(3798, 'page_view', NULL, '2025-12-23 07:06:41'),
(3799, 'page_view', NULL, '2025-12-23 07:08:12'),
(3800, 'page_view', NULL, '2025-12-23 07:08:33'),
(3801, 'page_view', NULL, '2025-12-23 07:08:40'),
(3802, 'page_view', NULL, '2025-12-23 07:08:42'),
(3803, 'page_view', NULL, '2025-12-23 07:08:52'),
(3804, 'page_view', NULL, '2025-12-23 07:09:00'),
(3805, 'page_view', NULL, '2025-12-23 07:09:02'),
(3806, 'page_view', NULL, '2025-12-23 07:11:28'),
(3807, 'page_view', NULL, '2025-12-23 07:11:31'),
(3808, 'page_view', NULL, '2025-12-23 07:11:33'),
(3809, 'page_view', NULL, '2025-12-23 07:11:39'),
(3810, 'page_view', NULL, '2025-12-23 07:11:42'),
(3811, 'page_view', NULL, '2025-12-23 07:11:46'),
(3812, 'page_view', NULL, '2025-12-23 07:11:56'),
(3813, 'page_view', NULL, '2025-12-23 07:11:59'),
(3814, 'page_view', NULL, '2025-12-23 07:12:04'),
(3815, 'page_view', NULL, '2025-12-23 07:13:47'),
(3816, 'page_view', NULL, '2025-12-23 07:13:53'),
(3817, 'page_view', NULL, '2025-12-23 07:16:16'),
(3818, 'page_view', NULL, '2025-12-23 07:16:19'),
(3819, 'page_view', NULL, '2025-12-23 09:24:37'),
(3820, 'page_view', NULL, '2025-12-23 09:24:40'),
(3821, 'page_view', NULL, '2025-12-23 09:34:22'),
(3822, 'page_view', NULL, '2025-12-23 09:35:35'),
(3823, 'page_view', NULL, '2025-12-23 09:35:37'),
(3824, 'page_view', NULL, '2025-12-23 09:36:53'),
(3825, 'page_view', NULL, '2025-12-23 09:36:57'),
(3826, 'page_view', NULL, '2025-12-23 09:37:00'),
(3827, 'page_view', NULL, '2025-12-23 09:37:04'),
(3828, 'page_view', NULL, '2025-12-23 09:37:51'),
(3829, 'page_view', NULL, '2025-12-23 09:38:04'),
(3830, 'page_view', NULL, '2025-12-23 09:38:09'),
(3831, 'page_view', NULL, '2025-12-23 09:42:34'),
(3832, 'page_view', NULL, '2025-12-23 10:00:20'),
(3833, 'page_view', NULL, '2025-12-23 10:01:02'),
(3834, 'page_view', NULL, '2025-12-23 10:01:07'),
(3835, 'page_view', NULL, '2025-12-23 10:02:25'),
(3836, 'page_view', NULL, '2025-12-23 10:02:47'),
(3837, 'page_view', NULL, '2025-12-23 10:02:51'),
(3838, 'page_view', NULL, '2025-12-23 10:03:13'),
(3839, 'page_view', NULL, '2025-12-23 10:09:49'),
(3840, 'page_view', NULL, '2025-12-23 10:09:53'),
(3841, 'page_view', NULL, '2025-12-23 10:14:35'),
(3842, 'page_view', NULL, '2025-12-23 10:14:44'),
(3843, 'page_view', NULL, '2025-12-23 10:19:29'),
(3844, 'page_view', NULL, '2025-12-23 10:57:08'),
(3845, 'page_view', NULL, '2025-12-23 10:57:10'),
(3846, 'page_view', NULL, '2025-12-23 10:59:51'),
(3847, 'page_view', NULL, '2025-12-23 10:59:54'),
(3848, 'page_view', NULL, '2025-12-23 10:59:57'),
(3849, 'page_view', NULL, '2025-12-23 11:00:03'),
(3850, 'page_view', NULL, '2025-12-23 11:00:05'),
(3851, 'page_view', NULL, '2025-12-23 11:00:15'),
(3852, 'page_view', NULL, '2025-12-23 11:00:17'),
(3853, 'page_view', NULL, '2025-12-23 12:35:14'),
(3854, 'page_view', NULL, '2025-12-23 12:35:16'),
(3855, 'page_view', NULL, '2025-12-23 12:35:17'),
(3856, 'page_view', NULL, '2025-12-23 12:35:18'),
(3857, 'page_view', NULL, '2025-12-23 13:06:13'),
(3858, 'page_view', NULL, '2025-12-23 13:06:18'),
(3859, 'page_view', NULL, '2025-12-23 13:06:23'),
(3860, 'page_view', NULL, '2025-12-23 13:11:53'),
(3861, 'page_view', NULL, '2025-12-23 13:17:18'),
(3862, 'page_view', NULL, '2025-12-23 13:44:19'),
(3863, 'page_view', NULL, '2025-12-23 13:44:26'),
(3864, 'page_view', NULL, '2025-12-23 13:44:34'),
(3865, 'page_view', NULL, '2025-12-23 13:44:37'),
(3866, 'page_view', NULL, '2025-12-23 13:44:39'),
(3867, 'page_view', NULL, '2025-12-23 13:44:42'),
(3868, 'page_view', NULL, '2025-12-23 13:44:44'),
(3869, 'page_view', NULL, '2025-12-23 14:01:16'),
(3870, 'page_view', NULL, '2025-12-23 14:01:42'),
(3871, 'page_view', NULL, '2025-12-23 14:04:22'),
(3872, 'page_view', NULL, '2025-12-23 14:04:28'),
(3873, 'page_view', NULL, '2025-12-23 14:04:32'),
(3874, 'page_view', NULL, '2025-12-23 14:04:34'),
(3875, 'page_view', NULL, '2025-12-23 14:06:11'),
(3876, 'page_view', NULL, '2025-12-23 14:06:18'),
(3877, 'page_view', NULL, '2025-12-23 14:10:43'),
(3878, 'page_view', NULL, '2025-12-23 14:10:47'),
(3879, 'page_view', NULL, '2025-12-23 14:10:52'),
(3880, 'page_view', NULL, '2025-12-23 14:10:59'),
(3881, 'page_view', NULL, '2025-12-23 18:45:39'),
(3882, 'page_view', NULL, '2025-12-23 18:45:41'),
(3883, 'page_view', NULL, '2025-12-23 18:45:44'),
(3884, 'page_view', NULL, '2025-12-23 18:45:50');

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

CREATE TABLE `collections` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `genre` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `collections`
--

INSERT INTO `collections` (`id`, `title`, `description`, `cover_image`, `genre`, `created_at`) VALUES
(11, 'The Maze Runner', 'A group of survivors fight to escape deadly mazes while uncovering dark secrets behind their world.', 'https://zinema.lk/uploads/69421ef035c1f_collection_tumblr_nescd73G8U1tzxzc4o1_1280.jpg', 'Action,Adventure', '2025-12-14 06:46:43'),
(12, 'Spider-Man', 'All the best adventures of the iconic web-slinger, Peter Parker, as he protects New York City.', 'https://zinema.lk/uploads/69421eb1b1789_collection_{fbb06c2b69d311108ea04f77ab656752}.jpg', 'Action, Sci-Fi, Drama', '2025-12-14 07:33:29'),
(13, 'Jumanji', 'A collection of adventurous tales where an ancient game pulls players into dangerous, exotic worlds they must escape.', 'https://zinema.lk/uploads/69421ea476df1_collection_Jumanji Welcome to the Jungle Movie Poster {1948352f161a8d474bca3174231878e9}.jpg', 'Action, Comedy, Thriller', '2025-12-14 12:43:30'),
(14, 'Garfield', 'The laziest, hungriest, and most cynical cat in the world, Garfield, dealing with Mondays, Odie, and his owner Jon.', 'https://zinema.lk/uploads/69421e73e7275_collection_68f22af26b94a_collection_s-l1200.jpg', 'Comedy', '2025-12-14 13:39:40'),
(15, 'Scooby-Doo', 'The Mystery Inc. gang—Shaggy, Scooby, Fred, Daphne, and Velma—solves spooky mysteries involving masked villains and ghosts.', 'https://zinema.lk/uploads/694216da5964c_collection_Scooby Doo 1 2002 Poster  Halloween.jpg', 'Comedy, Thriller', '2025-12-17 02:35:06'),
(16, 'Harry Potter ', 'A world-renowned fantasy series following the life of a young wizard, Harry Potter, and his friends Hermione Granger and Ron Weasley, who are students at the Hogwarts School of Witchcraft and Wizardry. The central story focuses on Harry\'s struggle against Lord Voldemort, a dark wizard who intends to become immortal and overthrow the wizarding world.', 'https://zinema.lk/uploads/694a9fc1497d9_collection_{4ca9dc9b139d7d44f580f4ce29d63dcd}.jpg', 'Action, Drama, Thriller', '2025-12-23 13:57:08');

-- --------------------------------------------------------

--
-- Table structure for table `episodes`
--

CREATE TABLE `episodes` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `episodes`
--

INSERT INTO `episodes` (`id`, `series_id`, `season_number`, `episode_number`, `title`, `description`, `video_url`, `views`, `thumb_image`, `air_date`, `duration`, `created_at`) VALUES
(67, 4, 1, 1, 'The First Encounter', 'Captain Yoo Si-jin and Sergeant Major Seo Dae-young catch a motorcycle thief on their day off. The thief is injured and sent to the hospital, where Si-jin meets Dr. Kang Mo-yeon for the first time. He is immediately attracted to her, but due to a misunderstanding, she mistakes him for a gang leader.', 'https://my-drive-streamer.indrasankag.workers.dev/1S3g0nrxyVXcu-swsxEHGJFAM2Vl0U28P', 0, 'https://sinhalamovies.web.lk/uploads/694aa0ab5cc5a_thumb_690454614cab1_thumb_Screenshot 2025-10-31 113647.png', '2016-02-24', 60, '2025-12-23 13:43:27');

-- --------------------------------------------------------

--
-- Table structure for table `forward_logs`
--

CREATE TABLE `forward_logs` (
  `id` int(11) NOT NULL,
  `token_id` int(11) NOT NULL,
  `user_phone` varchar(50) NOT NULL COMMENT 'Phone number who requested',
  `user_chat_id` varchar(255) NOT NULL COMMENT 'WhatsApp Chat ID',
  `status` enum('success','failed','pending') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `forwarded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) UNSIGNED NOT NULL,
  `ip_address` varchar(45) NOT NULL COMMENT 'IP address of the login attempt (supports IPv4 and IPv6)',
  `username` varchar(100) DEFAULT NULL COMMENT 'Username or email attempted',
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When the attempt occurred',
  `successful` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether the login was successful'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks login attempts for rate limiting and security monitoring';

-- --------------------------------------------------------

--
-- Table structure for table `media_refresh_log`
--

CREATE TABLE `media_refresh_log` (
  `id` int(11) NOT NULL,
  `message_id` varchar(255) NOT NULL COMMENT 'WhatsApp Message ID from storage group',
  `file_name` varchar(500) DEFAULT NULL COMMENT 'File name for reference',
  `file_type` enum('video','document','image','audio','other') DEFAULT 'video',
  `file_size` bigint(20) DEFAULT NULL COMMENT 'File size in bytes (optional)',
  `last_refreshed` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `refresh_count` int(11) DEFAULT 1 COMMENT 'How many times this file was refreshed',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `release_date` varchar(50) DEFAULT NULL,
  `genre` varchar(255) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT 0.0,
  `cover_image` varchar(255) DEFAULT NULL,
  `video_url` longtext DEFAULT NULL COMMENT 'Direct streaming URL (e.g., drive streamer URL)',
  `collection_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `views` int(11) DEFAULT 0,
  `language_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`id`, `title`, `release_date`, `genre`, `rating`, `cover_image`, `video_url`, `collection_id`, `created_at`, `views`, `language_type`) VALUES
(90, 'Harry Potter and the Sorcerer\'s Stone', 'Nov. 16, 2001', 'Action, Drama', 7.6, 'uploads/694a9514c0fc6_{f543964147cb9bdcb69dc6a72b65ac03}.jpg', 'https://my-drive-streamer.indrasankag.workers.dev/1b0iK1zAYDGzS7YM1nslqnCZAulrmn_fV', 16, '2025-12-23 13:11:48', 14, 'dubbed');

-- --------------------------------------------------------

--
-- Table structure for table `movie_comments`
--

CREATE TABLE `movie_comments` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `series`
--

CREATE TABLE `series` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `release_date` varchar(50) DEFAULT NULL,
  `genre` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `language_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `series`
--

INSERT INTO `series` (`id`, `title`, `release_date`, `genre`, `description`, `cover_image`, `language_type`, `created_at`) VALUES
(4, 'Descendants of the Sun [ හිමන්තරා ]', '2016', 'Romance, Drama, Action', 'The story follows a passionate romance between an elite Special Forces Captain, Yoo Shi-jin, and a capable surgeon, Kang Mo-yeon. Their differing views on life—one who kills to protect and one who saves lives at all costs—challenge their initial relationship. They are unexpectedly reunited when both are deployed to a fictional war-torn country, where their love deepens as they navigate conflict, natural disasters, and epidemics.', 'uploads/693fa7a5b09b9_68f32836f0c04_series_Descendants_of_the_Sun.webp', 'dubbed', '2025-12-15 06:16:05');

-- --------------------------------------------------------

--
-- Table structure for table `shots`
--

CREATE TABLE `shots` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `fb_share_url` varchar(512) DEFAULT NULL,
  `shot_video_file` longtext NOT NULL,
  `linked_content_type` enum('movie','series') NOT NULL,
  `linked_content_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shot_comments`
--

CREATE TABLE `shot_comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shot_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shot_likes`
--

CREATE TABLE `shot_likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shot_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
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
  `remember_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_favorites`
--

CREATE TABLE `user_favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shot_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_views`
--

CREATE TABLE `user_views` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shot_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_views`
--

INSERT INTO `user_views` (`id`, `user_id`, `shot_id`, `viewed_at`) VALUES
(0, 4, 13, '2025-12-17 00:47:56'),
(0, 4, 10, '2025-12-17 00:48:34'),
(0, 5, 22, '2025-12-23 07:11:53');

-- --------------------------------------------------------

--
-- Table structure for table `video_tokens`
--

CREATE TABLE `video_tokens` (
  `id` int(11) NOT NULL,
  `token` varchar(100) NOT NULL,
  `message_id` varchar(255) NOT NULL COMMENT 'WhatsApp Message ID from storage group',
  `file_name` varchar(500) DEFAULT NULL COMMENT 'Original file name for reference',
  `file_size` bigint(20) DEFAULT NULL COMMENT 'File size in bytes',
  `description` text DEFAULT NULL COMMENT 'Optional description',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `download_count` int(11) DEFAULT 0 COMMENT 'Track how many times this was forwarded',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Soft delete flag'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_forward_logs`
--

CREATE TABLE `whatsapp_forward_logs` (
  `id` int(11) NOT NULL,
  `token_id` int(11) DEFAULT NULL COMMENT 'Reference to whatsapp_tokens',
  `user_phone` varchar(50) NOT NULL COMMENT 'Phone number who requested',
  `user_chat_id` varchar(255) NOT NULL COMMENT 'WhatsApp Chat ID',
  `status` enum('success','failed','pending') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `forwarded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `whatsapp_forward_logs`
--

INSERT INTO `whatsapp_forward_logs` (`id`, `token_id`, `user_phone`, `user_chat_id`, `status`, `error_message`, `forwarded_at`) VALUES
(1, 2, '37495366524979', '37495366524979@lid', 'failed', 'Failed to forward video: Protocol error (Runtime.callFunctionOn): Target closed.', '2025-12-23 14:44:46');

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_message_ids`
--

CREATE TABLE `whatsapp_message_ids` (
  `id` int(11) NOT NULL,
  `content_type` enum('movie','episode') NOT NULL,
  `content_id` int(11) NOT NULL,
  `message_id` varchar(255) NOT NULL COMMENT 'WhatsApp Message ID from storage group',
  `file_name` varchar(500) DEFAULT NULL COMMENT 'Original file name for reference',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `whatsapp_message_ids`
--

INSERT INTO `whatsapp_message_ids` (`id`, `content_type`, `content_id`, `message_id`, `file_name`, `created_at`, `updated_at`) VALUES
(2, 'movie', 90, 'true_120363404925435399@g.us_A53248A33E7271FA7164E570564AC5F2_99042583400702@lid', 'Zinema.lk Harry_Potter.mp4', '2025-12-23 14:05:44', '2025-12-23 14:05:44');

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_tokens`
--

CREATE TABLE `whatsapp_tokens` (
  `id` int(11) NOT NULL,
  `token` varchar(12) NOT NULL COMMENT 'Unique 12-character alphanumeric token',
  `content_type` enum('movie','episode') NOT NULL COMMENT 'Type of content',
  `content_id` int(11) NOT NULL COMMENT 'ID of the movie or episode',
  `message_id` varchar(255) DEFAULT NULL COMMENT 'WhatsApp Message ID from storage group (set by admin)',
  `is_used` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether token has been used',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Soft delete flag',
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Token expiration time (10 minutes from creation)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL COMMENT 'When the token was used'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `whatsapp_tokens`
--

INSERT INTO `whatsapp_tokens` (`id`, `token`, `content_type`, `content_id`, `message_id`, `is_used`, `is_active`, `expires_at`, `created_at`, `used_at`) VALUES
(1, 'C84JFPYV5AH3', 'movie', 90, 'true_120363404925435399@g.us_A53248A33E7271FA7164E570564AC5F2_99042583400702@lid', 0, 1, '2025-12-23 14:16:17', '2025-12-23 14:06:17', NULL),
(2, 'DGED3AJ39ACJ', 'movie', 90, 'true_120363404925435399@g.us_A53248A33E7271FA7164E570564AC5F2_99042583400702@lid', 1, 1, '2025-12-23 14:11:06', '2025-12-23 14:10:59', '2025-12-23 14:11:06'),
(3, 'F44ZQSRUGWML', 'movie', 90, 'true_120363404925435399@g.us_A53248A33E7271FA7164E570564AC5F2_99042583400702@lid', 1, 1, '2025-12-23 18:45:57', '2025-12-23 18:45:50', '2025-12-23 18:45:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `analytics`
--
ALTER TABLE `analytics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `collections`
--
ALTER TABLE `collections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `episodes`
--
ALTER TABLE `episodes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `series_id` (`series_id`),
  ADD KEY `idx_series_season` (`series_id`,`season_number`);

--
-- Indexes for table `forward_logs`
--
ALTER TABLE `forward_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token_id` (`token_id`),
  ADD KEY `idx_user_phone` (`user_phone`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `media_refresh_log`
--
ALTER TABLE `media_refresh_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `message_id` (`message_id`),
  ADD KEY `idx_message_id` (`message_id`),
  ADD KEY `idx_last_refreshed` (`last_refreshed`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `collection_id` (`collection_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `series`
--
ALTER TABLE `series`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shots`
--
ALTER TABLE `shots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_content_type` (`linked_content_type`),
  ADD KEY `idx_content_id` (`linked_content_id`),
  ADD KEY `idx_admin` (`admin_id`);

--
-- Indexes for table `shot_comments`
--
ALTER TABLE `shot_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shot_id` (`shot_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `shot_likes`
--
ALTER TABLE `shot_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`user_id`,`shot_id`),
  ADD KEY `idx_shot_id` (`shot_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `msisdn` (`msisdn`),
  ADD KEY `idx_verification_token` (`verification_token`),
  ADD KEY `idx_google_id` (`google_id`);

--
-- Indexes for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_shot` (`user_id`,`shot_id`),
  ADD KEY `shot_id` (`shot_id`);

--
-- Indexes for table `video_tokens`
--
ALTER TABLE `video_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_message_id` (`message_id`);

--
-- Indexes for table `whatsapp_forward_logs`
--
ALTER TABLE `whatsapp_forward_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token_id` (`token_id`),
  ADD KEY `idx_user_phone` (`user_phone`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `whatsapp_message_ids`
--
ALTER TABLE `whatsapp_message_ids`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_content` (`content_type`,`content_id`),
  ADD KEY `idx_message_id` (`message_id`);

--
-- Indexes for table `whatsapp_tokens`
--
ALTER TABLE `whatsapp_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_content` (`content_type`,`content_id`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_is_used` (`is_used`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `analytics`
--
ALTER TABLE `analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3885;

--
-- AUTO_INCREMENT for table `collections`
--
ALTER TABLE `collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `episodes`
--
ALTER TABLE `episodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `forward_logs`
--
ALTER TABLE `forward_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media_refresh_log`
--
ALTER TABLE `media_refresh_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `series`
--
ALTER TABLE `series`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `shots`
--
ALTER TABLE `shots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `shot_comments`
--
ALTER TABLE `shot_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `shot_likes`
--
ALTER TABLE `shot_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_favorites`
--
ALTER TABLE `user_favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `video_tokens`
--
ALTER TABLE `video_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `whatsapp_forward_logs`
--
ALTER TABLE `whatsapp_forward_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `whatsapp_message_ids`
--
ALTER TABLE `whatsapp_message_ids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `whatsapp_tokens`
--
ALTER TABLE `whatsapp_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `episodes`
--
ALTER TABLE `episodes`
  ADD CONSTRAINT `episodes_ibfk_1` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `forward_logs`
--
ALTER TABLE `forward_logs`
  ADD CONSTRAINT `forward_logs_ibfk_1` FOREIGN KEY (`token_id`) REFERENCES `video_tokens` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `movies`
--
ALTER TABLE `movies`
  ADD CONSTRAINT `movies_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shots`
--
ALTER TABLE `shots`
  ADD CONSTRAINT `shots_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shot_comments`
--
ALTER TABLE `shot_comments`
  ADD CONSTRAINT `shot_comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shot_comments_ibfk_2` FOREIGN KEY (`shot_id`) REFERENCES `shots` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shot_likes`
--
ALTER TABLE `shot_likes`
  ADD CONSTRAINT `shot_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shot_likes_ibfk_2` FOREIGN KEY (`shot_id`) REFERENCES `shots` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD CONSTRAINT `user_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_favorites_ibfk_2` FOREIGN KEY (`shot_id`) REFERENCES `shots` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `whatsapp_forward_logs`
--
ALTER TABLE `whatsapp_forward_logs`
  ADD CONSTRAINT `whatsapp_forward_logs_ibfk_1` FOREIGN KEY (`token_id`) REFERENCES `whatsapp_tokens` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

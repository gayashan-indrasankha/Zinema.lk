-- Zinema.lk demo catalogue: six open-licensed Blender Foundation / Blender Studio films.
-- Licences and credits are listed in README.md. Run this against an EMPTY database that already
-- has the schema. Do not run it on a database holding a real catalogue.
--
-- Media is not in the repository (uploads/ is git-ignored). Expected files in uploads/demo/:
--   posters: big-buck-bunny.jpg elephants-dream.jpg sintel.jpg sprite-fright.jpg spring.jpg tears-of-steel.jpg
--   video:   spring.mp4 sintel-trailer.mp4
--   shots:   shot-spring-flame.mp4 shot-spring-staff.mp4 shot-spring-mountain.mp4 shot-sintel-dragon.mp4
-- Movie video paths assume the site is served at the /zinema alias.
-- The Sintel entry plays the official trailer; Spring plays the full film.
-- The other four entries have no video file, so they only demonstrate the movie page and token flow.

SET NAMES utf8mb4;

-- This hash does not match any known password. To sign in locally, set your own:
--   php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"
--   UPDATE admins SET password_hash = '<output>' WHERE username = 'demo';
INSERT INTO admins (id, username, password_hash) VALUES
  (1, 'demo', '$2y$10$kW3tqYb8d0mC1vXfJ7nPqeZpL6uR2sT9oG4hN5iV8aD1cE3bF7yWu');

INSERT INTO collections (id, title, description, cover_image, genre) VALUES
  (1, 'Comedy Shorts', 'Light-hearted animated shorts from the Blender open movie projects.', 'uploads/demo/big-buck-bunny.jpg', 'Comedy'),
  (2, 'Fantasy Journeys', 'Quests through strange, beautiful worlds.', 'uploads/demo/spring.jpg', 'Fantasy'),
  (3, 'Science Fiction', 'Machines, memories and alternate futures.', 'uploads/demo/tears-of-steel.jpg', 'Sci-Fi');

-- Ratings stay 0 on purpose: the pages hide the badge rather than show an invented score.
INSERT INTO movies (id, title, release_date, genre, rating, cover_image, video_url, collection_id, language_type) VALUES
  (1, 'Spring', '2019', 'Animation, Fantasy', 0, 'uploads/demo/spring.jpg', '/zinema/uploads/demo/spring.mp4', 2, 'English'),
  (2, 'Sintel', '2010', 'Animation, Fantasy', 0, 'uploads/demo/sintel.jpg', '/zinema/uploads/demo/sintel-trailer.mp4', 2, 'English'),
  (3, 'Sprite Fright', '2021', 'Animation, Comedy', 0, 'uploads/demo/sprite-fright.jpg', NULL, 1, 'English'),
  (4, 'Tears of Steel', '2012', 'Sci-Fi', 0, 'uploads/demo/tears-of-steel.jpg', NULL, 3, 'English'),
  (5, 'Big Buck Bunny', '2008', 'Animation, Comedy', 0, 'uploads/demo/big-buck-bunny.jpg', NULL, 1, 'English'),
  (6, 'Elephants Dream', '2006', 'Animation, Sci-Fi', 0, 'uploads/demo/elephants-dream.jpg', NULL, 3, 'English');

INSERT INTO shots (id, title, description, fb_share_url, shot_video_file, linked_content_type, linked_content_id, admin_id) VALUES
  (1, 'A flame in the dark: Spring', 'A young shepherd finds light where she least expects it.', NULL, 'uploads/demo/shot-spring-flame.mp4', 'movie', 1, 1),
  (2, 'The shepherd and her dog: Spring', 'A journey into the mountains begins.', NULL, 'uploads/demo/shot-spring-staff.mp4', 'movie', 1, 1),
  (3, 'Above the valley: Spring', 'One last look across the peaks.', NULL, 'uploads/demo/shot-spring-mountain.mp4', 'movie', 1, 1),
  (4, 'A dragon in the sky: Sintel', 'A girl, a dragon and a search across the world.', NULL, 'uploads/demo/shot-sintel-dragon.mp4', 'movie', 2, 1);

INSERT INTO site_settings (setting_key, setting_value, description) VALUES
  ('default_bot_phone', '94 7X XXX XXXX', 'Placeholder WhatsApp number for the demo'),
  ('token_expiry_minutes', '10', 'How long tokens remain valid (minutes)'),
  ('rate_limit_per_minute', '10', 'Max token requests per IP per minute'),
  ('rate_limit_per_hour', '50', 'Max token requests per IP per hour'),
  ('site_name', 'Zinema.lk', 'Website name'),
  ('admin_whatsapp', '', 'Admin WhatsApp number for support');

-- Sample bot status so the token page and monitoring dashboard can render without a live bot.
INSERT INTO bot_health_status (bot_id, status, bot_phone, queue_size, disk_usage_mb, memory_usage_mb, uptime_seconds, total_requests_today, successful_forwards_today, failed_forwards_today, last_heartbeat) VALUES
  (1, 'online', '94 7X XXX XXXX', 0, 512, 28.3, 7200, 12, 12, 0, NOW()),
  (2, 'online', '94 7X XXX XXXX', 1, 498, 31.6, 7200, 9, 9, 0, NOW());

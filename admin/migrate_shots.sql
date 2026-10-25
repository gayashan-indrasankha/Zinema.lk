-- Shots Conversion Migration Script
-- Convert shot_video_file from VARCHAR(512) to LONGTEXT
-- Clear all existing shots (Option B - Fresh Start)

-- Step 1: Alter column type to LONGTEXT for long CDN URLs
ALTER TABLE `shots` 
MODIFY COLUMN `shot_video_file` LONGTEXT NOT NULL;

-- Step 2: Clear all existing shots
TRUNCATE TABLE `shots`;

-- Verification query (run separately to check)
-- SHOW COLUMNS FROM shots LIKE 'shot_video_file';
-- SELECT COUNT(*) FROM shots;

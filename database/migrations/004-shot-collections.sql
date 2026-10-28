-- Additive structure repair. Launcher verifies the owned development DB first.
ALTER TABLE shots MODIFY linked_content_type enum('movie','series','collection') NOT NULL;
ALTER TABLE login_attempts ADD INDEX IF NOT EXISTS idx_login_peer_time(ip_address,attempt_time);

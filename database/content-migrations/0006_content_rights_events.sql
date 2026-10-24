CREATE TABLE content_rights_events (
    id VARCHAR(32) NOT NULL PRIMARY KEY,
    record_id VARCHAR(32) NOT NULL,
    record_version INT NOT NULL,
    actor_id VARCHAR(32) NOT NULL,
    action VARCHAR(32) NOT NULL,
    subject_id VARCHAR(32) NOT NULL,
    state VARCHAR(20) NOT NULL,
    occurred_at VARCHAR(20) NOT NULL,
    UNIQUE KEY unique_record_version (record_id, record_version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

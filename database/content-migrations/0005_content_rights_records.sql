CREATE TABLE content_rights_records (
    id VARCHAR(32) NOT NULL PRIMARY KEY,
    version INT NOT NULL,
    state VARCHAR(20) NOT NULL,
    content_type VARCHAR(12) NOT NULL,
    catalogue_id VARCHAR(18) NULL,
    document TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_content_reference (content_type, catalogue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

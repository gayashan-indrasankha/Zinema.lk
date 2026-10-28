-- Additive upgrade for an explicitly verified local development database only.
ALTER TABLE whatsapp_tokens
 ADD COLUMN IF NOT EXISTS claim_hash char(64) DEFAULT NULL,
 ADD COLUMN IF NOT EXISTS claim_owner char(64) DEFAULT NULL,
 ADD COLUMN IF NOT EXISTS claim_state enum('claimed','sending','uncertain','completed') DEFAULT NULL,
 ADD COLUMN IF NOT EXISTS claimed_at timestamp NULL DEFAULT NULL,
 MODIFY expires_at timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Explicit token expiry; never extended by a claim';

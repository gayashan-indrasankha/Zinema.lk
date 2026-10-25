-- Apply only after verifying the owned development database identity.
ALTER TABLE whatsapp_tokens
 ADD COLUMN IF NOT EXISTS claim_revision bigint unsigned NOT NULL DEFAULT 0,
 ADD COLUMN IF NOT EXISTS reconciled_at timestamp NULL DEFAULT NULL;
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

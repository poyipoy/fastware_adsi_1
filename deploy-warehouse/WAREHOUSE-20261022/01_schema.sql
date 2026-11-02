-- Import once in the selected application database. Never use --force.
SET NAMES utf8mb4;
SET SESSION time_zone = '+00:00';
DELIMITER $$
CREATE PROCEDURE warehouse_release_20261022_schema()
SQL SECURITY INVOKER
BEGIN
IF DATABASE() IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Select the application database first'; END IF;
IF VERSION() NOT REGEXP '^8[.]' OR VERSION() LIKE '%MariaDB%' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'This release requires MySQL 8; MariaDB is not verified'; END IF;
IF (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND ENGINE='InnoDB')<>1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Missing InnoDB prerequisite: users'; END IF;
IF (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME IN ('id','npk','role_id','is_active'))<>4 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Prerequisite columns differ: users'; END IF;
IF (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='migrations' AND ENGINE='InnoDB')<>1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Missing InnoDB prerequisite: migrations'; END IF;
IF (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='migrations' AND COLUMN_NAME IN ('id','migration','batch'))<>3 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Prerequisite columns differ: migrations'; END IF;
IF (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='mst_departments' AND ENGINE='InnoDB')<>1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Missing InnoDB prerequisite: mst_departments'; END IF;
IF (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='mst_departments' AND COLUMN_NAME IN ('id','name','is_active'))<>3 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Prerequisite columns differ: mst_departments'; END IF;
IF (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='mst_job_positions' AND ENGINE='InnoDB')<>1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Missing InnoDB prerequisite: mst_job_positions'; END IF;
IF (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='mst_job_positions' AND COLUMN_NAME IN ('id','department_id','is_active'))<>3 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Prerequisite columns differ: mst_job_positions'; END IF;
IF (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_job_positions' AND ENGINE='InnoDB')<>1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Missing InnoDB prerequisite: user_job_positions'; END IF;
IF (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_job_positions' AND COLUMN_NAME IN ('user_id','mst_job_position_id','is_active'))<>3 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Prerequisite columns differ: user_job_positions'; END IF;
IF EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_job_positions' AND COLUMN_NAME='effective_from') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_job_positions' AND COLUMN_NAME='effective_until') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Assignment date columns are incomplete'; END IF;
IF (SELECT CASE WHEN COUNT(*)=1 THEN MIN(id) WHEN SUM(role_id=1)=1 THEN MAX(CASE WHEN role_id=1 THEN id END) ELSE NULL END FROM users WHERE npk=5439 AND is_active=0) IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'NPK 5439 missing, inactive or ambiguous'; END IF;
IF (SELECT CASE WHEN COUNT(*)=1 THEN MIN(id) WHEN SUM(role_id=1)=1 THEN MAX(CASE WHEN role_id=1 THEN id END) ELSE NULL END FROM users WHERE npk=5472 AND is_active=0) IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'NPK 5472 missing, inactive or ambiguous'; END IF;
IF (SELECT CASE WHEN COUNT(*)=1 THEN MIN(id) WHEN SUM(role_id=1)=1 THEN MAX(CASE WHEN role_id=1 THEN id END) ELSE NULL END FROM users WHERE npk=5584 AND is_active=0) IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'NPK 5584 missing, inactive or ambiguous'; END IF;
IF (SELECT CASE WHEN COUNT(*)=1 THEN MIN(id) WHEN SUM(role_id=1)=1 THEN MAX(CASE WHEN role_id=1 THEN id END) ELSE NULL END FROM users WHERE npk=5639 AND is_active=0) IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'NPK 5639 missing, inactive or ambiguous'; END IF;
IF (SELECT CASE WHEN COUNT(*)=1 THEN MIN(id) WHEN SUM(role_id=1)=1 THEN MAX(CASE WHEN role_id=1 THEN id END) ELSE NULL END FROM users WHERE npk=5661 AND is_active=0) IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'NPK 5661 missing, inactive or ambiguous'; END IF;
IF EXISTS(SELECT 1 FROM migrations WHERE migration IN ('2026_08_07_000001_create_mst_wh_consumable_categories_table','2026_08_07_000002_create_mst_wh_consumables_table','2026_08_07_000003_create_mst_wh_user_cards_table','2026_08_07_000004_create_trs_wh_stock_transactions_table','2026_08_07_000005_create_log_wh_verifications_table','2026_08_11_000001_add_verification_permissions_to_mst_wh_user_cards_table','2026_08_18_000001_add_revision_two_inventory_fields_to_mst_wh_consumables_table','2026_08_18_000002_add_revision_two_audit_fields_to_trs_wh_stock_transactions_table','2026_08_18_000003_create_mst_wh_restricted_verifiers_table','2026_08_18_000004_seed_mst_wh_restricted_verifiers','2026_08_19_000001_create_trs_wh_location_shipments_table','2026_08_19_000002_add_location_shipment_id_to_trs_wh_stock_transactions_table','2026_08_19_000003_drop_storage_location_from_mst_wh_consumables_table','2026_08_20_000001_create_trs_wh_stock_ins_table','2026_08_26_000001_add_stock_attention_note_to_mst_wh_consumables_table','2026_08_26_000002_create_wh_transaction_sequences_table','2026_08_26_000003_convert_legacy_location_shipments_to_stock_ins','2026_08_27_155918_add_machine_type_used_to_trs_wh_stock_transactions_table','2026_09_24_000001_create_wh_stock_in_sequences_table','2026_10_06_000001_add_yusuf_to_mst_wh_restricted_verifiers','2026_10_06_000002_create_trs_wh_stock_transaction_machine_usages_table','2026_10_06_000003_drop_machine_type_from_mst_wh_consumables_table','2026_10_06_000004_migrate_legacy_stock_out_machine_usages') GROUP BY migration HAVING COUNT(*)>1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Duplicate Warehouse migration ledger entries; inspect first'; END IF;
IF (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('mst_wh_consumable_categories','mst_wh_consumables','mst_wh_restricted_verifiers','mst_wh_user_cards','trs_wh_stock_transactions','trs_wh_stock_ins','trs_wh_location_shipments','trs_wh_stock_transaction_machine_usages','log_wh_verifications','wh_transaction_sequences','wh_stock_in_sequences'))<>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Warehouse already exists or installation is partial; stop'; END IF;
IF EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME REGEXP '^(mst_wh_|trs_wh_|log_wh_|wh_)') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unexpected Warehouse tables already exist; stop'; END IF;
CREATE TABLE `mst_wh_consumable_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mst_wh_consumable_categories_code_unique` (`code`),
  KEY `mst_wh_consumable_categories_created_by_foreign` (`created_by`),
  KEY `mst_wh_consumable_categories_updated_by_foreign` (`updated_by`),
  KEY `wh_cat_active_name_idx` (`is_active`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `mst_wh_consumables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned DEFAULT NULL,
  `item_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `item_name` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `allow_fraction` tinyint(1) NOT NULL DEFAULT '0',
  `current_stock` decimal(15,3) NOT NULL DEFAULT '0.000',
  `stock_deltamas` decimal(15,3) NOT NULL DEFAULT '0.000',
  `stock_ds8` decimal(15,3) NOT NULL DEFAULT '0.000',
  `stock_used_deltamas` decimal(15,3) NOT NULL DEFAULT '0.000',
  `stock_used_ds8` decimal(15,3) NOT NULL DEFAULT '0.000',
  `minimum_stock` decimal(15,3) NOT NULL DEFAULT '0.000',
  `maximum_stock` decimal(15,3) DEFAULT NULL,
  `stock_attention_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mst_wh_consumables_item_code_unique` (`item_code`),
  UNIQUE KEY `mst_wh_consumables_barcode_unique` (`barcode`),
  KEY `mst_wh_consumables_created_by_foreign` (`created_by`),
  KEY `mst_wh_consumables_updated_by_foreign` (`updated_by`),
  KEY `wh_item_category_active_idx` (`category_id`,`is_active`),
  KEY `wh_item_active_stock_idx` (`is_active`,`current_stock`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `mst_wh_restricted_verifiers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `scope` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wh_restricted_verifier_user_scope_unique` (`user_id`,`scope`),
  KEY `wh_restricted_verifier_scope_active_idx` (`scope`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `mst_wh_user_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `card_code` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `can_verify_stock_in` tinyint(1) NOT NULL DEFAULT '0',
  `can_verify_stock_out` tinyint(1) NOT NULL DEFAULT '0',
  `registered_by` bigint unsigned DEFAULT NULL,
  `registered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mst_wh_user_cards_card_code_unique` (`card_code`),
  KEY `mst_wh_user_cards_registered_by_foreign` (`registered_by`),
  KEY `wh_card_user_active_idx` (`user_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `trs_wh_stock_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_number` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `idempotency_key` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `operation_key` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_condition` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NEW',
  `consumable_id` bigint unsigned NOT NULL,
  `machine_type_used` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `stock_before` decimal(15,3) NOT NULL,
  `stock_after` decimal(15,3) NOT NULL,
  `verified_user_id` bigint unsigned NOT NULL,
  `verified_user_name` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `verified_user_npk` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified_user_section` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purpose` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usage_location` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_location` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_location` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reversal_of_id` bigint unsigned DEFAULT NULL,
  `location_shipment_id` bigint unsigned DEFAULT NULL,
  `transaction_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `stock_in_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trs_wh_stock_transactions_transaction_number_unique` (`transaction_number`),
  UNIQUE KEY `trs_wh_stock_transactions_idempotency_key_unique` (`idempotency_key`),
  UNIQUE KEY `wh_trs_reversal_unique` (`reversal_of_id`),
  KEY `trs_wh_stock_transactions_created_by_foreign` (`created_by`),
  KEY `wh_trs_type_at_idx` (`transaction_type`,`transaction_at`),
  KEY `wh_trs_item_at_idx` (`consumable_id`,`transaction_at`),
  KEY `wh_trs_user_at_idx` (`verified_user_id`,`transaction_at`),
  KEY `wh_trs_section_at_idx` (`verified_user_section`,`transaction_at`),
  KEY `wh_trs_operation_idx` (`operation_key`),
  KEY `wh_trs_condition_type_at_idx` (`item_condition`,`transaction_type`,`transaction_at`),
  KEY `wh_trs_location_shipment_idx` (`location_shipment_id`),
  KEY `wh_trs_stock_in_idx` (`stock_in_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `trs_wh_stock_ins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_in_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `creation_idempotency_key` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `validation_idempotency_key` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancellation_idempotency_key` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `validation_result` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consumable_id` bigint unsigned NOT NULL,
  `item_condition` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity_expected` decimal(15,3) NOT NULL,
  `quantity_received` decimal(15,3) DEFAULT NULL,
  `received_consumable_id` bigint unsigned DEFAULT NULL,
  `received_condition` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination_location` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_location` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `validation_notes` text COLLATE utf8mb4_unicode_ci,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `creator_npk_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creator_name_snapshot` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `validator_user_id` bigint unsigned DEFAULT NULL,
  `validator_npk_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `validator_name_snapshot` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelled_by_user_id` bigint unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `stock_transaction_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trs_wh_stock_ins_stock_in_number_unique` (`stock_in_number`),
  UNIQUE KEY `trs_wh_stock_ins_creation_idempotency_key_unique` (`creation_idempotency_key`),
  UNIQUE KEY `trs_wh_stock_ins_validation_idempotency_key_unique` (`validation_idempotency_key`),
  UNIQUE KEY `trs_wh_stock_ins_cancellation_idempotency_key_unique` (`cancellation_idempotency_key`),
  UNIQUE KEY `wh_stock_in_transaction_unique` (`stock_transaction_id`),
  KEY `trs_wh_stock_ins_received_consumable_id_foreign` (`received_consumable_id`),
  KEY `trs_wh_stock_ins_cancelled_by_user_id_foreign` (`cancelled_by_user_id`),
  KEY `wh_stock_in_status_idx` (`status`),
  KEY `wh_stock_in_item_status_idx` (`consumable_id`,`status`),
  KEY `wh_stock_in_source_status_idx` (`source_location`,`status`),
  KEY `wh_stock_in_destination_idx` (`destination_location`),
  KEY `wh_stock_in_creator_idx` (`created_by`),
  KEY `wh_stock_in_validator_idx` (`validator_user_id`),
  KEY `wh_stock_in_transaction_idx` (`stock_transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `trs_wh_location_shipments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `consumable_id` bigint unsigned NOT NULL,
  `item_condition` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity_sent` decimal(15,3) NOT NULL,
  `from_location` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_location` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_by_user_id` bigint unsigned NOT NULL,
  `sender_npk_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_name_snapshot` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_notes` text COLLATE utf8mb4_unicode_ci,
  `sent_at` timestamp NOT NULL,
  `validation_actor_user_id` bigint unsigned DEFAULT NULL,
  `validator_user_id` bigint unsigned DEFAULT NULL,
  `validator_npk_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `validator_name_snapshot` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `received_quantity` decimal(15,3) DEFAULT NULL,
  `received_condition` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `validation_notes` text COLLATE utf8mb4_unicode_ci,
  `validated_at` timestamp NULL DEFAULT NULL,
  `stock_transaction_id` bigint unsigned DEFAULT NULL,
  `migrated_stock_in_id` bigint unsigned DEFAULT NULL,
  `migration_original_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelled_by_user_id` bigint unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `creation_idempotency_key` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `validation_idempotency_key` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancellation_idempotency_key` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trs_wh_location_shipments_shipment_number_unique` (`shipment_number`),
  UNIQUE KEY `trs_wh_location_shipments_creation_idempotency_key_unique` (`creation_idempotency_key`),
  UNIQUE KEY `trs_wh_location_shipments_validation_idempotency_key_unique` (`validation_idempotency_key`),
  UNIQUE KEY `trs_wh_location_shipments_cancellation_idempotency_key_unique` (`cancellation_idempotency_key`),
  UNIQUE KEY `wh_ship_transaction_unique` (`stock_transaction_id`),
  UNIQUE KEY `wh_ship_migrated_stock_in_unique` (`migrated_stock_in_id`),
  KEY `trs_wh_location_shipments_validation_actor_user_id_foreign` (`validation_actor_user_id`),
  KEY `trs_wh_location_shipments_cancelled_by_user_id_foreign` (`cancelled_by_user_id`),
  KEY `wh_ship_status_idx` (`status`),
  KEY `wh_ship_item_status_idx` (`consumable_id`,`status`),
  KEY `wh_ship_from_status_idx` (`from_location`,`status`),
  KEY `wh_ship_to_status_idx` (`to_location`,`status`),
  KEY `wh_ship_sender_status_idx` (`sent_by_user_id`,`status`),
  KEY `wh_ship_validator_idx` (`validator_user_id`),
  KEY `wh_ship_sent_at_idx` (`sent_at`),
  KEY `wh_ship_transaction_idx` (`stock_transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `trs_wh_stock_transaction_machine_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned NOT NULL,
  `machine_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wh_trx_machine_usage_unique` (`transaction_id`,`machine_code`),
  KEY `wh_trx_machine_usage_code_idx` (`machine_code`),
  KEY `wh_trx_machine_usage_trx_idx` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `log_wh_verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scanned_code_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `transaction_id` bigint unsigned DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failure_reason` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wh_log_status_at_idx` (`status`,`verified_at`),
  KEY `wh_log_user_at_idx` (`user_id`,`verified_at`),
  KEY `wh_log_transaction_idx` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `wh_transaction_sequences` (
  `year` smallint unsigned NOT NULL,
  `last_number` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `wh_stock_in_sequences` (
  `year` smallint unsigned NOT NULL,
  `last_number` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `mst_wh_consumable_categories` ADD CONSTRAINT `mst_wh_consumable_categories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `mst_wh_consumable_categories` ADD CONSTRAINT `mst_wh_consumable_categories_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `mst_wh_consumables` ADD CONSTRAINT `mst_wh_consumables_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `mst_wh_consumable_categories` (`id`) ON DELETE SET NULL;
ALTER TABLE `mst_wh_consumables` ADD CONSTRAINT `mst_wh_consumables_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `mst_wh_consumables` ADD CONSTRAINT `mst_wh_consumables_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `mst_wh_restricted_verifiers` ADD CONSTRAINT `mst_wh_restricted_verifiers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;
ALTER TABLE `mst_wh_user_cards` ADD CONSTRAINT `mst_wh_user_cards_registered_by_foreign` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `mst_wh_user_cards` ADD CONSTRAINT `mst_wh_user_cards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;
ALTER TABLE `trs_wh_stock_transactions` ADD CONSTRAINT `trs_wh_stock_transactions_consumable_id_foreign` FOREIGN KEY (`consumable_id`) REFERENCES `mst_wh_consumables` (`id`) ON DELETE RESTRICT;
ALTER TABLE `trs_wh_stock_transactions` ADD CONSTRAINT `trs_wh_stock_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `trs_wh_stock_transactions` ADD CONSTRAINT `trs_wh_stock_transactions_location_shipment_id_foreign` FOREIGN KEY (`location_shipment_id`) REFERENCES `trs_wh_location_shipments` (`id`) ON DELETE SET NULL;
ALTER TABLE `trs_wh_stock_transactions` ADD CONSTRAINT `trs_wh_stock_transactions_reversal_of_id_foreign` FOREIGN KEY (`reversal_of_id`) REFERENCES `trs_wh_stock_transactions` (`id`) ON DELETE RESTRICT;
ALTER TABLE `trs_wh_stock_transactions` ADD CONSTRAINT `trs_wh_stock_transactions_stock_in_id_foreign` FOREIGN KEY (`stock_in_id`) REFERENCES `trs_wh_stock_ins` (`id`) ON DELETE SET NULL;
ALTER TABLE `trs_wh_stock_transactions` ADD CONSTRAINT `trs_wh_stock_transactions_verified_user_id_foreign` FOREIGN KEY (`verified_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;
ALTER TABLE `trs_wh_stock_ins` ADD CONSTRAINT `trs_wh_stock_ins_cancelled_by_user_id_foreign` FOREIGN KEY (`cancelled_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `trs_wh_stock_ins` ADD CONSTRAINT `trs_wh_stock_ins_consumable_id_foreign` FOREIGN KEY (`consumable_id`) REFERENCES `mst_wh_consumables` (`id`) ON DELETE RESTRICT;
ALTER TABLE `trs_wh_stock_ins` ADD CONSTRAINT `trs_wh_stock_ins_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;
ALTER TABLE `trs_wh_stock_ins` ADD CONSTRAINT `trs_wh_stock_ins_received_consumable_id_foreign` FOREIGN KEY (`received_consumable_id`) REFERENCES `mst_wh_consumables` (`id`) ON DELETE RESTRICT;
ALTER TABLE `trs_wh_stock_ins` ADD CONSTRAINT `trs_wh_stock_ins_validator_user_id_foreign` FOREIGN KEY (`validator_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `trs_wh_stock_ins` ADD CONSTRAINT `wh_stock_in_transaction_fk` FOREIGN KEY (`stock_transaction_id`) REFERENCES `trs_wh_stock_transactions` (`id`) ON DELETE SET NULL;
ALTER TABLE `trs_wh_location_shipments` ADD CONSTRAINT `trs_wh_location_shipments_cancelled_by_user_id_foreign` FOREIGN KEY (`cancelled_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `trs_wh_location_shipments` ADD CONSTRAINT `trs_wh_location_shipments_consumable_id_foreign` FOREIGN KEY (`consumable_id`) REFERENCES `mst_wh_consumables` (`id`) ON DELETE RESTRICT;
ALTER TABLE `trs_wh_location_shipments` ADD CONSTRAINT `trs_wh_location_shipments_sent_by_user_id_foreign` FOREIGN KEY (`sent_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;
ALTER TABLE `trs_wh_location_shipments` ADD CONSTRAINT `trs_wh_location_shipments_validation_actor_user_id_foreign` FOREIGN KEY (`validation_actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `trs_wh_location_shipments` ADD CONSTRAINT `trs_wh_location_shipments_validator_user_id_foreign` FOREIGN KEY (`validator_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `trs_wh_location_shipments` ADD CONSTRAINT `wh_ship_migrated_stock_in_fk` FOREIGN KEY (`migrated_stock_in_id`) REFERENCES `trs_wh_stock_ins` (`id`) ON DELETE SET NULL;
ALTER TABLE `trs_wh_location_shipments` ADD CONSTRAINT `wh_ship_transaction_fk` FOREIGN KEY (`stock_transaction_id`) REFERENCES `trs_wh_stock_transactions` (`id`) ON DELETE SET NULL;
ALTER TABLE `trs_wh_stock_transaction_machine_usages` ADD CONSTRAINT `trs_wh_stock_transaction_machine_usages_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `trs_wh_stock_transactions` (`id`) ON DELETE CASCADE;
ALTER TABLE `log_wh_verifications` ADD CONSTRAINT `log_wh_verifications_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `trs_wh_stock_transactions` (`id`) ON DELETE SET NULL;
ALTER TABLE `log_wh_verifications` ADD CONSTRAINT `log_wh_verifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
SELECT 'SCHEMA_OK: 11 tables created; now import 02_master_access.sql' AS result;

END$$
DELIMITER ;
CALL warehouse_release_20261022_schema();

-- Warehouse Consumable Revisi Tahap 2 — complete bootstrap for a NEW installation.
-- Target: MySQL 8.0+ / Laravel application database.
-- IMPORTANT: do not run this file to upgrade an existing Warehouse installation.
-- Existing installations must run only the reviewed Warehouse migrations, including
-- 2026_08_20_000001_create_trs_wh_stock_ins_table.php for the final Stock In flow.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fail before persistent DDL unless each restricted verifier NPK resolves exactly once.
CREATE TEMPORARY TABLE `_wh_revision2_verifier_preflight` (
    `identity_name` VARCHAR(80) NOT NULL,
    `match_count` INT NOT NULL,
    CONSTRAINT `wh_revision2_verifier_exactly_one` CHECK (`match_count` = 1)
);
INSERT INTO `_wh_revision2_verifier_preflight`
SELECT 'NPK 5639', COUNT(*) FROM `users`
WHERE `npk` = 5639 AND `is_active` = 0;
INSERT INTO `_wh_revision2_verifier_preflight`
SELECT 'NPK 5439', COUNT(*) FROM `users`
WHERE `npk` = 5439 AND `is_active` = 0;
DROP TEMPORARY TABLE `_wh_revision2_verifier_preflight`;

CREATE TABLE IF NOT EXISTS `mst_wh_consumable_categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(30) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED NULL,
    `updated_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `mst_wh_consumable_categories_code_unique` (`code`),
    KEY `wh_cat_active_name_idx` (`is_active`, `name`),
    CONSTRAINT `mst_wh_consumable_categories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `mst_wh_consumable_categories_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mst_wh_consumables` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` BIGINT UNSIGNED NULL,
    `item_code` VARCHAR(50) NOT NULL,
    `barcode` VARCHAR(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    `item_name` VARCHAR(180) NOT NULL,
    `unit` VARCHAR(30) NOT NULL,
    `allow_fraction` TINYINT(1) NOT NULL DEFAULT 0,
    `current_stock` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    `stock_deltamas` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    `stock_ds8` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    `stock_used_deltamas` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    `stock_used_ds8` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    `minimum_stock` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    `maximum_stock` DECIMAL(15,3) NULL,
    `machine_type` VARCHAR(120) NULL,
    `description` TEXT NULL,
    `photo_path` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED NULL,
    `updated_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `mst_wh_consumables_item_code_unique` (`item_code`),
    UNIQUE KEY `mst_wh_consumables_barcode_unique` (`barcode`),
    KEY `wh_item_category_active_idx` (`category_id`, `is_active`),
    KEY `wh_item_active_stock_idx` (`is_active`, `current_stock`),
    CONSTRAINT `mst_wh_consumables_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `mst_wh_consumable_categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `mst_wh_consumables_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `mst_wh_consumables_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Legacy compatibility only. Runtime verification uses users.npk directly.
CREATE TABLE IF NOT EXISTS `mst_wh_user_cards` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `card_code` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `can_verify_stock_in` TINYINT(1) NOT NULL DEFAULT 0,
    `can_verify_stock_out` TINYINT(1) NOT NULL DEFAULT 0,
    `registered_by` BIGINT UNSIGNED NULL,
    `registered_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `mst_wh_user_cards_card_code_unique` (`card_code`),
    KEY `wh_card_user_active_idx` (`user_id`, `is_active`),
    CONSTRAINT `mst_wh_user_cards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `mst_wh_user_cards_registered_by_foreign` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trs_wh_stock_transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_number` VARCHAR(40) NOT NULL,
    `idempotency_key` CHAR(36) NOT NULL,
    `operation_key` CHAR(36) NULL,
    `transaction_type` VARCHAR(10) NOT NULL,
    `item_condition` VARCHAR(10) NOT NULL DEFAULT 'NEW',
    `consumable_id` BIGINT UNSIGNED NOT NULL,
    `quantity` DECIMAL(15,3) NOT NULL,
    `stock_before` DECIMAL(15,3) NOT NULL,
    `stock_after` DECIMAL(15,3) NOT NULL,
    `verified_user_id` BIGINT UNSIGNED NOT NULL,
    `verified_user_name` VARCHAR(180) NOT NULL,
    `verified_user_npk` VARCHAR(80) NULL,
    `verified_user_section` VARCHAR(120) NULL,
    `reference_number` VARCHAR(120) NULL,
    `purpose` VARCHAR(255) NULL,
    `usage_location` VARCHAR(180) NULL,
    `from_location` VARCHAR(120) NULL,
    `to_location` VARCHAR(120) NULL,
    `notes` TEXT NULL,
    `reversal_of_id` BIGINT UNSIGNED NULL,
    `location_shipment_id` BIGINT UNSIGNED NULL,
    `stock_in_id` BIGINT UNSIGNED NULL,
    `transaction_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `trs_wh_stock_transactions_transaction_number_unique` (`transaction_number`),
    UNIQUE KEY `trs_wh_stock_transactions_idempotency_key_unique` (`idempotency_key`),
    UNIQUE KEY `wh_trs_reversal_unique` (`reversal_of_id`),
    KEY `wh_trs_type_at_idx` (`transaction_type`, `transaction_at`),
    KEY `wh_trs_item_at_idx` (`consumable_id`, `transaction_at`),
    KEY `wh_trs_user_at_idx` (`verified_user_id`, `transaction_at`),
    KEY `wh_trs_section_at_idx` (`verified_user_section`, `transaction_at`),
    KEY `wh_trs_operation_idx` (`operation_key`),
    KEY `wh_trs_condition_type_at_idx` (`item_condition`, `transaction_type`, `transaction_at`),
    KEY `wh_trs_location_shipment_idx` (`location_shipment_id`),
    KEY `wh_trs_stock_in_idx` (`stock_in_id`),
    CONSTRAINT `trs_wh_stock_transactions_consumable_id_foreign` FOREIGN KEY (`consumable_id`) REFERENCES `mst_wh_consumables` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `trs_wh_stock_transactions_verified_user_id_foreign` FOREIGN KEY (`verified_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `trs_wh_stock_transactions_reversal_of_id_foreign` FOREIGN KEY (`reversal_of_id`) REFERENCES `trs_wh_stock_transactions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `trs_wh_stock_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trs_wh_location_shipments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `shipment_number` VARCHAR(50) NOT NULL,
    `consumable_id` BIGINT UNSIGNED NOT NULL,
    `item_condition` VARCHAR(16) NOT NULL,
    `quantity_sent` DECIMAL(15,3) NOT NULL,
    `from_location` VARCHAR(120) NOT NULL,
    `to_location` VARCHAR(120) NOT NULL,
    `status` VARCHAR(32) NOT NULL,
    `sent_by_user_id` BIGINT UNSIGNED NOT NULL,
    `sender_npk_snapshot` VARCHAR(100) NULL,
    `sender_name_snapshot` VARCHAR(180) NULL,
    `sender_notes` TEXT NULL,
    `sent_at` TIMESTAMP NOT NULL,
    `validation_actor_user_id` BIGINT UNSIGNED NULL,
    `validator_user_id` BIGINT UNSIGNED NULL,
    `validator_npk_snapshot` VARCHAR(100) NULL,
    `validator_name_snapshot` VARCHAR(180) NULL,
    `received_quantity` DECIMAL(15,3) NULL,
    `received_condition` VARCHAR(16) NULL,
    `validation_notes` TEXT NULL,
    `validated_at` TIMESTAMP NULL,
    `stock_transaction_id` BIGINT UNSIGNED NULL,
    `cancelled_by_user_id` BIGINT UNSIGNED NULL,
    `cancelled_at` TIMESTAMP NULL,
    `cancellation_reason` TEXT NULL,
    `creation_idempotency_key` CHAR(36) NOT NULL,
    `validation_idempotency_key` CHAR(36) NULL,
    `cancellation_idempotency_key` CHAR(36) NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `trs_wh_location_shipments_shipment_number_unique` (`shipment_number`),
    UNIQUE KEY `trs_wh_location_shipments_creation_idempotency_key_unique` (`creation_idempotency_key`),
    UNIQUE KEY `trs_wh_location_shipments_validation_idempotency_key_unique` (`validation_idempotency_key`),
    UNIQUE KEY `trs_wh_location_shipments_cancellation_idempotency_key_unique` (`cancellation_idempotency_key`),
    KEY `wh_ship_status_idx` (`status`),
    KEY `wh_ship_item_status_idx` (`consumable_id`, `status`),
    KEY `wh_ship_from_status_idx` (`from_location`, `status`),
    KEY `wh_ship_to_status_idx` (`to_location`, `status`),
    KEY `wh_ship_sender_status_idx` (`sent_by_user_id`, `status`),
    KEY `wh_ship_validator_idx` (`validator_user_id`),
    KEY `wh_ship_sent_at_idx` (`sent_at`),
    KEY `wh_ship_transaction_idx` (`stock_transaction_id`),
    CONSTRAINT `trs_wh_location_shipments_consumable_id_foreign` FOREIGN KEY (`consumable_id`) REFERENCES `mst_wh_consumables` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `trs_wh_location_shipments_sent_by_user_id_foreign` FOREIGN KEY (`sent_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `trs_wh_location_shipments_validation_actor_user_id_foreign` FOREIGN KEY (`validation_actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `trs_wh_location_shipments_validator_user_id_foreign` FOREIGN KEY (`validator_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `trs_wh_location_shipments_cancelled_by_user_id_foreign` FOREIGN KEY (`cancelled_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `trs_wh_stock_transactions`
    ADD CONSTRAINT `wh_trs_location_shipment_fk` FOREIGN KEY (`location_shipment_id`) REFERENCES `trs_wh_location_shipments` (`id`) ON DELETE SET NULL;
ALTER TABLE `trs_wh_location_shipments`
    ADD CONSTRAINT `wh_ship_transaction_fk` FOREIGN KEY (`stock_transaction_id`) REFERENCES `trs_wh_stock_transactions` (`id`) ON DELETE SET NULL,
    ADD UNIQUE KEY `wh_ship_transaction_unique` (`stock_transaction_id`);

-- Final Stock In lifecycle. Creation is pending-only; the stock transaction link
-- is populated only after restricted validation succeeds.
CREATE TABLE IF NOT EXISTS `trs_wh_stock_ins` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `stock_in_number` VARCHAR(50) NOT NULL,
    `creation_idempotency_key` CHAR(36) NOT NULL,
    `validation_idempotency_key` CHAR(36) NULL,
    `cancellation_idempotency_key` CHAR(36) NULL,
    `status` VARCHAR(32) NOT NULL,
    `validation_result` VARCHAR(32) NULL,
    `consumable_id` BIGINT UNSIGNED NOT NULL,
    `item_condition` VARCHAR(16) NOT NULL,
    `quantity_expected` DECIMAL(15,3) NOT NULL,
    `quantity_received` DECIMAL(15,3) NULL,
    `received_consumable_id` BIGINT UNSIGNED NULL,
    `received_condition` VARCHAR(16) NULL,
    `destination_location` VARCHAR(120) NOT NULL,
    `source_location` VARCHAR(120) NULL,
    `notes` TEXT NULL,
    `validation_notes` TEXT NULL,
    `cancellation_reason` TEXT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `creator_npk_snapshot` VARCHAR(100) NULL,
    `creator_name_snapshot` VARCHAR(180) NULL,
    `validated_at` TIMESTAMP NULL,
    `validator_user_id` BIGINT UNSIGNED NULL,
    `validator_npk_snapshot` VARCHAR(100) NULL,
    `validator_name_snapshot` VARCHAR(180) NULL,
    `cancelled_by_user_id` BIGINT UNSIGNED NULL,
    `cancelled_at` TIMESTAMP NULL,
    `stock_transaction_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `trs_wh_stock_ins_stock_in_number_unique` (`stock_in_number`),
    UNIQUE KEY `trs_wh_stock_ins_creation_idempotency_key_unique` (`creation_idempotency_key`),
    UNIQUE KEY `trs_wh_stock_ins_validation_idempotency_key_unique` (`validation_idempotency_key`),
    UNIQUE KEY `trs_wh_stock_ins_cancellation_idempotency_key_unique` (`cancellation_idempotency_key`),
    UNIQUE KEY `wh_stock_in_transaction_unique` (`stock_transaction_id`),
    KEY `wh_stock_in_status_idx` (`status`),
    KEY `wh_stock_in_item_status_idx` (`consumable_id`, `status`),
    KEY `wh_stock_in_source_status_idx` (`source_location`, `status`),
    KEY `wh_stock_in_destination_idx` (`destination_location`),
    KEY `wh_stock_in_creator_idx` (`created_by`),
    KEY `wh_stock_in_validator_idx` (`validator_user_id`),
    KEY `wh_stock_in_transaction_idx` (`stock_transaction_id`),
    CONSTRAINT `trs_wh_stock_ins_consumable_id_foreign` FOREIGN KEY (`consumable_id`) REFERENCES `mst_wh_consumables` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `trs_wh_stock_ins_received_consumable_id_foreign` FOREIGN KEY (`received_consumable_id`) REFERENCES `mst_wh_consumables` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `trs_wh_stock_ins_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `trs_wh_stock_ins_validator_user_id_foreign` FOREIGN KEY (`validator_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `trs_wh_stock_ins_cancelled_by_user_id_foreign` FOREIGN KEY (`cancelled_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `wh_stock_in_transaction_fk` FOREIGN KEY (`stock_transaction_id`) REFERENCES `trs_wh_stock_transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `trs_wh_stock_transactions`
    ADD CONSTRAINT `wh_trs_stock_in_fk` FOREIGN KEY (`stock_in_id`) REFERENCES `trs_wh_stock_ins` (`id`) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS `log_wh_verifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `scanned_code_hash` CHAR(64) NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `transaction_id` BIGINT UNSIGNED NULL,
    `status` VARCHAR(20) NOT NULL,
    `failure_reason` VARCHAR(120) NULL,
    `verified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `wh_log_status_at_idx` (`status`, `verified_at`),
    KEY `wh_log_user_at_idx` (`user_id`, `verified_at`),
    KEY `wh_log_transaction_idx` (`transaction_id`),
    CONSTRAINT `log_wh_verifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `log_wh_verifications_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `trs_wh_stock_transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mst_wh_restricted_verifiers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `scope` VARCHAR(30) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `wh_restricted_verifier_user_scope_unique` (`user_id`, `scope`),
    KEY `wh_restricted_verifier_scope_active_idx` (`scope`, `is_active`),
    CONSTRAINT `mst_wh_restricted_verifiers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Approved master identities are insert-only and start at zero stock.
INSERT INTO `mst_wh_consumables` (`category_id`, `item_code`, `barcode`, `item_name`, `unit`, `allow_fraction`, `current_stock`, `stock_deltamas`, `stock_ds8`, `stock_used_deltamas`, `stock_used_ds8`, `minimum_stock`, `maximum_stock`, `machine_type`, `description`, `photo_path`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`)
SELECT NULL, 'TFHINSR-000000008', 'TFHINSR-000000008', 'Insert Widia HNPJ0704ANSNGD WS40PM', 'pcs', 0, 0.000, 0.000, 0.000, 0.000, 0.000, 0.000, NULL, NULL, NULL, 1, NULL, NULL, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mst_wh_consumables` WHERE `item_code` = 'TFHINSR-000000008' OR `barcode` = 'TFHINSR-000000008');
INSERT INTO `mst_wh_consumables` (`category_id`, `item_code`, `barcode`, `item_name`, `unit`, `allow_fraction`, `current_stock`, `stock_deltamas`, `stock_ds8`, `stock_used_deltamas`, `stock_used_ds8`, `minimum_stock`, `maximum_stock`, `machine_type`, `description`, `photo_path`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`)
SELECT NULL, 'TFHINSR-000000005', 'TFHINSR-000000005', 'Insert Pramet HNGX 0906ANSN-M M9315', 'pcs', 0, 0.000, 0.000, 0.000, 0.000, 0.000, 0.000, NULL, NULL, NULL, 1, NULL, NULL, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mst_wh_consumables` WHERE `item_code` = 'TFHINSR-000000005' OR `barcode` = 'TFHINSR-000000005');
INSERT INTO `mst_wh_consumables` (`category_id`, `item_code`, `barcode`, `item_name`, `unit`, `allow_fraction`, `current_stock`, `stock_deltamas`, `stock_ds8`, `stock_used_deltamas`, `stock_used_ds8`, `minimum_stock`, `maximum_stock`, `machine_type`, `description`, `photo_path`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`)
SELECT NULL, 'TFHINSR-000000066', 'TFHINSR-000000066', 'Insert Moldino SEK53TN-C9 GX2140', 'pcs', 0, 0.000, 0.000, 0.000, 0.000, 0.000, 0.000, NULL, NULL, NULL, 1, NULL, NULL, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mst_wh_consumables` WHERE `item_code` = 'TFHINSR-000000066' OR `barcode` = 'TFHINSR-000000066');
INSERT INTO `mst_wh_consumables` (`category_id`, `item_code`, `barcode`, `item_name`, `unit`, `allow_fraction`, `current_stock`, `stock_deltamas`, `stock_ds8`, `stock_used_deltamas`, `stock_used_ds8`, `minimum_stock`, `maximum_stock`, `machine_type`, `description`, `photo_path`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`)
SELECT NULL, 'TFHINSR-000000004', 'TFHINSR-000000004', 'Insert Sumitomo SDEN1203AESN', 'pcs', 0, 0.000, 0.000, 0.000, 0.000, 0.000, 0.000, NULL, NULL, NULL, 1, NULL, NULL, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mst_wh_consumables` WHERE `item_code` = 'TFHINSR-000000004' OR `barcode` = 'TFHINSR-000000004');

INSERT INTO `mst_wh_restricted_verifiers` (`user_id`, `scope`, `is_active`, `created_at`, `updated_at`)
SELECT `id`, 'ALL', 1, NOW(), NOW() FROM `users`
WHERE `npk` = 5639 AND `is_active` = 0
  AND NOT EXISTS (SELECT 1 FROM `mst_wh_restricted_verifiers` WHERE `user_id` = `users`.`id` AND `scope` = 'ALL');
INSERT INTO `mst_wh_restricted_verifiers` (`user_id`, `scope`, `is_active`, `created_at`, `updated_at`)
SELECT `id`, 'ALL', 1, NOW(), NOW() FROM `users`
WHERE `npk` = 5439 AND `is_active` = 0
  AND NOT EXISTS (SELECT 1 FROM `mst_wh_restricted_verifiers` WHERE `user_id` = `users`.`id` AND `scope` = 'ALL');

-- Register the complete Warehouse bootstrap so a later blanket migrate does not recreate it.
SET @warehouse_migration_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_08_07_000001_create_mst_wh_consumable_categories_table', @warehouse_migration_batch WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_07_000001_create_mst_wh_consumable_categories_table');
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_08_07_000002_create_mst_wh_consumables_table', @warehouse_migration_batch WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_07_000002_create_mst_wh_consumables_table');
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_08_07_000003_create_mst_wh_user_cards_table', @warehouse_migration_batch WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_07_000003_create_mst_wh_user_cards_table');
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_08_07_000004_create_trs_wh_stock_transactions_table', @warehouse_migration_batch WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_07_000004_create_trs_wh_stock_transactions_table');
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_08_07_000005_create_log_wh_verifications_table', @warehouse_migration_batch WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_07_000005_create_log_wh_verifications_table');
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_08_11_000001_add_verification_permissions_to_mst_wh_user_cards_table', @warehouse_migration_batch WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_11_000001_add_verification_permissions_to_mst_wh_user_cards_table');
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_08_18_000001_add_revision_two_inventory_fields_to_mst_wh_consumables_table', @warehouse_migration_batch WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_18_000001_add_revision_two_inventory_fields_to_mst_wh_consumables_table');
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_08_18_000002_add_revision_two_audit_fields_to_trs_wh_stock_transactions_table', @warehouse_migration_batch WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_18_000002_add_revision_two_audit_fields_to_trs_wh_stock_transactions_table');
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_08_18_000003_create_mst_wh_restricted_verifiers_table', @warehouse_migration_batch WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_18_000003_create_mst_wh_restricted_verifiers_table');
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_08_18_000004_seed_mst_wh_restricted_verifiers', @warehouse_migration_batch WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_18_000004_seed_mst_wh_restricted_verifiers');
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_08_19_000001_create_trs_wh_location_shipments_table', @warehouse_migration_batch WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_19_000001_create_trs_wh_location_shipments_table');
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_08_19_000002_add_location_shipment_id_to_trs_wh_stock_transactions_table', @warehouse_migration_batch WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_19_000002_add_location_shipment_id_to_trs_wh_stock_transactions_table');
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_08_19_000003_drop_storage_location_from_mst_wh_consumables_table', @warehouse_migration_batch WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_19_000003_drop_storage_location_from_mst_wh_consumables_table');
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_08_20_000001_create_trs_wh_stock_ins_table', @warehouse_migration_batch WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_20_000001_create_trs_wh_stock_ins_table');

-- Read-only post-bootstrap verification. Every mismatch count must be zero.
SELECT 'mst_wh_consumable_categories' AS `table_name`, COUNT(*) AS `row_count` FROM `mst_wh_consumable_categories`
UNION ALL SELECT 'mst_wh_consumables', COUNT(*) FROM `mst_wh_consumables`
UNION ALL SELECT 'mst_wh_user_cards (legacy)', COUNT(*) FROM `mst_wh_user_cards`
UNION ALL SELECT 'trs_wh_stock_transactions', COUNT(*) FROM `trs_wh_stock_transactions`
UNION ALL SELECT 'trs_wh_location_shipments', COUNT(*) FROM `trs_wh_location_shipments`
UNION ALL SELECT 'trs_wh_stock_ins', COUNT(*) FROM `trs_wh_stock_ins`
UNION ALL SELECT 'log_wh_verifications', COUNT(*) FROM `log_wh_verifications`
UNION ALL SELECT 'mst_wh_restricted_verifiers', COUNT(*) FROM `mst_wh_restricted_verifiers`;
SELECT COUNT(*) AS `total_stock_mismatch` FROM `mst_wh_consumables` WHERE `current_stock` <> (`stock_deltamas` + `stock_ds8`);
SELECT COUNT(*) AS `condition_balance_mismatch` FROM `mst_wh_consumables`
WHERE `stock_used_deltamas` < 0 OR `stock_used_ds8` < 0 OR `stock_used_deltamas` > `stock_deltamas` OR `stock_used_ds8` > `stock_ds8`;
SELECT `u`.`name`, `u`.`npk`, `rv`.`scope`, `rv`.`is_active`
FROM `mst_wh_restricted_verifiers` AS `rv` JOIN `users` AS `u` ON `u`.`id` = `rv`.`user_id`
ORDER BY `u`.`npk`;

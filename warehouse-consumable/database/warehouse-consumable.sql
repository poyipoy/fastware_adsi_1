-- Warehouse Consumable — additive production schema and approved initial masters.
-- Target: MySQL 8.0+ / Laravel application database.
-- Safe properties: no DROP, TRUNCATE, DELETE, UPDATE, or production user changes.
-- Prerequisites: existing Laravel `users` and `migrations` tables using BIGINT UNSIGNED IDs.
-- Run this entire file against the intended production database after a verified database backup.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

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
    CONSTRAINT `mst_wh_consumable_categories_created_by_foreign`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `mst_wh_consumable_categories_updated_by_foreign`
        FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
    `minimum_stock` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    `maximum_stock` DECIMAL(15,3) NULL,
    `storage_location` VARCHAR(120) NULL,
    `description` TEXT NULL,
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
    CONSTRAINT `mst_wh_consumables_category_id_foreign`
        FOREIGN KEY (`category_id`) REFERENCES `mst_wh_consumable_categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `mst_wh_consumables_created_by_foreign`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `mst_wh_consumables_updated_by_foreign`
        FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Legacy compatibility only. Runtime verification uses users.npk directly and does not read this table.
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
    CONSTRAINT `mst_wh_user_cards_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `mst_wh_user_cards_registered_by_foreign`
        FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trs_wh_stock_transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_number` VARCHAR(40) NOT NULL,
    `idempotency_key` CHAR(36) NOT NULL,
    `transaction_type` VARCHAR(10) NOT NULL,
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
    `notes` TEXT NULL,
    `reversal_of_id` BIGINT UNSIGNED NULL,
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
    CONSTRAINT `trs_wh_stock_transactions_consumable_id_foreign`
        FOREIGN KEY (`consumable_id`) REFERENCES `mst_wh_consumables` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `trs_wh_stock_transactions_verified_user_id_foreign`
        FOREIGN KEY (`verified_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `trs_wh_stock_transactions_reversal_of_id_foreign`
        FOREIGN KEY (`reversal_of_id`) REFERENCES `trs_wh_stock_transactions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `trs_wh_stock_transactions_created_by_foreign`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT `log_wh_verifications_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `log_wh_verifications_transaction_id_foreign`
        FOREIGN KEY (`transaction_id`) REFERENCES `trs_wh_stock_transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Approved initial master data. These statements insert only missing identities;
-- they never overwrite an existing master item or create an opening-balance transaction.
INSERT INTO `mst_wh_consumables` (
    `category_id`, `item_code`, `barcode`, `item_name`, `unit`, `allow_fraction`,
    `current_stock`, `minimum_stock`, `maximum_stock`, `storage_location`, `description`,
    `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`
)
SELECT NULL, 'TFHINSR-000000008', 'TFHINSR-000000008', 'Insert Widia HNPJ0704ANSNGD WS40PM', 'pcs', 0,
       0.000, 0.000, NULL, NULL, NULL, 1, NULL, NULL, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `mst_wh_consumables`
    WHERE `item_code` = 'TFHINSR-000000008' OR `barcode` = 'TFHINSR-000000008'
);

INSERT INTO `mst_wh_consumables` (
    `category_id`, `item_code`, `barcode`, `item_name`, `unit`, `allow_fraction`,
    `current_stock`, `minimum_stock`, `maximum_stock`, `storage_location`, `description`,
    `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`
)
SELECT NULL, 'TFHINSR-000000005', 'TFHINSR-000000005', 'Insert Pramet HNGX 0906ANSN-M M9315', 'pcs', 0,
       0.000, 0.000, NULL, NULL, NULL, 1, NULL, NULL, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `mst_wh_consumables`
    WHERE `item_code` = 'TFHINSR-000000005' OR `barcode` = 'TFHINSR-000000005'
);

INSERT INTO `mst_wh_consumables` (
    `category_id`, `item_code`, `barcode`, `item_name`, `unit`, `allow_fraction`,
    `current_stock`, `minimum_stock`, `maximum_stock`, `storage_location`, `description`,
    `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`
)
SELECT NULL, 'TFHINSR-000000066', 'TFHINSR-000000066', 'Insert Moldino SEK53TN-C9 GX2140', 'pcs', 0,
       0.000, 0.000, NULL, NULL, NULL, 1, NULL, NULL, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `mst_wh_consumables`
    WHERE `item_code` = 'TFHINSR-000000066' OR `barcode` = 'TFHINSR-000000066'
);

INSERT INTO `mst_wh_consumables` (
    `category_id`, `item_code`, `barcode`, `item_name`, `unit`, `allow_fraction`,
    `current_stock`, `minimum_stock`, `maximum_stock`, `storage_location`, `description`,
    `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`
)
SELECT NULL, 'TFHINSR-000000004', 'TFHINSR-000000004', 'Insert Sumitomo SDEN1203AESN', 'pcs', 0,
       0.000, 0.000, NULL, NULL, NULL, 1, NULL, NULL, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `mst_wh_consumables`
    WHERE `item_code` = 'TFHINSR-000000004' OR `barcode` = 'TFHINSR-000000004'
);

-- Prevent a future `php artisan migrate` from re-running the Warehouse table migrations.
-- Existing migration rows are preserved. This assumes Laravel's standard `migrations` table exists.
SET @warehouse_migration_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_07_000001_create_mst_wh_consumable_categories_table', @warehouse_migration_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_07_000001_create_mst_wh_consumable_categories_table');
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_07_000002_create_mst_wh_consumables_table', @warehouse_migration_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_07_000002_create_mst_wh_consumables_table');
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_07_000003_create_mst_wh_user_cards_table', @warehouse_migration_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_07_000003_create_mst_wh_user_cards_table');
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_07_000004_create_trs_wh_stock_transactions_table', @warehouse_migration_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_07_000004_create_trs_wh_stock_transactions_table');
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_07_000005_create_log_wh_verifications_table', @warehouse_migration_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_07_000005_create_log_wh_verifications_table');
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_11_000001_add_verification_permissions_to_mst_wh_user_cards_table', @warehouse_migration_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_11_000001_add_verification_permissions_to_mst_wh_user_cards_table');

-- Read-only post-deployment verification.
SELECT 'mst_wh_consumable_categories' AS `table_name`, COUNT(*) AS `row_count` FROM `mst_wh_consumable_categories`
UNION ALL SELECT 'mst_wh_consumables', COUNT(*) FROM `mst_wh_consumables`
UNION ALL SELECT 'mst_wh_user_cards (legacy)', COUNT(*) FROM `mst_wh_user_cards`
UNION ALL SELECT 'trs_wh_stock_transactions', COUNT(*) FROM `trs_wh_stock_transactions`
UNION ALL SELECT 'log_wh_verifications', COUNT(*) FROM `log_wh_verifications`;

SELECT `item_code`, `item_name`, `unit`, `current_stock`, `minimum_stock`, `maximum_stock`, `storage_location`, `is_active`
FROM `mst_wh_consumables`
WHERE `item_code` IN (
    'TFHINSR-000000008',
    'TFHINSR-000000005',
    'TFHINSR-000000066',
    'TFHINSR-000000004'
)
ORDER BY `item_code`;

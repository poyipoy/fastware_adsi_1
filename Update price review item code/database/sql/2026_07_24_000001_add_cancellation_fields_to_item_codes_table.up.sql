-- Update Cancel Item Code - SQL alternative
-- Target: MySQL 8.x
--
-- IMPORTANT:
-- 1. Select the intended production database before running this script.
-- 2. Back up the database first.
-- 3. Run this cancellation SQL before the 2026_07_30 price-review SQL.
-- 4. Use the SQL path for both migrations, or the Laravel migration path for both.
-- 5. This script records the Laravel migration ledger after all DDL succeeds.

SELECT DATABASE() AS target_database_before_cancellation_update;

DELIMITER $$

DROP PROCEDURE IF EXISTS `apply_item_code_cancellation_20260724`$$

CREATE PROCEDURE `apply_item_code_cancellation_20260724`()
deployment: BEGIN
    DECLARE v_schema VARCHAR(64);
    DECLARE v_count BIGINT DEFAULT 0;
    DECLARE v_batch INT DEFAULT 0;
    DECLARE v_status_type VARCHAR(1024);
    DECLARE v_existing_type VARCHAR(255);
    DECLARE v_existing_nullable VARCHAR(3);
    DECLARE v_add_cancelled_by TINYINT DEFAULT 0;
    DECLARE v_add_cancelled_at TINYINT DEFAULT 0;
    DECLARE v_add_foreign_key TINYINT DEFAULT 0;
    DECLARE v_migration_name VARCHAR(255)
        DEFAULT '2026_07_24_000001_add_cancellation_fields_to_item_codes_table';
    DECLARE v_price_review_migration VARCHAR(255)
        DEFAULT '2026_07_30_000001_add_price_review_workflow_to_item_codes_table';

    SET v_schema = DATABASE();

    IF v_schema IS NULL OR v_schema = '' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Tidak ada database aktif. Pilih database produksi sebelum menjalankan SQL.';
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = v_schema
       AND TABLE_NAME = 'item_codes';

    IF v_count <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Preflight gagal: tabel item_codes tidak ditemukan pada database aktif.';
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = v_schema
       AND TABLE_NAME = 'users';

    IF v_count <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Preflight gagal: tabel users tidak ditemukan pada database aktif.';
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = v_schema
       AND TABLE_NAME = 'migrations';

    IF v_count <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Preflight gagal: tabel migrations tidak ditemukan pada database aktif.';
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = v_schema
       AND TABLE_NAME = 'item_codes'
       AND COLUMN_NAME IN ('status', 'finished_by');

    IF v_count <> 2 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Preflight gagal: kolom status atau finished_by tidak ditemukan.';
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = v_schema
       AND TABLE_NAME = 'users'
       AND COLUMN_NAME = 'id';

    IF v_count <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Preflight gagal: kolom users.id tidak ditemukan.';
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = v_schema
       AND TABLE_NAME = 'migrations'
       AND COLUMN_NAME IN ('migration', 'batch');

    IF v_count <> 2 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Preflight gagal: struktur tabel migrations tidak sesuai Laravel.';
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM `migrations`
     WHERE `migration` = v_migration_name;

    IF v_count > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Migration cancellation sudah tercatat. SQL tidak dijalankan ulang.';
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM `migrations`
     WHERE `migration` = v_price_review_migration;

    IF v_count > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Migration price review sudah tercatat. Jangan jalankan migration cancellation setelah price review.';
    END IF;

    SELECT COLUMN_TYPE
      INTO v_status_type
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = v_schema
       AND TABLE_NAME = 'item_codes'
       AND COLUMN_NAME = 'status'
     LIMIT 1;

    IF v_status_type IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Preflight gagal: kolom item_codes.status tidak dapat dibaca.';
    END IF;

    IF LOWER(v_status_type) LIKE '%pending_price_review%' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Preflight gagal: status price review sudah ada tanpa migration ledger yang sesuai.';
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM `item_codes`
     WHERE `status` NOT IN (
        'draft',
        'submitted',
        'approved_1',
        'approved_2',
        'finished',
        'cancelled'
     );

    IF v_count > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Preflight gagal: terdapat status Item Code di luar enum cancellation target.';
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = v_schema
       AND TABLE_NAME = 'item_codes'
       AND COLUMN_NAME = 'cancelled_by';

    IF v_count = 0 THEN
        SET v_add_cancelled_by = 1;
    ELSE
        SELECT COLUMN_TYPE, IS_NULLABLE
          INTO v_existing_type, v_existing_nullable
          FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = v_schema
           AND TABLE_NAME = 'item_codes'
           AND COLUMN_NAME = 'cancelled_by'
         LIMIT 1;

        IF LOWER(v_existing_type) <> 'bigint unsigned'
           OR v_existing_nullable <> 'YES' THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Preflight gagal: cancelled_by sudah ada tetapi tipenya tidak sesuai.';
        END IF;
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = v_schema
       AND TABLE_NAME = 'item_codes'
       AND COLUMN_NAME = 'cancelled_at';

    IF v_count = 0 THEN
        SET v_add_cancelled_at = 1;
    ELSE
        SELECT DATA_TYPE, IS_NULLABLE
          INTO v_existing_type, v_existing_nullable
          FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = v_schema
           AND TABLE_NAME = 'item_codes'
           AND COLUMN_NAME = 'cancelled_at'
         LIMIT 1;

        IF LOWER(v_existing_type) <> 'timestamp'
           OR v_existing_nullable <> 'YES' THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Preflight gagal: cancelled_at sudah ada tetapi tipenya tidak sesuai.';
        END IF;
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM information_schema.KEY_COLUMN_USAGE
     WHERE CONSTRAINT_SCHEMA = v_schema
       AND TABLE_NAME = 'item_codes'
       AND COLUMN_NAME = 'cancelled_by'
       AND REFERENCED_TABLE_NAME IS NOT NULL;

    IF v_count = 0 THEN
        SET v_add_foreign_key = 1;
    ELSE
        SELECT COUNT(*)
          INTO v_count
          FROM information_schema.KEY_COLUMN_USAGE
         WHERE CONSTRAINT_SCHEMA = v_schema
           AND TABLE_NAME = 'item_codes'
           AND COLUMN_NAME = 'cancelled_by'
           AND CONSTRAINT_NAME = 'item_codes_cancelled_by_foreign'
           AND REFERENCED_TABLE_NAME = 'users'
           AND REFERENCED_COLUMN_NAME = 'id';

        IF v_count <> 1 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Preflight gagal: FK cancelled_by sudah ada dengan definisi atau nama berbeda.';
        END IF;
    END IF;

    IF v_add_cancelled_by = 0 AND v_add_foreign_key = 1 THEN
        SELECT COUNT(*)
          INTO v_count
          FROM `item_codes` AS ic
          LEFT JOIN `users` AS u ON u.`id` = ic.`cancelled_by`
         WHERE ic.`cancelled_by` IS NOT NULL
           AND u.`id` IS NULL;

        IF v_count > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Preflight gagal: terdapat cancelled_by tanpa user yang valid.';
        END IF;
    END IF;

    ALTER TABLE `item_codes`
        MODIFY COLUMN `status` ENUM(
            'draft',
            'submitted',
            'approved_1',
            'approved_2',
            'finished',
            'cancelled'
        ) NOT NULL DEFAULT 'draft';

    IF v_add_cancelled_by = 1 THEN
        ALTER TABLE `item_codes`
            ADD COLUMN `cancelled_by` BIGINT UNSIGNED NULL AFTER `finished_by`;
    END IF;

    IF v_add_cancelled_at = 1 THEN
        ALTER TABLE `item_codes`
            ADD COLUMN `cancelled_at` TIMESTAMP NULL DEFAULT NULL AFTER `cancelled_by`;
    END IF;

    IF v_add_foreign_key = 1 THEN
        ALTER TABLE `item_codes`
            ADD CONSTRAINT `item_codes_cancelled_by_foreign`
            FOREIGN KEY (`cancelled_by`)
            REFERENCES `users` (`id`)
            ON DELETE SET NULL;
    END IF;

    SELECT COALESCE(MAX(`batch`), 0) + 1
      INTO v_batch
      FROM `migrations`;

    INSERT INTO `migrations` (`migration`, `batch`)
    VALUES (v_migration_name, v_batch);

    SELECT
        'SUCCESS' AS result,
        v_schema AS target_database,
        v_batch AS migration_batch;
END$$

CALL `apply_item_code_cancellation_20260724`()$$
DROP PROCEDURE IF EXISTS `apply_item_code_cancellation_20260724`$$

DELIMITER ;

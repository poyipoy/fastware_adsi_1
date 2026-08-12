-- Update Price Review Item Code - SQL alternative
-- Target: MySQL 8.x
--
-- IMPORTANT:
-- 1. Select the intended production database before running this script.
-- 2. Back up the database first.
-- 3. Use either this SQL file or the Laravel PHP migration, never both.
-- 4. This script records the Laravel migration ledger after all DDL succeeds.

SELECT DATABASE() AS target_database_before_price_review_update;

DELIMITER $$

DROP PROCEDURE IF EXISTS `apply_item_code_price_review_20260730`$$

CREATE PROCEDURE `apply_item_code_price_review_20260730`()
deployment: BEGIN
    DECLARE v_schema VARCHAR(64);
    DECLARE v_count BIGINT DEFAULT 0;
    DECLARE v_batch INT DEFAULT 0;
    DECLARE v_price_type VARCHAR(255);
    DECLARE v_existing_type VARCHAR(255);
    DECLARE v_existing_nullable VARCHAR(3);
    DECLARE v_add_reviewed_by TINYINT DEFAULT 0;
    DECLARE v_add_reviewed_at TINYINT DEFAULT 0;
    DECLARE v_add_foreign_key TINYINT DEFAULT 0;
    DECLARE v_migration_name VARCHAR(255)
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
       AND COLUMN_NAME IN ('status', 'price_per_pcs', 'created_by');

    IF v_count <> 3 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Preflight gagal: status, price_per_pcs, atau created_by tidak lengkap.';
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
            SET MESSAGE_TEXT = 'Migration price review sudah tercatat. SQL tidak dijalankan ulang.';
    END IF;

    SELECT COLUMN_TYPE
      INTO v_price_type
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = v_schema
       AND TABLE_NAME = 'item_codes'
       AND COLUMN_NAME = 'price_per_pcs'
     LIMIT 1;

    IF v_price_type IS NULL
       OR LOWER(v_price_type) NOT REGEXP '^decimal[(][0-9]+,[0-9]+[)]( unsigned)?$' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Preflight gagal: price_per_pcs wajib bertipe DECIMAL.';
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM `item_codes`
     WHERE `status` NOT IN (
        'draft',
        'pending_price_review',
        'submitted',
        'approved_1',
        'approved_2',
        'finished',
        'cancelled'
     );

    IF v_count > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Preflight gagal: terdapat status Item Code di luar enum target.';
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = v_schema
       AND TABLE_NAME = 'item_codes'
       AND COLUMN_NAME = 'price_reviewed_by';

    IF v_count = 0 THEN
        SET v_add_reviewed_by = 1;
    ELSE
        SELECT COLUMN_TYPE, IS_NULLABLE
          INTO v_existing_type, v_existing_nullable
          FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = v_schema
           AND TABLE_NAME = 'item_codes'
           AND COLUMN_NAME = 'price_reviewed_by'
         LIMIT 1;

        IF LOWER(v_existing_type) <> 'bigint unsigned'
           OR v_existing_nullable <> 'YES' THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Preflight gagal: price_reviewed_by sudah ada tetapi tipenya tidak sesuai.';
        END IF;
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = v_schema
       AND TABLE_NAME = 'item_codes'
       AND COLUMN_NAME = 'price_reviewed_at';

    IF v_count = 0 THEN
        SET v_add_reviewed_at = 1;
    ELSE
        SELECT DATA_TYPE, IS_NULLABLE
          INTO v_existing_type, v_existing_nullable
          FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = v_schema
           AND TABLE_NAME = 'item_codes'
           AND COLUMN_NAME = 'price_reviewed_at'
         LIMIT 1;

        IF LOWER(v_existing_type) <> 'timestamp'
           OR v_existing_nullable <> 'YES' THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Preflight gagal: price_reviewed_at sudah ada tetapi tipenya tidak sesuai.';
        END IF;
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM information_schema.KEY_COLUMN_USAGE
     WHERE CONSTRAINT_SCHEMA = v_schema
       AND TABLE_NAME = 'item_codes'
       AND COLUMN_NAME = 'price_reviewed_by'
       AND REFERENCED_TABLE_NAME IS NOT NULL;

    IF v_count = 0 THEN
        SET v_add_foreign_key = 1;
    ELSE
        SELECT COUNT(*)
          INTO v_count
          FROM information_schema.KEY_COLUMN_USAGE
         WHERE CONSTRAINT_SCHEMA = v_schema
           AND TABLE_NAME = 'item_codes'
           AND COLUMN_NAME = 'price_reviewed_by'
           AND CONSTRAINT_NAME = 'item_codes_price_reviewed_by_foreign'
           AND REFERENCED_TABLE_NAME = 'users'
           AND REFERENCED_COLUMN_NAME = 'id';

        IF v_count <> 1 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Preflight gagal: FK price_reviewed_by sudah ada dengan definisi atau nama berbeda.';
        END IF;
    END IF;

    IF v_add_reviewed_by = 0 AND v_add_foreign_key = 1 THEN
        SELECT COUNT(*)
          INTO v_count
          FROM `item_codes` AS ic
          LEFT JOIN `users` AS u ON u.`id` = ic.`price_reviewed_by`
         WHERE ic.`price_reviewed_by` IS NOT NULL
           AND u.`id` IS NULL;

        IF v_count > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Preflight gagal: terdapat price_reviewed_by tanpa user yang valid.';
        END IF;
    END IF;

    ALTER TABLE `item_codes`
        MODIFY COLUMN `status` ENUM(
            'draft',
            'pending_price_review',
            'submitted',
            'approved_1',
            'approved_2',
            'finished',
            'cancelled'
        ) NOT NULL DEFAULT 'draft';

    SET @price_review_ddl = CONCAT(
        'ALTER TABLE `item_codes` MODIFY COLUMN `price_per_pcs` ',
        UPPER(v_price_type),
        ' NULL'
    );
    PREPARE price_review_statement FROM @price_review_ddl;
    EXECUTE price_review_statement;
    DEALLOCATE PREPARE price_review_statement;

    IF v_add_reviewed_by = 1 THEN
        ALTER TABLE `item_codes`
            ADD COLUMN `price_reviewed_by` BIGINT UNSIGNED NULL AFTER `created_by`;
    END IF;

    IF v_add_reviewed_at = 1 THEN
        ALTER TABLE `item_codes`
            ADD COLUMN `price_reviewed_at` TIMESTAMP NULL DEFAULT NULL AFTER `price_reviewed_by`;
    END IF;

    IF v_add_foreign_key = 1 THEN
        ALTER TABLE `item_codes`
            ADD CONSTRAINT `item_codes_price_reviewed_by_foreign`
            FOREIGN KEY (`price_reviewed_by`)
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
        v_price_type AS preserved_price_type,
        v_batch AS migration_batch;
END$$

CALL `apply_item_code_price_review_20260730`()$$
DROP PROCEDURE IF EXISTS `apply_item_code_price_review_20260730`$$

DELIMITER ;

-- Item Code terminal cancellation
-- Target: MySQL 8.0+
-- Jalankan hanya setelah backup database production dibuat.
-- Script ini tidak melakukan backfill atau mengubah data Item Code lama.

ALTER TABLE `item_codes`
    MODIFY COLUMN `status` ENUM(
        'draft',
        'submitted',
        'approved_1',
        'approved_2',
        'finished',
        'cancelled'
    ) NOT NULL DEFAULT 'draft';

ALTER TABLE `item_codes`
    ADD COLUMN `cancelled_by` BIGINT UNSIGNED NULL AFTER `finished_by`,
    ADD COLUMN `cancelled_at` TIMESTAMP NULL DEFAULT NULL AFTER `cancelled_by`,
    ADD CONSTRAINT `item_codes_cancelled_by_foreign`
        FOREIGN KEY (`cancelled_by`)
        REFERENCES `users` (`id`)
        ON DELETE SET NULL;

-- Tandai migration Laravel sebagai sudah dijalankan.
-- Bagian ini mencegah `php artisan migrate` berikutnya mengulang perubahan yang sama.
SET @item_code_cancel_migration = '2026_07_24_000001_add_cancellation_fields_to_item_codes_table';
SET @item_code_cancel_batch = (
    SELECT COALESCE(MAX(`batch`), 0) + 1
    FROM `migrations`
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT @item_code_cancel_migration, @item_code_cancel_batch
WHERE NOT EXISTS (
    SELECT 1
    FROM `migrations`
    WHERE `migration` = @item_code_cancel_migration
);

-- Verifikasi setelah eksekusi.
SELECT
    `COLUMN_NAME`,
    `COLUMN_TYPE`,
    `IS_NULLABLE`,
    `COLUMN_DEFAULT`
FROM `information_schema`.`COLUMNS`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'item_codes'
  AND `COLUMN_NAME` IN ('status', 'cancelled_by', 'cancelled_at')
ORDER BY `ORDINAL_POSITION`;

SELECT
    `CONSTRAINT_NAME`,
    `COLUMN_NAME`,
    `REFERENCED_TABLE_NAME`,
    `REFERENCED_COLUMN_NAME`
FROM `information_schema`.`KEY_COLUMN_USAGE`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'item_codes'
  AND `CONSTRAINT_NAME` = 'item_codes_cancelled_by_foreign';

SELECT `migration`, `batch`
FROM `migrations`
WHERE `migration` = @item_code_cancel_migration;

-- ================================================================
-- ROLLBACK MANUAL (DINONAKTIFKAN)
-- ================================================================
-- Rollback hanya boleh dilakukan bila query berikut menghasilkan 0:
-- SELECT COUNT(*) AS cancelled_count
-- FROM `item_codes`
-- WHERE `status` = 'cancelled';
--
-- Jika cancelled_count lebih dari 0, JANGAN rollback. Lakukan forward-fix.
-- Jika hasilnya 0 dan rollback memang diperlukan, jalankan secara manual:
--
-- ALTER TABLE `item_codes`
--     DROP FOREIGN KEY `item_codes_cancelled_by_foreign`,
--     DROP COLUMN `cancelled_at`,
--     DROP COLUMN `cancelled_by`;
--
-- ALTER TABLE `item_codes`
--     MODIFY COLUMN `status` ENUM(
--         'draft',
--         'submitted',
--         'approved_1',
--         'approved_2',
--         'finished'
--     ) NOT NULL DEFAULT 'draft';

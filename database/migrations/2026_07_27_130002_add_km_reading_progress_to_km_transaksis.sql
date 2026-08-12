-- KM Engagement Foundation: granular PDF reading progress
-- Target: MySQL 8.0+
-- WAJIB: backup database dan validasi schema production sebelum eksekusi.
-- JANGAN jalankan bila satu saja kolom/index baru sudah ada; schema parsial harus direconcile dahulu.

-- PRECHECK: table_count=1, base_column_count=3, new_column_count=0,
-- progress_index_count=0, migration_row_count=0.
SELECT
    (SELECT COUNT(*) FROM `information_schema`.`TABLES`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'km_transaksis') AS `table_count`,
    (SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'km_transaksis'
          AND `COLUMN_NAME` IN ('id_user', 'status', 'points_awarded_at')) AS `base_column_count`,
    (SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'km_transaksis'
          AND `COLUMN_NAME` IN (
              'last_page', 'pages_total', 'unique_pages', 'unique_pages_count',
              'active_seconds', 'progress_percent', 'last_progress_at'
          )) AS `new_column_count`,
    (SELECT COUNT(DISTINCT `INDEX_NAME`) FROM `information_schema`.`STATISTICS`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'km_transaksis'
          AND `INDEX_NAME` = 'km_transaksis_user_status_progress_index') AS `progress_index_count`,
    (SELECT COUNT(*) FROM `migrations`
        WHERE `migration` = '2026_07_27_130002_add_km_reading_progress_to_km_transaksis') AS `migration_row_count`;

ALTER TABLE `km_transaksis`
    ADD COLUMN `last_page` INT UNSIGNED NULL AFTER `points_awarded_at`,
    ADD COLUMN `pages_total` INT UNSIGNED NULL AFTER `last_page`,
    ADD COLUMN `unique_pages` TEXT NULL AFTER `pages_total`,
    ADD COLUMN `unique_pages_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `unique_pages`,
    ADD COLUMN `active_seconds` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `unique_pages_count`,
    ADD COLUMN `progress_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `active_seconds`,
    ADD COLUMN `last_progress_at` TIMESTAMP NULL DEFAULT NULL AFTER `progress_percent`,
    ADD INDEX `km_transaksis_user_status_progress_index` (`id_user`, `status`, `last_progress_at`);

SET @km_130002_migration = '2026_07_27_130002_add_km_reading_progress_to_km_transaksis';
SET @km_130002_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT @km_130002_migration, @km_130002_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = @km_130002_migration
);

-- VERIFIKASI: 7 baris kolom, index berurutan id_user,status,last_progress_at,
-- nilai percent 0..100, dan 1 migration row.
SELECT `COLUMN_NAME`, `COLUMN_TYPE`, `IS_NULLABLE`, `COLUMN_DEFAULT`
FROM `information_schema`.`COLUMNS`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'km_transaksis'
  AND `COLUMN_NAME` IN (
      'last_page', 'pages_total', 'unique_pages', 'unique_pages_count',
      'active_seconds', 'progress_percent', 'last_progress_at'
  )
ORDER BY `ORDINAL_POSITION`;

SELECT `INDEX_NAME`, `NON_UNIQUE`, GROUP_CONCAT(`COLUMN_NAME` ORDER BY `SEQ_IN_INDEX`) AS `columns_ordered`
FROM `information_schema`.`STATISTICS`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'km_transaksis'
  AND `INDEX_NAME` = 'km_transaksis_user_status_progress_index'
GROUP BY `INDEX_NAME`, `NON_UNIQUE`;

SELECT COUNT(*) AS `invalid_progress_rows`
FROM `km_transaksis`
WHERE `progress_percent` > 100
   OR `unique_pages_count` > COALESCE(`pages_total`, `unique_pages_count`);

SELECT `migration`, `batch` FROM `migrations` WHERE `migration` = @km_130002_migration;

-- ================================================================
-- ROLLBACK MANUAL (DINONAKTIFKAN; akan menghapus progress tersimpan)
-- ================================================================
-- Setelah rollback code, backup, dan persetujuan terpisah:
-- ALTER TABLE `km_transaksis`
--     DROP INDEX `km_transaksis_user_status_progress_index`,
--     DROP COLUMN `last_progress_at`,
--     DROP COLUMN `progress_percent`,
--     DROP COLUMN `active_seconds`,
--     DROP COLUMN `unique_pages_count`,
--     DROP COLUMN `unique_pages`,
--     DROP COLUMN `pages_total`,
--     DROP COLUMN `last_page`;
-- DELETE FROM `migrations` WHERE `migration` = @km_130002_migration;

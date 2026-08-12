-- KM Engagement Foundation: notification center
-- Target: MySQL 8.0+
-- WAJIB: backup database dan validasi schema production sebelum eksekusi.
-- JANGAN jalankan bila precheck menunjukkan tabel/constraint bernama sama.

-- PRECHECK: hasil harus users=1, users_id_type='bigint unsigned',
-- km_notifications=0, migration_row=0.
SELECT
    (SELECT COUNT(*) FROM `information_schema`.`TABLES`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'users') AS `users_table_count`,
    (SELECT `COLUMN_TYPE` FROM `information_schema`.`COLUMNS`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'users'
          AND `COLUMN_NAME` = 'id') AS `users_id_type`,
    (SELECT COUNT(*) FROM `information_schema`.`TABLES`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'km_notifications') AS `notification_table_count`,
    (SELECT COUNT(*) FROM `migrations`
        WHERE `migration` = '2026_07_27_130001_create_km_notifications_table') AS `migration_row_count`;

CREATE TABLE `km_notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(48) NOT NULL,
    `event_key` VARCHAR(191) NOT NULL,
    `data` JSON NOT NULL,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `km_notifications_event_key_unique` (`event_key`),
    KEY `km_notifications_user_unread_id_index` (`user_id`, `read_at`, `id`),
    CONSTRAINT `km_notifications_user_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @km_130001_migration = '2026_07_27_130001_create_km_notifications_table';
SET @km_130001_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT @km_130001_migration, @km_130001_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = @km_130001_migration
);

-- VERIFIKASI: 7 kolom, 2 named index, 1 named FK, dan 1 migration row.
SELECT `COLUMN_NAME`, `COLUMN_TYPE`, `IS_NULLABLE`, `COLUMN_DEFAULT`
FROM `information_schema`.`COLUMNS`
WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'km_notifications'
ORDER BY `ORDINAL_POSITION`;

SELECT `INDEX_NAME`, `NON_UNIQUE`, GROUP_CONCAT(`COLUMN_NAME` ORDER BY `SEQ_IN_INDEX`) AS `columns_ordered`
FROM `information_schema`.`STATISTICS`
WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'km_notifications'
GROUP BY `INDEX_NAME`, `NON_UNIQUE`
ORDER BY `INDEX_NAME`;

SELECT `CONSTRAINT_NAME`, `COLUMN_NAME`, `REFERENCED_TABLE_NAME`, `REFERENCED_COLUMN_NAME`
FROM `information_schema`.`KEY_COLUMN_USAGE`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'km_notifications'
  AND `REFERENCED_TABLE_NAME` IS NOT NULL;

SELECT `migration`, `batch` FROM `migrations` WHERE `migration` = @km_130001_migration;

-- ================================================================
-- ROLLBACK MANUAL (DINONAKTIFKAN; production mengutamakan rollback code)
-- ================================================================
-- Setelah backup dan persetujuan terpisah:
-- DELETE FROM `migrations` WHERE `migration` = @km_130001_migration;
-- DROP TABLE `km_notifications`;

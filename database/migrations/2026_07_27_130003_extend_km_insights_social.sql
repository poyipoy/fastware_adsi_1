-- KM Engagement Foundation: threaded/social insights
-- Target: MySQL 8.0+
-- WAJIB: backup database dan validasi orphan/type schema production sebelum eksekusi.
-- JANGAN jalankan pada schema parsial atau bila named constraint sudah dipakai dengan bentuk lain.

-- PRECHECK: base_table_count=3; key types harus users=bigint unsigned,
-- pengajuan/insight/insight_document=int; social_column_count=0,
-- child_table_count=0, migration_row_count=0.
SELECT
    (SELECT COUNT(*) FROM `information_schema`.`TABLES`
        WHERE `TABLE_SCHEMA` = DATABASE()
          AND `TABLE_NAME` IN ('users', 'km_pengajuans', 'km_insights')) AS `base_table_count`,
    (SELECT `COLUMN_TYPE` FROM `information_schema`.`COLUMNS`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'users'
          AND `COLUMN_NAME` = 'id') AS `users_id_type`,
    (SELECT `COLUMN_TYPE` FROM `information_schema`.`COLUMNS`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'km_pengajuans'
          AND `COLUMN_NAME` = 'id') AS `pengajuan_id_type`,
    (SELECT `COLUMN_TYPE` FROM `information_schema`.`COLUMNS`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'km_insights'
          AND `COLUMN_NAME` = 'id') AS `insight_id_type`,
    (SELECT `COLUMN_TYPE` FROM `information_schema`.`COLUMNS`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'km_insights'
          AND `COLUMN_NAME` = 'id_km_pengajuan') AS `insight_document_type`,
    (SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'km_insights'
          AND `COLUMN_NAME` IN (
              'parent_id', 'edited_at', 'deleted_at', 'deleted_by',
              'delete_reason', 'featured_at', 'featured_by'
          )) AS `social_column_count`,
    (SELECT COUNT(*) FROM `information_schema`.`TABLES`
        WHERE `TABLE_SCHEMA` = DATABASE()
          AND `TABLE_NAME` IN ('km_insight_reactions', 'km_insight_mentions')) AS `child_table_count`,
    (SELECT COUNT(*) FROM `migrations`
        WHERE `migration` = '2026_07_27_130003_extend_km_insights_social') AS `migration_row_count`;

ALTER TABLE `km_insights`
    ADD COLUMN `parent_id` INT NULL AFTER `id_km_pengajuan`,
    ADD COLUMN `edited_at` TIMESTAMP NULL DEFAULT NULL AFTER `content`,
    ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `edited_at`,
    ADD COLUMN `deleted_by` BIGINT UNSIGNED NULL AFTER `deleted_at`,
    ADD COLUMN `delete_reason` VARCHAR(500) NULL AFTER `deleted_by`,
    ADD COLUMN `featured_at` TIMESTAMP NULL DEFAULT NULL AFTER `delete_reason`,
    ADD COLUMN `featured_by` BIGINT UNSIGNED NULL AFTER `featured_at`,
    ADD INDEX `km_insights_document_parent_id_index` (`id_km_pengajuan`, `parent_id`, `id`),
    ADD INDEX `km_insights_document_featured_at_index` (`id_km_pengajuan`, `featured_at`),
    ADD CONSTRAINT `km_insights_parent_foreign`
        FOREIGN KEY (`parent_id`) REFERENCES `km_insights` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `km_insights_deleted_by_foreign`
        FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `km_insights_featured_by_foreign`
        FOREIGN KEY (`featured_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

CREATE TABLE `km_insight_reactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `insight_id` INT NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `reaction` VARCHAR(16) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `km_insight_reactions_insight_user_unique` (`insight_id`, `user_id`),
    KEY `km_insight_reactions_user_insight_index` (`user_id`, `insight_id`),
    CONSTRAINT `km_insight_reactions_insight_foreign`
        FOREIGN KEY (`insight_id`) REFERENCES `km_insights` (`id`) ON DELETE CASCADE,
    CONSTRAINT `km_insight_reactions_user_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `km_insight_reactions_type_check`
        CHECK (`reaction` IN ('helpful', 'insightful', 'agree'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `km_insight_mentions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `insight_id` INT NOT NULL,
    `mentioned_user_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `km_insight_mentions_insight_user_unique` (`insight_id`, `mentioned_user_id`),
    KEY `km_insight_mentions_user_insight_index` (`mentioned_user_id`, `insight_id`),
    CONSTRAINT `km_insight_mentions_insight_foreign`
        FOREIGN KEY (`insight_id`) REFERENCES `km_insights` (`id`) ON DELETE CASCADE,
    CONSTRAINT `km_insight_mentions_user_foreign`
        FOREIGN KEY (`mentioned_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @km_130003_migration = '2026_07_27_130003_extend_km_insights_social';
SET @km_130003_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT @km_130003_migration, @km_130003_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = @km_130003_migration
);

-- VERIFIKASI: tidak boleh ada parent lintas dokumen atau self-parent.
SELECT COUNT(*) AS `invalid_parent_rows`
FROM `km_insights` AS `child`
JOIN `km_insights` AS `parent` ON `parent`.`id` = `child`.`parent_id`
WHERE `child`.`id_km_pengajuan` <> `parent`.`id_km_pengajuan`
   OR `child`.`id` = `parent`.`id`;

SELECT `TABLE_NAME`, `INDEX_NAME`, `NON_UNIQUE`,
       GROUP_CONCAT(`COLUMN_NAME` ORDER BY `SEQ_IN_INDEX`) AS `columns_ordered`
FROM `information_schema`.`STATISTICS`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` IN ('km_insights', 'km_insight_reactions', 'km_insight_mentions')
  AND `INDEX_NAME` <> 'PRIMARY'
GROUP BY `TABLE_NAME`, `INDEX_NAME`, `NON_UNIQUE`
ORDER BY `TABLE_NAME`, `INDEX_NAME`;

SELECT `TABLE_NAME`, `CONSTRAINT_NAME`, `DELETE_RULE`
FROM `information_schema`.`REFERENTIAL_CONSTRAINTS`
WHERE `CONSTRAINT_SCHEMA` = DATABASE()
  AND `TABLE_NAME` IN ('km_insights', 'km_insight_reactions', 'km_insight_mentions')
ORDER BY `TABLE_NAME`, `CONSTRAINT_NAME`;

SELECT `migration`, `batch` FROM `migrations` WHERE `migration` = @km_130003_migration;

-- ================================================================
-- ROLLBACK MANUAL (DINONAKTIFKAN; akan menghapus diskusi sosial)
-- ================================================================
-- Setelah rollback code, backup, dan persetujuan terpisah:
-- DROP TABLE `km_insight_mentions`;
-- DROP TABLE `km_insight_reactions`;
-- ALTER TABLE `km_insights`
--     DROP FOREIGN KEY `km_insights_parent_foreign`,
--     DROP FOREIGN KEY `km_insights_deleted_by_foreign`,
--     DROP FOREIGN KEY `km_insights_featured_by_foreign`,
--     DROP INDEX `km_insights_document_parent_id_index`,
--     DROP INDEX `km_insights_document_featured_at_index`,
--     DROP COLUMN `featured_by`, DROP COLUMN `featured_at`,
--     DROP COLUMN `delete_reason`, DROP COLUMN `deleted_by`,
--     DROP COLUMN `deleted_at`, DROP COLUMN `edited_at`, DROP COLUMN `parent_id`;
-- DELETE FROM `migrations` WHERE `migration` = @km_130003_migration;

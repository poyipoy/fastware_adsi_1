-- KM Engagement Foundation: append-only point ledger and opening balance
-- Target: MySQL 8.0+
-- WAJIB: backup database, cek duplicate event key, dan validasi master organisasi.
-- JANGAN jalankan bila km_point_ledger sudah ada; schema existing harus diaudit oleh migration Laravel.

-- PRECHECK: base_table_count=3, key types users=bigint unsigned dan
-- pengajuan/insight=int, user_balance_column_count=2, ledger_table_count=0,
-- migration_row_count=0. org_support_count=11 mengaktifkan snapshot master organisasi;
-- selain itu opening balance memakai fallback users.section.
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
    (SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'users'
          AND `COLUMN_NAME` IN ('km_total_poin', 'section')) AS `user_balance_column_count`,
    (SELECT COUNT(*) FROM `information_schema`.`TABLES`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'km_point_ledger') AS `ledger_table_count`,
    (SELECT COUNT(*) FROM `information_schema`.`TABLES`
        WHERE `TABLE_SCHEMA` = DATABASE()
          AND `TABLE_NAME` IN ('user_job_positions', 'mst_job_positions', 'mst_departments'))
      +
    (SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
        WHERE `TABLE_SCHEMA` = DATABASE()
          AND (`TABLE_NAME`, `COLUMN_NAME`) IN (
              ('user_job_positions', 'id'),
              ('user_job_positions', 'user_id'),
              ('user_job_positions', 'mst_job_position_id'),
              ('user_job_positions', 'is_active'),
              ('mst_job_positions', 'id'),
              ('mst_job_positions', 'department_id'),
              ('mst_departments', 'id'),
              ('mst_departments', 'name')
          )) AS `org_support_count`,
    (SELECT COUNT(*) FROM `migrations`
        WHERE `migration` = '2026_07_27_130004_create_km_point_ledger_table') AS `migration_row_count`;

CREATE TABLE `km_point_ledger` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `event_type` VARCHAR(48) NOT NULL,
    `event_key` VARCHAR(191) NOT NULL,
    `points` INT NOT NULL,
    `department_snapshot` VARCHAR(255) NULL,
    `km_pengajuan_id` INT NULL,
    `km_insight_id` INT NULL,
    `notes` JSON NULL,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `km_point_ledger_event_key_unique` (`event_key`),
    KEY `km_point_ledger_user_created_index` (`user_id`, `created_at`, `id`),
    KEY `km_point_ledger_department_created_index` (`department_snapshot`, `created_at`),
    KEY `km_point_ledger_document_index` (`km_pengajuan_id`),
    KEY `km_point_ledger_insight_index` (`km_insight_id`),
    CONSTRAINT `km_point_ledger_user_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `km_point_ledger_document_foreign`
        FOREIGN KEY (`km_pengajuan_id`) REFERENCES `km_pengajuans` (`id`) ON DELETE SET NULL,
    CONSTRAINT `km_point_ledger_insight_foreign`
        FOREIGN KEY (`km_insight_id`) REFERENCES `km_insights` (`id`) ON DELETE SET NULL,
    CONSTRAINT `km_point_ledger_created_by_foreign`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Snapshot departemen: assignment aktif dengan ID terbaru bila master organisasi siap.
CREATE TEMPORARY TABLE `tmp_km_department_snapshot` (
    `user_id` BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    `department_name` VARCHAR(255) NOT NULL
);

SET @km_org_support_count = (
    SELECT
        (SELECT COUNT(*) FROM `information_schema`.`TABLES`
            WHERE `TABLE_SCHEMA` = DATABASE()
              AND `TABLE_NAME` IN ('user_job_positions', 'mst_job_positions', 'mst_departments'))
        +
        (SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
            WHERE `TABLE_SCHEMA` = DATABASE()
              AND (`TABLE_NAME`, `COLUMN_NAME`) IN (
                  ('user_job_positions', 'id'),
                  ('user_job_positions', 'user_id'),
                  ('user_job_positions', 'mst_job_position_id'),
                  ('user_job_positions', 'is_active'),
                  ('mst_job_positions', 'id'),
                  ('mst_job_positions', 'department_id'),
                  ('mst_departments', 'id'),
                  ('mst_departments', 'name')
              ))
);

SET @km_org_snapshot_sql = IF(
    @km_org_support_count = 11,
    'INSERT INTO `tmp_km_department_snapshot` (`user_id`, `department_name`)
     SELECT `assignment`.`user_id`, `department`.`name`
     FROM `user_job_positions` AS `assignment`
     JOIN `mst_job_positions` AS `position`
       ON `position`.`id` = `assignment`.`mst_job_position_id`
     JOIN `mst_departments` AS `department`
       ON `department`.`id` = `position`.`department_id`
     LEFT JOIN `user_job_positions` AS `newer`
       ON `newer`.`user_id` = `assignment`.`user_id`
      AND `newer`.`is_active` = 1
      AND `newer`.`id` > `assignment`.`id`
     WHERE `assignment`.`is_active` = 1
       AND `newer`.`id` IS NULL
       AND TRIM(`department`.`name`) <> ''''',
    'SELECT 1'
);
PREPARE km_org_snapshot_statement FROM @km_org_snapshot_sql;
EXECUTE km_org_snapshot_statement;
DEALLOCATE PREPARE km_org_snapshot_statement;

INSERT IGNORE INTO `km_point_ledger` (
    `user_id`, `event_type`, `event_key`, `points`, `department_snapshot`,
    `km_pengajuan_id`, `km_insight_id`, `notes`, `created_by`, `created_at`
)
SELECT
    `user`.`id`,
    'opening_balance',
    CONCAT('opening_balance:', `user`.`id`),
    `user`.`km_total_poin`,
    COALESCE(`snapshot`.`department_name`, NULLIF(TRIM(`user`.`section`), '')),
    NULL,
    NULL,
    JSON_OBJECT('source', 'users.km_total_poin', 'effective_at', NOW()),
    NULL,
    NOW()
FROM `users` AS `user`
LEFT JOIN `tmp_km_department_snapshot` AS `snapshot`
  ON `snapshot`.`user_id` = `user`.`id`
WHERE `user`.`km_total_poin` > 0;

DROP TEMPORARY TABLE `tmp_km_department_snapshot`;

SET @km_130004_migration = '2026_07_27_130004_create_km_point_ledger_table';
SET @km_130004_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT @km_130004_migration, @km_130004_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = @km_130004_migration
);

-- VERIFIKASI: drift_user_count harus 0 segera setelah opening balance.
SELECT COUNT(*) AS `drift_user_count`
FROM (
    SELECT `user`.`id`
    FROM `users` AS `user`
    LEFT JOIN `km_point_ledger` AS `ledger` ON `ledger`.`user_id` = `user`.`id`
    GROUP BY `user`.`id`, `user`.`km_total_poin`
    HAVING COALESCE(`user`.`km_total_poin`, 0) <> COALESCE(SUM(`ledger`.`points`), 0)
) AS `drift`;

SELECT `event_type`, COUNT(*) AS `row_count`, SUM(`points`) AS `point_total`
FROM `km_point_ledger`
GROUP BY `event_type`
ORDER BY `event_type`;

SELECT `TABLE_NAME`, `CONSTRAINT_NAME`, `DELETE_RULE`
FROM `information_schema`.`REFERENTIAL_CONSTRAINTS`
WHERE `CONSTRAINT_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'km_point_ledger'
ORDER BY `CONSTRAINT_NAME`;

SELECT `migration`, `batch` FROM `migrations` WHERE `migration` = @km_130004_migration;

-- ================================================================
-- ROLLBACK MANUAL (DINONAKTIFKAN; ledger adalah catatan finansial-operasional)
-- ================================================================
-- Production mengutamakan rollback code. DROP ledger hanya setelah backup,
-- tidak ada award baru, reconciliation tersimpan, dan persetujuan stakeholder:
-- DELETE FROM `migrations` WHERE `migration` = @km_130004_migration;
-- DROP TABLE `km_point_ledger`;

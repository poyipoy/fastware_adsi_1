-- File 00 harus lulus. Hanya menambahkan tabel audit canonical jika belum ada.
-- DDL MySQL tidak ikut transaksi data; lakukan backup sebelum menjalankan urutan impor.
DELIMITER $$
DROP PROCEDURE IF EXISTS sales_dept_20261026_prerequisites$$
CREATE PROCEDURE sales_dept_20261026_prerequisites()
SQL SECURITY INVOKER
BEGIN
    DECLARE v_completed BOOLEAN;
    DECLARE v_actor BIGINT UNSIGNED;
    CALL sales_dept_20261026_validate(FALSE,v_completed,v_actor);
    IF NOT EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='km_organization_assignment_audits') THEN
        CREATE TABLE km_organization_assignment_audits (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_job_position_id BIGINT UNSIGNED NULL,
            actor_id BIGINT UNSIGNED NULL,
            action VARCHAR(32) NOT NULL,
            before_state JSON NULL,
            after_state JSON NULL,
            reason TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY km_org_assignment_audits_assignment_index (user_job_position_id,created_at),
            CONSTRAINT km_org_assignment_audits_assignment_foreign FOREIGN KEY (user_job_position_id) REFERENCES user_job_positions(id) ON DELETE SET NULL,
            CONSTRAINT km_org_assignment_audits_actor_foreign FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    END IF;
    CALL sales_dept_20261026_validate(TRUE,v_completed,v_actor);
    SELECT IF(v_completed,'COMPLETED_NOOP','PREREQUISITES_READY') status;
END$$
DELIMITER ;
CALL sales_dept_20261026_prerequisites();

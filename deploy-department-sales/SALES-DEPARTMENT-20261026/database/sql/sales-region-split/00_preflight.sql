-- Pilih database HR tujuan di phpMyAdmin. Backup dahulu. Urutan impor: 00, 01, 02, 03.
-- Tidak ada USE, data dump lokal, penulisan data aplikasi, atau perubahan tabel pada file ini.
-- Membutuhkan CREATE ROUTINE, ALTER ROUTINE, EXECUTE, SELECT. Helper memakai hak pemanggil.
DELIMITER $$
DROP PROCEDURE IF EXISTS sales_dept_20261026_validate$$
CREATE PROCEDURE sales_dept_20261026_validate(IN p_require_audit BOOLEAN, OUT p_completed BOOLEAN, OUT p_actor BIGINT UNSIGNED)
SQL SECURITY INVOKER
READS SQL DATA
validate_release: BEGIN
    DECLARE v_bad INT DEFAULT 0;
    DECLARE v_d12 BIGINT UNSIGNED;
    DECLARE v_d34 BIGINT UNSIGNED;
    DECLARE v_h12 BIGINT UNSIGNED;
    DECLARE v_h34 BIGINT UNSIGNED;
    DECLARE v_div BIGINT UNSIGNED;
    DECLARE v_state LONGTEXT;
    DECLARE v_today DATE;
    SET p_completed = FALSE;
    SET p_actor = NULL;
    SET v_today=DATE(UTC_TIMESTAMP()+INTERVAL 7 HOUR);
    IF DATABASE() IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pilih database HR tujuan terlebih dahulu.'; END IF;
    IF (LOCATE('MariaDB', VERSION()) > 0 AND
        (CAST(SUBSTRING_INDEX(VERSION(), '.', 1) AS UNSIGNED) < 10 OR
         (CAST(SUBSTRING_INDEX(VERSION(), '.', 1) AS UNSIGNED) = 10 AND CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(VERSION(), '.', 2), '.', -1) AS UNSIGNED) < 5)))
        OR (LOCATE('MariaDB', VERSION()) = 0 AND CAST(SUBSTRING_INDEX(VERSION(), '.', 1) AS UNSIGNED) < 8) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'SQL memerlukan MySQL 8+ atau MariaDB 10.5+; jangan lanjutkan pada versi lain.';
    END IF;
    SELECT COUNT(*) INTO v_bad FROM (
        SELECT 'users' t, 'id' c UNION ALL SELECT 'users','name' UNION ALL SELECT 'users','is_active'
        UNION ALL SELECT 'mst_departments','id' UNION ALL SELECT 'mst_departments','name' UNION ALL SELECT 'mst_departments','is_active'
        UNION ALL SELECT 'mst_departments','created_at' UNION ALL SELECT 'mst_departments','updated_at'
        UNION ALL SELECT 'mst_sections','id' UNION ALL SELECT 'mst_sections','name' UNION ALL SELECT 'mst_sections','department_id'
        UNION ALL SELECT 'mst_sections','is_active' UNION ALL SELECT 'mst_sections','updated_at'
        UNION ALL SELECT 'mst_job_positions','id' UNION ALL SELECT 'mst_job_positions','position_name'
        UNION ALL SELECT 'mst_job_positions','department_id' UNION ALL SELECT 'mst_job_positions','section_id'
        UNION ALL SELECT 'mst_job_positions','job_level' UNION ALL SELECT 'mst_job_positions','is_active'
        UNION ALL SELECT 'mst_job_positions','is_key_position' UNION ALL SELECT 'mst_job_positions','created_at' UNION ALL SELECT 'mst_job_positions','updated_at'
        UNION ALL SELECT 'mst_position_approvals','id' UNION ALL SELECT 'mst_position_approvals','position_id'
        UNION ALL SELECT 'mst_position_approvals','approval_level' UNION ALL SELECT 'mst_position_approvals','approver_position_id'
        UNION ALL SELECT 'mst_position_approvals','created_at' UNION ALL SELECT 'mst_position_approvals','updated_at'
        UNION ALL SELECT 'user_job_positions','id' UNION ALL SELECT 'user_job_positions','user_id'
        UNION ALL SELECT 'user_job_positions','mst_job_position_id' UNION ALL SELECT 'user_job_positions','is_active'
        UNION ALL SELECT 'user_job_positions','effective_from' UNION ALL SELECT 'user_job_positions','effective_until'
        UNION ALL SELECT 'user_job_positions','assignment_source' UNION ALL SELECT 'user_job_positions','created_at' UNION ALL SELECT 'user_job_positions','updated_at'
        UNION ALL SELECT 'mst_tcs','id_job_position' UNION ALL SELECT 'mst_soft_skills','id_job_position'
        UNION ALL SELECT 'mst_additionals','id_job_position' UNION ALL SELECT 'trs_penilaian_tcs','id'
        UNION ALL SELECT 'detail_penilaian_tcs','id' UNION ALL SELECT 'mst_pd_pengajuans','id' UNION ALL SELECT 'working_experiences','id'
    ) required_columns LEFT JOIN information_schema.COLUMNS actual
      ON actual.TABLE_SCHEMA = DATABASE() AND actual.TABLE_NAME = required_columns.t AND actual.COLUMN_NAME = required_columns.c
    WHERE actual.COLUMN_NAME IS NULL;
    IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Prasyarat HR/kolom periode assignment belum lengkap. Hentikan; jangan impor ulang seluruh HR.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN ('users','mst_departments','mst_sections','mst_job_positions','mst_position_approvals','user_job_positions')
      AND (ENGINE <> 'InnoDB' OR TABLE_TYPE <> 'BASE TABLE');
    IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tabel organisasi wajib InnoDB untuk rollback dan locking.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN ('users','user_job_positions') AND COLUMN_NAME = 'id' AND COLUMN_TYPE NOT LIKE 'bigint%unsigned';
    IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tipe ID users/assignment tidak cocok dengan FK audit canonical.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM (
        SELECT 'mst_sections' t, 'department_id' c, 'mst_departments' parent_t
        UNION ALL SELECT 'mst_job_positions','department_id','mst_departments'
        UNION ALL SELECT 'mst_job_positions','section_id','mst_sections'
        UNION ALL SELECT 'mst_position_approvals','position_id','mst_job_positions'
        UNION ALL SELECT 'mst_position_approvals','approver_position_id','mst_job_positions'
        UNION ALL SELECT 'user_job_positions','user_id','users'
        UNION ALL SELECT 'user_job_positions','mst_job_position_id','mst_job_positions'
    ) expected_fk WHERE NOT EXISTS (SELECT 1 FROM information_schema.KEY_COLUMN_USAGE actual
      WHERE actual.TABLE_SCHEMA=DATABASE() AND actual.TABLE_NAME=expected_fk.t AND actual.COLUMN_NAME=expected_fk.c
        AND actual.REFERENCED_TABLE_SCHEMA=DATABASE() AND actual.REFERENCED_TABLE_NAME=expected_fk.parent_t AND actual.REFERENCED_COLUMN_NAME='id');
    IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'FK organisasi tidak lengkap/berbeda; periksa schema sebelum deploy.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM (
        SELECT 'mst_departments' t, 'name' cols UNION ALL SELECT 'mst_sections','department_id,name'
        UNION ALL SELECT 'mst_job_positions','position_name' UNION ALL SELECT 'mst_position_approvals','position_id,approval_level'
    ) expected_unique WHERE NOT EXISTS (SELECT 1 FROM (
        SELECT TABLE_NAME,INDEX_NAME,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') cols,
          SUM(CASE WHEN SUB_PART IS NULL THEN 0 ELSE 1 END) prefixes
        FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND NON_UNIQUE=0 GROUP BY TABLE_NAME,INDEX_NAME
    ) actual WHERE actual.TABLE_NAME=expected_unique.t AND actual.cols=expected_unique.cols AND actual.prefixes=0);
    IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unique constraint organisasi tidak lengkap.'; END IF;

    IF EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='km_organization_assignment_audits') THEN
        SELECT COUNT(*) INTO v_bad FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='km_organization_assignment_audits'
          AND COLUMN_NAME IN ('id','user_job_position_id','actor_id','action','before_state','after_state','reason','created_at');
        IF v_bad <> 8 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Schema tabel audit existing tidak kompatibel.'; END IF;
        SELECT COUNT(*) INTO v_bad FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='km_organization_assignment_audits' AND (
          (COLUMN_NAME IN ('id','actor_id','user_job_position_id') AND COLUMN_TYPE NOT LIKE 'bigint%unsigned')
          OR (COLUMN_NAME='id' AND (EXTRA NOT LIKE '%auto_increment%' OR COLUMN_KEY<>'PRI'))
          OR (COLUMN_NAME IN ('actor_id','user_job_position_id','before_state','after_state') AND IS_NULLABLE<>'YES')
          OR (COLUMN_NAME='action' AND (DATA_TYPE<>'varchar' OR CHARACTER_MAXIMUM_LENGTH<32))
          OR (COLUMN_NAME IN ('before_state','after_state') AND DATA_TYPE NOT IN ('json','longtext','mediumtext','text'))
          OR (COLUMN_NAME='reason' AND DATA_TYPE NOT IN ('longtext','mediumtext','text'))
          OR (COLUMN_NAME='created_at' AND DATA_TYPE NOT IN ('timestamp','datetime')));
        IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tipe/nullability kolom audit existing tidak kompatibel.'; END IF;
        SELECT COUNT(*) INTO v_bad FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='km_organization_assignment_audits' AND ENGINE='InnoDB';
        IF v_bad <> 1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tabel audit wajib InnoDB.'; END IF;
        SELECT COUNT(*) INTO v_bad FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='km_organization_assignment_audits'
          AND REFERENCED_TABLE_SCHEMA=DATABASE() AND REFERENCED_COLUMN_NAME='id'
          AND ((COLUMN_NAME='actor_id' AND REFERENCED_TABLE_NAME='users') OR (COLUMN_NAME='user_job_position_id' AND REFERENCED_TABLE_NAME='user_job_positions'));
        IF v_bad <> 2 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'FK tabel audit existing tidak kompatibel.'; END IF;
        SELECT COUNT(*) INTO v_bad FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()
          AND TABLE_NAME='km_organization_assignment_audits' AND REFERENCED_TABLE_NAME IN ('users','user_job_positions') AND DELETE_RULE<>'SET NULL';
        IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Aturan delete FK audit wajib SET NULL.'; END IF;
        SELECT COUNT(*) INTO v_bad FROM km_organization_assignment_audits WHERE action='sales_region_split_completed';
        IF v_bad > 1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jurnal pemisahan duplikat; perlu pemeriksaan manual.'; END IF;
        IF v_bad = 1 THEN
            SELECT after_state,actor_id INTO v_state,p_actor FROM km_organization_assignment_audits WHERE action='sales_region_split_completed';
            IF COALESCE(JSON_UNQUOTE(JSON_EXTRACT(v_state,'$.version')),'') <> 'sales_region_split_v1'
               OR COALESCE(JSON_LENGTH(JSON_EXTRACT(v_state,'$.head_ids')),0) <> 4
               OR COALESCE(JSON_LENGTH(JSON_EXTRACT(v_state,'$.department_ids')),0) <> 4 THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jurnal pemisahan tidak valid; tidak boleh mengulang perubahan.';
            END IF;
            SET p_completed=TRUE;
            LEAVE validate_release;
        END IF;
    ELSEIF p_require_audit THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Impor 01_prerequisites.sql sebelum 02.';
    END IF;

    SELECT COUNT(*),MIN(id) INTO v_bad,p_actor FROM users WHERE BINARY name=BINARY 'SITI MARIA ULFA';
    IF v_bad <> 1 OR NOT EXISTS (SELECT 1 FROM users WHERE id=p_actor AND is_active=0) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Actor SITI MARIA ULFA harus unik dan login-enabled; ID lokal tidak digunakan.';
    END IF;
    SELECT COUNT(*) INTO v_bad FROM mst_departments WHERE name IN ('Sales Region 1 & 2','Sales Region 3 & 4') AND is_active=1;
    IF v_bad <> 2 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dua department sumber aktif belum ditemukan secara tepat.'; END IF;
    SELECT id INTO v_d12 FROM mst_departments WHERE name='Sales Region 1 & 2';
    SELECT id INTO v_d34 FROM mst_departments WHERE name='Sales Region 3 & 4';
    SELECT COUNT(*) INTO v_bad FROM mst_job_positions WHERE position_name IN ('Sales Dept Head Region 1&2','Sales Dept. Head Region 3&4') AND is_active=1 AND job_level='dept_head' AND section_id IS NULL;
    IF v_bad <> 2 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dua kepala sumber aktif/level dept_head tidak valid.'; END IF;
    SELECT id INTO v_h12 FROM mst_job_positions WHERE position_name='Sales Dept Head Region 1&2' AND department_id=v_d12;
    SELECT id INTO v_h34 FROM mst_job_positions WHERE position_name='Sales Dept. Head Region 3&4' AND department_id=v_d34;
    IF v_h12 IS NULL OR v_h34 IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Department kepala sumber tidak cocok.'; END IF;
    SELECT COUNT(*),MIN(id) INTO v_bad,v_div FROM mst_job_positions WHERE position_name='Sales Div Head' AND job_level='div_head' AND is_active=1;
    IF v_bad <> 1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sales Div Head harus unik dan aktif.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM mst_departments WHERE name IN ('Sales Region 1','Sales Region 2','Sales Region 3','Sales Region 4');
    IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Department tujuan sudah ada tanpa jurnal selesai. Jangan timpa data.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM mst_job_positions WHERE position_name IN ('Sales Dept Head Region 1','Sales Dept Head Region 2','Sales Dept Head Region 3','Sales Dept Head Region 4');
    IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kepala tujuan sudah ada tanpa jurnal selesai.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM mst_sections WHERE
        (name IN ('Sales Region 1','Sales Region 2') AND department_id=v_d12) OR
        (name IN ('Sales Region 3','Sales Region 4') AND department_id=v_d34);
    IF v_bad <> 4 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Empat section regional sumber belum lengkap.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM mst_sections WHERE department_id IN (v_d12,v_d34);
    IF v_bad <> 4 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Department sumber harus tepat memiliki empat section regional.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM mst_sections WHERE department_id IN (v_d12,v_d34) AND name NOT IN ('Sales Region 1','Sales Region 2','Sales Region 3','Sales Region 4');
    IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ada section Sales di luar empat region; perlu mapping eksplisit.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM mst_job_positions p LEFT JOIN mst_sections s ON s.id=p.section_id
      WHERE (p.department_id IN (v_d12,v_d34) AND p.id NOT IN (v_h12,v_h34) AND (s.id IS NULL OR s.department_id<>p.department_id))
         OR (s.department_id IN (v_d12,v_d34) AND (p.department_id IS NULL OR p.department_id<>s.department_id));
    IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Mapping department/section posisi sumber tidak konsisten.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM mst_position_approvals a LEFT JOIN mst_job_positions p ON p.id=a.position_id LEFT JOIN mst_sections s ON s.id=p.section_id
      WHERE a.approver_position_id IN (v_h12,v_h34) AND (s.id IS NULL OR s.department_id NOT IN (v_d12,v_d34) OR a.approval_level IS NULL OR a.approval_level NOT IN (0,1,2,3));
    IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Approval kepala lama di luar section regional; jangan menebak mapping.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM mst_position_approvals a LEFT JOIN mst_job_positions p ON p.id=a.approver_position_id
      WHERE a.position_id IN (v_h12,v_h34) AND (a.approval_level IS NULL OR a.approval_level NOT IN (0,1,2,3)
        OR (a.approver_position_id IS NOT NULL AND (p.id IS NULL OR p.is_active<>1))
        OR (a.approval_level=3 AND (a.approver_position_id IS NULL OR a.approver_position_id<>v_div)));
    IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Outgoing approval kepala lama tidak kompatibel.'; END IF;
    IF NOT EXISTS (SELECT 1 FROM user_job_positions WHERE mst_job_position_id=v_h12 AND is_active=1)
       OR NOT EXISTS (SELECT 1 FROM user_job_positions WHERE mst_job_position_id=v_h34 AND is_active=1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kepala sumber belum memiliki assignment aktif.';
    END IF;
    SELECT COUNT(*) INTO v_bad FROM user_job_positions a LEFT JOIN users u ON u.id=a.user_id WHERE a.mst_job_position_id IN (v_h12,v_h34) AND a.is_active=1
      AND (a.effective_from IS NULL OR a.effective_from>=v_today OR (a.effective_until IS NOT NULL AND a.effective_until<v_today) OR u.id IS NULL);
    IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Periode/incumbent tidak valid untuk penutupan kemarin.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM (SELECT user_id,mst_job_position_id FROM user_job_positions WHERE mst_job_position_id IN (v_h12,v_h34) AND is_active=1 GROUP BY user_id,mst_job_position_id HAVING COUNT(*)>1) duplicated;
    IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Assignment kepala sumber duplikat.'; END IF;
    IF EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='km_document_version_departments') THEN
        SELECT COUNT(*) INTO v_bad FROM km_document_version_departments WHERE department_id IN (v_d12,v_d34);
        IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Target department KM lama perlu keputusan sebelum pemisahan.'; END IF;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='km_document_version_job_positions') THEN
        SELECT COUNT(*) INTO v_bad FROM km_document_version_job_positions WHERE job_position_id IN (v_h12,v_h34);
        IF v_bad <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Target job kepala KM lama perlu keputusan sebelum pemisahan.'; END IF;
    END IF;
END$$
DELIMITER ;
CALL sales_dept_20261026_validate(FALSE,@sales_release_completed,@sales_release_actor);
SELECT DATABASE() selected_database,VERSION() engine_version,
  IF(@sales_release_completed,'COMPLETED_NOOP',IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='km_organization_assignment_audits'),'READY','READY_NEEDS_01_AUDIT_TABLE')) status,
  @sales_release_actor resolved_actor_id;

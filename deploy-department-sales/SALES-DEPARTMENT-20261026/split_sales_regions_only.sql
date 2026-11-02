-- SPLIT SALES SAJA: impor SATU file ini setelah backup dan pembatasan akses aplikasi.
-- Tidak memerlukan file 00-03. Jangan mengimpor kedua jalur bersamaan.
-- Tabel audit/semua prasyarat HR wajib SUDAH ADA. Tidak membuat/ALTER tabel aplikasi.
-- Helper routine dan tabel TEMPORARY hanya dipakai saat eksekusi; helper dibersihkan setelah berhasil.
-- Jika ada error, hentikan. Data cutover di-rollback; helper dapat tersisa jika client berhenti pada error.
-- Pilih database HR server tujuan di phpMyAdmin. Tidak ada USE atau ID master/user lokal.
DELIMITER $$
DROP PROCEDURE IF EXISTS sales_dept_20261026_only_validate$$
CREATE PROCEDURE sales_dept_20261026_only_validate(IN p_require_audit BOOLEAN, OUT p_completed BOOLEAN, OUT p_actor BIGINT UNSIGNED)
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
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tabel audit belum tersedia; split-only dibatalkan tanpa perubahan tabel.';
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

CALL sales_dept_20261026_only_validate(TRUE,@sales_release_completed,@sales_release_actor);
SELECT IF(@sales_release_completed,'COMPLETED_NOOP','READY') status,@sales_release_actor resolved_actor_id;
DELIMITER $$
DROP PROCEDURE IF EXISTS sales_dept_20261026_only_apply$$
CREATE PROCEDURE sales_dept_20261026_only_apply()
SQL SECURITY INVOKER
apply_release: BEGIN
    DECLARE v_done BOOLEAN DEFAULT FALSE;
    DECLARE v_completed BOOLEAN;
    DECLARE v_actor BIGINT UNSIGNED;
    DECLARE v_dummy BIGINT UNSIGNED;
    DECLARE v_d12 BIGINT UNSIGNED;
    DECLARE v_d34 BIGINT UNSIGNED;
    DECLARE v_h12 BIGINT UNSIGNED;
    DECLARE v_h34 BIGINT UNSIGNED;
    DECLARE v_div BIGINT UNSIGNED;
    DECLARE v_department BIGINT UNSIGNED;
    DECLARE v_head BIGINT UNSIGNED;
    DECLARE v_region INT DEFAULT 1;
    DECLARE v_bad INT DEFAULT 0;
    DECLARE v_expected_assignments INT;
    DECLARE v_expected_routes INT;
    DECLARE v_effective DATE;
    DECLARE v_now DATETIME;
    DECLARE v_original_timezone VARCHAR(64);
    DECLARE v_backup LONGTEXT;
    DECLARE v_departments LONGTEXT;
    DECLARE v_sections LONGTEXT;
    DECLARE v_positions LONGTEXT;
    DECLARE v_approvals LONGTEXT;
    DECLARE v_assignments LONGTEXT;
    DECLARE v_counts LONGTEXT;
    DECLARE v_department_ids LONGTEXT;
    DECLARE v_head_ids LONGTEXT;
    DECLARE v_hash CHAR(64);
    DECLARE c_departments CURSOR FOR SELECT id FROM mst_departments ORDER BY id FOR UPDATE;
    DECLARE c_sections CURSOR FOR SELECT id FROM mst_sections ORDER BY id FOR UPDATE;
    DECLARE c_positions CURSOR FOR SELECT id FROM mst_job_positions ORDER BY id FOR UPDATE;
    DECLARE c_approvals CURSOR FOR SELECT id FROM mst_position_approvals ORDER BY id FOR UPDATE;
    DECLARE c_assignments CURSOR FOR SELECT id FROM user_job_positions ORDER BY id FOR UPDATE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        DROP TEMPORARY TABLE IF EXISTS tmp_sales_dept_20261026_only_map;
        IF v_original_timezone IS NOT NULL THEN SET SESSION time_zone=v_original_timezone; END IF;
        RESIGNAL;
    END;

    SET v_original_timezone=@@session.time_zone;
    SET SESSION time_zone='+07:00';
    SET v_now=NOW();
    SET v_effective=CURRENT_DATE();
    -- Validate before starting a transaction, then again after acquiring current organization locks.
    CALL sales_dept_20261026_only_validate(TRUE,v_completed,v_actor);
    IF v_completed THEN
        SET SESSION time_zone=v_original_timezone;
        SELECT 'COMPLETED_NOOP' status;
        LEAVE apply_release;
    END IF;
    START TRANSACTION;
    SET v_done=FALSE; OPEN c_departments;
    lock_departments: LOOP FETCH c_departments INTO v_dummy; IF v_done THEN LEAVE lock_departments; END IF; END LOOP; CLOSE c_departments;
    SET v_done=FALSE; OPEN c_sections;
    lock_sections: LOOP FETCH c_sections INTO v_dummy; IF v_done THEN LEAVE lock_sections; END IF; END LOOP; CLOSE c_sections;
    SET v_done=FALSE; OPEN c_positions;
    lock_positions: LOOP FETCH c_positions INTO v_dummy; IF v_done THEN LEAVE lock_positions; END IF; END LOOP; CLOSE c_positions;
    SET v_done=FALSE; OPEN c_approvals;
    lock_approvals: LOOP FETCH c_approvals INTO v_dummy; IF v_done THEN LEAVE lock_approvals; END IF; END LOOP; CLOSE c_approvals;
    SET v_done=FALSE; OPEN c_assignments;
    lock_assignments: LOOP FETCH c_assignments INTO v_dummy; IF v_done THEN LEAVE lock_assignments; END IF; END LOOP; CLOSE c_assignments;
    CALL sales_dept_20261026_only_validate(TRUE,v_completed,v_actor);
    IF v_completed THEN
        COMMIT;
        SET SESSION time_zone=v_original_timezone;
        SELECT 'COMPLETED_NOOP' status;
        LEAVE apply_release;
    END IF;
    SELECT id INTO v_d12 FROM mst_departments WHERE name='Sales Region 1 & 2';
    SELECT id INTO v_d34 FROM mst_departments WHERE name='Sales Region 3 & 4';
    SELECT id INTO v_h12 FROM mst_job_positions WHERE position_name='Sales Dept Head Region 1&2';
    SELECT id INTO v_h34 FROM mst_job_positions WHERE position_name='Sales Dept. Head Region 3&4';
    SELECT id INTO v_div FROM mst_job_positions WHERE position_name='Sales Div Head';
    SELECT COUNT(*)*2 INTO v_expected_assignments FROM user_job_positions WHERE mst_job_position_id IN (v_h12,v_h34) AND is_active=1;
    SELECT COUNT(*) INTO v_expected_routes FROM mst_position_approvals WHERE approver_position_id IN (v_h12,v_h34);

    CREATE TEMPORARY TABLE tmp_sales_dept_20261026_only_map (
        region INT NOT NULL PRIMARY KEY, section_id BIGINT UNSIGNED NOT NULL,
        source_head_id BIGINT UNSIGNED NOT NULL, department_id BIGINT UNSIGNED NULL, head_id BIGINT UNSIGNED NULL
    ) ENGINE=InnoDB;
    INSERT INTO tmp_sales_dept_20261026_only_map(region,section_id,source_head_id)
      SELECT CAST(RIGHT(name,1) AS UNSIGNED),id,IF(name IN ('Sales Region 1','Sales Region 3'),v_h12,v_h34)
      FROM mst_sections WHERE department_id IN (v_d12,v_d34);

    SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('id',id,'name',name,'is_active',is_active,'created_at',created_at,'updated_at',updated_at)),JSON_ARRAY()) INTO v_departments
      FROM mst_departments WHERE id IN (v_d12,v_d34);
    SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('id',id,'department_id',department_id,'name',name,'is_active',is_active,'created_at',created_at,'updated_at',updated_at)),JSON_ARRAY()) INTO v_sections
      FROM mst_sections WHERE department_id IN (v_d12,v_d34);
    SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('id',id,'position_name',position_name,'department_id',department_id,'section_id',section_id,'job_level',job_level,'is_active',is_active,'is_key_position',is_key_position,'created_at',created_at,'updated_at',updated_at)),JSON_ARRAY()) INTO v_positions
      FROM mst_job_positions WHERE department_id IN (v_d12,v_d34);
    SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('id',a.id,'position_id',a.position_id,'approval_level',a.approval_level,'approver_position_id',a.approver_position_id,'created_at',a.created_at,'updated_at',a.updated_at)),JSON_ARRAY()) INTO v_approvals
      FROM mst_position_approvals a JOIN mst_job_positions p ON p.id=a.position_id
      WHERE a.approver_position_id IN (v_h12,v_h34) OR p.department_id IN (v_d12,v_d34);
    SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('id',id,'user_id',user_id,'mst_job_position_id',mst_job_position_id,'is_active',is_active,'effective_from',effective_from,'effective_until',effective_until,'assignment_source',assignment_source,'created_at',created_at,'updated_at',updated_at)),JSON_ARRAY()) INTO v_assignments
      FROM user_job_positions WHERE mst_job_position_id IN (v_h12,v_h34) AND is_active=1;
    SET v_counts=JSON_OBJECT('mst_tcs',(SELECT COUNT(*) FROM mst_tcs),'mst_soft_skills',(SELECT COUNT(*) FROM mst_soft_skills),
      'mst_additionals',(SELECT COUNT(*) FROM mst_additionals),'trs_penilaian_tcs',(SELECT COUNT(*) FROM trs_penilaian_tcs),
      'detail_penilaian_tcs',(SELECT COUNT(*) FROM detail_penilaian_tcs),'mst_pd_pengajuans',(SELECT COUNT(*) FROM mst_pd_pengajuans),
      'working_experiences',(SELECT COUNT(*) FROM working_experiences));
    SET v_backup=JSON_OBJECT('departments',JSON_EXTRACT(v_departments,'$'),'sections',JSON_EXTRACT(v_sections,'$'),
      'positions',JSON_EXTRACT(v_positions,'$'),'approvals',JSON_EXTRACT(v_approvals,'$'),'assignments',JSON_EXTRACT(v_assignments,'$'),
      'history_counts',JSON_EXTRACT(v_counts,'$'));
    SET v_hash=SHA2(v_backup,256);
    SET v_department_ids=JSON_OBJECT(); SET v_head_ids=JSON_OBJECT();

    WHILE v_region<=4 DO
        INSERT INTO mst_departments(name,is_active,created_at,updated_at) VALUES(CONCAT('Sales Region ',v_region),1,v_now,v_now);
        SET v_department=LAST_INSERT_ID();
        INSERT INTO mst_job_positions(position_name,department_id,section_id,job_level,is_active,is_key_position,created_at,updated_at)
          VALUES(CONCAT('Sales Dept Head Region ',v_region),v_department,NULL,'dept_head',1,1,v_now,v_now);
        SET v_head=LAST_INSERT_ID();
        UPDATE tmp_sales_dept_20261026_only_map SET department_id=v_department,head_id=v_head WHERE region=v_region;
        SET v_department_ids=JSON_SET(v_department_ids,CONCAT('$."',v_region,'"'),v_department);
        SET v_head_ids=JSON_SET(v_head_ids,CONCAT('$."',v_region,'"'),v_head);
        SET v_region=v_region+1;
    END WHILE;
    UPDATE mst_sections s JOIN tmp_sales_dept_20261026_only_map m ON m.section_id=s.id SET s.department_id=m.department_id,s.updated_at=v_now;
    UPDATE mst_job_positions p JOIN tmp_sales_dept_20261026_only_map m ON m.section_id=p.section_id SET p.department_id=m.department_id,p.updated_at=v_now;
    UPDATE mst_position_approvals a JOIN mst_job_positions p ON p.id=a.position_id JOIN tmp_sales_dept_20261026_only_map m ON m.section_id=p.section_id
      SET a.approver_position_id=m.head_id,a.updated_at=v_now WHERE a.approver_position_id IN (v_h12,v_h34);
    IF ROW_COUNT()<>v_expected_routes THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah approval yang dipindah berubah; rollback.'; END IF;
    INSERT INTO mst_position_approvals(position_id,approval_level,approver_position_id,created_at,updated_at)
      SELECT m.head_id,a.approval_level,a.approver_position_id,v_now,v_now FROM tmp_sales_dept_20261026_only_map m
      JOIN mst_position_approvals a ON a.position_id=m.source_head_id WHERE a.approval_level<>3;
    INSERT INTO mst_position_approvals(position_id,approval_level,approver_position_id,created_at,updated_at)
      SELECT head_id,3,v_div,v_now,v_now FROM tmp_sales_dept_20261026_only_map;
    INSERT INTO user_job_positions(user_id,mst_job_position_id,is_active,effective_from,effective_until,assignment_source,created_at,updated_at)
      SELECT a.user_id,m.head_id,1,v_effective,NULL,'sales_region_split_v1',v_now,v_now
      FROM user_job_positions a JOIN tmp_sales_dept_20261026_only_map m ON m.source_head_id=a.mst_job_position_id WHERE a.is_active=1;
    IF ROW_COUNT()<>v_expected_assignments THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah assignment baru tidak sesuai; rollback.'; END IF;
    INSERT INTO km_organization_assignment_audits(user_job_position_id,actor_id,action,before_state,after_state,reason,created_at)
      SELECT a.id,v_actor,'created',NULL,JSON_OBJECT('id',a.id,'user_id',a.user_id,'mst_job_position_id',a.mst_job_position_id,'is_active',a.is_active,
        'effective_from',a.effective_from,'effective_until',a.effective_until,'assignment_source',a.assignment_source,'created_at',a.created_at,'updated_at',a.updated_at),
        'Pemisahan department Sales menjadi empat region melalui SQL.',v_now
      FROM user_job_positions a JOIN tmp_sales_dept_20261026_only_map m ON m.head_id=a.mst_job_position_id;
    INSERT INTO km_organization_assignment_audits(user_job_position_id,actor_id,action,before_state,after_state,reason,created_at)
      SELECT a.id,v_actor,'updated',JSON_OBJECT('id',a.id,'user_id',a.user_id,'mst_job_position_id',a.mst_job_position_id,'is_active',a.is_active,
        'effective_from',a.effective_from,'effective_until',a.effective_until,'assignment_source',a.assignment_source,'created_at',a.created_at,'updated_at',a.updated_at),
        JSON_OBJECT('id',a.id,'user_id',a.user_id,'mst_job_position_id',a.mst_job_position_id,'is_active',0,'effective_from',a.effective_from,
          'effective_until',DATE_SUB(v_effective,INTERVAL 1 DAY),'assignment_source',a.assignment_source,'created_at',a.created_at,'updated_at',v_now),
        'Penutupan posisi kepala department Sales gabungan melalui SQL.',v_now
      FROM user_job_positions a WHERE a.mst_job_position_id IN (v_h12,v_h34) AND a.is_active=1;
    UPDATE user_job_positions SET is_active=0,effective_until=DATE_SUB(v_effective,INTERVAL 1 DAY),updated_at=v_now
      WHERE mst_job_position_id IN (v_h12,v_h34) AND is_active=1;
    UPDATE mst_job_positions SET is_active=0,updated_at=v_now WHERE id IN (v_h12,v_h34);
    UPDATE mst_departments SET is_active=0,updated_at=v_now WHERE id IN (v_d12,v_d34);

    SELECT COUNT(*) INTO v_bad FROM mst_job_positions p JOIN mst_sections s ON s.id=p.section_id
      JOIN tmp_sales_dept_20261026_only_map m ON m.section_id=s.id WHERE p.department_id<>s.department_id OR s.department_id<>m.department_id;
    IF v_bad<>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Postcheck konsistensi section/posisi gagal; rollback.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM mst_position_approvals WHERE approver_position_id IN(v_h12,v_h34);
    IF v_bad<>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Masih ada incoming approval ke kepala lama; rollback.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM user_job_positions WHERE mst_job_position_id IN(v_h12,v_h34) AND is_active=1;
    IF v_bad<>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Masih ada assignment aktif kepala lama; rollback.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM (
      SELECT id_job_position FROM mst_tcs UNION ALL SELECT id_job_position FROM mst_soft_skills UNION ALL SELECT id_job_position FROM mst_additionals
    ) standards JOIN tmp_sales_dept_20261026_only_map m ON m.head_id=standards.id_job_position;
    IF v_bad<>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Standar baru harus kosong; rollback.'; END IF;
    IF JSON_EXTRACT(v_counts,'$.mst_tcs')<>(SELECT COUNT(*) FROM mst_tcs)
      OR JSON_EXTRACT(v_counts,'$.mst_soft_skills')<>(SELECT COUNT(*) FROM mst_soft_skills)
      OR JSON_EXTRACT(v_counts,'$.mst_additionals')<>(SELECT COUNT(*) FROM mst_additionals)
      OR JSON_EXTRACT(v_counts,'$.trs_penilaian_tcs')<>(SELECT COUNT(*) FROM trs_penilaian_tcs)
      OR JSON_EXTRACT(v_counts,'$.detail_penilaian_tcs')<>(SELECT COUNT(*) FROM detail_penilaian_tcs)
      OR JSON_EXTRACT(v_counts,'$.mst_pd_pengajuans')<>(SELECT COUNT(*) FROM mst_pd_pengajuans)
      OR JSON_EXTRACT(v_counts,'$.working_experiences')<>(SELECT COUNT(*) FROM working_experiences) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah baris histori berubah selama cutover; rollback dan periksa aktivitas lain.';
    END IF;
    INSERT INTO km_organization_assignment_audits(user_job_position_id,actor_id,action,before_state,after_state,reason,created_at)
      VALUES(NULL,v_actor,'sales_region_split_completed',v_backup,
        JSON_OBJECT('version','sales_region_split_v1','hash',v_hash,'snapshot','sql:completion-audit.before_state',
          'department_ids',JSON_EXTRACT(v_department_ids,'$'),'head_ids',JSON_EXTRACT(v_head_ids,'$'),
          'effective_date',v_effective,'method','phpmyadmin_sql','updated_approval_references',v_expected_routes,'created_assignments',v_expected_assignments),
        'Cutover SQL selesai. Snapshot organisasi tersimpan pada before_state; eksekusi ulang tidak mengubah perubahan HR berikutnya.',v_now);
    DROP TEMPORARY TABLE tmp_sales_dept_20261026_only_map;
    COMMIT;
    SET SESSION time_zone=v_original_timezone;
    SELECT 'APPLIED' status,4 departments_created,4 heads_created,v_expected_routes approval_references_updated,v_expected_assignments assignments_created,v_effective effective_date,v_actor audit_actor_id;
END$$
DELIMITER ;
CALL sales_dept_20261026_only_apply();

-- Read-only data checks. Seluruh status mismatch harus diperiksa sebelum membuka kembali aplikasi.
-- before_state jurnal berisi snapshot baris organisasi yang disentuh; jangan publikasikan isinya.
SELECT DATABASE() selected_database,VERSION() engine_version;
SELECT COUNT(*) completion_journals,MIN(actor_id) audit_actor_id,MIN(created_at) applied_at
  FROM km_organization_assignment_audits WHERE action='sales_region_split_completed';
SELECT d.name department,d.is_active department_active,p.position_name head,p.job_level,p.is_active head_active,
  (SELECT COUNT(*) FROM mst_sections s WHERE s.department_id=d.id) sections,
  (SELECT COUNT(*) FROM mst_job_positions j WHERE j.department_id=d.id) positions,
  (SELECT COUNT(*) FROM user_job_positions a WHERE a.mst_job_position_id=p.id AND a.is_active=1
    AND a.effective_from<=DATE(UTC_TIMESTAMP()+INTERVAL 7 HOUR) AND (a.effective_until IS NULL OR a.effective_until>=DATE(UTC_TIMESTAMP()+INTERVAL 7 HOUR))) current_head_assignments
FROM mst_departments d JOIN mst_job_positions p ON p.department_id=d.id
WHERE d.name IN ('Sales Region 1','Sales Region 2','Sales Region 3','Sales Region 4') AND p.job_level='dept_head'
ORDER BY d.name;
SELECT 'section_department_mismatches' check_name,COUNT(*) violations
FROM mst_job_positions p JOIN mst_sections s ON s.id=p.section_id JOIN mst_departments d ON d.id=s.department_id
WHERE d.name IN ('Sales Region 1','Sales Region 2','Sales Region 3','Sales Region 4') AND (p.department_id IS NULL OR p.department_id<>s.department_id)
UNION ALL
SELECT 'active_combined_departments',COUNT(*) FROM mst_departments WHERE name IN ('Sales Region 1 & 2','Sales Region 3 & 4') AND is_active=1
UNION ALL
SELECT 'active_combined_heads',COUNT(*) FROM mst_job_positions WHERE position_name IN ('Sales Dept Head Region 1&2','Sales Dept. Head Region 3&4') AND is_active=1
UNION ALL
SELECT 'active_combined_assignments',COUNT(*) FROM user_job_positions a JOIN mst_job_positions p ON p.id=a.mst_job_position_id WHERE p.position_name IN ('Sales Dept Head Region 1&2','Sales Dept. Head Region 3&4') AND a.is_active=1
UNION ALL
SELECT 'incoming_routes_to_combined_heads',COUNT(*) FROM mst_position_approvals a JOIN mst_job_positions p ON p.id=a.approver_position_id WHERE p.position_name IN ('Sales Dept Head Region 1&2','Sales Dept. Head Region 3&4');
SELECT p.position_name head,a.user_id,a.effective_from,a.effective_until,a.is_active,a.assignment_source
FROM user_job_positions a JOIN mst_job_positions p ON p.id=a.mst_job_position_id
WHERE p.position_name IN ('Sales Dept Head Region 1','Sales Dept Head Region 2','Sales Dept Head Region 3','Sales Dept Head Region 4') ORDER BY a.user_id,p.position_name;
SELECT p.position_name position,a.approval_level,h.position_name approver
FROM mst_position_approvals a JOIN mst_job_positions p ON p.id=a.position_id LEFT JOIN mst_job_positions h ON h.id=a.approver_position_id
JOIN mst_departments d ON d.id=p.department_id
WHERE d.name IN ('Sales Region 1','Sales Region 2','Sales Region 3','Sales Region 4') ORDER BY p.position_name,a.approval_level;
SELECT 'mst_tcs' standard_table,COUNT(*) new_head_standards FROM mst_tcs s JOIN mst_job_positions p ON p.id=s.id_job_position WHERE p.position_name IN ('Sales Dept Head Region 1','Sales Dept Head Region 2','Sales Dept Head Region 3','Sales Dept Head Region 4')
UNION ALL SELECT 'mst_soft_skills',COUNT(*) FROM mst_soft_skills s JOIN mst_job_positions p ON p.id=s.id_job_position WHERE p.position_name IN ('Sales Dept Head Region 1','Sales Dept Head Region 2','Sales Dept Head Region 3','Sales Dept Head Region 4')
UNION ALL SELECT 'mst_additionals',COUNT(*) FROM mst_additionals s JOIN mst_job_positions p ON p.id=s.id_job_position WHERE p.position_name IN ('Sales Dept Head Region 1','Sales Dept Head Region 2','Sales Dept Head Region 3','Sales Dept Head Region 4');
-- Hanya helper release yang dihapus. Jurnal dan seluruh data aplikasi tetap disimpan.
DROP PROCEDURE IF EXISTS sales_dept_20261026_only_apply;
DROP PROCEDURE IF EXISTS sales_dept_20261026_only_validate;

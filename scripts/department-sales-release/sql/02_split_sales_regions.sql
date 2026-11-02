-- Jalankan pada jam sepi/maintenance setelah backup dan 00 + 01 lulus.
-- Tanggal efektif memakai kalender Asia/Jakarta. Tidak ada ID user/master dari database lokal.
-- Snapshot perubahan organisasi disimpan di before_state audit penyelesaian (bukan public file).
DELIMITER $$
DROP PROCEDURE IF EXISTS sales_dept_20261026_apply$$
CREATE PROCEDURE sales_dept_20261026_apply()
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
        DROP TEMPORARY TABLE IF EXISTS tmp_sales_dept_20261026_map;
        IF v_original_timezone IS NOT NULL THEN SET SESSION time_zone=v_original_timezone; END IF;
        RESIGNAL;
    END;

    SET v_original_timezone=@@session.time_zone;
    SET SESSION time_zone='+07:00';
    SET v_now=NOW();
    SET v_effective=CURRENT_DATE();
    -- Validate before starting a transaction, then again after acquiring current organization locks.
    CALL sales_dept_20261026_validate(TRUE,v_completed,v_actor);
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
    CALL sales_dept_20261026_validate(TRUE,v_completed,v_actor);
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

    CREATE TEMPORARY TABLE tmp_sales_dept_20261026_map (
        region INT NOT NULL PRIMARY KEY, section_id BIGINT UNSIGNED NOT NULL,
        source_head_id BIGINT UNSIGNED NOT NULL, department_id BIGINT UNSIGNED NULL, head_id BIGINT UNSIGNED NULL
    ) ENGINE=InnoDB;
    INSERT INTO tmp_sales_dept_20261026_map(region,section_id,source_head_id)
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
        UPDATE tmp_sales_dept_20261026_map SET department_id=v_department,head_id=v_head WHERE region=v_region;
        SET v_department_ids=JSON_SET(v_department_ids,CONCAT('$."',v_region,'"'),v_department);
        SET v_head_ids=JSON_SET(v_head_ids,CONCAT('$."',v_region,'"'),v_head);
        SET v_region=v_region+1;
    END WHILE;
    UPDATE mst_sections s JOIN tmp_sales_dept_20261026_map m ON m.section_id=s.id SET s.department_id=m.department_id,s.updated_at=v_now;
    UPDATE mst_job_positions p JOIN tmp_sales_dept_20261026_map m ON m.section_id=p.section_id SET p.department_id=m.department_id,p.updated_at=v_now;
    UPDATE mst_position_approvals a JOIN mst_job_positions p ON p.id=a.position_id JOIN tmp_sales_dept_20261026_map m ON m.section_id=p.section_id
      SET a.approver_position_id=m.head_id,a.updated_at=v_now WHERE a.approver_position_id IN (v_h12,v_h34);
    IF ROW_COUNT()<>v_expected_routes THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah approval yang dipindah berubah; rollback.'; END IF;
    INSERT INTO mst_position_approvals(position_id,approval_level,approver_position_id,created_at,updated_at)
      SELECT m.head_id,a.approval_level,a.approver_position_id,v_now,v_now FROM tmp_sales_dept_20261026_map m
      JOIN mst_position_approvals a ON a.position_id=m.source_head_id WHERE a.approval_level<>3;
    INSERT INTO mst_position_approvals(position_id,approval_level,approver_position_id,created_at,updated_at)
      SELECT head_id,3,v_div,v_now,v_now FROM tmp_sales_dept_20261026_map;
    INSERT INTO user_job_positions(user_id,mst_job_position_id,is_active,effective_from,effective_until,assignment_source,created_at,updated_at)
      SELECT a.user_id,m.head_id,1,v_effective,NULL,'sales_region_split_v1',v_now,v_now
      FROM user_job_positions a JOIN tmp_sales_dept_20261026_map m ON m.source_head_id=a.mst_job_position_id WHERE a.is_active=1;
    IF ROW_COUNT()<>v_expected_assignments THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah assignment baru tidak sesuai; rollback.'; END IF;
    INSERT INTO km_organization_assignment_audits(user_job_position_id,actor_id,action,before_state,after_state,reason,created_at)
      SELECT a.id,v_actor,'created',NULL,JSON_OBJECT('id',a.id,'user_id',a.user_id,'mst_job_position_id',a.mst_job_position_id,'is_active',a.is_active,
        'effective_from',a.effective_from,'effective_until',a.effective_until,'assignment_source',a.assignment_source,'created_at',a.created_at,'updated_at',a.updated_at),
        'Pemisahan department Sales menjadi empat region melalui SQL.',v_now
      FROM user_job_positions a JOIN tmp_sales_dept_20261026_map m ON m.head_id=a.mst_job_position_id;
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
      JOIN tmp_sales_dept_20261026_map m ON m.section_id=s.id WHERE p.department_id<>s.department_id OR s.department_id<>m.department_id;
    IF v_bad<>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Postcheck konsistensi section/posisi gagal; rollback.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM mst_position_approvals WHERE approver_position_id IN(v_h12,v_h34);
    IF v_bad<>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Masih ada incoming approval ke kepala lama; rollback.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM user_job_positions WHERE mst_job_position_id IN(v_h12,v_h34) AND is_active=1;
    IF v_bad<>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Masih ada assignment aktif kepala lama; rollback.'; END IF;
    SELECT COUNT(*) INTO v_bad FROM (
      SELECT id_job_position FROM mst_tcs UNION ALL SELECT id_job_position FROM mst_soft_skills UNION ALL SELECT id_job_position FROM mst_additionals
    ) standards JOIN tmp_sales_dept_20261026_map m ON m.head_id=standards.id_job_position;
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
    DROP TEMPORARY TABLE tmp_sales_dept_20261026_map;
    COMMIT;
    SET SESSION time_zone=v_original_timezone;
    SELECT 'APPLIED' status,4 departments_created,4 heads_created,v_expected_routes approval_references_updated,v_expected_assignments assignments_created,v_effective effective_date,v_actor audit_actor_id;
END$$
DELIMITER ;
CALL sales_dept_20261026_apply();

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
DROP PROCEDURE IF EXISTS sales_dept_20261026_apply;
DROP PROCEDURE IF EXISTS sales_dept_20261026_prerequisites;
DROP PROCEDURE IF EXISTS sales_dept_20261026_validate;

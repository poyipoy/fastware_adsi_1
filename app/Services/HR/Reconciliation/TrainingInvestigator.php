<?php

namespace App\Services\HR\Reconciliation;

class TrainingInvestigator
{
    public function investigate(array $tables): array
    {
        $parents = EmployeeIdentityCrosswalk::index($tables['trs_penilaian_tcs']);
        $users = EmployeeIdentityCrosswalk::index($tables['users']);
        $positions = EmployeeIdentityCrosswalk::index($tables['mst_job_positions']);
        $masters = [];
        foreach (CompetencyCrosswalk::TYPES as $type => $table) {
            $masters[$type] = EmployeeIdentityCrosswalk::index($tables[$table]);
        }
        $summary = ['target_2025_rows' => 0, 'cohorts' => array_fill_keys(['T25-A', 'T25-B', 'T25-C', 'T25-D', 'OTHER_RELATION_REVIEW'], 0),
            'cross_year_assessment_links' => 0, 'protected_assessment_parents' => 0, 'distinct_missing_parent_ids' => 0,
            'parent_position_mismatches' => 0, 'missing_employee_position_or_master' => 0,
            'broad_repeat_groups' => 0, 'broad_repeat_rows' => 0, 'strict_repeat_groups' => 0, 'strict_repeat_rows' => 0,
            'full_payload_duplicate_groups' => 0, 'full_payload_duplicate_rows' => 0,
            'proven_dummy' => 0, 'legacy_training_inserts' => 0, 'DELETE_APPROVED' => 0,
            'classification' => 'QUARANTINE', 'provenance' => 'PENDING_HR_DBA'];
        $missing = $protected = $broad = $strict = $payloads = [];
        foreach ($tables['mst_pd_pengajuans'] as $row) {
            if (trim((string) ($row['tahun_aktual'] ?? '')) !== '2025') {
                continue;
            }
            $summary['target_2025_rows']++;
            $parentId = EmployeeIdentityCrosswalk::id($row['id_trs'] ?? null);
            $parent = $parents[$parentId] ?? null;
            $selected = [];
            foreach (CompetencyCrosswalk::TYPES as $type => $_) {
                if (($id = EmployeeIdentityCrosswalk::id($row['id_'.$type] ?? null)) !== null) {
                    $selected[$type] = $id;
                }
            }
            $type = count($selected) === 1 ? array_key_first($selected) : null;
            $id = $type === null ? null : $selected[$type];
            $coherentContext = $parent !== null
                && EmployeeIdentityCrosswalk::id($parent['id_job_position'] ?? null) === EmployeeIdentityCrosswalk::id($row['id_job_position'] ?? null)
                && (string) ($parent['tahun_penilaian'] ?? '') === '2025';
            $cohort = $parent === null ? 'T25-A' : (
                EmployeeIdentityCrosswalk::id($parent['id_user'] ?? null) !== EmployeeIdentityCrosswalk::id($row['id_user'] ?? null) ? 'T25-B' : (
                    ! $coherentContext || $type === null ? 'OTHER_RELATION_REVIEW' : (
                        EmployeeIdentityCrosswalk::id($parent['id_'.$type] ?? null) !== $id ? 'T25-C' : 'T25-D')));
            $summary['cohorts'][$cohort]++;
            if ($parent === null && $parentId !== null) {
                $missing[$parentId] = true;
            }
            if ($parent !== null && (string) ($parent['tahun_penilaian'] ?? '') !== '2025') {
                $summary['cross_year_assessment_links']++;
                $protected[$parentId] = true;
            }
            if ($parent !== null && EmployeeIdentityCrosswalk::id($parent['id_job_position'] ?? null) !== EmployeeIdentityCrosswalk::id($row['id_job_position'] ?? null)) {
                $summary['parent_position_mismatches']++;
            }
            if (! isset($users[EmployeeIdentityCrosswalk::id($row['id_user'] ?? null)], $positions[EmployeeIdentityCrosswalk::id($row['id_job_position'] ?? null)])
                || $type === null || ! isset($masters[$type][$id])) {
                $summary['missing_employee_position_or_master']++;
            }
            $common = [EmployeeIdentityCrosswalk::id($row['id_user'] ?? null), '2025', $type, $id, EmployeeIdentityCrosswalk::label($row['program_training'] ?? null)];
            $this->increment($broad, $common);
            $this->increment($strict, [...$common, EmployeeIdentityCrosswalk::id($row['id_job_position'] ?? null), EmployeeIdentityCrosswalk::id($row['section_id'] ?? null), $row['due_date'] ?? null]);
            $business = $row;
            unset($business['id'], $business['created_at'], $business['updated_at']);
            ksort($business);
            $this->increment($payloads, $business);
        }
        $summary['distinct_missing_parent_ids'] = count($missing);
        $summary['protected_assessment_parents'] = count($protected);
        foreach (['broad' => $broad, 'strict' => $strict, 'full_payload' => $payloads] as $kind => $groups) {
            $repeated = array_filter($groups, fn ($count) => $count > 1);
            $prefix = $kind === 'full_payload' ? 'full_payload_duplicate' : $kind.'_repeat';
            $summary[$prefix.'_groups'] = count($repeated);
            $summary[$prefix.'_rows'] = array_sum($repeated);
        }

        return $summary;
    }

    private function increment(array &$groups, array $parts): void
    {
        $key = serialize($parts);
        $groups[$key] = ($groups[$key] ?? 0) + 1;
    }
}

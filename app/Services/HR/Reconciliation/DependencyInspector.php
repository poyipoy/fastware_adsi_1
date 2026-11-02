<?php

namespace App\Services\HR\Reconciliation;

class DependencyInspector
{
    public function inspect(array $snapshot): array
    {
        $tables = $snapshot['tables'];
        $assessments = EmployeeIdentityCrosswalk::index($tables['trs_penilaian_tcs']);
        $training = EmployeeIdentityCrosswalk::index($tables['mst_pd_pengajuans']);
        $children = $year2025Positions = $otherPositions = [];
        $master2025 = $masterOther = $all2025 = [];
        $summary = ['protected_assessments' => 0, 'protected_training' => 0, 'protected_participants' => 0,
            'training_references_to_2025_parents' => 0, 'non_2025_children_of_2025_assessments' => 0,
            'assessment_2025_child_count_histogram' => [], 'shared_assessment_masters' => [],
            'unlinked_history_rows_retained' => count($tables['detail_penilaian_tcs']),
            'history_position_association' => ['2025_only' => 0, 'other_only' => 0, 'both' => 0, 'neither' => 0],
            'logical_history_and_attachment_closure' => 'PENDING_HR_DBA', 'protected_disposition' => 'OUT_OF_SCOPE',
            'snapshot_only_no_runtime_integrity_claim' => true];
        foreach ($assessments as $id => $row) {
            $year2025 = (string) ($row['tahun_penilaian'] ?? '') === '2025';
            $position = EmployeeIdentityCrosswalk::id($row['id_job_position'] ?? null);
            if ($year2025) {
                $children[$id] = 0;
                $year2025Positions[$position] = true;
            } else {
                $summary['protected_assessments']++;
                $otherPositions[$position] = true;
            }
            foreach (CompetencyCrosswalk::TYPES as $type => $_) {
                if (($master = EmployeeIdentityCrosswalk::id($row['id_'.$type] ?? null)) !== null) {
                    if ($year2025) {
                        $master2025[$type][$master] = true;
                        $all2025[$type][$master] = true;
                    } else {
                        $masterOther[$type][$master] = true;
                    }
                }
            }
        }
        foreach ($training as $row) {
            $inScope = trim((string) ($row['tahun_aktual'] ?? '')) === '2025';
            $summary['protected_training'] += (int) ! $inScope;
            if ($inScope) {
                foreach (CompetencyCrosswalk::TYPES as $type => $_) {
                    if (($master = EmployeeIdentityCrosswalk::id($row['id_'.$type] ?? null)) !== null) {
                        $all2025[$type][$master] = true;
                    }
                }
            }
            $id = EmployeeIdentityCrosswalk::id($row['id_trs'] ?? null);
            if (array_key_exists($id ?? '', $children)) {
                $children[$id]++;
                $summary['training_references_to_2025_parents']++;
                $summary['non_2025_children_of_2025_assessments'] += (int) ! $inScope;
            }
        }
        foreach ($children as $count) {
            $summary['assessment_2025_child_count_histogram'][$count] = ($summary['assessment_2025_child_count_histogram'][$count] ?? 0) + 1;
        }
        foreach ($tables['mst_pd_pengajuan_participants'] as $row) {
            $parent = $training[EmployeeIdentityCrosswalk::id($row['people_development_id'] ?? null)] ?? null;
            $summary['protected_participants'] += (int) ($parent === null || (string) ($parent['tahun_aktual'] ?? '') !== '2025');
        }
        foreach (CompetencyCrosswalk::TYPES as $type => $_) {
            $summary['shared_assessment_masters'][$type] = count(array_intersect_key($master2025[$type] ?? [], $masterOther[$type] ?? []));
            $summary['shared_2025_assessment_or_training_masters'][$type] = count(array_intersect_key($all2025[$type] ?? [], $masterOther[$type] ?? []));
        }
        foreach ($tables['detail_penilaian_tcs'] as $row) {
            $id = EmployeeIdentityCrosswalk::id($row['id_job_position'] ?? null);
            $kind = isset($year2025Positions[$id]) ? (isset($otherPositions[$id]) ? 'both' : '2025_only') : (isset($otherPositions[$id]) ? 'other_only' : 'neither');
            $summary['history_position_association'][$kind]++;
        }
        $summary['declared_relevant_foreign_keys'] = array_values(array_filter($snapshot['foreign_keys'], fn ($fk) => in_array($fk['table'], ['trs_penilaian_tcs', 'mst_pd_pengajuans', 'mst_pd_pengajuan_participants', 'detail_penilaian_tcs'], true)
            || in_array($fk['parent_table'], ['trs_penilaian_tcs', 'mst_pd_pengajuans'], true)));

        return $summary;
    }
}

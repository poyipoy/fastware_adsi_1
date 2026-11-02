<?php

namespace App\Services\HR\Reconciliation;

class HistoricalPositionCrosswalk
{
    public function build(array $source, array $target, array $reviews = []): array
    {
        $sourceLabels = $targetLabels = [];
        $source = EmployeeIdentityCrosswalk::index($source);
        $target = EmployeeIdentityCrosswalk::index($target);
        foreach ($source as $row) {
            $sourceLabels[EmployeeIdentityCrosswalk::label($row['job_position'] ?? null)] = true;
        }
        foreach ($target as $id => $row) {
            $targetLabels[EmployeeIdentityCrosswalk::label($row['position_name'] ?? null)][] = (string) $id;
        }
        $candidates = $reviewed = [];
        foreach ($sourceLabels as $label => $_) {
            if ($label !== '' && count($targetLabels[$label] ?? []) === 1) {
                $candidates[$label] = $targetLabels[$label][0];
            }
        }
        foreach ($reviews as $entry) {
            $label = EmployeeIdentityCrosswalk::label($entry['source_label'] ?? null);
            $id = EmployeeIdentityCrosswalk::id($entry['target_id'] ?? null);
            if ($label !== '' && isset($sourceLabels[$label], $target[$id]) && EmployeeIdentityCrosswalk::reviewed($entry)) {
                $reviewed[$label][$id] = $id;
            }
        }
        $resolved = [];
        foreach ($reviewed as $label => $ids) {
            if (count($ids) === 1) {
                $resolved[$label] = (string) array_values($ids)[0];
            }
        }

        return ['source' => $source, 'target' => $target, 'candidates' => $candidates, 'resolved' => $resolved,
            'summary' => ['legacy_rows' => count($source), 'normalized_legacy_labels' => count($sourceLabels),
                'exact_label_candidates' => count($candidates), 'reviewed_position_pairs' => count($resolved)]];
    }
}

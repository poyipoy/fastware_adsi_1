<?php

namespace App\Services\HR\Reconciliation;

class CompetencyCrosswalk
{
    public const TYPES = ['tc' => 'mst_tcs', 'sk' => 'mst_soft_skills', 'ad' => 'mst_additionals'];

    private function rubric(array $row): array
    {
        return array_map(fn ($n) => EmployeeIdentityCrosswalk::label($row['deskripsi_'.$n] ?? null), range(1, 4));
    }

    public function build(array $source, array $target, array $positions, array $reviews = []): array
    {
        $sourceCategories = EmployeeIdentityCrosswalk::index($source['tc_poin_kategoris']);
        $targetCategories = EmployeeIdentityCrosswalk::index($target['tc_poin_kategoris']);
        $categoryCandidates = $categories = [];
        foreach ($sourceCategories as $id => $category) {
            $matches = array_keys(array_filter($targetCategories, fn ($row) => $this->rubric($row) === $this->rubric($category)));
            if (count($matches) === 1 && array_filter($this->rubric($category)) !== []) {
                $categoryCandidates[$id] = (string) $matches[0];
            }
        }
        $categoryReviews = [];
        foreach ($reviews['categories'] ?? [] as $entry) {
            $from = EmployeeIdentityCrosswalk::id($entry['source_id'] ?? null);
            $to = EmployeeIdentityCrosswalk::id($entry['target_id'] ?? null);
            if (isset($sourceCategories[$from], $targetCategories[$to]) && EmployeeIdentityCrosswalk::reviewed($entry)
                && ($this->rubric($sourceCategories[$from]) === $this->rubric($targetCategories[$to]) || ($entry['rubric_difference_reviewed'] ?? false) === true)) {
                $categoryReviews[$from][$to] = $to;
            }
        }
        foreach ($categoryReviews as $id => $ids) {
            if (count($ids) === 1) {
                $categories[$id] = (string) array_values($ids)[0];
            }
        }
        $catalogs = $summary = [];
        foreach (self::TYPES as $type => $table) {
            $old = EmployeeIdentityCrosswalk::index($source[$table]);
            $new = EmployeeIdentityCrosswalk::index($target[$table]);
            $references = [];
            foreach ($source['trs_penilaian_tcs'] as $row) {
                if (($id = EmployeeIdentityCrosswalk::id($row['id_'.$type] ?? null)) !== null) {
                    $references[$id] = ($references[$id] ?? 0) + 1;
                }
            }
            $stats = ['catalog_source' => count($old), 'catalog_target' => count($new), 'reference_occurrences' => array_sum($references),
                'referenced_ids' => count($references), 'available_referenced_definitions' => 0, 'missing_referenced_definitions' => 0,
                'missing_definition_occurrences' => 0, 'position_candidates' => 0, 'category_candidates' => 0,
                'label_only_candidates' => 0, 'contextual_candidates' => 0, 'reviewed_master_pairs' => 0,
                'source_referenced_nonblank_descriptions' => 0,
                'target_nonblank_descriptions' => count(array_filter($new, fn ($row) => trim((string) ($row['deskripsi_'.$type] ?? '')) !== ''))];
            foreach ($references as $id => $count) {
                if (! isset($old[$id])) {
                    $stats['missing_referenced_definitions']++;
                    $stats['missing_definition_occurrences'] += $count;

                    continue;
                }
                $stats['available_referenced_definitions']++;
                $master = $old[$id];
                $stats['source_referenced_nonblank_descriptions'] += (int) (trim((string) ($master['deskripsi_'.$type] ?? '')) !== '');
                $legacyPosition = $positions['source'][EmployeeIdentityCrosswalk::id($master['id_job_position'] ?? null)] ?? null;
                $label = EmployeeIdentityCrosswalk::label($legacyPosition['job_position'] ?? null);
                $position = $positions['candidates'][$label] ?? null;
                $category = $categoryCandidates[EmployeeIdentityCrosswalk::id($master['id_poin_kategori'] ?? null)] ?? null;
                $stats['position_candidates'] += (int) ($position !== null);
                $stats['category_candidates'] += (int) ($category !== null);
                $name = EmployeeIdentityCrosswalk::label($master['keterangan_'.$type] ?? null);
                $labels = array_filter($new, fn ($row) => $name !== '' && EmployeeIdentityCrosswalk::label($row['keterangan_'.$type] ?? null) === $name);
                $stats['label_only_candidates'] += (int) (count($labels) === 1);
                $context = array_filter($labels, fn ($row) => $position !== null && $category !== null
                    && EmployeeIdentityCrosswalk::id($row['id_job_position'] ?? null) === $position
                    && EmployeeIdentityCrosswalk::id($row['id_poin_kategori'] ?? null) === $category);
                $stats['contextual_candidates'] += (int) (count($context) === 1);
            }
            $accepted = [];
            foreach ($reviews['masters'][$type] ?? [] as $entry) {
                $from = EmployeeIdentityCrosswalk::id($entry['source_id'] ?? null);
                $to = EmployeeIdentityCrosswalk::id($entry['target_id'] ?? null);
                if (! isset($old[$from], $new[$to]) || ! EmployeeIdentityCrosswalk::reviewed($entry)) {
                    continue;
                }
                $a = $old[$from];
                $b = $new[$to];
                $lp = $positions['source'][EmployeeIdentityCrosswalk::id($a['id_job_position'] ?? null)] ?? [];
                $position = $positions['resolved'][EmployeeIdentityCrosswalk::label($lp['job_position'] ?? null)] ?? null;
                $category = $categories[EmployeeIdentityCrosswalk::id($a['id_poin_kategori'] ?? null)] ?? null;
                $definitionEqual = EmployeeIdentityCrosswalk::label($a['keterangan_'.$type] ?? null) === EmployeeIdentityCrosswalk::label($b['keterangan_'.$type] ?? null)
                    && EmployeeIdentityCrosswalk::label($a['deskripsi_'.$type] ?? null) === EmployeeIdentityCrosswalk::label($b['deskripsi_'.$type] ?? null);
                if ($position !== null && $category !== null && EmployeeIdentityCrosswalk::id($b['id_job_position'] ?? null) === $position
                    && EmployeeIdentityCrosswalk::id($b['id_poin_kategori'] ?? null) === $category
                    && ($definitionEqual || ($entry['definition_difference_reviewed'] ?? false) === true)
                    && (($a['nilai'] ?? null) === ($b['nilai'] ?? null) || ($entry['standard_difference_reviewed'] ?? false) === true)) {
                    $accepted[$from][$to] = $to;
                }
            }
            $resolved = [];
            foreach ($accepted as $id => $ids) {
                if (count($ids) === 1) {
                    $resolved[$id] = (string) array_values($ids)[0];
                }
            }
            $stats['reviewed_master_pairs'] = count($resolved);
            $catalogs[$type] = ['source' => $old, 'target' => $new, 'resolved' => $resolved];
            $summary[$type] = $stats;
        }

        return ['catalogs' => $catalogs, 'categories' => $categories, 'summary' => $summary,
            'category_summary' => ['rubric_signature_candidates' => count($categoryCandidates), 'reviewed_pairs' => count($categories)]];
    }
}

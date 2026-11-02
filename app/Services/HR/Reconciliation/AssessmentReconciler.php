<?php

namespace App\Services\HR\Reconciliation;

class AssessmentReconciler
{
    public function evaluate(array $source, array $target, array $identity, array $positions, array $masters, array $statusReviews = [], bool $attested = false, array $eventReviews = []): array
    {
        $target2025 = array_filter($target, fn ($r) => (string) ($r['tahun_penilaian'] ?? '') === '2025');
        $byId = EmployeeIdentityCrosswalk::index($target);
        $componentGroups = $tupleGroups = $targetKeys = [];
        foreach ($source as $i => $row) {
            $base = [EmployeeIdentityCrosswalk::id($row['id_user'] ?? null), EmployeeIdentityCrosswalk::label($row['id_job_position'] ?? null), '2025'];
            $tuple = $base;
            foreach (CompetencyCrosswalk::TYPES as $type => $_) {
                $id = EmployeeIdentityCrosswalk::id($row['id_'.$type] ?? null);
                $tuple[] = $id;
                if ($id !== null) {
                    $componentGroups[$type][serialize([...$base, $id])][] = $i;
                }
            }
            $tupleGroups[serialize($tuple)][] = $i;
        }
        foreach ($target2025 as $i => $row) {
            foreach (CompetencyCrosswalk::TYPES as $type => $_) {
                if (($id = EmployeeIdentityCrosswalk::id($row['id_'.$type] ?? null)) !== null) {
                    $key = serialize([EmployeeIdentityCrosswalk::id($row['id_user'] ?? null), EmployeeIdentityCrosswalk::id($row['id_job_position'] ?? null), '2025', $id]);
                    $targetKeys[$type][$key][] = $i;
                }
            }
        }
        $repeatMembers = $repeats = [];
        foreach (CompetencyCrosswalk::TYPES as $type => $_) {
            $groups = array_filter($componentGroups[$type] ?? [], fn ($g) => count($g) > 1);
            $repeats[$type] = ['groups' => count($groups), 'rows' => array_sum(array_map('count', $groups))];
            foreach ($groups as $group) {
                foreach ($group as $i) {
                    $repeatMembers[$i] = true;
                }
            }
        }
        $summary = ['source_rows' => count($source), 'business_year' => 2025,
            'period_evidence_type' => $attested ? 'USER_ATTESTATION' : 'UNVERIFIED',
            'target_2025_rows' => count($target2025), 'identity_candidates' => 0, 'identity_unresolved' => 0,
            'identity_reasons' => [], 'exact_position_candidate_rows' => 0, 'reviewed_position_rows' => 0,
            'missing_master_rows' => 0, 'repeated_component_rows' => count($repeatMembers), 'repeated_components' => $repeats,
            'repeated_full_tuple_groups' => count(array_filter($tupleGroups, fn ($g) => count($g) > 1)),
            'source_null_totals' => 0, 'source_created_years' => [], 'source_statuses' => [], 'target_statuses' => [],
            'target_locked' => 0, 'target_nonnull_totals' => 0, 'target_total_differs_from_component_sum' => 0,
            'modifier_identity_candidates' => 0, 'modifier_identity_unresolved' => 0,
            'source_pk_collisions' => 0, 'pk_collision_employee_differences' => 0,
            'established_business_matches' => 0, 'target_unique_conflict_rows' => 0, 'status_score_conflict_rows' => 0,
            'rows_checked_against_target_unique_keys' => 0, 'target_collision_evaluation' => 'AFTER_APPROVED_CROSSWALKS_ONLY',
            'target_component_duplicate_groups' => 0, 'primary_waterfall' => [], 'reason_counts' => [],
            'classifications' => array_fill_keys(['RETAIN', 'INSERT_CANDIDATE', 'UPDATE_CANDIDATE', 'REVIEW_DELETE', 'QUARANTINE', 'OUT_OF_SCOPE'], 0)];
        foreach ($targetKeys as $keys) {
            $summary['target_component_duplicate_groups'] += count(array_filter($keys, fn ($g) => count($g) > 1));
        }
        foreach ($target2025 as $row) {
            $this->count($summary['target_statuses'], (string) $row['status']);
            $summary['target_locked'] += (int) ((string) ($row['is_locked'] ?? '') === '1');
            if (($row['total_nilai'] ?? null) !== null) {
                $summary['target_nonnull_totals']++;
                if (($row['nilai_tc'] ?? null) !== null && ($row['nilai_sk'] ?? null) !== null && ($row['nilai_ad'] ?? null) !== null) {
                    $sum = (int) $row['nilai_tc'] + (int) $row['nilai_sk'] + (int) $row['nilai_ad'];
                    $summary['target_total_differs_from_component_sum'] += (int) ((string) $sum !== (string) $row['total_nilai']);
                }
            }
        }
        $reviews = [];
        foreach ($statusReviews as $entry) {
            $from = (string) ($entry['source_status'] ?? '');
            if (EmployeeIdentityCrosswalk::reviewed($entry) && in_array((string) ($entry['target_status'] ?? ''), ['0', '1', '2', '3', '4', '5'], true)
                && is_bool($entry['is_locked'] ?? null) && ($entry['null_total_semantics'] ?? '') === 'PRESERVE') {
                $reviews[$from][] = $entry;
            }
        }
        $records = [];
        foreach ($source as $i => $row) {
            $reasons = [];
            $uid = EmployeeIdentityCrosswalk::id($row['id_user'] ?? null);
            $employee = $identity['resolved'][$uid] ?? null;
            $label = EmployeeIdentityCrosswalk::label($row['id_job_position'] ?? null);
            $position = $positions['resolved'][$label] ?? null;
            $positionCandidate = $positions['candidates'][$label] ?? null;
            $summary['identity_candidates'] += (int) ($employee !== null);
            $summary['identity_unresolved'] += (int) ($employee === null);
            $summary['exact_position_candidate_rows'] += (int) ($positionCandidate !== null);
            $summary['reviewed_position_rows'] += (int) ($position !== null);
            if ($employee === null) {
                $reason = $identity['reasons'][$uid] ?? 'SOURCE_USER_MISSING';
                $reasons[] = $reason;
                $this->count($summary['identity_reasons'], $reason);
            }
            if ($position === null) {
                $reasons[] = $positionCandidate === null ? 'POSITION_NO_EXACT_CANDIDATE' : 'POSITION_REVIEW_PENDING';
            }
            if (! $attested) {
                $reasons[] = 'PERIOD_ATTESTATION_NOT_BOUND';
            }
            $status = count($reviews[(string) $row['status']] ?? []) === 1 ? $reviews[(string) $row['status']][0] : null;
            if ($status === null) {
                $reasons[] = 'STATUS_SEMANTICS_REVIEW_PENDING';
            }
            if (isset($repeatMembers[$i])) {
                $reasons[] = 'REPEATED_COMPONENT_KEY_REVIEW';
            }
            $proposal = $row;
            unset($proposal['id']); // A legacy PK can never become the target identity.
            $proposal['id_user'] = $employee;
            $proposal['id_job_position'] = $position;
            $proposal['tahun_penilaian'] = 2025;
            $proposal['status'] = $status === null ? null : (string) $status['target_status'];
            $proposal['is_locked'] = $status['is_locked'] ?? null;
            $missing = false;
            $componentCount = 0;
            foreach (CompetencyCrosswalk::TYPES as $type => $_) {
                $id = EmployeeIdentityCrosswalk::id($row['id_'.$type] ?? null);
                $score = $row['nilai_'.$type] ?? null;
                $proposal['id_'.$type] = null;
                if ($id !== null) {
                    $componentCount++;
                    $master = $masters['catalogs'][$type]['source'][$id] ?? null;
                    $mapped = $masters['catalogs'][$type]['resolved'][$id] ?? null;
                    if ($master === null) {
                        $missing = true;
                        $reasons[] = 'MISSING_LEGACY_MASTER';
                    } else {
                        $lp = $positions['source'][EmployeeIdentityCrosswalk::id($master['id_job_position'] ?? null)] ?? null;
                        if ($lp === null) {
                            $reasons[] = 'SOURCE_MASTER_POSITION_UNRESOLVED';
                        } elseif (EmployeeIdentityCrosswalk::label($lp['job_position'] ?? null) !== $label) {
                            $reasons[] = 'SOURCE_MASTER_POSITION_MISMATCH';
                        }
                        if (! isset($masters['categories'][EmployeeIdentityCrosswalk::id($master['id_poin_kategori'] ?? null)])) {
                            $reasons[] = 'CATEGORY_REVIEW_PENDING';
                        }
                    }
                    if ($mapped === null) {
                        $reasons[] = 'COMPETENCY_CROSSWALK_PENDING';
                    } else {
                        $proposal['id_'.$type] = $mapped;
                        $targetMaster = $masters['catalogs'][$type]['target'][$mapped];
                        if (EmployeeIdentityCrosswalk::id($targetMaster['id_job_position'] ?? null) !== $position) {
                            $reasons[] = 'TARGET_MASTER_POSITION_MISMATCH';
                        }
                    }
                    if ($score === null) {
                        $reasons[] = 'REFERENCE_WITH_NULL_SCORE';
                    }
                } elseif ($score !== null) {
                    $reasons[] = 'SCORE_WITHOUT_REFERENCE';
                }
                if ($score !== null && ! in_array((string) $score, ['0', '1', '2', '3', '4'], true)) {
                    $reasons[] = 'INVALID_SCORE';
                }
                if ((string) $score === '0' && ! ($status['zero_score_reviewed'] ?? false)) {
                    $reasons[] = 'ZERO_SCORE_REVIEW';
                }
            }
            if ($componentCount === 0) {
                $reasons[] = 'NO_COMPETENCY_COMPONENT';
            }
            if (($row['modified_at'] ?? null) !== null) {
                $modifier = $identity['resolved'][EmployeeIdentityCrosswalk::id($row['modified_at'])] ?? null;
                $summary['modifier_identity_candidates'] += (int) ($modifier !== null);
                $summary['modifier_identity_unresolved'] += (int) ($modifier === null);
                if ($modifier === null || ! ($status['modifier_reviewed'] ?? false)) {
                    $reasons[] = 'MODIFIER_REVIEW_PENDING';
                }
                $proposal['modified_at'] = $modifier;
            }
            $summary['missing_master_rows'] += (int) $missing;
            $summary['source_null_totals'] += (int) (($row['total_nilai'] ?? null) === null);
            $this->count($summary['source_created_years'], substr((string) ($row['created_at'] ?? ''), 0, 4));
            $this->count($summary['source_statuses'], (string) $row['status']);
            $collision = $byId[EmployeeIdentityCrosswalk::id($row['id'])] ?? null;
            $summary['source_pk_collisions'] += (int) ($collision !== null);
            $summary['pk_collision_employee_differences'] += (int) ($collision !== null && EmployeeIdentityCrosswalk::id($collision['id_user'] ?? null) !== $uid);
            $primary = $employee === null ? 'W1_IDENTITY' : ($positionCandidate === null && $position === null ? 'W2_POSITION' : ($missing ? 'W3_MISSING_MASTER' : 'W4_CROSSWALK_OR_REVIEW'));
            $this->count($summary['primary_waterfall'], $primary);
            $classification = 'QUARANTINE';
            if ($reasons === []) {
                $summary['rows_checked_against_target_unique_keys']++;
                $hits = [];
                foreach (CompetencyCrosswalk::TYPES as $type => $_) {
                    if ($proposal['id_'.$type] !== null) {
                        foreach ($targetKeys[$type][serialize([$employee, $position, '2025', $proposal['id_'.$type]])] ?? [] as $hit) {
                            $hits[$hit] = $target2025[$hit];
                        }
                    }
                }
                if ($hits === []) {
                    $classification = 'INSERT_CANDIDATE';
                } elseif (count($hits) === 1 && $this->sameTuple($proposal, array_values($hits)[0])) {
                    $existing = array_values($hits)[0];
                    $eventEvidence = array_filter($eventReviews, fn ($entry) => EmployeeIdentityCrosswalk::reviewed($entry)
                        && EmployeeIdentityCrosswalk::id($entry['source_id'] ?? null) === EmployeeIdentityCrosswalk::id($row['id'])
                        && EmployeeIdentityCrosswalk::id($entry['target_id'] ?? null) === EmployeeIdentityCrosswalk::id($existing['id']));
                    if (count($eventEvidence) !== 1) {
                        $reasons[] = 'TARGET_EVENT_IDENTITY_REVIEW_PENDING';
                    } else {
                        $summary['established_business_matches']++;
                        $conflict = false;
                        foreach (['nilai_tc', 'nilai_sk', 'nilai_ad', 'total_nilai', 'status', 'created_at', 'updated_at'] as $field) {
                            $conflict = $conflict || ($existing[$field] ?? null) !== ($proposal[$field] ?? null);
                        }
                        $summary['status_score_conflict_rows'] += (int) $conflict;
                        $classification = $conflict ? 'UPDATE_CANDIDATE' : 'RETAIN';
                        if ((bool) ($existing['is_locked'] ?? false) !== $proposal['is_locked']) {
                            $classification = 'QUARANTINE';
                            $reasons[] = 'LOCK_CONFLICT_NO_RESET';
                        }
                    }
                } else {
                    $summary['target_unique_conflict_rows']++;
                    $reasons[] = 'TARGET_COMPONENT_UNIQUE_CONFLICT';
                }
            }
            foreach (array_unique($reasons) as $reason) {
                $this->count($summary['reason_counts'], $reason);
            }
            $summary['classifications'][$classification]++;
            $records[] = ['source' => $row, 'proposal' => $proposal, 'classification' => $classification, 'reasons' => array_values(array_unique($reasons))];
        }

        // Different legacy keys may collapse to one target key after approved aliases.
        // Quarantine all participating rows; never select a survivor.
        $mappedGroups = [];
        foreach ($records as $i => $record) {
            $p = $record['proposal'];
            if ($p['id_user'] === null || $p['id_job_position'] === null) {
                continue;
            }
            foreach (CompetencyCrosswalk::TYPES as $type => $_) {
                if ($p['id_'.$type] !== null) {
                    $mappedGroups[serialize([$p['id_user'], $p['id_job_position'], 2025, $type, $p['id_'.$type]])][] = $i;
                }
            }
        }
        $mappedRepeats = [];
        foreach ($mappedGroups as $group) {
            if (count($group) > 1) {
                foreach ($group as $i) {
                    $mappedRepeats[$i] = true;
                }
            }
        }
        foreach ($mappedRepeats as $i => $_) {
            $summary['classifications'][$records[$i]['classification']]--;
            $summary['classifications']['QUARANTINE']++;
            $records[$i]['classification'] = 'QUARANTINE';
            $records[$i]['reasons'][] = 'MAPPED_SOURCE_UNIQUE_COLLISION';
        }
        $summary['mapped_source_unique_collision_rows'] = count($mappedRepeats);
        $summary['reason_counts']['MAPPED_SOURCE_UNIQUE_COLLISION'] = count($mappedRepeats);
        $summary['unmatched_source_records'] = count($source) - $summary['established_business_matches'];
        $summary['safe_candidates'] = $summary['classifications']['INSERT_CANDIDATE'];
        $summary['quarantined_records'] = $summary['classifications']['QUARANTINE'];

        return ['summary' => $summary, 'records' => $records]; // Private in-memory data only; orchestrator exports aggregates.
    }

    private function count(array &$counts, string $key): void
    {
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }

    private function sameTuple(array $a, array $b): bool
    {
        foreach (['id_tc', 'id_sk', 'id_ad'] as $field) {
            if (EmployeeIdentityCrosswalk::id($a[$field]) !== EmployeeIdentityCrosswalk::id($b[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }
}

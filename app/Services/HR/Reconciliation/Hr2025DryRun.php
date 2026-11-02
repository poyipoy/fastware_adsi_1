<?php

namespace App\Services\HR\Reconciliation;

use RuntimeException;

class Hr2025DryRun
{
    public const LEGACY_HASH = 'e697a99f80994ff8987b0a325f6b54ed7dc0f9767a2226f617afaeb7a1e9c3d8';

    public const TARGET_HASH = '996ef43a253969d992f29d917cdc021df353c6c89f8081a005b4f8787b1ea3db';

    public function run(string $legacyPath, string $targetPath, int $year = 2025, ?string $crosswalkPath = null): array
    {
        if ($year !== 2025) {
            throw new RuntimeException('ONLY_BUSINESS_YEAR_2025_ALLOWED');
        }
        $parser = new SqlDumpSnapshot;
        $legacy = $parser->load($legacyPath, self::LEGACY_HASH);
        $target = $parser->load($targetPath, self::TARGET_HASH);
        if (count($legacy['tables']['trs_penilaian_tcs']) !== 281 || count($legacy['tables']['mst_pd_pengajuans']) !== 0) {
            throw new RuntimeException('ATTESTED_COHORT_PARSE_MISMATCH');
        }
        $reviews = [];
        if ($crosswalkPath !== null) {
            if (! is_file($crosswalkPath) || ! is_readable($crosswalkPath) || filesize($crosswalkPath) > 4194304) {
                throw new RuntimeException('CROSSWALK_UNREADABLE');
            }
            $content = file_get_contents($crosswalkPath);
            $reviews = $content === false ? null : json_decode($content, true);
            if (! is_array($reviews) || ($reviews['source_sha256'] ?? '') !== self::LEGACY_HASH || ($reviews['target_sha256'] ?? '') !== self::TARGET_HASH) {
                throw new RuntimeException('CROSSWALK_SNAPSHOT_BINDING_INVALID');
            }
            $this->validateReviews($reviews);
        }
        $report = $this->analyze($legacy, $target, $reviews);
        // Recheck on-disk bytes too; a concurrent file change invalidates the result.
        if (hash_file('sha256', $legacyPath) !== self::LEGACY_HASH || hash_file('sha256', $targetPath) !== self::TARGET_HASH) {
            throw new RuntimeException('SNAPSHOT_CHANGED_DURING_ANALYSIS');
        }

        return $report;
    }

    private function validateReviews(array $reviews): void
    {
        if (array_diff(array_keys($reviews), ['source_sha256', 'target_sha256', 'revision', 'positions', 'categories', 'masters', 'statuses', 'assessment_identities']) !== []) {
            throw new RuntimeException('UNSUPPORTED_CROSSWALK_FIELD');
        }
        $lists = [];
        foreach (['positions', 'categories', 'statuses', 'assessment_identities'] as $field) {
            $lists[] = $reviews[$field] ?? [];
        }
        $masters = $reviews['masters'] ?? [];
        if (! is_array($masters) || array_diff(array_keys($masters), array_keys(CompetencyCrosswalk::TYPES)) !== []) {
            throw new RuntimeException('CROSSWALK_SHAPE_INVALID');
        }
        foreach ($masters as $entries) {
            $lists[] = $entries;
        }
        foreach ($lists as $list) {
            if (! is_array($list) || ! array_is_list($list)) {
                throw new RuntimeException('CROSSWALK_SHAPE_INVALID');
            }
            foreach ($list as $entry) {
                if (! is_array($entry)) {
                    throw new RuntimeException('CROSSWALK_SHAPE_INVALID');
                }
            }
        }
    }

    /** Pure analysis API; useful for synthetic isolated fixtures, never an apply plan. */
    public function analyze(array $legacy, array $target, array $reviews = []): array
    {
        $this->validateSchema($target);
        $source = $legacy['tables'];
        $destination = $target['tables'];
        $identities = (new EmployeeIdentityCrosswalk)->build($source['users'], $destination['users']);
        $positions = (new HistoricalPositionCrosswalk)->build($source['tc_job_positions'], $destination['mst_job_positions'], $reviews['positions'] ?? []);
        $masters = (new CompetencyCrosswalk)->build($source, $destination, $positions, $reviews);
        $attested = ($legacy['sha256'] ?? '') === self::LEGACY_HASH && count($source['trs_penilaian_tcs']) === 281;
        $assessments = (new AssessmentReconciler)->evaluate($source['trs_penilaian_tcs'], $destination['trs_penilaian_tcs'], $identities, $positions, $masters, $reviews['statuses'] ?? [], $attested, $reviews['assessment_identities'] ?? []);
        $training = (new TrainingInvestigator)->investigate($destination);
        $training['legacy_source_rows'] = count($source['mst_pd_pengajuans']);
        $report = [
            'report_version' => 1, 'mode' => 'DRY_RUN_OFFLINE_SNAPSHOTS', 'data_writes' => 0,
            'database_connections' => 0, 'dummy_generation' => false, 'apply_supported' => false,
            'source' => ['sha256' => $legacy['sha256'] ?? null, 'bytes' => $legacy['bytes'] ?? null],
            'target' => ['sha256' => $target['sha256'] ?? null, 'bytes' => $target['bytes'] ?? null],
            'approval_counters' => ['INSERT_APPROVED' => 0, 'UPDATE_APPROVED' => 0, 'DELETE_APPROVED' => 0],
            'identity' => ['source_users' => count($source['users']), 'target_users' => count($destination['users']), 'unique_npk_pairs' => count($identities['resolved'])],
            'positions' => $positions['summary'], 'categories' => $masters['category_summary'],
            'competency_masters' => $masters['summary'], 'assessments' => $assessments['summary'],
            'source_history' => ['rows' => count($source['detail_penilaian_tcs']), 'classification' => 'QUARANTINE_LINEAGE_UNVERIFIED'],
            'target_2025_assessments_disposition' => 'QUARANTINE_PENDING_PROVENANCE_AND_RECORD_REVIEW',
            'training' => $training,
            'dependencies' => (new DependencyInspector)->inspect($target),
            'staging' => ['status' => 'NOT_RUN', 'reason' => 'NO_EXPLICIT_AUTHORIZED_READ_ONLY_STAGING_CONNECTION'],
            'review_contract' => ['crosswalk_input_is_not_action_approval' => true, 'signature_authenticity' => 'EXTERNAL_HR_DBA_VERIFICATION_REQUIRED',
                'record_manifest_export' => 'DISABLED_PENDING_APPROVED_PRIVATE_DESTINATION', 'safe_candidates_are_not_approved_writes' => true],
        ];

        return $this->canonical($report);
    }

    private function validateSchema(array $target): void
    {
        $definition = $target['schema']['trs_penilaian_tcs'] ?? [];
        foreach (['id', 'id_user', 'id_job_position', 'id_tc', 'id_sk', 'id_ad', 'nilai_tc', 'nilai_sk', 'nilai_ad', 'total_nilai', 'status', 'is_locked', 'tahun_penilaian', 'created_at', 'updated_at'] as $column) {
            if (! array_key_exists($column, $definition['columns'] ?? [])) {
                throw new RuntimeException('TARGET_SCHEMA_COLUMN_DRIFT');
            }
        }
        $expected = [];
        foreach (['tc', 'sk', 'ad'] as $type) {
            $expected['uq_penilaian_'.$type] = ['id_user', 'id_job_position', 'tahun_penilaian', 'id_'.$type];
        }
        ksort($expected);
        $actual = $definition['unique_indexes'] ?? [];
        ksort($actual);
        if ($actual !== $expected) {
            throw new RuntimeException('TARGET_UNIQUE_CONSTRAINT_DRIFT');
        }
    }

    private function canonical(array $value): array
    {
        foreach ($value as &$item) {
            if (is_array($item)) {
                $item = $this->canonical($item);
            }
        }
        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }

    public function json(array $report): string
    {
        return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
    }
}

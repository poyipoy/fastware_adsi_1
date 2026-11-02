<?php

namespace Tests\Unit;

use App\Services\HR\Reconciliation\AssessmentReconciler;
use App\Services\HR\Reconciliation\CompetencyCrosswalk;
use App\Services\HR\Reconciliation\DependencyInspector;
use App\Services\HR\Reconciliation\EmployeeIdentityCrosswalk as Identity;
use App\Services\HR\Reconciliation\HistoricalPositionCrosswalk;
use App\Services\HR\Reconciliation\Hr2025DryRun;
use App\Services\HR\Reconciliation\SqlDumpSnapshot;
use App\Services\HR\Reconciliation\TrainingInvestigator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

class Hr2025ReconciliationTest extends TestCase
{
    private function review(array $fields = []): array
    {
        return $fields + ['decision' => 'APPROVED', 'hr_review' => 'SYNTHETIC_HR', 'dba_review' => 'SYNTHETIC_DBA', 'evidence' => 'SYNTHETIC_ONLY'];
    }

    private function fixtures(): array
    {
        $source = $target = array_fill_keys(SqlDumpSnapshot::TABLES, []);
        $source['users'] = [['id' => '10', 'npk' => '00123']];
        $target['users'] = [['id' => '20', 'npk' => '00123']];
        $source['tc_job_positions'] = [['id' => '1', 'job_position' => 'Legacy Role']];
        $target['mst_job_positions'] = [['id' => '90', 'position_name' => 'Target Role']];
        $rubric = ['deskripsi_1' => 'One', 'deskripsi_2' => 'Two', 'deskripsi_3' => 'Three', 'deskripsi_4' => 'Four'];
        $source['tc_poin_kategoris'] = [['id' => '3', 'judul_keterangan' => 'Old category'] + $rubric];
        $target['tc_poin_kategoris'] = [['id' => '9', 'judul_keterangan' => 'Renamed category'] + $rubric];
        $reviews = ['positions' => [$this->review(['source_label' => 'Legacy Role', 'target_id' => '90'])],
            'categories' => [$this->review(['source_id' => '3', 'target_id' => '9'])],
            'statuses' => [$this->review(['source_status' => '3', 'target_status' => '4', 'is_locked' => true, 'null_total_semantics' => 'PRESERVE', 'zero_score_reviewed' => true])]];
        foreach (CompetencyCrosswalk::TYPES as $type => $table) {
            $source[$table] = [['id' => '7', 'id_job_position' => '1', 'id_poin_kategori' => '3', 'keterangan_'.$type => 'Fixture '.$type, 'deskripsi_'.$type => 'Definition', 'nilai' => '3']];
            $target[$table] = [['id' => '70', 'id_job_position' => '90', 'id_poin_kategori' => '9', 'keterangan_'.$type => 'Fixture '.$type, 'deskripsi_'.$type => 'Definition', 'nilai' => '3']];
            $reviews['masters'][$type] = [$this->review(['source_id' => '7', 'target_id' => '70'])];
        }
        $source['trs_penilaian_tcs'] = [['id' => '1', 'id_user' => '10', 'id_job_position' => 'Legacy Role', 'id_tc' => '7', 'id_sk' => null, 'id_ad' => null,
            'nilai_tc' => '0', 'nilai_sk' => null, 'nilai_ad' => null, 'total_nilai' => null, 'status' => '3',
            'created_at' => '2026-01-02 10:00:00', 'updated_at' => '2026-01-03 11:00:00', 'modified_at' => null]];

        return [$source, $target, $reviews];
    }

    private function evaluate(array $source, array $target, array $reviews, bool $attested = true): array
    {
        $identity = (new Identity)->build($source['users'], $target['users']);
        $positions = (new HistoricalPositionCrosswalk)->build($source['tc_job_positions'], $target['mst_job_positions'], $reviews['positions'] ?? []);
        $masters = (new CompetencyCrosswalk)->build($source, $target, $positions, $reviews);

        return (new AssessmentReconciler)->evaluate($source['trs_penilaian_tcs'], $target['trs_penilaian_tcs'], $identity, $positions, $masters, $reviews['statuses'] ?? [], $attested, $reviews['assessment_identities'] ?? []);
    }

    public function test_literal_parser_handles_escapes_comments_multi_inserts_null_and_large_numbers(): void
    {
        $sql = <<<'SQL'
-- INSERT INTO `users` (`id`,`npk`) VALUES (99,'bad');
CREATE TABLE `mst_tcs` (`id` bigint) ENGINE=InnoDB;
INSERT INTO `mst_tcs` (`id`,`deskripsi_tc`,`nilai`,`nullable`) VALUES
(18446744073709551615, 'a; b,(c)\nO\'Brien and ''quote'' \\ literal', 0, NULL);
/* INSERT INTO `mst_tcs` (`id`) VALUES (2); */
INSERT INTO `mst_tcs` (`id`,`deskripsi_tc`,`nilai`,`nullable`) VALUES (2,'NULL',4,'');
INSERT INTO `users` (`id`,`npk`,`name`,`password`) VALUES (1,'00123','PRIVATE_FIXTURE','SECRET_FIXTURE');
SQL;
        $result = (new SqlDumpSnapshot)->parse($sql);
        $this->assertCount(2, $result['tables']['mst_tcs']);
        $this->assertSame('18446744073709551615', $result['tables']['mst_tcs'][0]['id']);
        $this->assertSame("a; b,(c)\nO'Brien and 'quote' \\ literal", $result['tables']['mst_tcs'][0]['deskripsi_tc']);
        $this->assertSame('0', $result['tables']['mst_tcs'][0]['nilai']);
        $this->assertNull($result['tables']['mst_tcs'][0]['nullable']);
        $this->assertSame('NULL', $result['tables']['mst_tcs'][1]['deskripsi_tc']);
        $this->assertSame([['id' => '1', 'npk' => '00123']], $result['tables']['users']);
    }

    public static function unsupportedSql(): array
    {
        return [['INSERT INTO `mst_tcs` (`id`) VALUES (NOW());'],
            ['INSERT INTO `mst_tcs` (`id`) VALUES (1) ON DUPLICATE KEY UPDATE id=2;'],
            ['INSERT INTO `mst_tcs` (`id`) VALUES (1), ;'],
            ['INSERT INTO `mst_tcs` (`id`,`nilai`) VALUES (1);'],
            ["INSERT INTO `mst_tcs` (`id`) VALUES ('unterminated);"],
            ['INSERT IGNORE INTO `mst_tcs` (`id`) VALUES (1);'],
            ["SET SQL_MODE='NO_BACKSLASH_ESCAPES';"]];
    }

    #[DataProvider('unsupportedSql')]
    public function test_parser_rejects_expressions_malformed_and_unsupported_dialects(string $sql): void
    {
        $this->expectException(RuntimeException::class);
        (new SqlDumpSnapshot)->parse($sql);
    }

    public function test_hash_drift_is_rejected_before_parsing_and_source_is_never_changed(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'hr-pr2-synthetic-');
        try {
            $content = 'INSERT INTO `mst_tcs` (`id`) VALUES (1);';
            file_put_contents($path, $content);
            $reader = new SqlDumpSnapshot;
            $this->assertSame('1', $reader->load($path, hash('sha256', $content))['tables']['mst_tcs'][0]['id']);
            $this->assertSame($content, file_get_contents($path));
            $this->expectExceptionMessage('SNAPSHOT_HASH_MISMATCH');
            $reader->load($path, str_repeat('0', 64));
        } finally {
            unlink($path);
        }
    }

    public function test_unique_npk_matching_preserves_leading_zeros_without_id_or_name_fallback(): void
    {
        $result = (new Identity)->build([['id' => 1, 'npk' => ' 00123 '], ['id' => 2, 'npk' => '123'], ['id' => 3, 'npk' => '0']],
            [['id' => 80, 'npk' => '00123'], ['id' => 90, 'npk' => '123'], ['id' => 3, 'npk' => '999']]);
        $this->assertSame('80', $result['resolved'][1]);
        $this->assertSame('90', $result['resolved'][2]);
        $this->assertSame('INVALID_OR_ZERO_NPK', $result['reasons'][3]);
    }

    public function test_duplicate_npk_on_either_side_and_missing_user_stay_blocked(): void
    {
        $result = (new Identity)->build([['id' => 1, 'npk' => '123'], ['id' => 2, 'npk' => '123'], ['id' => 3, 'npk' => '456'], ['id' => 4, 'npk' => '789']],
            [['id' => 10, 'npk' => '123'], ['id' => 20, 'npk' => '456'], ['id' => 21, 'npk' => '456']]);
        $this->assertSame([], $result['resolved']);
        $this->assertSame('SOURCE_NPK_AMBIGUOUS', $result['reasons'][1]);
        $this->assertSame('TARGET_NPK_AMBIGUOUS', $result['reasons'][3]);
        $this->assertSame('TARGET_NPK_MISSING', $result['reasons'][4]);
    }

    public function test_exact_position_and_rubric_labels_are_candidates_not_approvals(): void
    {
        [$s, $t] = $this->fixtures();
        $t['mst_job_positions'][0]['position_name'] = 'Legacy Role';
        $p = (new HistoricalPositionCrosswalk)->build($s['tc_job_positions'], $t['mst_job_positions']);
        $m = (new CompetencyCrosswalk)->build($s, $t, $p);
        $this->assertCount(1, $p['candidates']);
        $this->assertSame([], $p['resolved']);
        $this->assertSame(1, $m['category_summary']['rubric_signature_candidates']);
        $this->assertSame([], $m['catalogs']['tc']['resolved']);
        $this->assertSame('QUARANTINE', $this->evaluate($s, $t, [])['records'][0]['classification']);
    }

    public function test_approved_contextual_crosswalks_work_for_each_competency_type(): void
    {
        [$s, $t, $review] = $this->fixtures();
        foreach (['sk', 'ad'] as $type) {
            $s['trs_penilaian_tcs'][0]['id_'.$type] = '7';
            $s['trs_penilaian_tcs'][0]['nilai_'.$type] = '3';
        }
        $result = $this->evaluate($s, $t, $review);
        $row = $result['records'][0];
        $this->assertSame('INSERT_CANDIDATE', $row['classification']);
        $this->assertSame(['70', '70', '70'], [$row['proposal']['id_tc'], $row['proposal']['id_sk'], $row['proposal']['id_ad']]);
        $this->assertSame('20', $row['proposal']['id_user']);
        $this->assertSame('90', $row['proposal']['id_job_position']);
        $this->assertArrayNotHasKey('id', $row['proposal']);
    }

    public function test_year_attestation_not_created_at_and_null_zero_timestamps_are_preserved(): void
    {
        [$s, $t, $r] = $this->fixtures();
        $result = $this->evaluate($s, $t, $r);
        $p = $result['records'][0]['proposal'];
        $this->assertSame(2025, $p['tahun_penilaian']);
        $this->assertSame('USER_ATTESTATION', $result['summary']['period_evidence_type']);
        $this->assertSame('0', $p['nilai_tc']);
        $this->assertNull($p['total_nilai']);
        $this->assertNull($p['nilai_sk']);
        $this->assertSame($s['trs_penilaian_tcs'][0]['created_at'], $p['created_at']);
        $this->assertSame($s['trs_penilaian_tcs'][0]['updated_at'], $p['updated_at']);
        $this->assertContains('PERIOD_ATTESTATION_NOT_BOUND', $this->evaluate($s, $t, $r, false)['records'][0]['reasons']);
    }

    public function test_missing_master_definition_or_unreviewed_status_blocks_inserts(): void
    {
        [$s, $t, $r] = $this->fixtures();
        $s['mst_tcs'] = [];
        $r['statuses'] = [];
        $row = $this->evaluate($s, $t, $r)['records'][0];
        $this->assertSame('QUARANTINE', $row['classification']);
        $this->assertContains('MISSING_LEGACY_MASTER', $row['reasons']);
        $this->assertContains('STATUS_SEMANTICS_REVIEW_PENDING', $row['reasons']);
    }

    public function test_type_position_category_and_definition_mismatches_require_explicit_review(): void
    {
        [$s, $t, $r] = $this->fixtures();
        $t['mst_tcs'][0]['id_poin_kategori'] = '999';
        $this->assertContains('COMPETENCY_CROSSWALK_PENDING', $this->evaluate($s, $t, $r)['records'][0]['reasons']);
        $t['mst_tcs'][0]['id_poin_kategori'] = '9';
        $t['mst_tcs'][0]['deskripsi_tc'] = 'Different version';
        $this->assertSame('QUARANTINE', $this->evaluate($s, $t, $r)['records'][0]['classification']);
        $r['masters']['tc'][0]['definition_difference_reviewed'] = true;
        $this->assertSame('INSERT_CANDIDATE', $this->evaluate($s, $t, $r)['records'][0]['classification']);
        $t['mst_tcs'][0]['id_job_position'] = '999';
        $this->assertSame('QUARANTINE', $this->evaluate($s, $t, $r)['records'][0]['classification']);
        $s['trs_penilaian_tcs'][0]['id_tc'] = null;
        $s['trs_penilaian_tcs'][0]['nilai_tc'] = null;
        $this->assertContains('NO_COMPETENCY_COMPONENT', $this->evaluate($s, $t, $r)['records'][0]['reasons']);
    }

    public function test_conflicting_position_reviews_are_not_resolved_by_order_or_current_assignment(): void
    {
        [$s, $t, $r] = $this->fixtures();
        $t['mst_job_positions'][] = ['id' => '91', 'position_name' => 'Other role'];
        $r['positions'][] = $this->review(['source_label' => 'Legacy Role', 'target_id' => '91']);
        $this->assertSame('QUARANTINE', $this->evaluate($s, $t, $r)['records'][0]['classification']);
        $r['positions'] = array_reverse($r['positions']);
        $this->assertSame('QUARANTINE', $this->evaluate($s, $t, $r)['records'][0]['classification']);
    }

    public function test_crosswalk_input_is_bound_to_both_snapshots_and_cannot_approve_deletions(): void
    {
        $root = dirname(__DIR__, 2);
        $path = tempnam(sys_get_temp_dir(), 'hr-pr2-review-synthetic-');
        try {
            file_put_contents($path, json_encode(['source_sha256' => Hr2025DryRun::LEGACY_HASH, 'target_sha256' => Hr2025DryRun::TARGET_HASH, 'DELETE_APPROVED' => 934]));
            try {
                (new Hr2025DryRun)->run($root.'/dms_adasi_rev1.sql', $root.'/dms_adasi_rev1 (12).sql', 2025, $path);
                $this->fail('Write approval field must be rejected.');
            } catch (RuntimeException $exception) {
                $this->assertSame('UNSUPPORTED_CROSSWALK_FIELD', $exception->getMessage());
            }
            file_put_contents($path, json_encode(['source_sha256' => str_repeat('0', 64), 'target_sha256' => Hr2025DryRun::TARGET_HASH]));
            $this->expectExceptionMessage('CROSSWALK_SNAPSHOT_BINDING_INVALID');
            (new Hr2025DryRun)->run($root.'/dms_adasi_rev1.sql', $root.'/dms_adasi_rev1 (12).sql', 2025, $path);
        } finally {
            unlink($path);
        }
    }

    public function test_repeated_source_keys_do_not_choose_survivors(): void
    {
        [$s, $t, $r] = $this->fixtures();
        $row = $s['trs_penilaian_tcs'][0];
        $row['id'] = '2';
        $row['nilai_tc'] = '4';
        $s['trs_penilaian_tcs'][] = $row;
        $result = $this->evaluate($s, $t, $r);
        $this->assertSame(2, $result['summary']['repeated_component_rows']);
        $this->assertSame(2, $result['summary']['classifications']['QUARANTINE']);
        $this->assertCount(2, $result['records']);
    }

    public function test_aliases_that_collapse_different_source_keys_are_quarantined(): void
    {
        [$s, $t, $r] = $this->fixtures();
        $second = $s['mst_tcs'][0];
        $second['id'] = '8';
        $s['mst_tcs'][] = $second;
        $r['masters']['tc'][] = $this->review(['source_id' => '8', 'target_id' => '70']);
        $row = $s['trs_penilaian_tcs'][0];
        $row['id'] = '2';
        $row['id_tc'] = '8';
        $s['trs_penilaian_tcs'][] = $row;
        $result = $this->evaluate($s, $t, $r);
        $this->assertSame(0, $result['summary']['repeated_component_rows']);
        $this->assertSame(2, $result['summary']['mapped_source_unique_collision_rows']);
        $this->assertSame(0, $result['summary']['safe_candidates']);
    }

    public function test_target_natural_key_conflict_is_not_a_pk_upsert_or_automatic_event_match(): void
    {
        [$s, $t, $r] = $this->fixtures();
        $base = $this->evaluate($s, $t, $r)['records'][0]['proposal'];
        $t['trs_penilaian_tcs'] = [$base + ['id' => '1']];
        $result = $this->evaluate($s, $t, $r);
        $this->assertSame('QUARANTINE', $result['records'][0]['classification']);
        $this->assertContains('TARGET_EVENT_IDENTITY_REVIEW_PENDING', $result['records'][0]['reasons']);
        $this->assertSame(0, $result['summary']['established_business_matches']);
        $t['trs_penilaian_tcs'][0]['id_sk'] = '99';
        $result = $this->evaluate($s, $t, $r);
        $this->assertSame(1, $result['summary']['target_unique_conflict_rows']);
        $this->assertSame('QUARANTINE', $result['records'][0]['classification']);
    }

    public function test_exact_reviewed_event_conflict_is_only_a_candidate_and_lock_is_not_reset(): void
    {
        [$s, $t, $r] = $this->fixtures();
        $t['trs_penilaian_tcs'] = [$this->evaluate($s, $t, $r)['records'][0]['proposal'] + ['id' => '9']];
        $t['trs_penilaian_tcs'][0]['total_nilai'] = '3';
        $r['assessment_identities'] = [$this->review(['source_id' => '1', 'target_id' => '9'])];
        $result = $this->evaluate($s, $t, $r);
        $this->assertSame('UPDATE_CANDIDATE', $result['records'][0]['classification']);
        $this->assertNull($result['records'][0]['proposal']['total_nilai']);
        $t['trs_penilaian_tcs'][0]['is_locked'] = false;
        $this->assertContains('LOCK_CONFLICT_NO_RESET', $this->evaluate($s, $t, $r)['records'][0]['reasons']);
    }

    public function test_training_cohorts_cross_year_links_and_participant_dependencies_are_review_only(): void
    {
        [, $t] = $this->fixtures();
        $t['trs_penilaian_tcs'] = [
            ['id' => '1', 'id_user' => '20', 'id_job_position' => '90', 'id_tc' => '70', 'tahun_penilaian' => '2025'],
            ['id' => '2', 'id_user' => '30', 'id_job_position' => '90', 'id_tc' => '70', 'tahun_penilaian' => '2025'],
            ['id' => '3', 'id_user' => '20', 'id_job_position' => '90', 'id_tc' => '71', 'tahun_penilaian' => '2025'],
            ['id' => '4', 'id_user' => '30', 'id_job_position' => '90', 'id_tc' => '70', 'tahun_penilaian' => '2026'],
        ];
        foreach (['999', '2', '3', '1', '4'] as $i => $parent) {
            $t['mst_pd_pengajuans'][] = ['id' => (string) ($i + 1), 'id_trs' => $parent, 'id_user' => '20', 'id_job_position' => '90', 'id_tc' => '70', 'tahun_aktual' => '2025'];
        }
        $t['mst_pd_pengajuans'][] = ['id' => '6', 'tahun_aktual' => '2027'];
        $t['mst_pd_pengajuan_participants'][] = ['id' => '1', 'people_development_id' => '6'];
        $before = serialize($t);
        $report = (new TrainingInvestigator)->investigate($t);
        $this->assertSame(['T25-A' => 1, 'T25-B' => 2, 'T25-C' => 1, 'T25-D' => 1, 'OTHER_RELATION_REVIEW' => 0], $report['cohorts']);
        $this->assertSame(1, $report['cross_year_assessment_links']);
        $this->assertSame(0, $report['DELETE_APPROVED']);
        $dependency = (new DependencyInspector)->inspect(['tables' => $t, 'foreign_keys' => []]);
        $this->assertSame(1, $dependency['protected_assessments']);
        $this->assertSame(1, $dependency['protected_training']);
        $this->assertSame(1, $dependency['protected_participants']);
        $this->assertSame($before, serialize($t));
    }

    public function test_real_offline_dry_run_matches_verified_cohort_and_is_deterministic(): void
    {
        $root = dirname(__DIR__, 2);
        $service = new Hr2025DryRun;
        $report = $service->run($root.'/dms_adasi_rev1.sql', $root.'/dms_adasi_rev1 (12).sql');
        $this->assertSame(281, $report['assessments']['source_rows']);
        $this->assertSame(264, $report['assessments']['identity_candidates']);
        $this->assertSame(17, $report['assessments']['identity_unresolved']);
        $this->assertSame(44, $report['assessments']['repeated_component_rows']);
        $this->assertSame(281, $report['assessments']['source_null_totals']);
        $this->assertSame([10, 271], array_values($report['assessments']['source_created_years']));
        $this->assertSame(320, $report['assessments']['target_2025_rows']);
        $this->assertSame(934, $report['training']['target_2025_rows']);
        $this->assertSame([490, 433, 10, 1], array_map(fn ($key) => $report['training']['cohorts'][$key], ['T25-A', 'T25-B', 'T25-C', 'T25-D']));
        $this->assertSame(12, $report['training']['cross_year_assessment_links']);
        $this->assertSame(['ad' => 4, 'sk' => 2, 'tc' => 3], $report['dependencies']['shared_2025_assessment_or_training_masters']);
        $this->assertSame(0, $report['assessments']['safe_candidates']);
        $this->assertSame(0, $report['data_writes']);
        $json = $service->json($report);
        $this->assertSame($json, $service->json($service->run($root.'/dms_adasi_rev1.sql', $root.'/dms_adasi_rev1 (12).sql')));
        foreach (['"npk"', '"password"', '"name"', '"records"', '"proposal"', '"modified_updated"'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $json);
        }
        $this->assertSame(Hr2025DryRun::LEGACY_HASH, hash_file('sha256', $root.'/dms_adasi_rev1.sql'));
        $this->assertSame(Hr2025DryRun::TARGET_HASH, hash_file('sha256', $root.'/dms_adasi_rev1 (12).sql'));
    }

    public static function prohibitedArguments(): array
    {
        return [['--apply'], ['--delete'], ['--dummy-training=1'], ['--year=2026'], ['--year=2027'], ['--target=mysql']];
    }

    #[DataProvider('prohibitedArguments')]
    public function test_offline_entry_point_rejects_write_modes_other_years_and_live_target(string $option): void
    {
        $root = dirname(__DIR__, 2);
        $command = [PHP_BINARY, $root.'/scripts/hr-2025-dry-run.php', '--legacy='.$root.'/dms_adasi_rev1.sql', '--target='.$root.'/dms_adasi_rev1 (12).sql', $option];
        $process = new Process($command, $root);
        $process->run();
        $this->assertNotSame(0, $process->getExitCode());
        $this->assertStringContainsString('HR2025_DRY_RUN_REJECTED', $process->getErrorOutput());
    }

    public function test_target_unique_constraint_drift_is_rejected(): void
    {
        $root = dirname(__DIR__, 2);
        $parser = new SqlDumpSnapshot;
        $target = $parser->load($root.'/dms_adasi_rev1 (12).sql', Hr2025DryRun::TARGET_HASH);
        unset($target['schema']['trs_penilaian_tcs']['unique_indexes']['uq_penilaian_ad']);
        $this->expectExceptionMessage('TARGET_UNIQUE_CONSTRAINT_DRIFT');
        (new Hr2025DryRun)->analyze($parser->load($root.'/dms_adasi_rev1.sql', Hr2025DryRun::LEGACY_HASH), $target);
    }

    public function test_offline_process_does_not_bootstrap_or_fall_back_to_an_environment_database(): void
    {
        $root = dirname(__DIR__, 2);
        $process = new Process([PHP_BINARY, $root.'/scripts/hr-2025-dry-run.php', '--legacy='.$root.'/dms_adasi_rev1.sql', '--target='.$root.'/dms_adasi_rev1 (12).sql'], $root,
            ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'DISABLED_FOR_OFFLINE_TEST', 'DB_HOST' => 'invalid.invalid', 'DB_DATABASE' => 'DO_NOT_CONNECT']);
        $process->run();
        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $report = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(0, $report['database_connections']);
        $this->assertSame(0, $report['training']['legacy_source_rows']);
        $this->assertSame(['DELETE_APPROVED' => 0, 'INSERT_APPROVED' => 0, 'UPDATE_APPROVED' => 0], $report['approval_counters']);
    }
}

<?php

namespace Tests\Feature\HR;

use App\Console\Commands\ReconcileHr2025Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Support\HrIsolatedDatabaseTestCase;

class Hr2025DryRunCommandTest extends HrIsolatedDatabaseTestCase
{
    public function test_artisan_dry_run_twice_has_no_queries_writes_or_external_side_effects(): void
    {
        Mail::fake();
        Notification::fake();
        Queue::fake();
        $employee = $this->employee('SYNTHETIC_PRIVATE_NAME');
        $position = $this->position();
        $this->assign($employee, $position);
        DB::table('trs_penilaian_tcs')->insert(['id_user' => $employee->id, 'id_job_position' => $position->id,
            'nilai_tc' => 0, 'total_nilai' => null, 'status' => 4, 'tahun_penilaian' => 2026, 'is_locked' => true]);
        DB::statement('CREATE TABLE mst_pd_pengajuans (id int PRIMARY KEY, tahun_aktual int, id_trs int NULL)');
        DB::table('mst_pd_pengajuans')->insert(['id' => 1, 'tahun_aktual' => 2027, 'id_trs' => 1]);
        $tables = ['users', 'user_job_positions', 'mst_job_positions', 'mst_position_approvals', 'trs_penilaian_tcs', 'mst_pd_pengajuans'];
        $snapshot = fn () => array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(), $tables);
        $before = $snapshot();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $arguments = ['--legacy' => base_path('dms_adasi_rev1.sql'), '--target' => base_path('dms_adasi_rev1 (12).sql')];
        $this->assertSame(0, Artisan::call('hr:reconcile-2025', $arguments));
        $first = Artisan::output();
        $this->assertSame(0, Artisan::call('hr:reconcile-2025', $arguments));
        $this->assertSame($first, Artisan::output());
        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertSame($before, $snapshot());
        $report = json_decode($first, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(0, $report['data_writes']);
        $this->assertSame(0, $report['database_connections']);
        $this->assertSame(281, $report['assessments']['quarantined_records']);
        $this->assertSame(0, $report['training']['legacy_training_inserts']);
        $this->assertSame(['DELETE_APPROVED' => 0, 'INSERT_APPROVED' => 0, 'UPDATE_APPROVED' => 0], $report['approval_counters']);
        $this->assertStringNotContainsString('SYNTHETIC_PRIVATE_NAME', $first);
        Mail::assertNothingSent();
        Notification::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_command_has_no_apply_delete_dummy_or_live_connection_options(): void
    {
        $definition = (new ReconcileHr2025Command)->getDefinition();
        foreach (['apply', 'delete', 'dummy', 'connection', 'staging'] as $option) {
            $this->assertFalse($definition->hasOption($option));
        }
        $this->assertSame(2, Artisan::call('hr:reconcile-2025', []));
        $this->assertSame(2, Artisan::call('hr:reconcile-2025', ['--year' => '2026', '--legacy' => 'ignored', '--target' => 'ignored']));
    }
}

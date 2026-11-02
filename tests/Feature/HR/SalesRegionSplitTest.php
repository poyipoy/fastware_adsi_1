<?php

namespace Tests\Feature\HR;

use App\Models\MstJobPosition;
use App\Models\User;
use App\Models\UserJobPosition;
use App\Services\HR\SalesRegionSplitService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Support\HrIsolatedDatabaseTestCase;

class SalesRegionSplitTest extends HrIsolatedDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('ALTER TABLE mst_departments ADD UNIQUE(name)');
        DB::statement('ALTER TABLE mst_sections ADD UNIQUE(department_id,name), ADD FOREIGN KEY(department_id) REFERENCES mst_departments(id)');
        DB::statement('ALTER TABLE mst_job_positions ADD UNIQUE(position_name), ADD FOREIGN KEY(department_id) REFERENCES mst_departments(id), ADD FOREIGN KEY(section_id) REFERENCES mst_sections(id)');
        DB::statement('ALTER TABLE mst_position_approvals ADD UNIQUE(position_id,approval_level), ADD FOREIGN KEY(position_id) REFERENCES mst_job_positions(id), ADD FOREIGN KEY(approver_position_id) REFERENCES mst_job_positions(id)');
        DB::statement('ALTER TABLE user_job_positions ADD FOREIGN KEY(user_id) REFERENCES users(id), ADD FOREIGN KEY(mst_job_position_id) REFERENCES mst_job_positions(id)');
        DB::statement('ALTER TABLE km_organization_assignment_audits ADD FOREIGN KEY(actor_id) REFERENCES users(id), ADD FOREIGN KEY(user_job_position_id) REFERENCES user_job_positions(id)');
    }

    private function organization(): array
    {
        Storage::fake('local');
        $actor = User::create(['name' => 'ADMINSTRATOR']);
        $first = DB::table('mst_departments')->insertGetId(['name' => 'Sales Region 1 & 2']);
        $second = DB::table('mst_departments')->insertGetId(['name' => 'Sales Region 3 & 4']);
        $division = MstJobPosition::create(['position_name' => 'Sales Div Head', 'job_level' => 'div_head']);
        $heads = [];
        foreach (['Sales Dept Head Region 1&2', 'Sales Dept. Head Region 3&4'] as $i => $name) {
            $heads[] = MstJobPosition::create(['position_name' => $name, 'department_id' => $i ? $second : $first,
                'job_level' => 'dept_head', 'is_active' => true, 'is_key_position' => true]);
        }
        $incumbents = [$this->employee('Fixture first incumbent'), $this->employee('Fixture second incumbent')];
        foreach ($heads as $i => $head) {
            $this->assign($incumbents[$i], $head, ['effective_from' => today()->subYear()]);
            DB::table('mst_position_approvals')->insert(['position_id' => $head->id, 'approval_level' => 3, 'approver_position_id' => $division->id]);
        }
        $children = [];
        foreach (range(1, 4) as $region) {
            $department = $region <= 2 ? $first : $second;
            $section = DB::table('mst_sections')->insertGetId(['name' => 'Sales Region '.$region, 'department_id' => $department]);
            $child = MstJobPosition::create(['position_name' => 'Fixture regional child '.$region, 'department_id' => $department,
                'section_id' => $section, 'job_level' => 'staff', 'is_active' => true]);
            $children[$region] = $child;
            foreach ([1, 2] as $level) {
                DB::table('mst_position_approvals')->insert(['position_id' => $child->id, 'approval_level' => $level,
                    'approver_position_id' => $heads[$region <= 2 ? 0 : 1]->id]);
            }
            DB::table('mst_position_approvals')->insert(['position_id' => $child->id, 'approval_level' => 3, 'approver_position_id' => $division->id]);
        }
        DB::table('mst_tcs')->insert(['id_job_position' => $heads[0]->id, 'keterangan_tc' => 'Historic standard']);
        DB::table('trs_penilaian_tcs')->insert(['id_user' => $incumbents[0]->id, 'id_job_position' => $heads[0]->id,
            'tahun_penilaian' => 2025, 'nilai_tc' => 3, 'status' => 4, 'is_locked' => 1]);

        return compact('actor', 'heads', 'children', 'incumbents', 'division');
    }

    private function service(): SalesRegionSplitService
    {
        return app(SalesRegionSplitService::class);
    }

    public function test_preview_and_default_command_write_nothing_and_do_not_expose_incumbents(): void
    {
        $fixture = $this->organization();
        $before = DB::table('mst_departments')->get()->toJson();
        $preview = $this->service()->preview();
        $this->assertSame('ready', $preview['status']);
        $this->assertSame([], $preview['blockers']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $preview['hash']);
        $this->artisan('hr:split-sales-regions')->assertSuccessful()
            ->doesntExpectOutputToContain($fixture['incumbents'][0]->name);
        $this->assertSame($before, DB::table('mst_departments')->get()->toJson());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('km_organization_assignment_audits', 0);
    }

    public function test_apply_splits_structure_routes_and_assignments_without_copying_history_or_standards(): void
    {
        $fixture = $this->organization();
        $history = DB::table('trs_penilaian_tcs')->get()->toJson();
        $standards = DB::table('mst_tcs')->get()->toJson();
        $result = $this->service()->apply($fixture['actor'], $this->service()->preview()['hash']);
        $this->assertSame('applied', $result['status']);
        $this->assertNotEmpty(Storage::disk('local')->allFiles('private/hr/sales-region-split'));
        foreach (range(1, 4) as $region) {
            $head = MstJobPosition::where('position_name', 'Sales Dept Head Region '.$region)->firstOrFail();
            $this->assertNull($head->section_id);
            $this->assertTrue($head->is_key_position);
            $this->assertSame($head->department_id, $fixture['children'][$region]->fresh()->department_id);
            foreach ([1, 2] as $level) {
                $this->assertDatabaseHas('mst_position_approvals', ['position_id' => $fixture['children'][$region]->id,
                    'approval_level' => $level, 'approver_position_id' => $head->id]);
            }
            $this->assertDatabaseHas('mst_position_approvals', ['position_id' => $head->id,
                'approval_level' => 3, 'approver_position_id' => $fixture['division']->id]);
            $this->assertDatabaseHas('user_job_positions', ['user_id' => $fixture['incumbents'][in_array($region, [1, 3]) ? 0 : 1]->id,
                'mst_job_position_id' => $head->id, 'is_active' => 1, 'effective_from' => today()->toDateString()]);
        }
        foreach ($fixture['heads'] as $head) {
            $this->assertFalse($head->fresh()->is_active);
            $this->assertDatabaseHas('user_job_positions', ['mst_job_position_id' => $head->id, 'is_active' => 0,
                'effective_until' => today()->subDay()->toDateString()]);
        }
        $this->assertSame($history, DB::table('trs_penilaian_tcs')->get()->toJson());
        $this->assertSame($standards, DB::table('mst_tcs')->get()->toJson());
    }

    public function test_stale_review_hash_and_unauthorized_actor_cannot_apply(): void
    {
        $fixture = $this->organization();
        $hash = $this->service()->preview()['hash'];
        $fixture['children'][1]->update(['position_name' => 'Changed after preview']);
        try {
            $this->service()->apply($fixture['actor'], $hash);
            $this->fail('Stale preview accepted');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('preview', $exception->getMessage());
        }
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('mst_departments', 2);
        $this->expectException(AuthorizationException::class);
        $this->service()->apply($fixture['incumbents'][0], $this->service()->preview()['hash']);
    }

    public function test_unknown_incoming_reference_or_km_target_blocks_apply(): void
    {
        $fixture = $this->organization();
        $outside = $this->position();
        DB::table('mst_position_approvals')->insert(['position_id' => $outside->id, 'approval_level' => 2,
            'approver_position_id' => $fixture['heads'][0]->id]);
        $this->assertSame('blocked', $this->service()->preview()['status']);
        DB::table('mst_position_approvals')->where('position_id', $outside->id)->delete();
        DB::statement('CREATE TABLE km_document_version_departments (document_version_id bigint, department_id bigint)');
        DB::table('km_document_version_departments')->insert(['document_version_id' => 123, 'department_id' => $fixture['heads'][0]->department_id]);
        $this->assertSame('blocked', $this->service()->preview()['status']);
        $this->assertDatabaseCount('mst_departments', 2);
    }

    public function test_repeat_is_noop_after_hr_changes_successor_assignment_or_name(): void
    {
        $fixture = $this->organization();
        $hash = $this->service()->preview()['hash'];
        $this->service()->apply($fixture['actor'], $hash);
        $head = MstJobPosition::where('position_name', 'Sales Dept Head Region 1')->firstOrFail();
        $head->update(['position_name' => 'HR renamed successor']);
        UserJobPosition::where('mst_job_position_id', $head->id)->update(['is_active' => false]);
        $auditCount = DB::table('km_organization_assignment_audits')->count();
        $fileCount = count(Storage::disk('local')->allFiles());
        $result = $this->service()->apply($fixture['actor'], $hash);
        $this->assertSame('completed', $result['status']);
        $this->assertDatabaseHas('mst_job_positions', ['id' => $head->id, 'position_name' => 'HR renamed successor']);
        $this->assertDatabaseHas('user_job_positions', ['mst_job_position_id' => $head->id, 'is_active' => 0]);
        $this->assertSame($auditCount, DB::table('km_organization_assignment_audits')->count());
        $this->assertSame($fileCount, count(Storage::disk('local')->allFiles()));
    }

    public function test_incumbents_only_receive_their_two_regions_and_hr_can_replace_them(): void
    {
        $fixture = $this->organization();
        $this->service()->apply($fixture['actor'], $this->service()->preview()['hash']);
        foreach ([[0, [1, 3]], [1, [2, 4]]] as [$index, $regions]) {
            $scope = app(\App\Services\HR\TcpdDashboardAccessService::class)->scope($fixture['incumbents'][$index]);
            $expected = MstJobPosition::whereIn('position_name', array_map(fn ($region) => 'Sales Dept Head Region '.$region, $regions))
                ->pluck('department_id')->map('intval')->sort()->values()->all();
            $this->assertSame($expected, $scope['department_ids']);
            $expectedJobs = array_map(fn ($region) => $fixture['children'][$region]->id, $regions);
            sort($expectedJobs);
            $this->assertSame($expectedJobs, $scope['job_position_ids']);
        }
        $head = MstJobPosition::where('position_name', 'Sales Dept Head Region 1')->firstOrFail();
        $mapping = UserJobPosition::where('mst_job_position_id', $head->id)->firstOrFail();
        $replacement = $this->employee('Fixture replacement');
        $this->actingAs($fixture['actor'])->put(route('user-job-position.update', $mapping), [
            'user_id' => $replacement->id, 'mst_job_position_id' => $head->id,
            'effective_from' => today()->toDateString(),
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame($replacement->id, $mapping->fresh()->user_id);
    }

    public function test_apply_command_requires_actor_and_review_hash(): void
    {
        $this->organization();
        $this->artisan('hr:split-sales-regions --apply')->assertFailed();
        $this->assertDatabaseCount('mst_departments', 2);
    }

    public function test_partial_destination_and_future_incumbent_fail_closed(): void
    {
        $fixture = $this->organization();
        $id = DB::table('mst_departments')->insertGetId(['name' => 'Sales Region 1']);
        $this->assertSame('blocked', $this->service()->preview()['status']);
        DB::table('mst_departments')->where('id', $id)->delete();
        UserJobPosition::where('mst_job_position_id', $fixture['heads'][0]->id)
            ->update(['effective_from' => today()->addDay()->toDateString()]);
        $preview = $this->service()->preview();
        $this->assertSame('blocked', $preview['status']);
        $this->expectException(RuntimeException::class);
        $this->service()->apply($fixture['actor'], $preview['hash']);
    }

    public function test_head_position_km_target_requires_review_and_never_gets_silently_retargeted(): void
    {
        $fixture = $this->organization();
        DB::statement('CREATE TABLE km_document_version_job_positions (document_version_id bigint, job_position_id bigint)');
        DB::table('km_document_version_job_positions')->insert(['document_version_id' => 22, 'job_position_id' => $fixture['heads'][0]->id]);
        $preview = $this->service()->preview();
        $this->assertSame('blocked', $preview['status']);
        try {
            $this->service()->apply($fixture['actor'], $preview['hash']);
            $this->fail('Targeted head position was retired');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('KM', $exception->getMessage());
        }
        $this->assertTrue($fixture['heads'][0]->fresh()->is_active);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_snapshot_failure_happens_before_database_writes(): void
    {
        $fixture = $this->organization();
        $hash = $this->service()->preview()['hash'];
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        try {
            $this->service()->apply($fixture['actor'], $hash);
            $this->fail('Applied without a snapshot');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Snapshot', $exception->getMessage());
        }
        $this->assertDatabaseCount('mst_departments', 2);
        $this->assertDatabaseCount('user_job_positions', 2);
        $this->assertDatabaseCount('km_organization_assignment_audits', 0);
    }

    public function test_km_target_sync_revalidates_old_targets_after_cutover(): void
    {
        $fixture = $this->organization();
        DB::statement('CREATE TABLE km_document_version_departments (document_version_id bigint unsigned, department_id bigint unsigned, created_at timestamp null, updated_at timestamp null, PRIMARY KEY(document_version_id, department_id), FOREIGN KEY(department_id) REFERENCES mst_departments(id)) ENGINE=InnoDB');
        DB::statement('CREATE TABLE km_document_version_job_positions (document_version_id bigint unsigned, job_position_id bigint unsigned, created_at timestamp null, updated_at timestamp null, PRIMARY KEY(document_version_id, job_position_id), FOREIGN KEY(job_position_id) REFERENCES mst_job_positions(id)) ENGINE=InnoDB');
        $this->service()->apply($fixture['actor'], $this->service()->preview()['hash']);
        $version = new \App\Models\KmDocumentVersion;
        $version->id = 100;
        $version->exists = true;
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\KnowledgeManagement\KmTargetingService::class)->sync($version,
            [$fixture['heads'][0]->department_id], [$fixture['heads'][0]->id]);
    }

    public function test_late_failure_rolls_back_structure_assignments_and_audits_together(): void
    {
        $fixture = $this->organization();
        $hash = $this->service()->preview()['hash'];
        $failing = \Mockery::mock(\App\Services\KnowledgeManagement\KmOrganizationAssignmentService::class)->makePartial();
        $failing->shouldReceive('update')->once()->andThrow(new RuntimeException('Fixture late failure'));
        $this->app->instance(\App\Services\KnowledgeManagement\KmOrganizationAssignmentService::class, $failing);
        try {
            $this->service()->apply($fixture['actor'], $hash);
            $this->fail('Expected fixture failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fixture late failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('mst_departments', 2);
        $this->assertDatabaseCount('user_job_positions', 2);
        $this->assertDatabaseCount('km_organization_assignment_audits', 0);
        $this->assertTrue($fixture['heads'][0]->fresh()->is_active);
        $this->assertNotEmpty(Storage::disk('local')->allFiles('private/hr/sales-region-split'));
    }
}

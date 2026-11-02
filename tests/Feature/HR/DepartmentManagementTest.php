<?php

namespace Tests\Feature\HR;

use App\Models\MstDepartment;
use App\Models\MstJobPosition;
use App\Models\MstSection;
use App\Services\HR\DepartmentStructureService;
use Illuminate\Support\Facades\DB;
use Tests\Support\HrIsolatedDatabaseTestCase;

class DepartmentManagementTest extends HrIsolatedDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('ALTER TABLE mst_departments ADD UNIQUE (name)');
        DB::statement('ALTER TABLE mst_sections ADD UNIQUE (department_id, name), ADD FOREIGN KEY (department_id) REFERENCES mst_departments(id)');
        DB::statement('ALTER TABLE mst_job_positions ADD FOREIGN KEY (department_id) REFERENCES mst_departments(id), ADD FOREIGN KEY (section_id) REFERENCES mst_sections(id)');
    }

    public function test_manager_can_create_rename_view_and_set_status(): void
    {
        $this->actingAs($this->manager());
        $this->post(route('mst-department.store'), ['name' => '  Department Baru  '])->assertRedirect();
        $department = MstDepartment::firstOrFail();
        $this->assertSame('Department Baru', $department->name);
        $this->put(route('mst-department.update', $department), ['name' => 'Department Revisi'])->assertRedirect();
        $this->get(route('mst-department.index', ['search' => 'Revisi']))->assertOk()->assertSee('Department Revisi');
        $this->get(route('mst-department.show', $department))->assertOk()->assertSee('Department Revisi');
        $this->patch(route('mst-department.status', $department), ['is_active' => false])->assertRedirect();
        $this->assertFalse($department->fresh()->is_active);
        $this->postJson(route('mst-department.store'), ['name' => ' Department Revisi '])->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_non_manager_cannot_read_or_mutate_any_department_endpoint(): void
    {
        $department = MstDepartment::create(['name' => 'Private department']);
        $section = MstSection::create(['name' => 'Private section', 'department_id' => $department->id]);
        $employee = $this->employee();
        $employee->id = 2000;
        $this->actingAs($employee);
        $this->get(route('mst-department.index'))->assertForbidden();
        $this->get(route('mst-department.show', $department))->assertForbidden();
        $this->postJson(route('mst-department.store'), ['name' => 'Injected'])->assertForbidden();
        $this->putJson(route('mst-department.update', $department), ['name' => 'Injected'])->assertForbidden();
        $this->patchJson(route('mst-department.status', $department), ['is_active' => false])->assertForbidden();
        $this->postJson(route('mst-department.move-section', [$department, $section]), [])->assertForbidden();
        $this->getJson(route('mst-job-position.ajax.sections-by-dept', $department))->assertForbidden();
    }

    public function test_move_updates_active_and_inactive_positions_but_preserves_other_relations(): void
    {
        [$source, $target, $section, $position] = $this->structure();
        $inactive = MstJobPosition::create(['position_name' => 'Inactive', 'department_id' => $source->id, 'section_id' => $section->id, 'is_active' => false]);
        $assignment = $this->assign($this->employee(), $position);
        $approver = $this->position('dept_head');
        DB::table('mst_position_approvals')->insert(['position_id' => $position->id, 'approval_level' => 2, 'approver_position_id' => $approver->id]);
        $beforeAssignment = $assignment->fresh()->getAttributes();
        $beforeApproval = DB::table('mst_position_approvals')->first();
        $this->actingAs($this->manager())->post(route('mst-department.move-section', [$source, $section]), $this->movePayload($section, $target))->assertRedirect();
        foreach ([$position, $inactive] as $job) {
            $this->assertSame($target->id, $job->fresh()->department_id);
            $this->assertSame($section->id, $job->fresh()->section_id);
        }
        $this->assertSame($target->id, $section->fresh()->department_id);
        $this->assertSame($beforeAssignment, $assignment->fresh()->getAttributes());
        $this->assertEquals($beforeApproval, DB::table('mst_position_approvals')->first());
    }

    public function test_move_rejects_stale_page_inactive_destination_and_duplicate_section(): void
    {
        [$source, $target, $section] = $this->structure();
        $payload = $this->movePayload($section, $target);
        $this->actingAs($this->manager());
        $target->update(['is_active' => false]);
        $this->postJson(route('mst-department.move-section', [$source, $section]), $payload)->assertUnprocessable();
        $target->update(['is_active' => true]);
        MstSection::create(['name' => $section->name, 'department_id' => $target->id]);
        $this->postJson(route('mst-department.move-section', [$source, $section]), $payload)->assertUnprocessable();
        MstSection::where('department_id', $target->id)->delete();
        $section->update(['name' => 'Changed concurrently']);
        $this->postJson(route('mst-department.move-section', [$source, $section]), $payload)->assertStatus(409);
        $this->assertSame($source->id, $section->fresh()->department_id);
    }

    public function test_active_children_prevent_deactivation_and_position_writes_require_matching_active_structure(): void
    {
        [$source, $target, $section, $position] = $this->structure();
        $this->actingAs($this->manager());
        $this->patchJson(route('mst-department.status', $source), ['is_active' => false])->assertUnprocessable();
        $this->assertTrue($source->fresh()->is_active);
        $this->postJson(route('mst-job-position.store'), ['position_name' => 'Invalid pair', 'department_id' => $target->id, 'section_id' => $section->id])->assertUnprocessable();
        $this->putJson(route('mst-job-position.update', $position), ['position_name' => $position->position_name, 'department_id' => $target->id, 'section_id' => $section->id])->assertUnprocessable();
        $target->update(['is_active' => false]);
        $this->postJson(route('mst-job-position.ajax.store-section'), ['name' => 'Bad section', 'department_id' => $target->id])->assertUnprocessable();
        $this->postJson(route('mst-job-position.store'), ['position_name' => 'Inactive destination', 'department_id' => $target->id])->assertUnprocessable();
        $this->assertSame($source->id, $position->fresh()->department_id);
    }

    public function test_move_rolls_back_if_a_position_write_fails(): void
    {
        [$source, $target, $section] = $this->structure();
        DB::unprepared("CREATE TRIGGER reject_department_move BEFORE UPDATE ON mst_job_positions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fixture failure'");
        $this->actingAs($this->manager())->postJson(route('mst-department.move-section', [$source, $section]), $this->movePayload($section, $target))->assertStatus(500);
        $this->assertSame($source->id, $section->fresh()->department_id);
        $this->assertSame(1, MstJobPosition::where('department_id', $source->id)->count());
    }

    public function test_concurrent_writer_waits_for_section_move_and_revalidates_after_commit(): void
    {
        [$source, $target, $section] = $this->structure();
        $connection = DB::connection();
        $default = config('database.default');
        config(['database.connections.department_concurrency' => config('database.connections.mysql')]);
        $connection->beginTransaction();
        try {
            app(DepartmentStructureService::class)->moveSection($source->id, $section->id, $target->id,
                app(DepartmentStructureService::class)->sectionToken($section));
            config(['database.default' => 'department_concurrency']);
            DB::connection()->statement('SET SESSION innodb_lock_wait_timeout = 1');
            try {
                app(DepartmentStructureService::class)->createSection($source->id, 'Concurrent section');
                $this->fail('The concurrent writer bypassed the organization lock.');
            } catch (\Illuminate\Database\QueryException $exception) {
                $this->assertSame(1205, $exception->errorInfo[1]);
            }
            $connection->commit();
            $this->actingAs($this->manager())->postJson(route('mst-job-position.store'), [
                'position_name' => 'Stale concurrent position', 'department_id' => $source->id, 'section_id' => $section->id,
            ])->assertUnprocessable()->assertJsonValidationErrors('section_id');
            $this->assertSame($target->id, $section->fresh()->department_id);
            $this->assertDatabaseMissing('mst_sections', ['name' => 'Concurrent section']);
        } finally {
            config(['database.default' => $default]);
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
            DB::purge('department_concurrency');
        }
    }

    public function test_department_names_are_escaped_and_rendered_forms_have_safe_contracts(): void
    {
        [$source, $target, $section] = $this->structure();
        $source->update(['name' => '<img src=x onerror=alert(1)>']);
        $this->actingAs($this->manager());
        $page = $this->get(route('mst-department.show', $source))->assertOk()
            ->assertSee($source->name)->assertDontSee($source->name, false)
            ->assertSee('name="_token"', false)->assertSee('name="structure_token"', false)
            ->assertSee('name="expected_department_id"', false);
        if (getenv('DEPARTMENT_BROWSER_EXPORT')) {
            $directory = storage_path('framework/department-browser-qa');
            if (! is_dir($directory)) {
                mkdir($directory, 0700, true);
            }
            // Synthetic fixtures only. Rendered pages never contain live employee data.
            file_put_contents($directory.'/detail.html', $page->getContent());
            file_put_contents($directory.'/index.html', $this->get(route('mst-department.index'))->getContent());
        }
    }

    public function test_archived_position_cannot_receive_new_or_reactivated_assignments(): void
    {
        [$source, $target, $section, $position] = $this->structure();
        $employee = $this->employee();
        $assignment = $this->assign($employee, $position, ['is_active' => false]);
        $position->update(['is_active' => false]);
        $this->actingAs($this->manager());
        $this->postJson(route('user-job-position.store'), ['user_ids' => [$employee->id], 'mst_job_position_id' => $position->id])->assertUnprocessable();
        $this->putJson(route('user-job-position.update', $assignment), ['user_id' => $employee->id, 'mst_job_position_id' => $position->id])->assertUnprocessable();
        $this->patchJson(route('user-job-position.toggle-active', $assignment))->assertUnprocessable();
        $this->assertFalse($assignment->fresh()->is_active);
        $this->assertDatabaseCount('user_job_positions', 1);
    }

    private function structure(): array
    {
        $source = MstDepartment::create(['name' => 'Source', 'is_active' => true]);
        $target = MstDepartment::create(['name' => 'Target', 'is_active' => true]);
        $section = MstSection::create(['name' => 'Section', 'department_id' => $source->id, 'is_active' => true]);
        $position = MstJobPosition::create(['position_name' => 'Staff', 'department_id' => $source->id, 'section_id' => $section->id, 'is_active' => true]);

        return [$source, $target, $section, $position];
    }

    private function movePayload(MstSection $section, MstDepartment $target): array
    {
        return ['department_id' => $target->id, 'expected_department_id' => $section->department_id,
            'structure_token' => app(DepartmentStructureService::class)->sectionToken($section)];
    }
}

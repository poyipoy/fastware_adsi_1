<?php

namespace Tests\Feature\HR;

use App\Enums\HRMenuAccessGroup;
use App\Models\MstJobPosition;
use App\Models\User;
use App\Models\WorkingExperience;
use App\Services\HR\JobPositionAccessService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\HrIsolatedDatabaseTestCase;

class EmployeeMappingAuthorizationTest extends HrIsolatedDatabaseTestCase
{
    public function test_existing_manager_allowlist_and_full_access_ids_are_preserved(): void
    {
        $access = app(JobPositionAccessService::class);
        foreach (HRMenuAccessGroup::JOB_POSITION->getAllowedUsers() as $name) {
            $user = new \App\Models\User(['name' => $name]);
            $user->id = 2000;
            $this->assertTrue($access->canManageMapping($user));
        }
        foreach ([1, 91] as $id) {
            $user = new \App\Models\User(['name' => 'Synthetic full access']);
            $user->id = $id;
            $this->assertTrue($access->canManageMapping($user));
        }
    }

    public function test_manager_sees_all_mappings_and_preserves_assignment_audit_and_deactivation(): void
    {
        $employee = $this->employee();
        $position = $this->position();
        $this->actingAs($this->manager())->post(route('user-job-position.store'), [
            'user_ids' => [$employee->id], 'mst_job_position_id' => $position->id,
        ])->assertRedirect()->assertSessionHas('success');
        $id = DB::table('user_job_positions')->value('id');
        $this->assertSame(1, DB::table('km_organization_assignment_audits')->count());
        $page = $this->get(route('user-job-position.index'))->assertOk()
            ->assertViewHas('mappings', fn ($paginator) => $paginator->total() === 1)
            ->assertSee('Assign Karyawan ke Posisi');
        $this->assertInlineJavascriptParses($page->getContent(), 'manager mapping page');
        $this->delete(route('user-job-position.destroy', $id))->assertRedirect();
        $this->assertSame(1, DB::table('user_job_positions')->count());
        $this->assertSame(0, (int) DB::table('user_job_positions')->value('is_active'));
        $this->assertSame(2, DB::table('km_organization_assignment_audits')->count());
    }

    public static function headLevels(): array
    {
        return [['sec_head'], ['dept_head'], ['div_head']];
    }

    #[DataProvider('headLevels')]
    public function test_heads_read_only_direct_and_transitive_descendants(string $level): void
    {
        $head = $this->employee('Fixture head');
        $head->id = 2000;
        $position = $this->position($level);
        $this->assign($head, $position);
        $direct = $this->position();
        $this->edge($direct, $position, $this->levelFor($level));
        $directMapping = $this->subordinate($direct);
        $intermediate = $this->position($level === 'div_head' ? 'dept_head' : 'sec_head');
        $this->edge($intermediate, $position, $this->levelFor($level));
        $middleMapping = $this->subordinate($intermediate);
        $child = $this->position($level === 'div_head' ? 'sec_head' : 'staff');
        $this->edge($child, $intermediate, 1);
        $childMapping = $this->subordinate($child);
        $expected = [$directMapping->id, $middleMapping->id, $childMapping->id];
        if ($level === 'div_head') {
            $leaf = $this->position();
            $this->edge($leaf, $child, 1);
            $expected[] = $this->subordinate($leaf)->id;
        }
        $unrelated = $this->subordinate($this->position(), 'Private unrelated employee');
        $this->actingAs($head);
        $page = $this->assertMappingIds($expected);
        $page->assertViewHas('positions', fn ($p) => $p->count() === count($expected))
            ->assertViewHas('users', fn ($p) => $p->isEmpty())
            ->assertSee('Akses baca saja')->assertSee('Detail')
            ->assertDontSee('Private unrelated employee')
            ->assertDontSee('id="modalImportWe"', false)
            ->assertDontSee('id="formEditMapping"', false)
            ->assertDontSee('id="we-btn-add"', false);
        $this->assertInlineJavascriptParses($page->getContent(), $level.' mapping page');
        $this->getJson(route('user-job-position.api.by-user', ['user_id' => $unrelated->user_id]))->assertForbidden();
        $this->getJson(route('user-job-position.api.working-experience.index', ['user_id' => $unrelated->user_id]))->assertForbidden();
    }

    public static function invalidAssignments(): array
    {
        return [['future'], ['expired'], ['inactive'], ['inactive_position'], ['missing_start']];
    }

    #[DataProvider('invalidAssignments')]
    public function test_invalid_assignment_does_not_grant_reader_access(string $kind): void
    {
        $user = $this->employee();
        $user->id = 2000;
        $position = $this->position('dept_head');
        if ($kind === 'inactive_position') {
            $position->update(['is_active' => false]);
        }
        $attributes = match ($kind) {
            'future' => ['effective_from' => today()->addDay()],
            'expired' => ['effective_until' => today()->subDay()],
            'inactive' => ['is_active' => false],
            'missing_start' => ['effective_from' => null],
            default => [],
        };
        $this->assign($user, $position, $attributes);
        $this->actingAs($user)->get(route('user-job-position.index'))->assertForbidden();
    }

    public function test_multiple_effective_head_assignments_union_and_deduplicate_only_their_branches(): void
    {
        $user = $this->employee();
        $user->id = 2000;
        $section = $this->position('sec_head');
        $division = $this->position('div_head');
        $this->assign($user, $section);
        $this->assign($user, $division);
        $left = $this->position();
        $right = $this->position();
        $shared = $this->position();
        $this->edge($left, $section, 1);
        $this->edge($right, $division, 3);
        $this->edge($shared, $section, 1);
        $this->edge($shared, $division, 3);
        $ids = [$this->subordinate($left)->id, $this->subordinate($right)->id, $this->subordinate($shared)->id];
        $future = $this->position('dept_head');
        $staffAssignment = $this->position();
        $this->assign($user, $future, ['effective_from' => today()->addDay()]);
        $this->assign($user, $staffAssignment);
        foreach ([$future, $staffAssignment] as $ineligibleRoot) {
            $hidden = $this->position();
            $this->edge($hidden, $ineligibleRoot, 0);
            $this->subordinate($hidden);
        }
        $this->subordinate($this->position(), 'Private unrelated branch');
        $this->actingAs($user);
        $this->assertMappingIds($ids);
    }

    public function test_staff_and_guest_cannot_bypass_read_endpoints(): void
    {
        $employee = $this->employee();
        $staff = new \App\Models\User(['name' => 'Fixture staff']);
        $staff->id = 2000;
        $this->get(route('user-job-position.index'))->assertRedirect();
        $this->actingAs($staff)->get(route('user-job-position.index'))->assertForbidden();
        $this->getJson(route('user-job-position.api.by-user', ['user_id' => $employee->id]))->assertForbidden();
        $this->getJson(route('user-job-position.api.working-experience.index', ['user_id' => $employee->id]))->assertForbidden();
    }

    public function test_head_cannot_mutate_any_mapping_or_working_experience_endpoint(): void
    {
        $head = $this->employee();
        $head->id = 2000;
        $headPosition = $this->position('sec_head');
        $this->assign($head, $headPosition);
        $employee = $this->employee();
        $mapping = $this->assign($employee, $this->position());
        $this->edge($mapping->jobPosition, $headPosition, 1);
        $experience = WorkingExperience::create(['user_id' => $employee->id, 'year_start' => 2020, 'job_position' => 'Fixture experience']);
        $before = DB::table('user_job_positions')->get()->toJson();
        $this->actingAs($head);
        foreach ([
            ['POST', 'user-job-position.store', []], ['PUT', 'user-job-position.update', [$mapping->id]],
            ['PATCH', 'user-job-position.toggle-active', [$mapping->id]], ['DELETE', 'user-job-position.destroy', [$mapping->id]],
            ['POST', 'user-job-position.api.working-experience.store', []],
            ['PUT', 'user-job-position.api.working-experience.update', [$experience->id]],
            ['DELETE', 'user-job-position.api.working-experience.destroy', [$experience->id]],
            ['POST', 'user-job-position.api.working-experience.import', []],
            ['GET', 'user-job-position.api.working-experience.import.template', []],
        ] as [$method, $route, $parameters]) {
            $this->json($method, route($route, $parameters), ['user_id' => $employee->id])->assertForbidden();
        }
        $this->post(route('user-job-position.destroy', $mapping->id), ['_method' => 'DELETE'])->assertForbidden();
        $this->assertSame($before, DB::table('user_job_positions')->get()->toJson());
        $this->assertSame(1, WorkingExperience::count());
        $this->assertSame(0, DB::table('km_organization_assignment_audits')->count());
    }

    public function test_manager_working_experience_crud_and_lookup_still_work(): void
    {
        $employee = $this->employee();
        $position = $this->position();
        $this->assign($employee, $position);
        $this->actingAs($this->manager())->postJson(route('user-job-position.api.working-experience.store'), [
            'user_id' => $employee->id, 'year_start' => 2020, 'job_position' => $position->position_name,
        ])->assertOk();
        $experience = WorkingExperience::firstOrFail();
        $this->getJson(route('user-job-position.api.working-experience.index', ['user_id' => $employee->id]))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(route('user-job-position.api.by-user', ['user_id' => $employee->id]))->assertOk()->assertJsonCount(1);
        $this->putJson(route('user-job-position.api.working-experience.update', $experience->id), ['year_start' => 2021, 'job_position' => $position->position_name])->assertOk();
        $this->deleteJson(route('user-job-position.api.working-experience.destroy', $experience->id))->assertOk();
        $this->assertSame(0, WorkingExperience::count());
    }

    public function test_manager_update_and_toggle_preserve_assignment_and_audit(): void
    {
        $employee = $this->employee();
        $position = $this->position();
        $mapping = $this->assign($employee, $position);
        $newPosition = $this->position();
        $this->actingAs($this->manager())->put(route('user-job-position.update', $mapping->id), [
            'user_id' => $employee->id, 'mst_job_position_id' => $newPosition->id,
        ])->assertRedirect();
        $this->assertSame($newPosition->id, $mapping->fresh()->mst_job_position_id);
        $this->patch(route('user-job-position.toggle-active', $mapping->id))->assertRedirect();
        $this->assertFalse($mapping->fresh()->is_active);
        $this->patch(route('user-job-position.toggle-active', $mapping->id))->assertRedirect();
        $this->assertTrue($mapping->fresh()->is_active);
        $this->assertSame(3, DB::table('km_organization_assignment_audits')->count());
    }

    public function test_employee_with_multiple_positions_exposes_only_authorized_rows_and_minimal_ajax_fields(): void
    {
        [$head, $root] = $this->reader();
        $allowed = $this->position();
        $outside = $this->position();
        $this->edge($allowed, $root, 1);
        $mapping = $this->subordinate($allowed);
        $employee = $mapping->user;
        $this->assign($employee, $outside);
        WorkingExperience::create(['user_id' => $employee->id, 'year_start' => 2020, 'job_position' => 'Fixture history']);
        $this->actingAs($head);
        $this->assertMappingIds([$mapping->id]);
        $response = $this->getJson(route('user-job-position.api.by-user', ['user_id' => $employee->id, 'job_position_id' => $outside->id]))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $allowed->id)
            ->assertJsonMissingPath('0.approval_routes')->assertJsonMissingPath('0.users');
        $this->assertSame(['id', 'position_name', 'department_id', 'section_id', 'job_level'], array_keys($response->json('0')));
        $this->getJson(route('user-job-position.api.working-experience.index', ['user_id' => $employee->id]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.job_position', 'Fixture history');
        $this->getJson(route('user-job-position.api.by-user', ['user_id' => $head->id]))->assertForbidden();
        $this->getJson(route('user-job-position.api.by-user', ['user_id' => [$employee->id]]))->assertForbidden();
    }

    public function test_level_zero_is_an_explicit_optional_sub_section_route_not_self_or_staff_permission(): void
    {
        [$head, $root] = $this->reader();
        $subSection = $this->position('sec_head');
        $this->edge($subSection, $root, 1);
        $staff = $this->position();
        $this->edge($staff, $subSection, 0);
        $indirect = $this->subordinate($staff);
        $nullRoute = $this->position();
        DB::table('mst_position_approvals')->insert(['position_id' => $nullRoute->id, 'approval_level' => 0, 'approver_position_id' => null]);
        $this->subordinate($nullRoute);
        $this->actingAs($head);
        $this->assertMappingIds([$indirect->id]);

        // The database may designate a staff-coded position as a route recipient;
        // that edge does not itself turn its holder into an eligible head reader.
        $ordinary = $this->employee('Fixture ordinary route recipient');
        $this->assign($ordinary, $nullRoute);
        $this->edge($this->position(), $nullRoute, 0);
        $this->actingAs($ordinary)->get(route('user-job-position.index'))->assertForbidden();
    }

    public function test_level_roles_are_not_numeric_ranks_or_job_label_fallbacks(): void
    {
        [$head, $root] = $this->reader('dept_head');
        $staff = $this->position();
        $this->edge($staff, $root, 1); // Observed target pattern: Dept Head acts at level 1.
        $this->edge($staff, $root, 0); // Same explicit relationship at another role.
        $mapping = $this->subordinate($staff);
        $unrelated = $this->position();
        $unrelated->update(['position_name' => $staff->position_name]);
        $this->subordinate($unrelated);
        $this->actingAs($head);
        $this->assertMappingIds([$mapping->id]);
    }

    public static function unsafeEdges(): array
    {
        return [['self'], ['missing_parent'], ['inactive_parent'], ['unknown_level'], ['conflicting_slot'], ['null_conflict']];
    }

    #[DataProvider('unsafeEdges')]
    public function test_malformed_or_ambiguous_branches_fail_closed_without_hiding_independent_valid_branch(string $kind): void
    {
        [$head, $root] = $this->reader();
        $valid = $this->position();
        $this->edge($valid, $root, 1);
        $allowed = $this->subordinate($valid);
        $unsafe = $this->position();
        $this->edge($unsafe, $root, 1);
        $other = $this->position();
        $parent = match ($kind) {
            'self' => $unsafe->id,
            'missing_parent' => 999999,
            'inactive_parent' => $other->id,
            'null_conflict' => null,
            default => $root->id,
        };
        if ($kind === 'inactive_parent') {
            $other->update(['is_active' => false]);
        }
        DB::table('mst_position_approvals')->insert([
            'position_id' => $unsafe->id, 'approver_position_id' => $kind === 'conflicting_slot' ? $other->id : $parent,
            'approval_level' => $kind === 'unknown_level' ? 8 : (in_array($kind, ['conflicting_slot', 'null_conflict'], true) ? 1 : 0),
        ]);
        $this->subordinate($unsafe);
        $leaf = $this->position();
        $this->edge($leaf, $unsafe, 0);
        $this->edge($leaf, $root, 2); // An alternate path cannot bypass quarantine.
        $this->subordinate($leaf);
        $this->actingAs($head);
        $this->assertMappingIds([$allowed->id]);
    }

    public function test_cycles_and_their_downstream_paths_are_closed_but_independent_scope_survives(): void
    {
        [$head, $root] = $this->reader();
        $a = $this->position();
        $b = $this->position();
        $c = $this->position();
        $valid = $this->position();
        $this->edge($a, $root, 1);
        $this->edge($b, $a, 0);
        $this->edge($a, $b, 0);
        $this->edge($c, $b, 0);
        $this->edge($c, $root, 2);
        $this->edge($valid, $root, 1);
        foreach ([$a, $b, $c] as $position) {
            $this->subordinate($position);
        }
        $allowed = $this->subordinate($valid);
        $this->actingAs($head);
        $this->assertMappingIds([$allowed->id]);
    }

    public function test_actor_self_held_positions_and_ancestors_are_excluded_across_multiple_assignments(): void
    {
        [$head, $division] = $this->reader('div_head');
        $department = $this->position('dept_head');
        $section = $this->position('sec_head');
        $staff = $this->position();
        $this->edge($department, $division, 3);
        $this->edge($section, $department, 2);
        $this->edge($staff, $section, 1);
        $this->assign($head, $section);
        $this->assign($head, $staff);
        $this->subordinate($department);
        $this->subordinate($section);
        $this->subordinate($staff);
        $leaf = $this->position();
        $this->edge($leaf, $division, 3);
        $allowed = $this->subordinate($leaf);
        $this->actingAs($head);
        $this->assertMappingIds([$allowed->id]);
    }

    #[DataProvider('invalidAssignments')]
    public function test_invalid_target_assignment_cannot_grant_mapping_or_working_experience_access(string $kind): void
    {
        [$head, $root] = $this->reader();
        $position = $this->position();
        $this->edge($position, $root, 1);
        $employee = $this->employee();
        $attributes = match ($kind) {
            'future' => ['effective_from' => today()->addDay()],
            'expired' => ['effective_until' => today()->subDay()],
            'inactive' => ['is_active' => false],
            'missing_start' => ['effective_from' => null],
            default => [],
        };
        $this->assign($employee, $position, $attributes);
        if ($kind === 'inactive_position') {
            $position->update(['is_active' => false]);
        }
        $this->actingAs($head);
        $this->assertMappingIds([]);
        $this->getJson(route('user-job-position.api.by-user', ['user_id' => $employee->id]))->assertForbidden();
        $this->getJson(route('user-job-position.api.working-experience.index', ['user_id' => $employee->id]))->assertForbidden();
    }

    public function test_search_filters_pagination_and_position_options_remain_in_scope(): void
    {
        [$head, $root] = $this->reader();
        $allowed = $this->position();
        $outside = $this->position();
        $this->edge($allowed, $root, 1);
        for ($i = 0; $i < 26; $i++) {
            $this->subordinate($allowed, 'Visible fixture '.$i);
        }
        $this->subordinate($outside, 'Private needle');
        $this->actingAs($head)->get(route('user-job-position.index'))->assertOk()
            ->assertViewHas('mappings', fn ($p) => $p->total() === 26 && $p->count() === 25)
            ->assertViewHas('positions', fn ($p) => $p->pluck('id')->all() === [$allowed->id]);
        $this->get(route('user-job-position.index', ['page' => 2]))->assertOk()
            ->assertViewHas('mappings', fn ($p) => $p->total() === 26 && $p->count() === 1);
        foreach ([['search' => 'Private'], ['position_id' => $outside->id], ['search' => '9000001', 'position_id' => $outside->id]] as $filter) {
            $this->get(route('user-job-position.index', $filter))->assertOk()
                ->assertViewHas('mappings', fn ($p) => $p->total() === 0)
                ->assertDontSee('Private needle');
        }
        $this->get(route('user-job-position.index', ['search' => 'Visible fixture 25', 'position_id' => $allowed->id]))->assertOk()
            ->assertViewHas('mappings', fn ($p) => $p->total() === 1);
        $this->getJson(route('user-job-position.index', ['position_id' => [$outside->id]]))->assertUnprocessable();
        $this->getJson(route('user-job-position.index', ['search' => ['Private']]))->assertUnprocessable();
    }

    public function test_scope_is_fresh_deterministic_and_query_count_does_not_grow_per_descendant(): void
    {
        [$head, $root] = $this->reader();
        $child = $this->position();
        $this->edge($child, $root, 1);
        $allowed = $this->subordinate($child);
        $this->actingAs($head);
        $access = app(JobPositionAccessService::class);
        DB::enableQueryLog();
        $one = $access->getReadableMappingScope($head)->pluck('id')->all();
        $smallCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        $ids = [$allowed->id];
        for ($i = 0; $i < 20; $i++) {
            $next = $this->position();
            $this->edge($next, $child, 0);
            $ids[] = $this->subordinate($next)->id;
        }
        DB::flushQueryLog();
        DB::enableQueryLog();
        $many = $access->getReadableMappingScope($head)->pluck('id')->all();
        $this->assertSame($smallCount, count(DB::getQueryLog()));
        DB::disableQueryLog();
        sort($ids);
        sort($many);
        $this->assertSame($ids, $many);
        $routes = DB::table('mst_position_approvals')->get()->map(fn ($r) => (array) $r)->reverse()->all();
        DB::table('mst_position_approvals')->delete();
        DB::table('mst_position_approvals')->insert(array_values($routes));
        // Identical duplicate route is harmless; differing parents are tested above.
        DB::table('mst_position_approvals')->insert(['position_id' => $child->id, 'approver_position_id' => $root->id, 'approval_level' => 1]);
        $this->assertMappingIds($ids);
        DB::table('mst_position_approvals')->where('position_id', $child->id)->update(['approver_position_id' => null]);
        $this->assertMappingIds([]);
        $this->assertSame([$allowed->id], $one);
    }

    public function test_effective_dates_are_inclusive_and_expiry_revokes_access_without_cached_scope(): void
    {
        [$head, $root] = $this->reader();
        DB::table('user_job_positions')->where('user_id', $head->id)
            ->update(['effective_from' => today()->toDateString(), 'effective_until' => today()->toDateString()]);
        $child = $this->position();
        $this->edge($child, $root, 1);
        $mapping = $this->subordinate($child);
        $mapping->update(['effective_from' => today(), 'effective_until' => today()]);
        $this->actingAs($head);
        $this->assertMappingIds([$mapping->id]);
        $this->travel(1)->days();
        $this->get(route('user-job-position.index'))->assertForbidden();
        $this->getJson(route('user-job-position.api.working-experience.index', ['user_id' => $mapping->user_id]))->assertForbidden();
    }

    public function test_live_mapping_menu_fragment_does_not_grant_master_position_management(): void
    {
        [$head] = $this->reader();
        $layout = file_get_contents(resource_path('views/layout.blade.php'));
        $start = strpos($layout, '@if ($hrRoleAccess->hasFullAccess($hrUser))', strpos($layout, 'Base Competency'));
        $end = strpos($layout, '@if (\\App\\Enums\\HRMenuAccessGroup::TECHNICAL_COMPETENCY', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $fragment = substr($layout, $start, $end - $start);
        $data = ['hrUser' => $head, 'hrRoleAccess' => app(\App\Services\HR\HRRoleAccessService::class)];
        $html = \Illuminate\Support\Facades\Blade::render($fragment, $data);
        $this->assertStringContainsString('Mapping Karyawan', $html);
        $this->assertStringNotContainsString('Master Job Position', $html);
        $head->userJobPositions()->update(['is_active' => false]);
        $html = \Illuminate\Support\Facades\Blade::render($fragment, $data);
        $this->assertStringNotContainsString('Mapping Karyawan', $html);
    }

    private function reader(string $level = 'sec_head'): array
    {
        DB::table('users')->insert(['id' => 2000, 'name' => 'Fixture head']);
        $user = User::findOrFail(2000);
        $position = $this->position($level);
        $this->assign($user, $position);

        return [$user, $position];
    }

    private function levelFor(string $level): int
    {
        return ['sec_head' => 1, 'dept_head' => 2, 'div_head' => 3][$level];
    }

    private function edge(MstJobPosition $child, MstJobPosition $parent, int $level): void
    {
        DB::table('mst_position_approvals')->insert(['position_id' => $child->id, 'approver_position_id' => $parent->id, 'approval_level' => $level]);
    }

    private function subordinate(MstJobPosition $position, string $name = 'Fixture subordinate'): \App\Models\UserJobPosition
    {
        return $this->assign($this->employee($name), $position);
    }

    private function assertMappingIds(array $ids): \Illuminate\Testing\TestResponse
    {
        sort($ids);

        return $this->get(route('user-job-position.index'))->assertOk()
            ->assertViewHas('mappings', function ($page) use ($ids) {
                $actual = $page->pluck('id')->all();
                sort($actual);

                return $actual === $ids && $page->total() === count($ids);
            });
    }
}

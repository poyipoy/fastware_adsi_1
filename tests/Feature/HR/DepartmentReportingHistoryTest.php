<?php

namespace Tests\Feature\HR;

use App\Http\Controllers\magang\StrukturOrganisasiController;
use App\Models\MstDepartment;
use App\Models\MstSection;
use App\Models\TrsPenilaianTc;
use App\Services\Dashboard\TcpdDashboardService;
use App\Services\HR\TcpdDashboardAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Support\HrIsolatedDatabaseTestCase;

class DepartmentReportingHistoryTest extends HrIsolatedDatabaseTestCase
{
    public function test_sales_counts_follow_four_departments_and_current_assignments_only(): void
    {
        $departments = [];
        foreach (range(1, 4) as $region) {
            $departments[$region] = MstDepartment::create(['name' => 'Sales Region '.$region, 'is_active' => true]);
        }
        $employee = $this->employee();
        foreach ([1, 1, 3] as $region) {
            $position = $this->position();
            $position->update(['department_id' => $departments[$region]->id]);
            $this->assign($employee, $position);
        }
        foreach ([['is_active' => false], ['effective_from' => today()->addDay()], ['effective_until' => today()->subDay()]] as $attributes) {
            $position = $this->position();
            $position->update(['department_id' => $departments[2]->id]);
            $this->assign($employee, $position, $attributes);
        }
        $inactivePosition = $this->position();
        $inactivePosition->update(['department_id' => $departments[4]->id, 'is_active' => false]);
        $this->assign($employee, $inactivePosition);
        $nonSales = $this->position();
        $nonSales->update(['position_name' => 'Finance Admin']);
        $this->assign($employee, $nonSales, ['is_active' => false]);

        $counts = app(StrukturOrganisasiController::class)->index()->getData()['employeeCounts'];
        $this->assertSame([1, 0, 1, 0], array_map(fn ($region) => $counts['Sales Region '.$region], range(1, 4)));
        $this->assertArrayNotHasKey('Sales Region 1 & 2', $counts);
        $this->assertArrayNotHasKey('Sales Region 3 & 4', $counts);
        $this->assertSame(1, $counts['Finance & Accounting, HRGA']);
    }

    public function test_legacy_sales_counts_remain_until_all_four_departments_exist(): void
    {
        MstDepartment::create(['name' => 'Sales Region 1', 'is_active' => true]);
        $position = $this->position();
        $position->update(['position_name' => 'Sales Engineer Reg 2']);
        $this->assign($this->employee(), $position);
        $counts = app(StrukturOrganisasiController::class)->index()->getData()['employeeCounts'];
        $this->assertSame(1, $counts['Sales Region 1 & 2']);
        $this->assertArrayNotHasKey('Sales Region 1', $counts);
    }

    public function test_company_and_sensitive_cache_keys_change_when_organization_changes(): void
    {
        $department = MstDepartment::create(['name' => 'Sales Region 1', 'is_active' => true]);
        $section = MstSection::create(['name' => 'Region 1', 'department_id' => $department->id, 'is_active' => true]);
        $position = $this->position();
        $position->update(['department_id' => $department->id, 'section_id' => $section->id]);
        $assignment = $this->assign($this->employee(), $position);
        $scope = ['access_class' => 'full_hr', 'user_id' => 91, 'section_ids' => [$section->id], 'department_ids' => [$department->id], 'job_position_ids' => [$position->id]];
        $access = Mockery::mock(TcpdDashboardAccessService::class);
        $access->shouldReceive('scope')->andReturn($scope);
        $service = new class($access) extends TcpdDashboardService
        {
            public function snapshotCacheProbe(int $positionId): array
            {
                return $this->buildTcpdSnapshot('Fixture position', null, null, [$positionId]);
            }
        };
        $keys = [];
        Cache::shouldReceive('remember')->andReturnUsing(function ($key) use (&$keys) {
            $keys[] = $key;

            return [];
        });
        $request = Request::create('/', 'GET', ['year_from' => 2026, 'year_to' => 2026]);
        $capture = function () use ($service, $request, $scope, $position, &$keys): array {
            $service->getCompanyPayload($request, $this->manager());
            $service->getSensitivePayload($request, $scope);
            $service->snapshotCacheProbe($position->id);

            return array_slice($keys, -3);
        };
        $previous = $capture();
        $this->assertSame($previous, $capture());
        foreach ([
            fn () => $department->update(['name' => 'Renamed Sales Region']),
            fn () => $section->update(['name' => 'Renamed section']),
            fn () => $position->update(['department_id' => null]),
            fn () => $assignment->update(['is_active' => false]),
        ] as $change) {
            $change();
            $current = $capture();
            $this->assertNotSame($previous[0], $current[0]);
            $this->assertNotSame($previous[1], $current[1]);
            $this->assertNotSame($previous[2], $current[2]);
            $previous = $current;
        }
    }

    public function test_department_rename_refreshes_sensitive_payload_without_flushing_unrelated_cache(): void
    {
        config(['cache.default' => 'array']);
        Cache::put('unrelated_department_test', 'preserved', 60);
        $department = MstDepartment::create(['name' => 'Sales Region 1', 'is_active' => true]);
        $scope = ['access_class' => 'full_hr', 'user_id' => 91, 'department_ids' => [$department->id]];
        $request = Request::create('/', 'GET', ['year_from' => 2026, 'year_to' => 2026]);
        $service = app(TcpdDashboardService::class);
        $this->assertSame('Sales Region 1', $service->getSensitivePayload($request, $scope)['scope']['departments'][0]['name']);
        $department->update(['name' => 'Renamed Region 1']);
        $this->assertSame('Renamed Region 1', $service->getSensitivePayload($request, $scope)['scope']['departments'][0]['name']);
        $this->assertSame('preserved', Cache::get('unrelated_department_test'));
    }

    public function test_full_hr_can_read_inactive_assessment_history_but_cannot_write_it(): void
    {
        foreach (range(1, 3) as $category) {
            \App\Models\PoinKategori::create(['judul_keterangan' => 'Rubric '.$category]);
        }
        $position = $this->position('dept_head');
        $position->update(['is_active' => false]);
        $assessment = TrsPenilaianTc::create(['id_user' => $this->employee()->id, 'id_job_position' => $position->id, 'status' => 4, 'tahun_penilaian' => 2025, 'is_locked' => true, 'nilai_tc' => 3]);
        $before = $assessment->fresh()->getRawOriginal();
        $this->actingAs($this->manager());
        $this->getJson(route('job.positions.details2', $position->id))->assertOk();
        $this->getJson(route('getDataTrs', ['id_job_position' => $position->id, 'year' => 2025]))->assertOk()->assertJsonCount(1);
        $this->get(route('penilaian.view', $position->id))->assertOk();
        $this->get(route('penilaian.yearlyHistory', [$position->id, 2025]))->assertOk();
        $this->postJson(route('savePenilaian'), ['posisi' => $position->id, 'id_user' => [$assessment->id_user]])->assertForbidden();
        $this->get(route('penilaian.edit', $position->id))->assertForbidden();
        $this->assertSame($before, $assessment->fresh()->getRawOriginal());

        $head = $this->employee('Fixture department head');
        $this->assign($head, $this->position('dept_head'));
        $this->actingAs($head);
        $this->getJson(route('job.positions.details2', $position->id))->assertForbidden();
        $this->getJson(route('getDataTrs', ['id_job_position' => $position->id, 'year' => 2025]))->assertForbidden();
        $this->get(route('penilaian.view', $position->id))->assertForbidden();
        $this->get(route('penilaian.yearlyHistory', [$position->id, 2025]))->assertForbidden();
    }
}

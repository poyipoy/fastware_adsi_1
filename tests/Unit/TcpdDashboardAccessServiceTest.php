<?php

namespace Tests\Unit;

use App\Models\MstJobPosition;
use App\Models\User;
use App\Models\UserJobPosition;
use App\Services\HR\HRRoleAccessService;
use App\Services\HR\TcpdDashboardAccessService;
use App\Services\HR\TcpdSensitiveAccessService;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TcpdDashboardAccessServiceTest extends TestCase
{
    public function test_full_hr_receives_company_scope_and_can_clear_cache(): void
    {
        $service = new FakeTcpdDashboardAccessService(
            new HRRoleAccessService(),
            collect(),
            collect([
                $this->job(101, 11, 21),
                $this->job(102, 12, 22),
            ]),
        );
        $user = $this->user(1);

        $scope = $service->scope($user);

        $this->assertTrue($scope['can_view']);
        $this->assertSame('full_hr', $scope['access_class']);
        $this->assertSame([11, 12], $scope['section_ids']);
        $this->assertSame([21, 22], $scope['department_ids']);
        $this->assertSame([101, 102], $scope['job_position_ids']);
        $this->assertTrue($service->canClearCache($user));
    }

    public function test_department_head_has_priority_and_receives_union_of_active_departments(): void
    {
        $assignments = collect([
            $this->assignment('sec_head', 11, 21),
            $this->assignment('div_head', null, null),
            $this->assignment('dept_head', null, 22),
            $this->assignment('dept_head', null, 23),
        ]);
        $service = new FakeTcpdDashboardAccessService(
            new HRRoleAccessService(),
            $assignments,
            collect([
                $this->job(101, 11, 21),
                $this->job(102, 12, 22),
                $this->job(103, 13, 23),
            ]),
        );

        $scope = $service->scope($this->user(50));

        $this->assertTrue($scope['can_view']);
        $this->assertSame('department_head', $scope['access_class']);
        $this->assertSame([22, 23], $scope['department_ids']);
        $this->assertSame([12, 13], $scope['section_ids']);
        $this->assertSame([102, 103], $scope['job_position_ids']);
        $this->assertFalse($service->canClearCache($this->user(50)));
    }

    public function test_division_head_with_department_scope_has_department_head_access(): void
    {
        $service = new FakeTcpdDashboardAccessService(
            new HRRoleAccessService(),
            collect([$this->assignment('div_head', null, 24)]),
            collect([
                $this->job(101, 11, 21),
                $this->job(104, 14, 24),
            ]),
            [24],
        );
        $user = $this->user(56);

        $scope = $service->scope($user);

        $this->assertTrue($scope['can_view']);
        $this->assertSame('division_head', $scope['access_class']);
        $this->assertSame([24], $scope['department_ids']);
        $this->assertSame([104], $scope['job_position_ids']);

        $sensitive = new TcpdSensitiveAccessService(new HRRoleAccessService(), $service);
        $this->assertTrue($sensitive->scope($user)['can_view']);
        $this->assertSame('division_head', $sensitive->scope($user)['access_class']);
    }

    public function test_division_head_uses_approval_hierarchy_departments_and_unions_department_head_mapping(): void
    {
        $service = new FakeTcpdDashboardAccessService(
            new HRRoleAccessService(),
            collect([
                $this->assignment('div_head', null, null),
                $this->assignment('dept_head', null, 23),
            ]),
            collect([
                $this->job(101, 11, 21),
                $this->job(102, 12, 22),
                $this->job(103, 13, 23),
                $this->job(104, 14, 24),
            ]),
            [21, 22],
        );

        $scope = $service->scope($this->user(57));

        $this->assertTrue($scope['can_view']);
        $this->assertSame('division_head', $scope['access_class']);
        $this->assertSame([21, 22, 23], $scope['department_ids']);
        $this->assertSame([101, 102, 103], $scope['job_position_ids']);
    }

    public function test_section_head_only_receives_jobs_in_active_sections(): void
    {
        $service = new FakeTcpdDashboardAccessService(
            new HRRoleAccessService(),
            collect([
                $this->assignment('sec_head', 12, 22),
                $this->assignment('sec_head', 11, 21),
            ]),
            collect([
                $this->job(101, 11, 21),
                $this->job(102, 12, 22),
                $this->job(103, 13, 23),
            ]),
        );

        $scope = $service->scope($this->user(51));

        $this->assertTrue($scope['can_view']);
        $this->assertSame('section_head', $scope['access_class']);
        $this->assertSame([11, 12], $scope['section_ids']);
        $this->assertSame([21, 22], $scope['department_ids']);
        $this->assertSame([101, 102], $scope['job_position_ids']);
    }

    public function test_staff_unmapped_division_head_and_head_without_organization_are_denied(): void
    {
        foreach ([
            collect(),
            collect([$this->assignment('staff', 11, 21)]),
            collect([$this->assignment('div_head', null, null)]),
            collect([$this->assignment('sec_head', null, 21)]),
            collect([$this->assignment('dept_head', null, null)]),
        ] as $assignments) {
            $service = new FakeTcpdDashboardAccessService(
                new HRRoleAccessService(),
                $assignments,
                collect([$this->job(101, 11, 21)]),
            );

            $scope = $service->scope($this->user(52));

            $this->assertFalse($scope['can_view']);
            $this->assertSame('denied', $scope['access_class']);
            $this->assertSame([], $scope['job_position_ids']);
        }

        $administratorOutsideFullHr = $this->user(99);
        $administratorOutsideFullHr->role_id = 1;
        $service = new FakeTcpdDashboardAccessService(
            new HRRoleAccessService(),
            collect(),
            collect([$this->job(101, 11, 21)]),
        );
        $this->assertFalse($service->canView($administratorOutsideFullHr));
    }

    public function test_inactive_future_expired_and_inactive_master_assignments_are_denied(): void
    {
        $invalidAssignments = [
            $this->assignment('sec_head', 11, 21, assignmentActive: false),
            $this->assignment('sec_head', 11, 21, effectiveFrom: today()->addDay()->toDateString()),
            $this->assignment('sec_head', 11, 21, effectiveUntil: today()->subDay()->toDateString()),
            $this->assignment('sec_head', 11, 21, masterActive: false),
        ];

        foreach ($invalidAssignments as $assignment) {
            $service = new FakeTcpdDashboardAccessService(
                new HRRoleAccessService(),
                collect([$assignment]),
                collect([$this->job(101, 11, 21)]),
            );

            $this->assertFalse($service->canView($this->user(55)));
        }
    }

    public function test_sensitive_data_remains_limited_to_division_head_department_head_and_full_hr(): void
    {
        $sectionAccess = new FakeTcpdDashboardAccessService(
            new HRRoleAccessService(),
            collect([$this->assignment('sec_head', 11, 21)]),
            collect([$this->job(101, 11, 21)]),
        );
        $sectionSensitive = new TcpdSensitiveAccessService(
            new HRRoleAccessService(),
            $sectionAccess,
        );

        $this->assertFalse($sectionSensitive->scope($this->user(53))['can_view']);

        $departmentAccess = new FakeTcpdDashboardAccessService(
            new HRRoleAccessService(),
            collect([$this->assignment('dept_head', null, 21)]),
            collect([$this->job(101, 11, 21)]),
        );
        $departmentSensitive = new TcpdSensitiveAccessService(
            new HRRoleAccessService(),
            $departmentAccess,
        );

        $scope = $departmentSensitive->scope($this->user(54), 21);
        $this->assertTrue($scope['can_view']);
        $this->assertSame('department_head', $scope['access_class']);
        $this->assertSame([21], $scope['department_ids']);

        try {
            $departmentSensitive->scope($this->user(54), 22, true);
            $this->fail('Department di luar scope seharusnya ditolak.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    private function user(int $id): User
    {
        $user = new User(['name' => "User {$id}"]);
        $user->id = $id;

        return $user;
    }

    private function assignment(
        string $level,
        ?int $sectionId,
        ?int $departmentId,
        bool $assignmentActive = true,
        bool $masterActive = true,
        ?string $effectiveFrom = null,
        ?string $effectiveUntil = null,
    ): UserJobPosition {
        $assignment = new UserJobPosition([
            'is_active' => $assignmentActive,
            'effective_from' => $effectiveFrom,
            'effective_until' => $effectiveUntil,
        ]);
        $assignment->setRelation('jobPosition', new MstJobPosition([
            'job_level' => $level,
            'section_id' => $sectionId,
            'department_id' => $departmentId,
            'is_active' => $masterActive,
        ]));

        return $assignment;
    }

    private function job(int $id, ?int $sectionId, ?int $departmentId): MstJobPosition
    {
        $job = new MstJobPosition([
            'section_id' => $sectionId,
            'department_id' => $departmentId,
        ]);
        $job->id = $id;

        return $job;
    }
}

class FakeTcpdDashboardAccessService extends TcpdDashboardAccessService
{
    public function __construct(
        HRRoleAccessService $roleAccess,
        private readonly Collection $assignments,
        private readonly Collection $jobs,
        private readonly array $divisionDepartments = [],
    ) {
        parent::__construct($roleAccess);
    }

    protected function activeHeadAssignments(User $user): Collection
    {
        return $this->assignments;
    }

    protected function divisionDepartmentIds(Collection $assignments): array
    {
        return $this->divisionDepartments;
    }

    protected function dashboardJobRows(
        ?array $departmentIds = null,
        ?array $sectionIds = null,
    ): Collection {
        return $this->jobs
            ->when(
                $departmentIds !== null,
                fn (Collection $jobs) => $jobs->filter(
                    fn ($job) => in_array((int) $job->department_id, $departmentIds, true),
                ),
            )
            ->when(
                $sectionIds !== null,
                fn (Collection $jobs) => $jobs->filter(
                    fn ($job) => in_array((int) $job->section_id, $sectionIds, true),
                ),
            )
            ->values();
    }
}

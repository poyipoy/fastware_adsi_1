<?php

namespace Tests\Unit;

use App\Models\TcJobPosition;
use App\Models\User;
use App\Services\HR\JobPositionAccessService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class JobPositionAccessServiceTest extends TestCase
{
    private FakeJobPositionAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $positions = collect([
            $this->position(101, 'Cutting Operator', ' Mugi Pramono ', 'ARY RODJO PRASETYO', 1, 'Production Cutting'),
            $this->position(102, 'MC Operator', 'MUGI PRAMONO', 'ARY RODJO PRASETYO', 1, 'Production Machining'),
            $this->position(103, 'Unrelated Job', 'OTHER HEAD', 'OTHER DEPT HEAD', 1, 'Other Section'),
            $this->position(104, 'Inactive Job', 'MUGI PRAMONO', 'MUGI PRAMONO', 0, 'Production Cutting'),
            $this->position(105, 'Override Job', 'OTHER HEAD', 'OTHER DEPT HEAD', 1, 'Override Section'),
            $this->position(106, 'Role Override Job', 'OTHER HEAD', 'OTHER DEPT HEAD', 1, 'Role Override Section'),
            $this->position(107, 'Department Controlled Job', 'OTHER HEAD', 'MUGI PRAMONO', 1, 'Department Section'),
        ]);

        $this->service = new FakeJobPositionAccessService(
            $positions,
            [
                10 => ['Override Job', 'Stale Access Job'],
            ],
            [
                22 => ['Role Override Job'],
            ]
        );
    }

    public function test_section_head_receives_section_head_jobs_and_access_overrides(): void
    {
        $user = new User(['name' => 'MUGI PRAMONO', 'role_id' => 22]);
        $user->id = 10;

        $jobNames = $this->service->getAccessibleJobPositionNames($user)->all();

        $this->assertContains('Cutting Operator', $jobNames);
        $this->assertContains('MC Operator', $jobNames);
        $this->assertContains('Department Controlled Job', $jobNames);
        $this->assertContains('Override Job', $jobNames);
        $this->assertContains('Role Override Job', $jobNames);
        $this->assertNotContains('Inactive Job', $jobNames);
        $this->assertNotContains('Unrelated Job', $jobNames);
        $this->assertNotContains('Stale Access Job', $jobNames);
    }

    public function test_accessible_job_position_options_keep_active_ids_for_dropdown(): void
    {
        $user = new User(['name' => 'MUGI PRAMONO', 'role_id' => 22]);
        $user->id = 10;

        $optionIds = $this->service->getAccessibleJobPositionOptions($user)
            ->pluck('id')
            ->all();

        $this->assertContains(101, $optionIds);
        $this->assertContains(102, $optionIds);
        $this->assertContains(105, $optionIds);
        $this->assertContains(106, $optionIds);
        $this->assertContains(107, $optionIds);
        $this->assertNotContains(103, $optionIds);
        $this->assertNotContains(104, $optionIds);
    }

    public function test_full_access_user_receives_all_active_job_positions(): void
    {
        $user = new User(['name' => 'SITI MARIA ULFA', 'role_id' => 15]);
        $user->id = 11;

        $jobNames = $this->service->getAccessibleJobPositionNames($user)->all();

        $this->assertContains('Cutting Operator', $jobNames);
        $this->assertContains('Unrelated Job', $jobNames);
        $this->assertContains('Override Job', $jobNames);
        $this->assertNotContains('Inactive Job', $jobNames);
    }

    public function test_admin_role_receives_all_active_job_positions(): void
    {
        $user = new User(['name' => 'ANY ADMIN', 'role_id' => 1]);
        $user->id = 12;

        $jobNames = $this->service->getAccessibleJobPositionNames($user)->all();

        $this->assertContains('Cutting Operator', $jobNames);
        $this->assertContains('Unrelated Job', $jobNames);
        $this->assertNotContains('Inactive Job', $jobNames);
    }

    public function test_invalid_job_positions_are_reported_after_stale_access_is_pruned(): void
    {
        $user = new User(['name' => 'MUGI PRAMONO', 'role_id' => 22]);
        $user->id = 10;

        $invalid = $this->service->getInvalidJobPositions($user, [
            'Cutting Operator',
            'Stale Access Job',
            '',
        ]);

        $this->assertNotContains('Cutting Operator', $invalid);
        $this->assertContains('Stale Access Job', $invalid);
        $this->assertContains('(kosong)', $invalid);
    }

    private function position(int $id, string $jobPosition, string $sectionHead, string $departmentHead, int $status, string $userSection): TcJobPosition
    {
        $position = new TcJobPosition([
            'job_position' => $jobPosition,
            'section_head_name' => $sectionHead,
            'department_head_name' => $departmentHead,
            'status' => $status,
        ]);
        $position->id = $id;

        $position->setRelation('user', new User(['section' => $userSection]));

        return $position;
    }
}

class FakeJobPositionAccessService extends JobPositionAccessService
{
    public function __construct(
        private Collection $positions,
        private array $userOverrides,
        private array $roleOverrides
    ) {
    }

    protected function getActiveJobPositions(): Collection
    {
        return $this->positions
            ->filter(fn(TcJobPosition $position) => (int) $position->status === 1)
            ->values();
    }

    protected function getSectionHeadJobPositionNames(User $user): Collection
    {
        return $this->getActiveJobPositions()
            ->filter(fn(TcJobPosition $position) => $this->normalize($position->section_head_name) === $this->normalize($user->name))
            ->pluck('job_position');
    }

    protected function getDepartmentHeadJobPositionNames(User $user): Collection
    {
        return $this->getActiveJobPositions()
            ->filter(fn(TcJobPosition $position) => $this->normalize($position->department_head_name) === $this->normalize($user->name))
            ->pluck('job_position');
    }

    protected function getOverrideJobPositionNames(User $user): Collection
    {
        return collect($this->userOverrides[$user->id] ?? [])
            ->merge($this->roleOverrides[$user->role_id] ?? []);
    }
}

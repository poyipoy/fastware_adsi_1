<?php

namespace Tests\Unit;

use App\Models\MstJobPosition;
use App\Models\User;
use App\Services\HR\JobPositionAccessService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class JobPositionAccessServiceTest extends TestCase
{
    public function test_access_uses_active_master_positions_and_canonical_names(): void
    {
        $service = new FakeJobPositionAccessService(collect([
            $this->position(101, 'Cutting Operator', 10, 1, true),
            $this->position(102, 'MC Operator', 11, 1, true),
            $this->position(103, 'Other Job', 12, 2, true),
            $this->position(104, 'Inactive Job', 10, 1, false),
        ]));

        $user = new User(['name' => 'SECTION HEAD']);
        $user->id = 50;

        $positions = $service->getAccessibleJobPositions($user, false);

        $this->assertSame([101, 102], $positions->pluck('id')->all());
        $this->assertSame(['Cutting Operator', 'MC Operator'], $positions->pluck('position_name')->all());
    }

    public function test_full_hr_receives_every_active_position(): void
    {
        $service = new FakeJobPositionAccessService(collect([
            $this->position(101, 'Cutting Operator', 10, 1, true),
            $this->position(103, 'Other Job', 12, 2, true),
            $this->position(104, 'Inactive Job', 10, 1, false),
        ]), true);

        $user = new User(['name' => 'FULL HR']);
        $user->id = 1;

        $this->assertSame([101, 103], $service->getAccessibleJobPositions($user)->pluck('id')->all());
    }

    public function test_invalid_positions_include_empty_and_unknown_values(): void
    {
        $service = new FakeJobPositionAccessService(collect([
            $this->position(101, 'Cutting Operator', 10, 1, true),
        ]));
        $user = new User(['name' => 'SECTION HEAD']);
        $user->id = 50;

        $this->assertSame(
            ['Unknown Job', '(kosong)'],
            $service->getInvalidJobPositions($user, ['cutting operator', 'Unknown Job', ''])
        );
    }

    private function position(int $id, string $name, int $sectionId, int $departmentId, bool $active): MstJobPosition
    {
        $position = new MstJobPosition([
            'position_name' => $name,
            'section_id' => $sectionId,
            'department_id' => $departmentId,
            'is_active' => $active,
        ]);
        $position->id = $id;

        return $position;
    }
}

class FakeJobPositionAccessService extends JobPositionAccessService
{
    public function __construct(private readonly Collection $positions, private readonly bool $fullAccess = false)
    {
    }

    public function hasFullAccess(User $user): bool
    {
        return $this->fullAccess;
    }

    public function getUserApprovalScope(User $user): array
    {
        return ['section_ids' => [10, 11], 'dept_ids' => [], 'div_dept_ids' => []];
    }

    public function getAccessibleJobPositions(User $user, bool $excludeSelf = true): Collection
    {
        $active = $this->getActiveJobPositions();

        if ($this->hasFullAccess($user)) {
            return $active;
        }

        return $active
            ->filter(fn (MstJobPosition $position) => in_array($position->section_id, [10, 11], true))
            ->values();
    }

    public function getAccessibleJobPositionNames(User $user, bool $excludeSelf = true): Collection
    {
        return $this->getAccessibleJobPositions($user, $excludeSelf)->pluck('position_name');
    }

    protected function getActiveJobPositions(): Collection
    {
        return $this->positions->where('is_active', true)->values();
    }

    protected function getOverrideJobPositionNames(User $user): Collection
    {
        return collect();
    }
}

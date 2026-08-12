<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use App\Services\HR\HRRoleAccessService;
use PHPUnit\Framework\TestCase;

class HRRoleAccessServiceTest extends TestCase
{
    public function test_only_configured_user_ids_have_full_hr_access(): void
    {
        $service = new HRRoleAccessService();

        $this->assertTrue($service->hasFullAccess($this->user(1, 'ADMINSTRATOR')));
        $this->assertTrue($service->hasFullAccess($this->user(91, 'SITI MARIA ULFA')));
        $this->assertFalse($service->hasFullAccess($this->user(99, 'OTHER ADMIN')));
        $this->assertFalse($service->hasFullAccess(null));
    }

    public function test_competency_matrix_is_derived_from_active_organization_level(): void
    {
        $service = new FakeHRRoleAccessService();

        $sectionHead = $this->user(20, 'SECTION HEAD');
        $departmentHead = $this->user(21, 'DEPARTMENT HEAD');
        $divisionHead = $this->user(22, 'DIVISION HEAD');
        $staff = $this->user(23, 'STAFF');

        $this->assertTrue($service->canAccessCompetencyLevel($sectionHead, 'kasie'));
        $this->assertFalse($service->canAccessCompetencyLevel($sectionHead, 'kadept'));
        $this->assertTrue($service->canAccessCompetencyLevel($departmentHead, 'kasie'));
        $this->assertTrue($service->canAccessCompetencyLevel($departmentHead, 'kadept'));
        $this->assertTrue($service->canAccessCompetencyLevel($divisionHead, 'divhead'));
        $this->assertFalse($service->canAccessCompetencyLevel($staff, 'kasie'));
    }

    public function test_role_prefix_check_is_case_and_whitespace_normalized(): void
    {
        $service = new HRRoleAccessService();
        $user = $this->user(20, 'ANY', '  sc Logistics Sec Head  ');

        $this->assertTrue($service->roleStartsWith($user, 'SC'));
        $this->assertFalse($service->roleStartsWith($user, 'DH'));
    }

    private function user(int $id, string $name, string $role = 'UR Staff'): User
    {
        $user = new User(['name' => $name]);
        $user->id = $id;
        $user->setRelation('roles', new Role(['role' => $role]));

        return $user;
    }
}

class FakeHRRoleAccessService extends HRRoleAccessService
{
    public function isKaSie(?User $user): bool
    {
        return $user?->id === 20;
    }

    public function isKaDept(?User $user): bool
    {
        return $user?->id === 21;
    }

    public function isDivHead(?User $user): bool
    {
        return $user?->id === 22;
    }
}

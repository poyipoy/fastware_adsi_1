<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use App\Services\HR\HRRoleAccessService;
use PHPUnit\Framework\TestCase;

class HRRoleAccessServiceTest extends TestCase
{
    private HRRoleAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new HRRoleAccessService();
    }

    public function test_section_head_can_only_access_kasie_competency_level(): void
    {
        $user = $this->userWithRole('MUGI PRAMONO', 'SC Logistics & QC Sec Head', 17);

        $this->assertTrue($this->service->canAccessCompetencyLevel($user, 'kasie'));
        $this->assertFalse($this->service->canAccessCompetencyLevel($user, 'kadept'));
        $this->assertFalse($this->service->canAccessCompetencyLevel($user, 'hr'));
    }

    public function test_department_head_can_only_access_kadept_competency_level(): void
    {
        $user = $this->userWithRole('ARY RODJO PRASETYO', 'DH Production Dept Head', 4);

        $this->assertFalse($this->service->canAccessCompetencyLevel($user, 'kasie'));
        $this->assertTrue($this->service->canAccessCompetencyLevel($user, 'kadept'));
        $this->assertFalse($this->service->canAccessCompetencyLevel($user, 'hr'));
    }

    public function test_foreman_and_user_roles_do_not_access_kasie_or_kadept_levels(): void
    {
        $foreman = $this->userWithRole('FOREMAN USER', 'FM QC Foreman', 13);
        $staff = $this->userWithRole('STAFF USER', 'UR Admin', 54);

        $this->assertFalse($this->service->canAccessCompetencyLevel($foreman, 'kasie'));
        $this->assertFalse($this->service->canAccessCompetencyLevel($foreman, 'kadept'));
        $this->assertFalse($this->service->canAccessCompetencyLevel($staff, 'kasie'));
        $this->assertFalse($this->service->canAccessCompetencyLevel($staff, 'kadept'));
    }

    public function test_full_access_user_can_access_all_competency_levels(): void
    {
        $user = $this->userWithRole('JESSICA PAUNE', 'UR HRGA', 99);

        $this->assertTrue($this->service->canAccessCompetencyLevel($user, 'kasie'));
        $this->assertTrue($this->service->canAccessCompetencyLevel($user, 'kadept'));
        $this->assertTrue($this->service->canAccessCompetencyLevel($user, 'hr'));
    }

    private function userWithRole(string $name, string $roleName, int $roleId): User
    {
        $user = new User(['name' => $name, 'role_id' => $roleId]);
        $user->setRelation('roles', new Role(['role' => $roleName]));

        return $user;
    }
}

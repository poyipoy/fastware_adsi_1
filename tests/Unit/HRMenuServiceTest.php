<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\HR\HRRoleAccessService;
use App\Services\HRMenuService;
use App\Services\KnowledgeManagement\KmAccessService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class HRMenuServiceTest extends TestCase
{
    private HRMenuService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $roleAccess = new FakeMenuRoleAccessService();
        $this->app->instance(HRRoleAccessService::class, $roleAccess);
        $this->service = new HRMenuService($roleAccess, new FakeMenuKmAccessService());
    }

    public function test_menu_structure_uses_active_user_object_without_legacy_name_lookup(): void
    {
        $menu = $this->service->getMenuStructure($this->user(1, 'ADMINSTRATOR'));

        $this->assertInstanceOf(Collection::class, $menu);
        $this->assertTrue($menu['show_main_menu']);
        $this->assertArrayHasKey('items', $menu['knowledge_management']);
        $this->assertArrayHasKey('items', $menu['base_competency']);
        $this->assertArrayHasKey('items', $menu['training_development']);
    }

    public function test_full_hr_sees_approval_and_training_follow_up_menu(): void
    {
        $menu = $this->service->getMenuStructure($this->user(1, 'ADMINSTRATOR'));
        $visible = collect($menu['training_development']['items'])->where('visible', true)->pluck('label');

        $this->assertContains('Persetujuan Development', $visible);
        $this->assertContains('History Development', $visible);
    }

    public function test_staff_without_scope_has_no_hr_menu_access(): void
    {
        $user = $this->user(40, 'RANDOM USER');
        $menu = $this->service->getMenuStructure($user);

        $this->assertFalse($menu['show_main_menu']);
        $this->assertFalse($this->service->hasAnyAccess($user));
    }

    public function test_visible_items_filters_hidden_entries(): void
    {
        $visible = $this->service->getVisibleItems([
            ['label' => 'Visible', 'visible' => true],
            ['label' => 'Hidden', 'visible' => false],
        ]);

        $this->assertSame(['Visible'], $visible->pluck('label')->values()->all());
    }

    private function user(int $id, string $name): User
    {
        $user = new User(['name' => $name]);
        $user->id = $id;

        return $user;
    }
}

class FakeMenuRoleAccessService extends HRRoleAccessService
{
    public function isKaSie(?User $user): bool
    {
        return false;
    }

    public function isKaDept(?User $user): bool
    {
        return false;
    }

    public function isDivHead(?User $user): bool
    {
        return false;
    }
}

class FakeMenuKmAccessService extends KmAccessService
{
    public function __construct()
    {
    }

    public function canCreate(User $user): bool
    {
        return (int) $user->id === 1;
    }

    public function canApprove(User $user): bool
    {
        return (int) $user->id === 1;
    }
}

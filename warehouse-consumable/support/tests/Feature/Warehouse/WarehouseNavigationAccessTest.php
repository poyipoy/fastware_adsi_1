<?php

namespace Tests\Feature\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WarehouseNavigationAccessTest extends WarehouseTestCase
{
    private const ABILITIES = [
        'warehouse.dashboard.view',
        'warehouse.stock-in.create',
        'warehouse.stock-out.create',
        'warehouse.master.manage',
        'warehouse.transaction.view',
        'warehouse.transaction.reverse',
        'warehouse.report.export',
    ];

    public function test_guest_is_redirected_from_warehouse_routes(): void
    {
        $this->get(route('warehouse.dashboard'))->assertRedirect();
        $this->get(route('warehouse.transactions.create'))->assertRedirect();
    }

    public function test_each_approved_department_receives_every_warehouse_ability(): void
    {
        foreach ((array) config('warehouse.authorization.authorized_department_names') as $department) {
            $user = $this->createUser([], false);
            $this->createDepartmentPosition(
                $user,
                $department,
                $department === 'PDCA, Inventory, Procurement & IT' ? 'IT Staff '.uniqid() : null,
            );

            foreach (self::ABILITIES as $ability) {
                self::assertTrue(
                    app(WarehouseAccessService::class)->can($user, $ability),
                    sprintf('%s should grant %s.', $department, $ability),
                );
            }

            $this->actingAs($user)->get(route('warehouse.dashboard'))->assertOk();
            $this->actingAs($user)->get(route('warehouse.transactions.create'))
                ->assertOk()
                ->assertSee('data-warehouse-type="IN"', false)
                ->assertSee('data-warehouse-type="OUT"', false);
        }
    }

    public function test_administrator_receives_every_warehouse_ability_without_assignment(): void
    {
        $administrator = $this->createUser(['role_id' => 1], false);

        foreach (self::ABILITIES as $ability) {
            self::assertTrue(app(WarehouseAccessService::class)->can($administrator, $ability));
        }

        self::assertTrue(app(WarehouseAccessService::class)->canAdjust($administrator));
        $this->actingAs($administrator)->get(route('warehouse.consumables.index'))->assertOk();
    }

    public function test_user_outside_approved_departments_is_hidden_and_denied_direct_access(): void
    {
        $outsider = $this->createUser([], false);
        $this->createDepartmentPosition($outsider, 'Human Resource', 'HR Test Staff '.uniqid());

        foreach (self::ABILITIES as $ability) {
            self::assertFalse(app(WarehouseAccessService::class)->can($outsider, $ability));
            self::assertTrue(Gate::forUser($outsider)->denies($ability));
        }

        $this->actingAs($outsider)->get(route('warehouse.dashboard'))->assertForbidden();
        $this->actingAs($outsider)->get(route('warehouse.transactions.create'))->assertForbidden();
        $this->actingAs($outsider)->get(route('warehouse.consumables.index'))->assertForbidden();
    }

    public function test_inactive_user_and_inactive_or_expired_organization_data_are_denied(): void
    {
        $withoutAssignment = $this->createUser([], false);
        self::assertFalse(app(WarehouseAccessService::class)->hasModuleAccess($withoutAssignment));

        $inactiveUser = $this->createUser(['is_active' => 1]);
        self::assertFalse(app(WarehouseAccessService::class)->hasModuleAccess($inactiveUser));

        $expired = $this->createUser([], false);
        $this->createDepartmentPosition($expired, 'Production', null, 'staff', [
            'effective_from' => today()->subDays(10)->toDateString(),
            'effective_until' => today()->subDay()->toDateString(),
        ]);
        self::assertFalse(app(WarehouseAccessService::class)->hasModuleAccess($expired));

        $inactivePosition = $this->createUser([], false);
        $this->createDepartmentPosition($inactivePosition, 'Production', 'Inactive Position '.uniqid());
        DB::table('mst_job_positions')
            ->whereIn('id', DB::table('user_job_positions')->where('user_id', $inactivePosition->id)->pluck('mst_job_position_id'))
            ->update(['is_active' => false]);
        self::assertFalse(app(WarehouseAccessService::class)->hasModuleAccess($inactivePosition));

        $inactiveDepartment = $this->createUser([], false);
        $this->createDepartmentPosition($inactiveDepartment, 'Logistic & Warehouse', 'Inactive Department Position '.uniqid());
        DB::table('mst_departments')->where('name', 'Logistic & Warehouse')->update(['is_active' => false]);
        self::assertFalse(app(WarehouseAccessService::class)->hasModuleAccess($inactiveDepartment));
    }
}

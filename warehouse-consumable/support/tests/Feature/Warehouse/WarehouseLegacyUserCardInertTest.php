<?php

namespace Tests\Feature\Warehouse;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class WarehouseLegacyUserCardInertTest extends WarehouseTestCase
{
    public function test_mapping_routes_and_master_link_are_removed(): void
    {
        $user = $this->createUser();

        foreach (['index', 'store', 'permissions', 'status'] as $action) {
            self::assertFalse(Route::has('warehouse.user-cards.'.$action));
        }

        $this->actingAs($user)->get('/warehouse/user-cards')->assertNotFound();
        $this->actingAs($user)->get(route('warehouse.consumables.index'))
            ->assertOk()
            ->assertDontSee('Pemetaan ID Karyawan');
    }

    public function test_legacy_mapping_rows_are_inert_and_npk_is_the_only_scan_identity(): void
    {
        $employee = $this->createUser();
        $operator = $this->createUser();

        DB::table('mst_wh_user_cards')->insert([
            'user_id' => $employee->id,
            'card_code' => 'LEGACY-CARD-'.$employee->npk,
            'is_active' => true,
            'can_verify_stock_in' => true,
            'can_verify_stock_out' => true,
            'registered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($operator)->postJson(route('warehouse.scans.user'), [
            'code' => 'LEGACY-CARD-'.$employee->npk,
            'type' => 'OUT',
        ])->assertUnprocessable();

        $this->actingAs($operator)->postJson(route('warehouse.scans.user'), [
            'code' => (string) $employee->npk,
            'type' => 'OUT',
        ])->assertOk()->assertJsonPath('data.id', $employee->id);
    }
}

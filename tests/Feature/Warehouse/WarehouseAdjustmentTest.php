<?php

namespace Tests\Feature\Warehouse;

use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseAdjustmentTest extends WarehouseTestCase
{
    public function test_adjustment_form_generates_an_idempotency_key(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);

        $response = $this->actingAs($pic)->get(route('warehouse.adjustments.create'));

        $response->assertOk()->assertSee('name="idempotency_key"', false);
        self::assertMatchesRegularExpression(
            '/name="idempotency_key" value="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"/i',
            $response->getContent(),
        );
    }

    public function test_pic_adjustment_creates_traceable_movement(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['current_stock' => '3.000']);

        $response = $this->actingAs($pic)->post(route('warehouse.adjustments.store'), [
            'consumable_id' => $item->id, 'direction' => 'OUT', 'item_condition' => 'NEW', 'storage_location' => 'DS8', 'quantity' => '1', 'reason_category' => 'damaged', 'reason' => 'Damaged during handling', 'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect(route('warehouse.dashboard'));
        $transaction = WarehouseStockTransaction::query()->latest('id')->firstOrFail();
        $response->assertSessionHas('status', 'Penyesuaian stok berhasil dicatat. Nomor transaksi: '.$transaction->transaction_number.'.');
        $this->assertDatabaseHas('trs_wh_stock_transactions', ['transaction_type' => 'ADJUSTMENT', 'consumable_id' => $item->id, 'stock_before' => '3.000', 'stock_after' => '2.000']);
    }

    public function test_adjustment_reason_is_required(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $item = WarehouseConsumable::factory()->create();

        $this->actingAs($pic)->post(route('warehouse.adjustments.store'), ['consumable_id' => $item->id, 'direction' => 'IN', 'item_condition' => 'NEW', 'storage_location' => 'DS8', 'quantity' => '1', 'reason_category' => 'opening_balance', 'verified_code' => 'missing', 'idempotency_key' => (string) Str::uuid()])->assertSessionHasErrors('reason');
    }

    public function test_adjustment_rejects_verifier_without_warehouse_access(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $verified = $this->createUser([], false);
        $this->createDepartmentPosition($verified, 'Human Resource', 'Adjustment Outsider '.uniqid());
        $item = WarehouseConsumable::factory()->create(['current_stock' => '3.000']);

        $this->actingAs($pic)->post(route('warehouse.adjustments.store'), [
            'consumable_id' => $item->id,
            'direction' => 'OUT',
            'item_condition' => 'NEW',
            'storage_location' => 'DS8',
            'quantity' => '1',
            'reason_category' => 'damaged',
            'reason' => 'Damaged during handling',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('adjustment');

        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '3.000']);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }

    public function test_adjustment_rejects_warehouse_user_who_is_not_a_restricted_verifier(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $verified = $this->createUser();
        DB::table('mst_wh_restricted_verifiers')->where('user_id', $verified->id)->delete();
        $item = WarehouseConsumable::factory()->create(['current_stock' => '3.000']);

        $this->actingAs($pic)->post(route('warehouse.adjustments.store'), [
            'consumable_id' => $item->id,
            'direction' => 'OUT',
            'item_condition' => 'NEW',
            'storage_location' => 'DS8',
            'quantity' => '1',
            'reason_category' => 'damaged',
            'reason' => 'Damaged during handling',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('adjustment');

        $this->assertDatabaseHas('mst_wh_consumables', [
            'id' => $item->id,
            'current_stock' => '3.000',
        ]);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }
}

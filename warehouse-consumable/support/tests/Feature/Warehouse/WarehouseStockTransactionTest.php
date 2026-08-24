<?php

namespace Tests\Feature\Warehouse;

use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockIn;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class WarehouseStockTransactionTest extends WarehouseTestCase
{
    public function test_pic_can_create_pending_stock_in_without_mutation(): void
    {
        $pic = $this->createUser();
        DB::table('mst_wh_restricted_verifiers')->where('user_id', $pic->id)->delete();
        $this->createPicPosition($pic);
        $item = WarehouseConsumable::factory()->create(['barcode' => '000-IN', 'current_stock' => '2.000']);

        $response = $this->actingAs($pic)->postJson(route('warehouse.transactions.store'), [
            'type' => 'IN', 'item_barcode' => '000-IN', 'quantity' => '3',
            'item_condition' => 'NEW', 'location' => 'DS8',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('pending_stock_in', true)
            ->assertJsonPath('data.status', 'WAITING_VALIDATION')
            ->assertJsonPath('data.quantity_expected', '3.000');
        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '2.000', 'stock_ds8' => '2.000']);
        $this->assertDatabaseHas('trs_wh_stock_ins', ['consumable_id' => $item->id, 'status' => 'WAITING_VALIDATION']);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
        self::assertSame(1, WarehouseStockIn::query()->count());
    }

    public function test_employee_can_stock_out_from_selected_location(): void
    {
        $employee = $this->createUser();
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'barcode' => '000-OUT',
            'current_stock' => '5.000',
            'stock_deltamas' => '5.000',
        ]);

        $response = $this->actingAs($employee)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT', 'item_barcode' => '000-OUT', 'quantity' => '2',
            'location' => 'Deltamas',
            'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertCreated()->assertJsonPath('data.from_location', 'Deltamas');
        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '3.000', 'stock_deltamas' => '3.000']);
        $this->assertDatabaseHas('trs_wh_stock_transactions', ['consumable_id' => $item->id, 'from_location' => 'Deltamas']);
    }

    public function test_stock_in_requires_selected_location_and_does_not_mutate_stock(): void
    {
        $pic = $this->createUser();
        DB::table('mst_wh_restricted_verifiers')->where('user_id', $pic->id)->delete();
        $this->createPicPosition($pic);
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'barcode' => '000-IN-MISSING-LOCATION',
            'current_stock' => '2.000',
            'stock_ds8' => '2.000',
        ]);

        $this->actingAs($pic)->postJson(route('warehouse.transactions.store'), [
            'type' => 'IN', 'item_barcode' => $item->barcode, 'quantity' => '3',
            'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable()->assertJsonValidationErrors('location');

        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '2.000', 'stock_ds8' => '2.000']);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }

    public function test_stock_in_rejects_location_outside_the_approved_options(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['barcode' => '000-IN-BAD-LOCATION', 'current_stock' => '2.000']);

        $this->actingAs($pic)->postJson(route('warehouse.transactions.store'), [
            'type' => 'IN', 'item_barcode' => $item->barcode, 'quantity' => '3',
            'location' => 'Rack C-99',
            'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable()->assertJsonValidationErrors('location');

        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '2.000', 'stock_ds8' => '2.000']);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }

    public function test_stock_out_requires_selected_location(): void
    {
        $employee = $this->createUser();
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'barcode' => '000-OUT-LOCATION',
            'current_stock' => '5.000',
            'stock_deltamas' => '5.000',
        ]);

        $this->actingAs($employee)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT', 'item_barcode' => $item->barcode, 'quantity' => '2',
            'location' => 'Rack C-99',
            'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable()->assertJsonValidationErrors('location');

        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '5.000', 'stock_deltamas' => '5.000']);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }

    public function test_stock_out_cannot_overdraw(): void
    {
        $employee = $this->createUser();
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['barcode' => '000-LOW', 'current_stock' => '1.000']);

        $response = $this->actingAs($employee)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT', 'item_barcode' => '000-LOW', 'quantity' => '2',
            'location' => 'DS8',
            'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '1.000']);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }

    public function test_fraction_is_rejected_for_integer_item(): void
    {
        $employee = $this->createUser();
        $verified = $this->createUser();
        WarehouseConsumable::factory()->create(['barcode' => '000-FRAC', 'current_stock' => '3.000', 'allow_fraction' => false]);

        $this->actingAs($employee)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT', 'item_barcode' => '000-FRAC', 'quantity' => '1.5',
            'location' => 'DS8',
            'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable();
    }

    public function test_stock_in_creation_does_not_require_a_verifier(): void
    {
        $pic = $this->createUser();
        DB::table('mst_wh_restricted_verifiers')->where('user_id', $pic->id)->delete();
        $this->createPicPosition($pic);
        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'DIRECT-IN',
            'barcode' => 'DIRECT-IN',
            'current_stock' => '2.000',
        ]);

        $this->actingAs($pic)->postJson(route('warehouse.transactions.store'), [
            'type' => 'IN',
            'item_barcode' => 'DIRECT-IN',
            'quantity' => '3',
            'item_condition' => 'NEW',
            'location' => 'DS8',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated()
            ->assertJsonPath('pending_stock_in', true)
            ->assertJsonPath('data.status', 'WAITING_VALIDATION');

        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '2.000']);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }

    public function test_restricted_validator_can_create_used_stock_in_and_stock_out(): void
    {
        $actor = $this->createUser(['name' => 'Warehouse Restricted Validator']);
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'barcode' => '000-USED-IN',
            'current_stock' => '1.000',
            'stock_ds8' => '1.000',
            'stock_used_ds8' => '0.000',
        ]);

        $this->actingAs($actor)->postJson(route('warehouse.transactions.store'), [
            'type' => 'IN',
            'item_barcode' => $item->barcode,
            'quantity' => '2',
            'item_condition' => 'USED',
            'location' => 'DS8',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated()->assertJsonPath('data.transaction_type', 'IN');

        $item->refresh();
        self::assertSame('3.000', (string) $item->current_stock);
        self::assertSame('2.000', (string) $item->stock_used_ds8);
        $this->assertDatabaseCount('trs_wh_stock_ins', 0);
        $this->assertDatabaseHas('trs_wh_stock_transactions', [
            'consumable_id' => $item->id,
            'transaction_type' => 'IN',
            'item_condition' => 'USED',
        ]);

        $this->actingAs($actor)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT',
            'item_barcode' => $item->barcode,
            'quantity' => '1',
            'item_condition' => 'USED',
            'location' => 'DS8',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated()->assertJsonPath('data.transaction_type', 'OUT');

        $item->refresh();
        self::assertSame('2.000', (string) $item->current_stock);
        self::assertSame('1.000', (string) $item->stock_used_ds8);
        $this->assertDatabaseHas('trs_wh_stock_transactions', [
            'consumable_id' => $item->id,
            'transaction_type' => 'OUT',
            'item_condition' => 'USED',
        ]);
    }
}

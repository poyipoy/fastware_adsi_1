<?php

namespace Tests\Feature\Warehouse;

use App\Models\Warehouse\WarehouseConsumable;
use Illuminate\Support\Str;

class WarehouseStockTransactionTest extends WarehouseTestCase
{
    public function test_pic_can_stock_in_and_snapshot_is_accurate(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $employee = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['barcode' => '000-IN', 'current_stock' => '2.000']);

        $response = $this->actingAs($pic)->postJson(route('warehouse.transactions.store'), [
            'type' => 'IN', 'item_barcode' => '000-IN', 'quantity' => '3',
            'storage_location' => 'DS8',
            'verified_code' => (string) $employee->npk, 'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertCreated()->assertJsonPath('data.stock_before', '2.000')->assertJsonPath('data.stock_after', '5.000');
        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '5.000', 'storage_location' => 'DS8']);
        $this->assertDatabaseHas('trs_wh_stock_transactions', ['consumable_id' => $item->id, 'usage_location' => 'DS8']);
        $this->assertDatabaseHas('log_wh_verifications', ['status' => 'SUCCESS', 'user_id' => $employee->id]);
    }

    public function test_employee_can_stock_out_with_quantity_only_and_keeps_location(): void
    {
        $employee = $this->createUser();
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'barcode' => '000-OUT',
            'current_stock' => '5.000',
            'storage_location' => 'Deltamas',
        ]);

        $response = $this->actingAs($employee)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT', 'item_barcode' => '000-OUT', 'quantity' => '2',
            'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertCreated()->assertJsonPath('data.storage_location', 'Deltamas');
        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '3.000', 'storage_location' => 'Deltamas']);
        $this->assertDatabaseHas('trs_wh_stock_transactions', ['consumable_id' => $item->id, 'usage_location' => null]);
    }

    public function test_stock_in_requires_storage_location_and_does_not_mutate_stock(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'barcode' => '000-IN-MISSING-LOCATION',
            'current_stock' => '2.000',
            'storage_location' => 'DS8',
        ]);

        $this->actingAs($pic)->postJson(route('warehouse.transactions.store'), [
            'type' => 'IN', 'item_barcode' => $item->barcode, 'quantity' => '3',
            'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable()->assertJsonValidationErrors('storage_location');

        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '2.000', 'storage_location' => 'DS8']);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }

    public function test_stock_in_rejects_storage_location_outside_the_approved_options(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['barcode' => '000-IN-BAD-LOCATION', 'current_stock' => '2.000']);

        $this->actingAs($pic)->postJson(route('warehouse.transactions.store'), [
            'type' => 'IN', 'item_barcode' => $item->barcode, 'quantity' => '3',
            'storage_location' => 'Rack C-99',
            'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable()->assertJsonValidationErrors('storage_location');

        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '2.000', 'storage_location' => 'DS8']);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }

    public function test_stock_out_rejects_manual_storage_location(): void
    {
        $employee = $this->createUser();
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'barcode' => '000-OUT-LOCATION',
            'current_stock' => '5.000',
            'storage_location' => 'Deltamas',
        ]);

        $this->actingAs($employee)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT', 'item_barcode' => $item->barcode, 'quantity' => '2',
            'storage_location' => 'Rack C-99',
            'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable()->assertJsonValidationErrors('storage_location');

        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '5.000', 'storage_location' => 'Deltamas']);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }

    public function test_stock_out_cannot_overdraw(): void
    {
        $employee = $this->createUser();
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['barcode' => '000-LOW', 'current_stock' => '1.000']);

        $response = $this->actingAs($employee)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT', 'item_barcode' => '000-LOW', 'quantity' => '2',
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
            'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable();
    }

    public function test_direct_transaction_request_cannot_bypass_verifier_access(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $verified = $this->createUser([], false);
        $this->createDepartmentPosition($verified, 'Human Resource', 'Direct Request Outsider '.uniqid());
        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'DIRECT-IN',
            'barcode' => 'DIRECT-IN',
            'current_stock' => '2.000',
        ]);

        $this->actingAs($pic)->postJson(route('warehouse.transactions.store'), [
            'type' => 'IN',
            'item_barcode' => 'DIRECT-IN',
            'quantity' => '3',
            'storage_location' => 'DS8',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'NPK karyawan tidak memiliki akses Warehouse untuk memverifikasi Stock In.');

        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '2.000']);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }
}

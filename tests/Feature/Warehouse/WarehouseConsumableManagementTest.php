<?php

namespace Tests\Feature\Warehouse;

use App\Models\Warehouse\WarehouseConsumable;

class WarehouseConsumableManagementTest extends WarehouseTestCase
{
    public function test_pic_can_create_consumable_with_zero_stock(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $response = $this->actingAs($pic)->post(route('warehouse.consumables.store'), [
            'item_code' => 'CNS-0001',
            'item_name' => 'Isolasi Listrik',
            'minimum_stock' => '8',
            'maximum_stock' => '30',
            'storage_location' => 'DS8',
            'barcode' => 'MALICIOUS-OVERRIDE',
            'unit' => 'liter',
            'allow_fraction' => true,
            'category_id' => 999999,
        ]);

        $response->assertRedirect(route('warehouse.consumables.index'));
        $this->assertDatabaseHas('mst_wh_consumables', [
            'item_code' => 'CNS-0001',
            'barcode' => 'CNS-0001',
            'unit' => 'pcs',
            'allow_fraction' => false,
            'category_id' => null,
            'current_stock' => '0.000',
            'storage_location' => 'DS8',
        ]);
    }

    public function test_master_rejects_storage_location_outside_the_approved_options(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);

        $this->actingAs($pic)->post(route('warehouse.consumables.store'), [
            'item_code' => 'CNS-BAD-LOCATION',
            'item_name' => 'Lokasi Tidak Valid',
            'minimum_stock' => '0',
            'storage_location' => 'Rack C-02',
        ])->assertSessionHasErrors('storage_location');

        $this->assertDatabaseMissing('mst_wh_consumables', ['item_code' => 'CNS-BAD-LOCATION']);
    }

    public function test_duplicate_code_and_barcode_are_rejected(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        WarehouseConsumable::factory()->create(['item_code' => 'DUP-1', 'barcode' => 'LEGACY-0001']);

        $response = $this->actingAs($pic)->post(route('warehouse.consumables.store'), [
            'item_code' => 'DUP-1',
            'item_name' => 'Duplicate',
            'minimum_stock' => 0,
        ]);

        $response->assertSessionHasErrors('item_code');

        $this->actingAs($pic)->post(route('warehouse.consumables.store'), [
            'item_code' => 'LEGACY-0001',
            'item_name' => 'Cross-column duplicate',
            'minimum_stock' => 0,
        ])->assertSessionHasErrors('item_code');
    }

    public function test_edit_cannot_change_current_stock(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $item = WarehouseConsumable::factory()->create([
            'current_stock' => '4.000',
            'item_code' => 'EDIT-1',
            'barcode' => 'LEGACY-EDIT-BARCODE',
            'unit' => 'box',
        ]);

        $response = $this->actingAs($pic)->put(route('warehouse.consumables.update', $item), [
            'item_code' => 'EDIT-2',
            'item_name' => 'Changed Name',
            'minimum_stock' => '1',
            'current_stock' => '999',
            'barcode' => 'OVERRIDE-IGNORED',
            'unit' => 'pcs',
        ]);

        $response->assertRedirect(route('warehouse.consumables.index'));
        $this->assertDatabaseHas('mst_wh_consumables', [
            'id' => $item->id,
            'item_code' => 'EDIT-2',
            'barcode' => 'LEGACY-EDIT-BARCODE',
            'unit' => 'box',
            'current_stock' => '4.000',
            'item_name' => 'Changed Name',
        ]);
    }

    public function test_maximum_stock_must_not_be_below_minimum(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);

        $response = $this->actingAs($pic)->post(route('warehouse.consumables.store'), [
            'item_code' => 'BAD-MAX',
            'item_name' => 'Bad Max',
            'minimum_stock' => '10',
            'maximum_stock' => '9',
        ]);

        $response->assertSessionHasErrors('maximum_stock');
    }

    public function test_master_stock_limits_only_accept_whole_numbers(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);

        $this->actingAs($pic)->post(route('warehouse.consumables.store'), [
            'item_code' => 'FRACTION-MIN',
            'item_name' => 'Fraction Minimum',
            'minimum_stock' => '1.5',
            'maximum_stock' => '5.5',
        ])->assertSessionHasErrors(['minimum_stock', 'maximum_stock']);
    }

    public function test_opening_balance_is_a_stock_movement(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['current_stock' => '0.000']);

        $response = $this->actingAs($pic)->post(route('warehouse.consumables.opening-balance', $item), [
            'quantity' => '12',
            'verified_code' => (string) $verified->npk,
            'reason' => 'Initial physical count',
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $response->assertRedirect(route('warehouse.consumables.index'));
        $this->assertDatabaseHas('trs_wh_stock_transactions', ['consumable_id' => $item->id, 'transaction_type' => 'ADJUSTMENT', 'quantity' => '12.000']);
        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '12.000']);
    }

    public function test_opening_balance_requires_verifier_with_warehouse_access(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $verified = $this->createUser([], false);
        $this->createDepartmentPosition($verified, 'Human Resource', 'Opening Balance Outsider '.uniqid());
        $item = WarehouseConsumable::factory()->create(['current_stock' => '0.000']);

        $this->actingAs($pic)->post(route('warehouse.consumables.opening-balance', $item), [
            'quantity' => '12',
            'verified_code' => (string) $verified->npk,
            'reason' => 'Initial count',
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertSessionHasErrors('verified_code');

        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '0.000']);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }
}

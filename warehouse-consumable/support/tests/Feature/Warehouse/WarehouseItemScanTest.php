<?php

namespace Tests\Feature\Warehouse;

use App\Models\Warehouse\WarehouseConsumable;

class WarehouseItemScanTest extends WarehouseTestCase
{
    public function test_active_item_barcode_resolves_exactly_and_preserves_leading_zero(): void
    {
        $employee = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['barcode' => '000089123456']);

        $response = $this->actingAs($employee)->postJson(route('warehouse.scans.item'), ['code' => " 000089123456\r\n"]);

        $response->assertOk()
            ->assertJsonStructure(['data' => [
                'id', 'item_code', 'barcode', 'item_name', 'category', 'unit',
                'current_stock', 'minimum_stock', 'storage_location', 'stock_status',
            ]])
            ->assertJsonPath('data.barcode', '000089123456')
            ->assertJsonPath('data.id', $item->id);
    }

    public function test_unknown_or_inactive_item_is_rejected_and_failure_is_logged(): void
    {
        $employee = $this->createUser();
        WarehouseConsumable::factory()->create(['barcode' => 'INACTIVE-1', 'is_active' => false]);

        $response = $this->actingAs($employee)->postJson(route('warehouse.scans.item'), ['code' => 'INACTIVE-1']);

        $response->assertNotFound();
        $this->assertDatabaseHas('log_wh_verifications', ['status' => 'FAILED', 'failure_reason' => 'Unknown or inactive item barcode']);
    }

    public function test_item_code_is_the_primary_scan_identity_and_legacy_barcode_still_resolves(): void
    {
        $employee = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'TFHINSR-000000008',
            'barcode' => 'LEGACY-BARCODE-008',
            'item_name' => 'Insert Widia HNPJ0704ANSNGD WS40PM',
        ]);

        $this->actingAs($employee)->postJson(route('warehouse.scans.item'), [
            'code' => "TFHINSR-000000008\r\n",
        ])->assertOk()->assertJsonPath('data.id', $item->id);

        $this->actingAs($employee)->postJson(route('warehouse.scans.item'), [
            'code' => 'LEGACY-BARCODE-008',
        ])->assertOk()->assertJsonPath('data.id', $item->id);
    }

    public function test_cross_column_identity_collision_is_rejected_as_ambiguous(): void
    {
        $employee = $this->createUser();
        WarehouseConsumable::factory()->create([
            'item_code' => 'SCAN-COLLISION',
            'barcode' => 'LEGACY-FIRST',
        ]);
        WarehouseConsumable::factory()->create([
            'item_code' => 'OTHER-ITEM',
            'barcode' => 'SCAN-COLLISION',
        ]);

        $this->actingAs($employee)->postJson(route('warehouse.scans.item'), [
            'code' => 'SCAN-COLLISION',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Item Code cocok dengan lebih dari satu barang.');
    }

    public function test_control_character_is_rejected_without_raw_code_in_log(): void
    {
        $employee = $this->createUser();
        $raw = "BAD\x01CODE";

        $response = $this->actingAs($employee)->postJson(route('warehouse.scans.item'), ['code' => $raw]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('log_wh_verifications', ['scanned_code_hash' => $raw]);
    }
}

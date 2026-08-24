<?php

namespace Tests\Feature\Warehouse;

use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use Illuminate\Support\Str;

class WarehouseReversalTest extends WarehouseTestCase
{
    public function test_admin_reversal_creates_opposite_movement_without_editing_original(): void
    {
        $admin = $this->createUser(['role_id' => 1]);
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['current_stock' => '24.000']);
        $original = WarehouseStockTransaction::factory()->create([
            'transaction_type' => WarehouseTransactionType::IN, 'consumable_id' => $item->id, 'quantity' => '10.000', 'stock_before' => '14.000', 'stock_after' => '24.000',
            'verified_user_id' => $verified->id, 'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->postJson(route('warehouse.transactions.reverse', $original), [
            'reason' => 'Receiving correction', 'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('trs_wh_stock_transactions', ['id' => $original->id, 'stock_before' => '14.000', 'stock_after' => '24.000', 'reversal_of_id' => null]);
        $this->assertDatabaseHas('trs_wh_stock_transactions', ['transaction_type' => 'REVERSAL', 'reversal_of_id' => $original->id, 'stock_before' => '24.000', 'stock_after' => '14.000']);
        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '14.000']);
    }

    public function test_duplicate_reversal_is_rejected(): void
    {
        $admin = $this->createUser(['role_id' => 1]);
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['current_stock' => '4.000']);
        $original = WarehouseStockTransaction::factory()->create(['transaction_type' => WarehouseTransactionType::IN, 'consumable_id' => $item->id, 'quantity' => '1.000', 'stock_before' => '3.000', 'stock_after' => '4.000', 'verified_user_id' => $verified->id, 'created_by' => $admin->id]);
        $payload = ['reason' => 'Correction', 'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid()];

        $this->actingAs($admin)->postJson(route('warehouse.transactions.reverse', $original), $payload)->assertCreated();
        $this->actingAs($admin)->postJson(route('warehouse.transactions.reverse', $original), ['reason' => 'Second correction', 'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid()])->assertStatus(409);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 2);
    }

    public function test_reversal_is_blocked_when_current_stock_is_insufficient(): void
    {
        $admin = $this->createUser(['role_id' => 1]);
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['current_stock' => '5.000']);
        $original = WarehouseStockTransaction::factory()->create(['transaction_type' => WarehouseTransactionType::IN, 'consumable_id' => $item->id, 'quantity' => '10.000', 'stock_before' => '0.000', 'stock_after' => '10.000', 'verified_user_id' => $verified->id, 'created_by' => $admin->id]);

        $this->actingAs($admin)->postJson(route('warehouse.transactions.reverse', $original), ['reason' => 'Invalid current stock', 'verified_code' => (string) $verified->npk, 'idempotency_key' => (string) Str::uuid()])->assertUnprocessable();
        $this->assertDatabaseCount('trs_wh_stock_transactions', 1);
        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '5.000']);
    }

    public function test_reversal_rechecks_verifier_access_for_its_effective_stock_direction(): void
    {
        $admin = $this->createUser(['role_id' => 1]);
        $verified = $this->createUser([], false);
        $this->createDepartmentPosition($verified, 'Human Resource', 'Reversal Outsider '.uniqid());
        $item = WarehouseConsumable::factory()->create(['current_stock' => '8.000']);
        $originalIn = WarehouseStockTransaction::factory()->create([
            'transaction_type' => WarehouseTransactionType::IN,
            'consumable_id' => $item->id,
            'quantity' => '3.000',
            'stock_before' => '5.000',
            'stock_after' => '8.000',
            'verified_user_id' => $verified->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->postJson(route('warehouse.transactions.reverse', $originalIn), [
            'reason' => 'Receiving correction',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Akun karyawan tidak memiliki akses Warehouse untuk memverifikasi Stock Out.');

        $this->assertDatabaseCount('trs_wh_stock_transactions', 1);
        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '8.000']);
    }

    public function test_reversal_of_stock_out_uses_stock_in_permission(): void
    {
        $admin = $this->createUser(['role_id' => 1]);
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['current_stock' => '3.000']);
        $originalOut = WarehouseStockTransaction::factory()->create([
            'transaction_type' => WarehouseTransactionType::OUT,
            'consumable_id' => $item->id,
            'quantity' => '2.000',
            'stock_before' => '5.000',
            'stock_after' => '3.000',
            'verified_user_id' => $verified->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->postJson(route('warehouse.transactions.reverse', $originalOut), [
            'reason' => 'Return stock correction',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated();

        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '5.000']);
    }
}

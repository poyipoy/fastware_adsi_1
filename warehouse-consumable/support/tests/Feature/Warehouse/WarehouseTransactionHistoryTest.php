<?php

namespace Tests\Feature\Warehouse;

use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use Illuminate\Support\Str;

class WarehouseTransactionHistoryTest extends WarehouseTestCase
{
    public function test_authorized_history_and_detail_use_immutable_snapshots(): void
    {
        $admin = $this->createUser(['role_id' => 1]);
        $verified = $this->createUser(['name' => 'Snapshot Employee', 'section' => 'Assembly']);
        $item = WarehouseConsumable::factory()->create(['item_name' => 'Snapshot Item']);
        $transaction = WarehouseStockTransaction::factory()->create([
            'transaction_number' => 'WH-HISTORY-'.Str::random(8), 'transaction_type' => WarehouseTransactionType::IN, 'consumable_id' => $item->id,
            'verified_user_id' => $verified->id, 'verified_user_name' => 'Snapshot Employee', 'verified_user_section' => 'Assembly', 'created_by' => $admin->id, 'quantity' => '2.000', 'stock_before' => '0.000', 'stock_after' => '2.000', 'reference_number' => 'REF-001',
        ]);

        $this->actingAs($admin)->get(route('warehouse.transactions.index', ['reference_number' => 'REF-001']))->assertOk()->assertSee($transaction->transaction_number);
        $this->actingAs($admin)->get(route('warehouse.transactions.show', $transaction))->assertOk()->assertSee('Snapshot Employee')->assertSee('Assembly')->assertDontSee('card_code');
    }

    public function test_authorized_department_employee_can_inspect_arbitrary_transaction_id(): void
    {
        $employee = $this->createUser();
        $owner = $this->createUser();
        $item = WarehouseConsumable::factory()->create();
        $transaction = WarehouseStockTransaction::factory()->create(['consumable_id' => $item->id, 'verified_user_id' => $owner->id, 'created_by' => $owner->id]);

        $this->actingAs($employee)->get(route('warehouse.transactions.show', $transaction))->assertOk();
    }

    public function test_employee_outside_authorized_departments_cannot_inspect_transaction(): void
    {
        $outsider = $this->createUser([], false);
        $this->createDepartmentPosition($outsider, 'Human Resource', 'History Outsider '.uniqid());
        $owner = $this->createUser();
        $item = WarehouseConsumable::factory()->create();
        $transaction = WarehouseStockTransaction::factory()->create([
            'consumable_id' => $item->id,
            'verified_user_id' => $owner->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($outsider)->get(route('warehouse.transactions.show', $transaction))->assertForbidden();
    }
}

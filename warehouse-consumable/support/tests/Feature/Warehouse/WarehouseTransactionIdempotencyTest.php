<?php

namespace Tests\Feature\Warehouse;

use App\Models\Warehouse\WarehouseConsumable;
use Illuminate\Support\Str;

class WarehouseTransactionIdempotencyTest extends WarehouseTestCase
{
    public function test_replay_of_same_idempotency_key_returns_canonical_transaction_once(): void
    {
        $employee = $this->createUser();
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['barcode' => '000-IDEMP', 'current_stock' => '10.000']);
        $key = (string) Str::uuid();
        $payload = ['type' => 'OUT', 'item_barcode' => '000-IDEMP', 'quantity' => '2', 'verified_code' => (string) $verified->npk, 'idempotency_key' => $key];

        $first = $this->actingAs($employee)->postJson(route('warehouse.transactions.store'), $payload);
        $second = $this->actingAs($employee)->postJson(route('warehouse.transactions.store'), $payload);

        $first->assertCreated();
        $second->assertOk()->assertJsonPath('idempotent_replay', true)->assertJsonPath('data.transaction_number', $first->json('data.transaction_number'));
        $this->assertDatabaseCount('trs_wh_stock_transactions', 1);
        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '8.000']);
    }
}

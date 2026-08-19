<?php

namespace Tests\Feature\Warehouse;

use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockIn;
use App\Models\Warehouse\WarehouseStockTransaction;
use App\Services\Warehouse\WarehouseStockReservationService;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WarehouseStockInTest extends WarehouseTestCase
{
    public function test_creation_is_waiting_and_does_not_mutate_stock_or_ledger(): void
    {
        $creator = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'IN-PENDING-001',
            'barcode' => 'IN-PENDING-001',
            'stock_ds8' => '5.000',
            'current_stock' => '5.000',
        ]);

        $this->actingAs($creator)->postJson(route('warehouse.stock-in.store'), [
            'item_barcode' => $item->barcode,
            'item_condition' => 'NEW',
            'quantity_expected' => '10',
            'destination_location' => 'DS8',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated()->assertJsonPath('data.status', 'WAITING_VALIDATION');

        $item->refresh();
        self::assertSame('5.000', (string) $item->stock_ds8);
        self::assertSame('5.000', (string) $item->current_stock);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
        $this->assertDatabaseHas('trs_wh_stock_ins', [
            'status' => 'WAITING_VALIDATION',
            'quantity_expected' => '10.000',
            'quantity_received' => null,
        ]);
    }

    public function test_creation_and_validation_are_idempotent_and_manual_quantity_uses_actual(): void
    {
        $creator = $this->createUser();
        $validator = $this->restrictedFixture('RAGIL ISHA RAHMANTO', 5639);
        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'IN-MANUAL-001',
            'barcode' => 'IN-MANUAL-001',
            'stock_ds8' => '5.000',
            'current_stock' => '5.000',
        ]);
        $creationKey = (string) Str::uuid();
        $payload = [
            'item_barcode' => $item->barcode,
            'item_condition' => 'NEW',
            'quantity_expected' => '10',
            'destination_location' => 'DS8',
            'idempotency_key' => $creationKey,
        ];

        $this->actingAs($creator)->postJson(route('warehouse.stock-in.store'), $payload)->assertCreated();
        $this->actingAs($creator)->postJson(route('warehouse.stock-in.store'), $payload)->assertOk();
        self::assertSame(1, WarehouseStockIn::query()->count());
        $stockIn = WarehouseStockIn::query()->firstOrFail();
        $validationKey = (string) Str::uuid();
        $validationPayload = [
            'validator_code' => (string) $validator->npk,
            'received_item_barcode' => $item->barcode,
            'received_condition' => 'NEW',
            'quantity_received' => '8',
            'validation_result' => 'MANUAL_ADJUSTMENT',
            'validation_notes' => 'Barang fisik diterima hanya 8 pcs dari 10 pcs yang dicatat.',
            'idempotency_key' => $validationKey,
        ];

        $this->actingAs($creator)->postJson(route('warehouse.stock-in.validate', $stockIn), $validationPayload)
            ->assertCreated()
            ->assertJsonPath('data.status', 'VALIDATED')
            ->assertJsonPath('data.validation_result', 'MANUAL_ADJUSTMENT');

        $this->actingAs($creator)->postJson(route('warehouse.stock-in.validate', $stockIn), $validationPayload)
            ->assertOk()
            ->assertJsonPath('idempotent_replay', true);

        $stockIn->refresh();
        $item->refresh();
        self::assertSame('10.000', (string) $stockIn->quantity_expected);
        self::assertSame('8.000', (string) $stockIn->quantity_received);
        self::assertSame('13.000', (string) $item->stock_ds8);
        self::assertSame('1', (string) WarehouseStockTransaction::query()->count());
    }

    public function test_internal_source_reserves_without_mutating_until_validation_and_preserves_global_total(): void
    {
        $creator = $this->createUser();
        $validator = $this->restrictedFixture('ARY RODJO PRASETYO', 5439);
        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'IN-INTERNAL-001',
            'barcode' => 'IN-INTERNAL-001',
            'stock_deltamas' => '20.000',
            'stock_ds8' => '5.000',
            'current_stock' => '25.000',
        ]);

        $this->actingAs($creator)->postJson(route('warehouse.stock-in.store'), [
            'consumable_id' => $item->id,
            'item_condition' => 'NEW',
            'quantity_expected' => '8',
            'destination_location' => 'DS8',
            'source_location' => 'Deltamas',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated();

        self::assertSame('8.000', app(WarehouseStockReservationService::class)->reserved($item->id, 'Deltamas', \App\Enums\Warehouse\WarehouseItemCondition::NEW));
        $item->refresh();
        self::assertSame('20.000', (string) $item->stock_deltamas);
        self::assertSame('5.000', (string) $item->stock_ds8);

        $stockIn = WarehouseStockIn::query()->firstOrFail();
        $this->actingAs($creator)->postJson(route('warehouse.stock-in.validate', $stockIn), [
            'validator_code' => (string) $validator->npk,
            'received_item_barcode' => $item->barcode,
            'received_condition' => 'NEW',
            'quantity_received' => '7',
            'validation_result' => 'MANUAL_ADJUSTMENT',
            'validation_notes' => 'Tujuh unit diterima secara fisik.',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated();

        $item->refresh();
        self::assertSame('13.000', (string) $item->stock_deltamas);
        self::assertSame('12.000', (string) $item->stock_ds8);
        self::assertSame('25.000', (string) $item->current_stock);
        self::assertSame('0.000', app(WarehouseStockReservationService::class)->reserved($item->id, 'Deltamas', \App\Enums\Warehouse\WarehouseItemCondition::NEW));
        $this->assertDatabaseHas('trs_wh_stock_transactions', ['transaction_type' => 'TRANSFER', 'stock_in_id' => $stockIn->id]);
    }

    public function test_non_restricted_validator_is_rejected_and_manual_notes_are_required(): void
    {
        $creator = $this->createUser();
        $outsider = $this->createUser([], false);
        $item = WarehouseConsumable::factory()->create(['item_code' => 'IN-SECURITY-001', 'barcode' => 'IN-SECURITY-001']);
        $this->actingAs($creator)->postJson(route('warehouse.stock-in.store'), [
            'consumable_id' => $item->id,
            'item_condition' => 'NEW',
            'quantity_expected' => '3',
            'destination_location' => 'DS8',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated();
        $stockIn = WarehouseStockIn::query()->firstOrFail();

        $this->actingAs($creator)->postJson(route('warehouse.stock-in.validate', $stockIn), [
            'validator_code' => (string) $outsider->npk,
            'quantity_received' => '3',
            'validation_result' => 'MATCH',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable();

        $validator = $this->restrictedFixture('RAGIL ISHA RAHMANTO', 5639);
        $this->actingAs($creator)->postJson(route('warehouse.stock-in.validate', $stockIn), [
            'validator_code' => (string) $validator->npk,
            'quantity_received' => '2',
            'validation_result' => 'MANUAL_ADJUSTMENT',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable();
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }

    private function restrictedFixture(string $name, int $npk): User
    {
        $users = User::query()->where('npk', $npk)->orderBy('id')->get();
        $user = $users->first() ?? $this->createUser(['name' => $name, 'npk' => $npk]);
        foreach ($users->skip(1) as $duplicate) {
            $duplicate->forceFill(['npk' => $this->freshNpk()])->save();
        }
        $user->forceFill(['name' => $name, 'is_active' => config('warehouse.identity.active_user_value', 0)])->save();
        $this->createDepartmentPosition($user);
        DB::table('mst_wh_restricted_verifiers')->insertOrIgnore([
            'user_id' => $user->getKey(),
            'scope' => 'ALL',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user->refresh();
    }
}

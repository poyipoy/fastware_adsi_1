<?php

namespace Tests\Feature\Warehouse;

use App\Data\Warehouse\WarehouseStockCommand;
use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Exceptions\WarehouseDomainException;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseLocationShipment;
use App\Models\Warehouse\WarehouseStockTransaction;
use App\Services\Warehouse\WarehouseStockReservationService;
use App\Services\Warehouse\WarehouseStockService;
use Illuminate\Support\Str;

class WarehouseLocationShipmentTest extends WarehouseTestCase
{
    public function test_primary_match_reserves_then_moves_location_atomically_and_is_idempotent(): void
    {
        [$sender, $validator, $item] = $this->fixture();
        $createKey = (string) Str::uuid();

        $created = $this->actingAs($sender)->postJson(route('warehouse.location-shipments.store'), [
            'consumable_id' => $item->getKey(),
            'item_condition' => 'NEW',
            'quantity' => '8',
            'from_location' => 'Deltamas',
            'to_location' => 'DS8',
            'notes' => 'Primary acceptance shipment',
            'idempotency_key' => $createKey,
        ]);

        $created->assertCreated()->assertJsonPath('data.status', 'WAITING_VALIDATION');
        $item->refresh();
        self::assertSame('20.000', (string) $item->stock_deltamas);
        self::assertSame('5.000', (string) $item->stock_ds8);
        self::assertSame('25.000', (string) $item->current_stock);
        self::assertSame('8.000', app(WarehouseStockReservationService::class)->reserved($item->id, 'Deltamas', WarehouseItemCondition::NEW));

        try {
            app(WarehouseStockService::class)->execute(new WarehouseStockCommand(
                type: WarehouseTransactionType::OUT,
                consumableId: $item->id,
                quantity: '13',
                verifiedUserId: $validator->id,
                idempotencyKey: (string) Str::uuid(),
                createdBy: $sender->id,
                itemCondition: WarehouseItemCondition::NEW,
                sourceLocation: 'Deltamas',
            ));
            self::fail('Stock Out must respect the active shipment reservation.');
        } catch (WarehouseDomainException $exception) {
            self::assertSame(422, $exception->status);
        }

        $shipment = WarehouseLocationShipment::query()->firstOrFail();
        $validationKey = (string) Str::uuid();
        $validated = $this->actingAs($validator)->postJson(route('warehouse.location-shipments.validate', $shipment), [
            'received_quantity' => '8',
            'received_condition' => 'NEW',
            'validator_code' => (string) $validator->npk,
            'validation_notes' => 'Fisik sesuai.',
            'idempotency_key' => $validationKey,
        ]);

        $validated->assertCreated()->assertJsonPath('data.status', 'VALIDATED');
        $item->refresh();
        self::assertSame('12.000', (string) $item->stock_deltamas);
        self::assertSame('13.000', (string) $item->stock_ds8);
        self::assertSame('25.000', (string) $item->current_stock);
        self::assertSame('0.000', app(WarehouseStockReservationService::class)->reserved($item->id, 'Deltamas', WarehouseItemCondition::NEW));
        $this->assertDatabaseCount('trs_wh_stock_transactions', 1);
        $this->assertDatabaseHas('trs_wh_stock_transactions', [
            'transaction_type' => 'TRANSFER',
            'location_shipment_id' => $shipment->id,
            'from_location' => 'Deltamas',
            'to_location' => 'DS8',
        ]);

        $this->actingAs($validator)->postJson(route('warehouse.location-shipments.validate', $shipment), [
            'received_quantity' => '8',
            'received_condition' => 'NEW',
            'validator_code' => (string) $validator->npk,
            'validation_notes' => 'Fisik sesuai.',
            'idempotency_key' => $validationKey,
        ])->assertOk()->assertJsonPath('data.status', 'VALIDATED');
        $this->assertDatabaseCount('trs_wh_stock_transactions', 1);
    }

    public function test_mismatch_keeps_balances_and_reservation_until_cancel(): void
    {
        [$sender, $validator, $item] = $this->fixture();
        $this->actingAs($sender)->postJson(route('warehouse.location-shipments.store'), [
            'consumable_id' => $item->id,
            'item_condition' => 'NEW',
            'quantity' => '8',
            'from_location' => 'Deltamas',
            'to_location' => 'DS8',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated();
        $shipment = WarehouseLocationShipment::query()->firstOrFail();

        $this->actingAs($validator)->postJson(route('warehouse.location-shipments.validate', $shipment), [
            'received_quantity' => '7',
            'received_condition' => 'NEW',
            'validator_code' => (string) $validator->npk,
            'validation_notes' => 'Satu unit kurang saat serah terima.',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertOk()->assertJsonPath('data.status', 'DISCREPANCY');

        $item->refresh();
        self::assertSame('20.000', (string) $item->stock_deltamas);
        self::assertSame('5.000', (string) $item->stock_ds8);
        self::assertSame('25.000', (string) $item->current_stock);
        self::assertSame('8.000', app(WarehouseStockReservationService::class)->reserved($item->id, 'Deltamas', WarehouseItemCondition::NEW));
        self::assertSame(0, WarehouseStockTransaction::query()->count());

        $this->actingAs($sender)->postJson(route('warehouse.location-shipments.cancel', $shipment), [
            'reason' => 'Discrepancy ditangani dan shipment dibatalkan.',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertOk()->assertJsonPath('data.status', 'CANCELLED');
        self::assertSame('0.000', app(WarehouseStockReservationService::class)->reserved($item->id, 'Deltamas', WarehouseItemCondition::NEW));
    }

    public function test_sender_cannot_validate_own_shipment_and_same_location_is_rejected(): void
    {
        [$sender, $validator, $item] = $this->fixture();
        $createKey = (string) Str::uuid();
        $this->actingAs($sender)->postJson(route('warehouse.location-shipments.store'), [
            'consumable_id' => $item->id,
            'item_condition' => 'NEW',
            'quantity' => '1',
            'from_location' => 'DS8',
            'to_location' => 'Deltamas',
            'idempotency_key' => $createKey,
        ])->assertCreated();
        $shipment = WarehouseLocationShipment::query()->firstOrFail();
        $this->actingAs($sender)->postJson(route('warehouse.location-shipments.validate', $shipment), [
            'received_quantity' => '1',
            'received_condition' => 'NEW',
            'validator_code' => (string) $sender->npk,
            'validation_notes' => 'Tidak boleh self validate.',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable();

        $this->actingAs($validator)->postJson(route('warehouse.location-shipments.cancel', $shipment), [
            'reason' => 'Batalkan setelah uji self validator.',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertOk();

        $sameLocationKey = (string) Str::uuid();
        $this->actingAs($sender)->postJson(route('warehouse.location-shipments.store'), [
            'consumable_id' => $item->id,
            'item_condition' => 'NEW',
            'quantity' => '1',
            'from_location' => 'DS8',
            'to_location' => 'DS8',
            'idempotency_key' => $sameLocationKey,
        ])->assertUnprocessable()->assertJsonValidationErrors('to_location');
    }

    public function test_creation_and_cancellation_replay_are_idempotent_and_conflicts_are_rejected(): void
    {
        [$sender, , $item] = $this->fixture();
        $createKey = (string) Str::uuid();
        $payload = [
            'consumable_id' => $item->id,
            'item_condition' => 'NEW',
            'quantity' => '2',
            'from_location' => 'Deltamas',
            'to_location' => 'DS8',
            'notes' => 'Idempotent shipment.',
            'idempotency_key' => $createKey,
        ];

        $this->actingAs($sender)->postJson(route('warehouse.location-shipments.store'), $payload)->assertCreated();
        $this->actingAs($sender)->postJson(route('warehouse.location-shipments.store'), $payload)
            ->assertOk()
            ->assertJsonPath('data.status', 'WAITING_VALIDATION');
        $this->assertDatabaseCount('trs_wh_location_shipments', 1);

        $shipment = WarehouseLocationShipment::query()->firstOrFail();
        $cancelKey = (string) Str::uuid();
        $cancelPayload = [
            'reason' => 'Reservation tidak lagi diperlukan.',
            'idempotency_key' => $cancelKey,
        ];
        $this->actingAs($sender)->postJson(route('warehouse.location-shipments.cancel', $shipment), $cancelPayload)
            ->assertOk()
            ->assertJsonPath('data.status', 'CANCELLED');
        $this->actingAs($sender)->postJson(route('warehouse.location-shipments.cancel', $shipment), $cancelPayload)
            ->assertOk()
            ->assertJsonPath('data.status', 'CANCELLED');
        $this->actingAs($sender)->postJson(route('warehouse.location-shipments.cancel', $shipment), [
            'reason' => 'Payload berbeda.',
            'idempotency_key' => $cancelKey,
        ])->assertStatus(409);
    }

    /** @return array{0:\App\Models\User,1:\App\Models\User,2:WarehouseConsumable} */
    private function fixture(): array
    {
        $sender = $this->createUser();
        $validator = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'SHIP-PRIMARY',
            'barcode' => 'SHIP-PRIMARY',
            'current_stock' => '25.000',
            'stock_deltamas' => '20.000',
            'stock_ds8' => '5.000',
            'stock_used_deltamas' => '0.000',
            'stock_used_ds8' => '0.000',
        ]);

        return [$sender, $validator, $item];
    }
}

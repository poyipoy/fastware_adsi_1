<?php

namespace Tests\Unit\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseLocationShipment;
use App\Services\Warehouse\WarehouseStockReservationService;
use Tests\Feature\Warehouse\WarehouseTestCase;

class WarehouseStockReservationServiceTest extends WarehouseTestCase
{
    public function test_reservation_is_location_and_condition_aware_and_excludes_terminal_states(): void
    {
        $sender = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'current_stock' => '15.000',
            'stock_deltamas' => '10.000',
            'stock_ds8' => '5.000',
            'stock_used_deltamas' => '4.000',
            'stock_used_ds8' => '1.000',
        ]);
        $base = [
            'shipment_number' => 'SHP-RESERVE-1',
            'consumable_id' => $item->id,
            'item_condition' => 'NEW',
            'quantity_sent' => '3.000',
            'from_location' => 'Deltamas',
            'to_location' => 'DS8',
            'status' => 'WAITING_VALIDATION',
            'sent_by_user_id' => $sender->id,
            'sent_at' => now(),
            'creation_idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        ];
        WarehouseLocationShipment::query()->create($base);
        WarehouseLocationShipment::query()->create(array_merge($base, [
            'shipment_number' => 'SHP-RESERVE-2',
            'item_condition' => 'USED',
            'quantity_sent' => '2.000',
            'creation_idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        ]));
        WarehouseLocationShipment::query()->create(array_merge($base, [
            'shipment_number' => 'SHP-RESERVE-3',
            'status' => 'VALIDATED',
            'creation_idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        ]));

        $service = app(WarehouseStockReservationService::class);
        self::assertSame('3.000', $service->reserved($item->id, 'Deltamas', WarehouseItemCondition::NEW));
        self::assertSame('2.000', $service->reserved($item->id, 'Deltamas', WarehouseItemCondition::USED));
        self::assertSame('3.000', $service->available($item, 'Deltamas', WarehouseItemCondition::NEW));
        self::assertSame('2.000', $service->available($item, 'Deltamas', WarehouseItemCondition::USED));
    }
}

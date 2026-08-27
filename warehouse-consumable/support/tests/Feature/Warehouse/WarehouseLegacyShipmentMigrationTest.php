<?php

namespace Tests\Feature\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseLocationShipmentStatus;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseLocationShipment;
use App\Models\Warehouse\WarehouseStockIn;
use App\Services\Warehouse\WarehouseStockReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class WarehouseLegacyShipmentMigrationTest extends WarehouseTestCase
{
    public function test_upgrade_converts_waiting_and_discrepancy_shipments_idempotently_without_ledger_mutation(): void
    {
        $sender = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'MIGRATION-TRANSFER-ITEM',
            'current_stock' => '8.000',
            'stock_ds8' => '5.000',
            'stock_used_ds8' => '1.000',
            'stock_deltamas' => '3.000',
            'stock_used_deltamas' => '2.000',
        ]);
        $waiting = $this->legacyShipment(
            $sender,
            $item,
            'WH-SHIP-MIG-WAITING',
            WarehouseItemCondition::NEW,
            WarehouseLocationShipmentStatus::WAITING_VALIDATION,
            '2.000',
            'DS8',
            'Deltamas',
            CarbonImmutable::parse('2026-08-20 09:30:00', 'Asia/Jakarta'),
        );
        $discrepancy = $this->legacyShipment(
            $sender,
            $item,
            'WH-SHIP-MIG-DISCREPANCY',
            WarehouseItemCondition::USED,
            WarehouseLocationShipmentStatus::DISCREPANCY,
            '1.000',
            'Deltamas',
            'DS8',
            CarbonImmutable::parse('2026-08-21 10:15:00', 'Asia/Jakarta'),
        );
        $migration = require database_path('migrations/2026_08_26_000003_convert_legacy_location_shipments_to_stock_ins.php');

        $migration->up();
        $migration->up();

        $waiting->refresh();
        $discrepancy->refresh();
        $waitingStockIn = WarehouseStockIn::query()->findOrFail($waiting->migrated_stock_in_id);
        $discrepancyStockIn = WarehouseStockIn::query()->findOrFail($discrepancy->migrated_stock_in_id);

        self::assertSame(WarehouseLocationShipmentStatus::CANCELLED, $waiting->status);
        self::assertSame(WarehouseLocationShipmentStatus::CANCELLED, $discrepancy->status);
        self::assertSame('WAITING_VALIDATION', $waiting->migration_original_status);
        self::assertSame('DISCREPANCY', $discrepancy->migration_original_status);
        self::assertSame('WH-IN-MIG-'.$waiting->id, $waitingStockIn->stock_in_number);
        self::assertSame('WH-IN-MIG-'.$discrepancy->id, $discrepancyStockIn->stock_in_number);
        self::assertSame('WAITING_VALIDATION', $waitingStockIn->status->value);
        self::assertSame('WAITING_VALIDATION', $discrepancyStockIn->status->value);
        self::assertSame('NEW', $waitingStockIn->item_condition->value);
        self::assertSame('USED', $discrepancyStockIn->item_condition->value);
        self::assertSame('2.000', (string) $waitingStockIn->quantity_expected);
        self::assertSame('Deltamas', $waitingStockIn->destination_location);
        self::assertSame('DS8', $waitingStockIn->source_location);
        self::assertSame((string) $sender->npk, $waitingStockIn->creator_npk_snapshot);
        self::assertStringContainsString('Catatan shipment lama', (string) $waitingStockIn->notes);
        self::assertSame(
            Uuid::uuid5(Uuid::NAMESPACE_URL, 'warehouse-location-shipment-to-stock-in:'.$waiting->id)->toString(),
            $waitingStockIn->creation_idempotency_key,
        );
        self::assertSame($waiting->sent_at->format('Y-m-d H:i:s'), $waitingStockIn->created_at->format('Y-m-d H:i:s'));
        self::assertSame(2, WarehouseStockIn::query()->count());
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);

        $item->refresh();
        self::assertSame('8.000', (string) $item->current_stock);
        self::assertSame('5.000', (string) $item->stock_ds8);
        self::assertSame('3.000', (string) $item->stock_deltamas);
        self::assertSame('2.000', app(WarehouseStockReservationService::class)->reserved($item->id, 'DS8', WarehouseItemCondition::NEW));
        self::assertSame('1.000', app(WarehouseStockReservationService::class)->reserved($item->id, 'Deltamas', WarehouseItemCondition::USED));
    }

    public function test_rollback_restores_only_unvalidated_migration_rows_and_stops_safely_after_validation(): void
    {
        $sender = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['current_stock' => '3.000', 'stock_ds8' => '3.000']);
        $shipment = $this->legacyShipment(
            $sender,
            $item,
            'WH-SHIP-MIG-ROLLBACK',
            WarehouseItemCondition::NEW,
            WarehouseLocationShipmentStatus::WAITING_VALIDATION,
            '1.000',
            'DS8',
            'Deltamas',
            now(),
        );
        $migration = require database_path('migrations/2026_08_26_000003_convert_legacy_location_shipments_to_stock_ins.php');
        $migration->up();
        $stockIn = WarehouseStockIn::query()->findOrFail($shipment->refresh()->migrated_stock_in_id);

        $migration->down();
        $shipment->refresh();
        self::assertSame(WarehouseLocationShipmentStatus::WAITING_VALIDATION, $shipment->status);
        self::assertNull($shipment->migrated_stock_in_id);
        self::assertDatabaseMissing('trs_wh_stock_ins', ['id' => $stockIn->id]);

        // Re-apply to prove that a pending conversion is reversible, then
        // emulate a completed validation. The downgrade must not erase it.
        $migration->up();
        $stockIn = WarehouseStockIn::query()->firstOrFail();
        $stockIn->forceFill(['status' => 'VALIDATED'])->save();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Rollback konversi Pengiriman Antar Lokasi dihentikan');
        $migration->down();
    }

    private function legacyShipment(
        $sender,
        WarehouseConsumable $item,
        string $number,
        WarehouseItemCondition $condition,
        WarehouseLocationShipmentStatus $status,
        string $quantity,
        string $from,
        string $to,
        $sentAt,
    ): WarehouseLocationShipment {
        return WarehouseLocationShipment::query()->create([
            'shipment_number' => $number,
            'consumable_id' => $item->id,
            'item_condition' => $condition,
            'quantity_sent' => $quantity,
            'from_location' => $from,
            'to_location' => $to,
            'status' => $status,
            'sent_by_user_id' => $sender->id,
            'sender_npk_snapshot' => (string) $sender->npk,
            'sender_name_snapshot' => $sender->name,
            'sender_notes' => 'Catatan shipment lama '.$number,
            'sent_at' => $sentAt,
            'creation_idempotency_key' => (string) Str::uuid(),
        ]);
    }
}

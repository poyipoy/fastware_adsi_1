<?php

namespace App\Services\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Exceptions\WarehouseDomainException;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseLocationShipment;
use App\Models\Warehouse\WarehouseStockIn;
use Illuminate\Support\Facades\Schema;

final class WarehouseStockReservationService
{
    public function reserved(
        int $consumableId,
        string $location,
        WarehouseItemCondition $condition,
        ?int $excludeShipmentId = null,
        ?int $excludeStockInId = null,
    ): string {
        $reservedMilli = 0;
        if (Schema::hasTable('trs_wh_stock_ins')) {
            $stockInQuery = WarehouseStockIn::query()
                ->reserving()
                ->where('consumable_id', $consumableId)
                ->where('source_location', $location)
                ->where('item_condition', $condition->value)
                ->select('quantity_expected');

            if ($excludeStockInId !== null) {
                $stockInQuery->where('id', '<>', $excludeStockInId);
            }

            foreach ($stockInQuery->pluck('quantity_expected') as $quantity) {
                $reservedMilli += WarehouseQuantity::toMilli((string) $quantity);
            }
        }

        // Keep reservations from legacy rows while the old table is still
        // present. New Stock In records never depend on this domain object.
        if (Schema::hasTable('trs_wh_location_shipments')) {
            $shipmentQuery = WarehouseLocationShipment::query()
                ->reserving()
                ->where('consumable_id', $consumableId)
                ->where('from_location', $location)
                ->where('item_condition', $condition->value)
                ->select('quantity_sent');

            if ($excludeShipmentId !== null) {
                $shipmentQuery->where('id', '<>', $excludeShipmentId);
            }

            foreach ($shipmentQuery->pluck('quantity_sent') as $quantity) {
                $reservedMilli += WarehouseQuantity::toMilli((string) $quantity);
            }
        }

        return WarehouseQuantity::fromMilli($reservedMilli);
    }

    public function available(
        WarehouseConsumable $item,
        string $location,
        WarehouseItemCondition $condition,
        ?int $excludeShipmentId = null,
        ?int $excludeStockInId = null,
    ): string {
        $physical = WarehouseQuantity::toMilli($item->availableAt($location, $condition));
        $reserved = WarehouseQuantity::toMilli($this->reserved(
            (int) $item->getKey(),
            $location,
            $condition,
            $excludeShipmentId,
            $excludeStockInId,
        ));

        if ($physical < $reserved) {
            throw new WarehouseDomainException('Reservation aktif melebihi stok fisik; operasi dihentikan untuk menjaga integritas saldo.', 409);
        }

        return WarehouseQuantity::fromMilli($physical - $reserved);
    }

    public function assertAvailable(
        WarehouseConsumable $item,
        string $location,
        WarehouseItemCondition $condition,
        string $quantity,
        ?int $excludeShipmentId = null,
        ?int $excludeStockInId = null,
    ): void {
        $available = $this->available($item, $location, $condition, $excludeShipmentId, $excludeStockInId);
        if (WarehouseQuantity::compare($available, $quantity) < 0) {
            throw new WarehouseDomainException(sprintf(
                'Stok %s di lokasi %s tidak mencukupi setelah memperhitungkan reservation aktif. Tersedia %s.',
                $condition->label(),
                $location,
                WarehouseQuantity::display($available),
            ), 422);
        }
    }
}

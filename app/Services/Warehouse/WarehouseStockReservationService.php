<?php

namespace App\Services\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Exceptions\WarehouseDomainException;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseLocationShipment;

final class WarehouseStockReservationService
{
    public function reserved(
        int $consumableId,
        string $location,
        WarehouseItemCondition $condition,
        ?int $excludeShipmentId = null,
    ): string {
        $query = WarehouseLocationShipment::query()
            ->reserving()
            ->where('consumable_id', $consumableId)
            ->where('from_location', $location)
            ->where('item_condition', $condition->value)
            ->select('quantity_sent');

        if ($excludeShipmentId !== null) {
            $query->where('id', '<>', $excludeShipmentId);
        }

        $reservedMilli = 0;
        foreach ($query->pluck('quantity_sent') as $quantity) {
            $reservedMilli += WarehouseQuantity::toMilli((string) $quantity);
        }

        return WarehouseQuantity::fromMilli($reservedMilli);
    }

    public function available(
        WarehouseConsumable $item,
        string $location,
        WarehouseItemCondition $condition,
        ?int $excludeShipmentId = null,
    ): string {
        $physical = WarehouseQuantity::toMilli($item->availableAt($location, $condition));
        $reserved = WarehouseQuantity::toMilli($this->reserved(
            (int) $item->getKey(),
            $location,
            $condition,
            $excludeShipmentId,
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
    ): void {
        $available = $this->available($item, $location, $condition, $excludeShipmentId);
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

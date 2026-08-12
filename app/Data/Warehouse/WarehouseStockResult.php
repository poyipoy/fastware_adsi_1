<?php

namespace App\Data\Warehouse;

use App\Models\Warehouse\WarehouseStockTransaction;

final readonly class WarehouseStockResult
{
    public function __construct(
        public WarehouseStockTransaction $transaction,
        public bool $idempotentReplay = false,
    ) {
    }
}

<?php

namespace App\Data\Warehouse;

use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\Warehouse\WarehouseStockTransaction;

final readonly class WarehouseStockCommand
{
    public function __construct(
        public WarehouseTransactionType $type,
        public int $consumableId,
        public string $quantity,
        public int $verifiedUserId,
        public ?string $referenceNumber = null,
        public ?string $purpose = null,
        /** @deprecated Use storageLocation for new Stock In commands. */
        public ?string $usageLocation = null,
        public ?string $notes = null,
        public ?string $idempotencyKey = null,
        public ?int $createdBy = null,
        public ?string $adjustmentReasonCategory = null,
        public ?string $adjustmentReason = null,
        public ?string $adjustmentDirection = null,
        public ?int $reversalOfId = null,
        public ?string $verificationCodeHash = null,
        public ?string $storageLocation = null,
    ) {
    }
}

final readonly class WarehouseStockResult
{
    public function __construct(
        public WarehouseStockTransaction $transaction,
        public bool $idempotentReplay = false,
    ) {
    }
}

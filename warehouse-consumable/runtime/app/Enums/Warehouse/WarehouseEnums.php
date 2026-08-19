<?php

namespace App\Enums\Warehouse;

enum WarehouseTransactionType: string
{
    case IN = 'IN';
    case OUT = 'OUT';
    case ADJUSTMENT = 'ADJUSTMENT';
    case REVERSAL = 'REVERSAL';
    case TRANSFER = 'TRANSFER';

    public function isInbound(): bool
    {
        return $this === self::IN;
    }

    public function isOutbound(): bool
    {
        return $this === self::OUT;
    }
}

enum WarehouseItemCondition: string
{
    case NEW = 'NEW';
    case USED = 'USED';

    public function label(): string
    {
        return $this === self::NEW ? 'Baru' : 'Bekas';
    }
}

enum WarehouseVerificationScope: string
{
    case ALL = 'ALL';
}

enum WarehouseVerificationStatus: string
{
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';
}

enum WarehouseLocationShipmentStatus: string
{
    case WAITING_VALIDATION = 'WAITING_VALIDATION';
    case VALIDATED = 'VALIDATED';
    case DISCREPANCY = 'DISCREPANCY';
    case CANCELLED = 'CANCELLED';

    public function isTerminal(): bool
    {
        return in_array($this, [self::VALIDATED, self::CANCELLED], true);
    }

    public function reservesStock(): bool
    {
        return in_array($this, [self::WAITING_VALIDATION, self::DISCREPANCY], true);
    }
}

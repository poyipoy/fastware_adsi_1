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

/**
 * The Stock In lifecycle is intentionally separate from the legacy shipment
 * lifecycle.  A pending Stock In never represents a completed stock movement.
 */
enum WarehouseStockInStatus: string
{
    case WAITING_VALIDATION = 'WAITING_VALIDATION';
    case VALIDATED = 'VALIDATED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::WAITING_VALIDATION => 'Menunggu Validasi',
            self::VALIDATED => 'Tervalidasi',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::VALIDATED, self::CANCELLED], true);
    }

    public function reservesSource(): bool
    {
        return $this === self::WAITING_VALIDATION;
    }
}

enum WarehouseStockInValidationResult: string
{
    case MATCH = 'MATCH';
    case MANUAL_ADJUSTMENT = 'MANUAL_ADJUSTMENT';

    public function label(): string
    {
        return $this === self::MATCH ? 'Sesuai' : 'Input Manual';
    }
}

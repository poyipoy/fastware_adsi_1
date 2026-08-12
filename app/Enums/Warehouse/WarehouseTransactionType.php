<?php

namespace App\Enums\Warehouse;

enum WarehouseTransactionType: string
{
    case IN = 'IN';
    case OUT = 'OUT';
    case ADJUSTMENT = 'ADJUSTMENT';
    case REVERSAL = 'REVERSAL';

    public function isInbound(): bool
    {
        return $this === self::IN;
    }

    public function isOutbound(): bool
    {
        return $this === self::OUT;
    }
}

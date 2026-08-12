<?php

namespace Tests\Unit\Warehouse;

use App\Services\Warehouse\WarehouseQuantity;
use PHPUnit\Framework\TestCase;

class WarehouseQuantityDisplayTest extends TestCase
{
    public function test_whole_quantities_are_displayed_without_decimal_suffix(): void
    {
        self::assertSame('0', WarehouseQuantity::display('0.000'));
        self::assertSame('12', WarehouseQuantity::display('12.000'));
        self::assertSame('12', WarehouseQuantity::display('00012.000'));
    }

    public function test_real_fractional_quantity_keeps_its_value_without_trailing_zeroes(): void
    {
        self::assertSame('12.5', WarehouseQuantity::display('12.500'));
        self::assertSame('12.125', WarehouseQuantity::display('12.125'));
    }
}

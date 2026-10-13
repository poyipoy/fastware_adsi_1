<?php

namespace Tests\Feature\Warehouse;

use App\Services\Warehouse\WarehouseStockInNumberGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WarehouseStockInNumberGeneratorTest extends WarehouseTestCase
{
    public function test_stock_in_numbers_use_one_yearly_sequence_and_reset_each_year(): void
    {
        Carbon::setTestNow('2026-09-24 10:00:00');
        try {
            $generator = app(WarehouseStockInNumberGenerator::class);

            self::assertSame('WH-IN-20260001', $generator->generate());
            self::assertSame('WH-IN-20260002', $generator->generate());

            Carbon::setTestNow('2027-01-01 00:00:01');
            self::assertSame('WH-IN-20270001', $generator->generate());

            self::assertSame(2, (int) DB::table('wh_stock_in_sequences')->where('year', 2026)->value('last_number'));
            self::assertSame(1, (int) DB::table('wh_stock_in_sequences')->where('year', 2027)->value('last_number'));
        } finally {
            Carbon::setTestNow();
        }
    }
}

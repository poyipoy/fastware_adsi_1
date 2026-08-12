<?php

namespace Tests\Feature\Warehouse;

use App\Data\Warehouse\WarehouseDashboardFilter;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use App\Services\Warehouse\WarehouseDashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class WarehouseDashboardQueryTest extends WarehouseTestCase
{
    public function test_reversed_stock_out_does_not_inflate_top_usage_or_trend(): void
    {
        $user = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['current_stock' => '5.000']);
        $original = WarehouseStockTransaction::factory()->create([
            'transaction_number' => 'WH-ORIGINAL-'.Str::random(8), 'idempotency_key' => (string) Str::uuid(), 'transaction_type' => WarehouseTransactionType::OUT,
            'consumable_id' => $item->id, 'quantity' => '2.000', 'stock_before' => '5.000', 'stock_after' => '3.000', 'verified_user_id' => $user->id, 'created_by' => $user->id, 'transaction_at' => now(),
        ]);
        WarehouseStockTransaction::factory()->create([
            'transaction_number' => 'WH-REVERSAL-'.Str::random(8), 'idempotency_key' => (string) Str::uuid(), 'transaction_type' => WarehouseTransactionType::REVERSAL,
            'consumable_id' => $item->id, 'quantity' => '2.000', 'stock_before' => '3.000', 'stock_after' => '5.000', 'verified_user_id' => $user->id, 'created_by' => $user->id, 'transaction_at' => now(), 'reversal_of_id' => $original->id,
        ]);

        $filter = new WarehouseDashboardFilter(CarbonImmutable::now()->startOfDay(), CarbonImmutable::now()->endOfDay());
        $service = app(WarehouseDashboardService::class);

        self::assertCount(0, $service->topUsage($filter));
        self::assertCount(0, $service->movementTrend($filter));
    }

    public function test_movement_trend_for_view_keys_enum_rows_without_changing_raw_trend(): void
    {
        $user = $this->createUser();
        $item = WarehouseConsumable::factory()->create();
        $day = CarbonImmutable::now('Asia/Jakarta')->startOfDay();

        $this->movement($user, $item, WarehouseTransactionType::IN, '2.000', $day->addHours(8));
        $this->movement($user, $item, WarehouseTransactionType::OUT, '3.000', $day->addHours(9));
        $this->movement($user, $item, WarehouseTransactionType::IN, '4.000', $day->addDay()->addHours(8));

        $filter = new WarehouseDashboardFilter($day, $day->addDay()->endOfDay());
        $service = app(WarehouseDashboardService::class);
        $rawTrend = $service->movementTrend($filter);
        $viewTrend = $service->movementTrendForView($filter);

        self::assertSame('IN', $rawTrend->get($day->toDateString())->first()->transaction_type->value);
        self::assertSame('2.000', $viewTrend->get($day->toDateString())->get('IN')->quantity);
        self::assertSame('3.000', $viewTrend->get($day->toDateString())->get('OUT')->quantity);
        self::assertSame('4.000', $viewTrend->get($day->addDay()->toDateString())->get('IN')->quantity);
        self::assertNull($viewTrend->get($day->addDay()->toDateString())->get('OUT'));
    }

    public function test_current_month_summary_uses_calendar_bounds_and_excludes_reversal_pairs(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $user = $this->createUser();
            $item = WarehouseConsumable::factory()->create(['current_stock' => '10.000']);

            $this->movement($user, $item, WarehouseTransactionType::IN, '2.000', CarbonImmutable::parse('2026-08-01 00:00:00', 'Asia/Jakarta'));
            $this->movement($user, $item, WarehouseTransactionType::OUT, '3.000', CarbonImmutable::parse('2026-08-31 23:59:59', 'Asia/Jakarta'));
            $this->movement($user, $item, WarehouseTransactionType::IN, '5.000', CarbonImmutable::parse('2026-07-31 23:59:59', 'Asia/Jakarta'));
            $this->movement($user, $item, WarehouseTransactionType::OUT, '7.000', CarbonImmutable::parse('2026-09-01 00:00:00', 'Asia/Jakarta'));
            $reversedOriginal = $this->movement($user, $item, WarehouseTransactionType::IN, '99.000', CarbonImmutable::parse('2026-08-10 12:00:00', 'Asia/Jakarta'));
            $this->movement($user, $item, WarehouseTransactionType::REVERSAL, '99.000', CarbonImmutable::parse('2026-08-10 12:01:00', 'Asia/Jakarta'), $reversedOriginal->id);

            $filter = new WarehouseDashboardFilter(
                from: CarbonImmutable::parse('2026-07-01 00:00:00', 'Asia/Jakarta'),
                to: CarbonImmutable::parse('2026-07-31 23:59:59', 'Asia/Jakarta'),
                transactionType: WarehouseTransactionType::ADJUSTMENT->value,
            );
            $summary = app(WarehouseDashboardService::class)->summary($filter);
            $recentTransactions = app(WarehouseDashboardService::class)->recentTransactions(WarehouseDashboardFilter::currentMonth());

            self::assertSame(['quantity' => '2.000', 'transaction_count' => 1], $summary['stock_in_month']);
            self::assertSame(['quantity' => '3.000', 'transaction_count' => 1], $summary['stock_out_month']);
            self::assertSame(2, $recentTransactions->total());
            self::assertSame([
                'from' => '2026-08-01',
                'to' => '2026-08-31',
                'label' => 'August 2026',
            ], $summary['current_month']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    private function movement($user, WarehouseConsumable $item, WarehouseTransactionType $type, string $quantity, CarbonImmutable $at, ?int $reversalOf = null): WarehouseStockTransaction
    {
        return WarehouseStockTransaction::factory()->create([
            'transaction_number' => 'WH-MONTH-'.Str::upper(Str::random(10)),
            'idempotency_key' => (string) Str::uuid(),
            'transaction_type' => $type,
            'consumable_id' => $item->id,
            'quantity' => $quantity,
            'stock_before' => '10.000',
            'stock_after' => '10.000',
            'verified_user_id' => $user->id,
            'created_by' => $user->id,
            'transaction_at' => $at,
            'reversal_of_id' => $reversalOf,
        ]);
    }
}

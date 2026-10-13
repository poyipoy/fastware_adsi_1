<?php

namespace Tests\Feature\Warehouse;

use App\Data\Warehouse\WarehouseDashboardFilter;
use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use App\Services\Warehouse\WarehouseDashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class WarehouseMovementStockChartTest extends WarehouseTestCase
{
    public function test_movement_stock_chart_fills_missing_dates_with_zero(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-05 10:00:00', 'Asia/Jakarta'));

        try {
            $user = $this->createUser();
            $item = WarehouseConsumable::factory()->create(['item_name' => 'Zero Fill Item']);

            // Transaction on day 2026-08-01 (IN: 5)
            $this->transaction($user, $item, WarehouseTransactionType::IN, '5.000', CarbonImmutable::parse('2026-08-01 10:00:00', 'Asia/Jakarta'));

            // Transaction on day 2026-08-04 (OUT: 3)
            $this->transaction($user, $item, WarehouseTransactionType::OUT, '3.000', CarbonImmutable::parse('2026-08-04 10:00:00', 'Asia/Jakarta'));

            $filter = new WarehouseDashboardFilter(
                from: CarbonImmutable::parse('2026-08-01', 'Asia/Jakarta')->startOfDay(),
                to: CarbonImmutable::parse('2026-08-05', 'Asia/Jakarta')->endOfDay(),
            );

            /** @var WarehouseDashboardService $service */
            $service = app(WarehouseDashboardService::class);
            $chart = $service->movementStockChart($filter);

            self::assertCount(5, $chart['dates']);
            self::assertSame(['2026-08-01', '2026-08-02', '2026-08-03', '2026-08-04', '2026-08-05'], $chart['dates']);
            self::assertSame([5.0, 0.0, 0.0, 0.0, 0.0], $chart['stock_in']);
            self::assertSame([0.0, 0.0, 0.0, 3.0, 0.0], $chart['stock_out']);
            self::assertTrue($chart['has_data']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_movement_stock_chart_calculates_rolling_7_day_average_correctly(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-10 10:00:00', 'Asia/Jakarta'));

        try {
            $user = $this->createUser();
            $item = WarehouseConsumable::factory()->create();

            // Transactions prior to start date (July 31 and Aug 1)
            // Range will be Aug 3 to Aug 5
            // Prior day within 6 days: July 30 (outside 6 days), July 31 (inside 6 days for Aug 3..5), Aug 1, Aug 2
            // Aug 3 rolling window: July 28 - Aug 3
            // Let's place OUT=7 on Aug 1
            $this->transaction($user, $item, WarehouseTransactionType::OUT, '7.000', CarbonImmutable::parse('2026-08-01 10:00:00', 'Asia/Jakarta'));
            // And OUT=7 on Aug 3
            $this->transaction($user, $item, WarehouseTransactionType::OUT, '7.000', CarbonImmutable::parse('2026-08-03 10:00:00', 'Asia/Jakarta'));

            $filter = new WarehouseDashboardFilter(
                from: CarbonImmutable::parse('2026-08-03', 'Asia/Jakarta')->startOfDay(),
                to: CarbonImmutable::parse('2026-08-04', 'Asia/Jakarta')->endOfDay(),
            );

            /** @var WarehouseDashboardService $service */
            $service = app(WarehouseDashboardService::class);
            $chart = $service->movementStockChart($filter);

            // On 2026-08-03: past 7 days include Aug 1 (7) and Aug 3 (7) -> total 14 / 7 = 2.0
            self::assertSame(2.0, $chart['average_7_days'][0]);
            // On 2026-08-04: past 7 days include Aug 1 (7) and Aug 3 (7) and Aug 4 (0) -> total 14 / 7 = 2.0
            self::assertSame(2.0, $chart['average_7_days'][1]);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_movement_stock_chart_filters_by_machine_type(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-10 10:00:00', 'Asia/Jakarta'));

        try {
            $user = $this->createUser();
            $itemM1 = WarehouseConsumable::factory()->create();
            $itemM2 = WarehouseConsumable::factory()->create();

            // Stock OUT for Machine-A
            $tOut1 = $this->transaction($user, $itemM1, WarehouseTransactionType::OUT, '4.000', CarbonImmutable::parse('2026-08-02 11:00:00', 'Asia/Jakarta'));
            $tOut1->forceFill(['machine_type_used' => 'Machine-A'])->save();

            // Stock OUT for Machine-B
            $tOut2 = $this->transaction($user, $itemM2, WarehouseTransactionType::OUT, '8.000', CarbonImmutable::parse('2026-08-02 11:00:00', 'Asia/Jakarta'));
            $tOut2->forceFill(['machine_type_used' => 'Machine-B'])->save();

            $filter = new WarehouseDashboardFilter(
                from: CarbonImmutable::parse('2026-08-02', 'Asia/Jakarta')->startOfDay(),
                to: CarbonImmutable::parse('2026-08-02', 'Asia/Jakarta')->endOfDay(),
                machineType: 'Machine-A',
            );

            /** @var WarehouseDashboardService $service */
            $service = app(WarehouseDashboardService::class);
            $chart = $service->movementStockChart($filter);

            // Only Machine-A should be in the chart: OUT = 4
            self::assertSame([4.0], $chart['stock_out']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_stockout_machine_filter_isolates_top_items_while_top_machine_usage_remains_unfiltered(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-15 10:00:00', 'Asia/Jakarta'));

        try {
            $user = $this->createUser();
            $itemA = WarehouseConsumable::factory()->create(['item_name' => 'Tool A']);
            $itemB = WarehouseConsumable::factory()->create(['item_name' => 'Tool B']);

            // Out for Tool A on Lathe-1
            $t1 = $this->transaction($user, $itemA, WarehouseTransactionType::OUT, '15.000', CarbonImmutable::parse('2026-08-05 10:00:00', 'Asia/Jakarta'), machineTypeUsed: 'Lathe-1');

            // Out for Tool B on Milling-2
            $t2 = $this->transaction($user, $itemB, WarehouseTransactionType::OUT, '25.000', CarbonImmutable::parse('2026-08-05 10:00:00', 'Asia/Jakarta'), machineTypeUsed: 'Milling-2');

            $stockOutFilter = WarehouseDashboardFilter::fromStockOutMonthRequest(new \Illuminate\Http\Request(['stockout_month' => '2026-08']));

            /** @var WarehouseDashboardService $service */
            $service = app(WarehouseDashboardService::class);

            // When machine filter is Lathe-1, topUsage should only return Tool A
            $topItems = $service->topUsage($stockOutFilter, machineTypeUsed: 'Lathe-1');
            self::assertCount(1, $topItems);
            self::assertSame('Tool A', $topItems->first()->item_name);

            // topUsageByMachineType should NOT filter by machine and still show BOTH machines
            $topMachines = $service->topUsageByMachineType($stockOutFilter);
            self::assertCount(2, $topMachines);
            self::assertSame('Milling-2', $topMachines->first()->machine_type);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_adjustment_history_displays_and_filters_on_create_page(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['current_stock' => '10.000']);

        // Create an ADJUSTMENT transaction
        $this->actingAs($pic)->post(route('warehouse.adjustments.store'), [
            'consumable_id' => $item->id,
            'direction' => 'OUT',
            'item_condition' => 'NEW',
            'storage_location' => 'DS8',
            'quantity' => '2',
            'reason_category' => 'Audit',
            'reason' => 'Stock physical count discrepancy',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect(route('warehouse.adjustments.create'));

        // Visit adjustment page to verify table history
        $response = $this->actingAs($pic)->get(route('warehouse.adjustments.create'));

        $response->assertOk()
            ->assertSee('Riwayat Log Penyesuaian Stok')
            ->assertSee('Stock physical count discrepancy')
            ->assertSee('Tercatat')
            ->assertSee('OUT');

        // Test filtering by direction=IN (should not show the OUT transaction)
        $responseFiltered = $this->actingAs($pic)->get(route('warehouse.adjustments.create', ['adjustment_direction' => 'IN']));
        $responseFiltered->assertOk()
            ->assertSee('Belum ada penyesuaian')
            ->assertDontSee('Stock physical count discrepancy');
    }

    public function test_movement_machine_options_splits_comma_separated_machines_individually(): void
    {
        $uniqueSuffix = Str::random(5);
        $user = $this->createUser();
        $item = WarehouseConsumable::factory()->create();

        $this->transaction($user, $item, WarehouseTransactionType::OUT, '1.000', CarbonImmutable::now())
            ->forceFill(['machine_type_used' => "MC1_{$uniqueSuffix}, MC3_{$uniqueSuffix}, MC4_{$uniqueSuffix}"])->save();
        $this->transaction($user, $item, WarehouseTransactionType::OUT, '1.000', CarbonImmutable::now())
            ->forceFill(['machine_type_used' => "MC2_{$uniqueSuffix}"])->save();

        /** @var WarehouseDashboardService $service */
        $service = app(WarehouseDashboardService::class);
        $options = $service->movementMachineOptions();

        self::assertTrue($options->contains("MC1_{$uniqueSuffix}"));
        self::assertTrue($options->contains("MC2_{$uniqueSuffix}"));
        self::assertTrue($options->contains("MC3_{$uniqueSuffix}"));
        self::assertTrue($options->contains("MC4_{$uniqueSuffix}"));
        self::assertFalse($options->contains("MC1_{$uniqueSuffix}, MC3_{$uniqueSuffix}, MC4_{$uniqueSuffix}"));
    }

    public function test_movement_stock_chart_filters_individual_machine_from_comma_separated_item(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-10 10:00:00', 'Asia/Jakarta'));

        try {
            $user = $this->createUser();
            $uniqueSuffix = Str::random(5);
            $m1 = "M1_{$uniqueSuffix}";
            $m2 = "M2_{$uniqueSuffix}";
            $item = WarehouseConsumable::factory()->create();

            // Stock OUT specifically for m1
            $tOutM1 = $this->transaction($user, $item, WarehouseTransactionType::OUT, '6.000', CarbonImmutable::parse('2026-08-08 11:00:00', 'Asia/Jakarta'));
            $tOutM1->forceFill(['machine_type_used' => $m1])->save();

            // Stock OUT specifically for m2
            $tOutM2 = $this->transaction($user, $item, WarehouseTransactionType::OUT, '4.000', CarbonImmutable::parse('2026-08-08 12:00:00', 'Asia/Jakarta'));
            $tOutM2->forceFill(['machine_type_used' => $m2])->save();

            $filterM1 = new WarehouseDashboardFilter(
                from: CarbonImmutable::parse('2026-08-08', 'Asia/Jakarta')->startOfDay(),
                to: CarbonImmutable::parse('2026-08-08', 'Asia/Jakarta')->endOfDay(),
                machineType: $m1,
            );

            /** @var WarehouseDashboardService $service */
            $service = app(WarehouseDashboardService::class);
            $chartM1 = $service->movementStockChart($filterM1);

            // M1 chart should have OUT = 6.0 (only M1)
            self::assertSame([6.0], $chartM1['stock_out']);

            // Insert options for M1 should include this item
            $insertsM1 = $service->movementInsertOptions($m1);
            self::assertTrue($insertsM1->contains('id', $item->id));
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    private function transaction(
        $user,
        WarehouseConsumable $item,
        WarehouseTransactionType $type,
        string $quantity,
        $at,
        ?int $reversalOf = null,
        WarehouseItemCondition $condition = WarehouseItemCondition::NEW,
        ?string $machineTypeUsed = null,
    ): WarehouseStockTransaction {
        $before = $type === WarehouseTransactionType::OUT ? '10.000' : '0.000';
        $after = $type === WarehouseTransactionType::OUT ? '9.000' : $quantity;

        return WarehouseStockTransaction::factory()->create([
            'transaction_number' => 'WH-TEST-'.Str::upper(Str::random(10)),
            'idempotency_key' => (string) Str::uuid(),
            'transaction_type' => $type,
            'item_condition' => $condition,
            'consumable_id' => $item->id,
            'quantity' => $quantity,
            'stock_before' => $before,
            'stock_after' => $after,
            'machine_type_used' => $machineTypeUsed,
            'verified_user_id' => $user->id,
            'created_by' => $user->id,
            'transaction_at' => $at,
            'reversal_of_id' => $reversalOf,
        ]);
    }
}

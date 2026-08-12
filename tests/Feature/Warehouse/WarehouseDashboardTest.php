<?php

namespace Tests\Feature\Warehouse;

use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class WarehouseDashboardTest extends WarehouseTestCase
{
    public function test_dashboard_kpis_use_current_stock_and_month_boundaries(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            WarehouseConsumable::factory()->create(['item_code' => 'HEALTHY-1', 'barcode' => 'DASH-H', 'current_stock' => '10.000', 'minimum_stock' => '2.000']);
            WarehouseConsumable::factory()->create(['item_code' => 'LOW-1', 'barcode' => 'DASH-L', 'current_stock' => '2.000', 'minimum_stock' => '3.000']);
            WarehouseConsumable::factory()->create(['item_code' => 'OUT-1', 'barcode' => 'DASH-O', 'current_stock' => '0.000', 'minimum_stock' => '1.000']);
            $inItem = WarehouseConsumable::factory()->create(['item_code' => 'IN-MONTH', 'barcode' => 'DASH-IN', 'current_stock' => '4.000']);
            $outItem = WarehouseConsumable::factory()->create(['item_code' => 'OUT-MONTH', 'barcode' => 'DASH-OUT', 'current_stock' => '3.000']);

            $this->transaction($employee, $inItem, WarehouseTransactionType::IN, '2.000', CarbonImmutable::parse('2026-08-01 00:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $outItem, WarehouseTransactionType::OUT, '1.000', CarbonImmutable::parse('2026-08-31 23:59:59', 'Asia/Jakarta'));
            $trendOutItem = WarehouseConsumable::factory()->create(['item_name' => 'Trend Stock Out']);
            $this->transaction($employee, $trendOutItem, WarehouseTransactionType::OUT, '1.000', CarbonImmutable::parse('2026-08-10 08:00:00', 'Asia/Jakarta'));

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard'));

            $response->assertOk()
                ->assertSee('Barang aktif')
                ->assertSee('Stok aman')
                ->assertSee('Stock In Bulan Ini')
                ->assertSee('Stock Out Bulan Ini')
                ->assertSee('Agustus 2026')
                ->assertSee('Tren Stock In/Out')
                ->assertSee('bi bi-funnel', false)
                ->assertDontSee('Filter Dashboard')
                ->assertDontSee('2026-07-13 — 2026-08-11')
                ->assertDontSee('Stock In Today')
                ->assertDontSee('Stock Out Today')
                ->assertSee('>2<', false)
                ->assertSee('>1<', false)
                ->assertSee('<td>2026-08-01</td><td>2</td><td>0</td></tr>', false)
                ->assertSee('<td>2026-08-10</td><td>0</td><td>1</td></tr>', false);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_dashboard_rejects_invalid_trend_date_range(): void
    {
        $employee = $this->createUser();

        $this->actingAs($employee)->get(route('warehouse.dashboard', ['trend_date_from' => '2026-08-10', 'trend_date_to' => '2026-08-01']))
            ->assertSessionHasErrors('trend_date_to');
    }

    public function test_trend_filter_only_changes_trend_data(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $inPeriodItem = WarehouseConsumable::factory()->create(['item_name' => 'Trend In Period']);
            $defaultUsageItem = WarehouseConsumable::factory()->create(['item_name' => 'Default Usage Item']);
            $lowStockItem = WarehouseConsumable::factory()->create(['item_name' => 'Low Stock Item', 'current_stock' => '1.000', 'minimum_stock' => '2.000']);

            $this->transaction($employee, $inPeriodItem, WarehouseTransactionType::IN, '2.000', CarbonImmutable::parse('2026-08-07 08:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $defaultUsageItem, WarehouseTransactionType::OUT, '5.000', CarbonImmutable::parse('2026-08-01 08:00:00', 'Asia/Jakarta'));

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard', [
                'trend_date_from' => '2026-08-07',
                'trend_date_to' => '2026-08-07',
            ]));

            $trend = $response->viewData('trend');
            $topUsage = $response->viewData('topUsage');
            $lowStock = $response->viewData('lowStock');
            $recentTransactions = $response->viewData('recentTransactions');
            $summary = $response->viewData('summary');

            $response->assertOk()
                ->assertSee('aria-label="Filter tren aktif"', false)
                ->assertSee('value="2026-08-07"', false);
            self::assertSame(['2026-08-07'], $trend->keys()->all());
            self::assertSame('2.000', (string) $trend->get('2026-08-07')->get('IN')->quantity);
            self::assertTrue($topUsage->pluck('id')->contains($defaultUsageItem->id));
            self::assertTrue($lowStock->getCollection()->pluck('id')->contains($lowStockItem->id));
            self::assertTrue($recentTransactions->getCollection()->pluck('consumable_id')->contains($defaultUsageItem->id));
            self::assertSame('2026-07-13', $summary['period']['from']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_dashboard_is_empty_state_safe(): void
    {
        $employee = $this->createUser();

        $this->actingAs($employee)->get(route('warehouse.dashboard'))
            ->assertOk()
            ->assertSee('Belum ada transaksi bulan ini.')
            ->assertSee('Belum ada Stock Out')
            ->assertSee('Tren Stock In/Out')
            ->assertSee('>Filter<', false)
            ->assertDontSee('Filter Dashboard');
    }

    public function test_recent_transactions_show_adjustment_direction(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $item = WarehouseConsumable::factory()->create(['item_name' => 'Adjustment Direction Item']);

            WarehouseStockTransaction::factory()->create([
                'transaction_type' => WarehouseTransactionType::ADJUSTMENT,
                'consumable_id' => $item->id,
                'quantity' => '5.000',
                'stock_before' => '10.000',
                'stock_after' => '15.000',
                'verified_user_id' => $employee->id,
                'created_by' => $employee->id,
                'transaction_at' => CarbonImmutable::parse('2026-08-11 08:00:00', 'Asia/Jakarta'),
            ]);
            WarehouseStockTransaction::factory()->create([
                'transaction_type' => WarehouseTransactionType::ADJUSTMENT,
                'consumable_id' => $item->id,
                'quantity' => '2.000',
                'stock_before' => '15.000',
                'stock_after' => '13.000',
                'verified_user_id' => $employee->id,
                'created_by' => $employee->id,
                'transaction_at' => CarbonImmutable::parse('2026-08-11 09:00:00', 'Asia/Jakarta'),
            ]);

            $this->actingAs($employee)->get(route('warehouse.dashboard'))
                ->assertOk()
                ->assertSee('class="d-block warehouse-transaction-direction">Stock In</small>', false)
                ->assertSee('class="d-block warehouse-transaction-direction">Stock Out</small>', false);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_dashboard_recent_transactions_are_limited_to_current_month_and_ignore_trend_filter(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $currentIn = WarehouseConsumable::factory()->create(['item_name' => 'Current Month In']);
            $currentOut = WarehouseConsumable::factory()->create(['item_name' => 'Current Month Out']);
            $previousMonth = WarehouseConsumable::factory()->create(['item_name' => 'Previous Month Movement']);
            $nextMonth = WarehouseConsumable::factory()->create(['item_name' => 'Next Month Movement']);

            $this->transaction($employee, $currentIn, WarehouseTransactionType::IN, '2.000', CarbonImmutable::parse('2026-08-01 00:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $currentOut, WarehouseTransactionType::OUT, '1.000', CarbonImmutable::parse('2026-08-31 23:59:59', 'Asia/Jakarta'));
            $this->transaction($employee, $previousMonth, WarehouseTransactionType::IN, '5.000', CarbonImmutable::parse('2026-07-31 23:59:59', 'Asia/Jakarta'));
            $this->transaction($employee, $nextMonth, WarehouseTransactionType::OUT, '7.000', CarbonImmutable::parse('2026-09-01 00:00:00', 'Asia/Jakarta'));

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard', [
                'trend_date_from' => '2026-07-01',
                'trend_date_to' => '2026-07-31',
            ]));

            $recentTransactions = $response->viewData('recentTransactions');

            $response->assertOk()
                ->assertSee('Pergerakan bulan berjalan.')
                ->assertSee('Agustus 2026');
            self::assertSame(2, $recentTransactions->total());
            self::assertSame(
                [$currentIn->id, $currentOut->id],
                $recentTransactions->getCollection()->pluck('consumable_id')->sort()->values()->all(),
            );
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_dashboard_recent_transactions_paginate_current_month_and_keep_trend_query_string(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $item = WarehouseConsumable::factory()->create(['item_name' => 'Paginated Month Item']);
            $monthStart = CarbonImmutable::parse('2026-08-01 00:00:00', 'Asia/Jakarta');

            for ($index = 1; $index <= 11; $index++) {
                $this->transaction(
                    $employee,
                    $item,
                    $index % 2 === 0 ? WarehouseTransactionType::OUT : WarehouseTransactionType::IN,
                    '1.000',
                    $monthStart->addMinutes($index),
                );
            }

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard', [
                'trend_date_from' => '2026-07-01',
                'trend_date_to' => '2026-07-31',
                'transaction_page' => 2,
            ]));

            $recentTransactions = $response->viewData('recentTransactions');
            $secondPageUrl = html_entity_decode($recentTransactions->url(2));

            $response->assertOk()
                ->assertSee('class="page-link"', false)
                ->assertSee('Sebelumnya')
                ->assertSee('Berikutnya')
                ->assertDontSee('w-5 h-5', false);
            self::assertSame(2, $recentTransactions->currentPage());
            self::assertSame(10, $recentTransactions->perPage());
            self::assertSame(11, $recentTransactions->total());
            self::assertCount(1, $recentTransactions->items());
            self::assertStringContainsString('transaction_page=2', $secondPageUrl);
            self::assertStringContainsString('trend_date_from=2026-07-01', $secondPageUrl);
            self::assertStringContainsString('trend_date_to=2026-07-31', $secondPageUrl);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_dashboard_data_keeps_today_compatibility_keys_and_exposes_current_month(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $trendItem = WarehouseConsumable::factory()->create(['item_name' => 'Trend API Item']);
            $this->transaction($employee, $trendItem, WarehouseTransactionType::IN, '4.000', CarbonImmutable::parse('2026-08-05 08:00:00', 'Asia/Jakarta'));

            $this->actingAs($employee)->getJson(route('warehouse.dashboard.data'))
                ->assertOk()
                ->assertJsonPath('summary.current_month.from', '2026-08-01')
                ->assertJsonPath('summary.current_month.to', '2026-08-31')
                ->assertJsonPath('summary.current_month.label', 'August 2026')
                ->assertJsonStructure([
                    'summary' => [
                        'stock_in_today' => ['quantity', 'transaction_count'],
                        'stock_out_today' => ['quantity', 'transaction_count'],
                        'stock_in_month' => ['quantity', 'transaction_count'],
                        'stock_out_month' => ['quantity', 'transaction_count'],
                    ],
                ]);

            $payload = $this->actingAs($employee)->getJson(route('warehouse.dashboard.data'))->json();
            self::assertSame('IN', $payload['trend']['2026-08-05'][0]['transaction_type']);

            $legacyFilteredPayload = $this->actingAs($employee)->getJson(route('warehouse.dashboard.data', [
                'date_from' => '2026-08-05',
                'date_to' => '2026-08-05',
            ]))->assertOk()->json();
            self::assertArrayHasKey('2026-08-05', $legacyFilteredPayload['trend']);
            self::assertSame('IN', $legacyFilteredPayload['trend']['2026-08-05'][0]['transaction_type']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    private function transaction($user, WarehouseConsumable $item, WarehouseTransactionType $type, string $quantity, $at, ?int $reversalOf = null): WarehouseStockTransaction
    {
        $before = $type === WarehouseTransactionType::OUT ? '10.000' : '0.000';
        $after = $type === WarehouseTransactionType::OUT ? '9.000' : $quantity;

        return WarehouseStockTransaction::factory()->create([
            'transaction_number' => 'WH-DASH-'.Str::upper(Str::random(10)),
            'idempotency_key' => (string) Str::uuid(),
            'transaction_type' => $type,
            'consumable_id' => $item->id,
            'quantity' => $quantity,
            'stock_before' => $before,
            'stock_after' => $after,
            'verified_user_id' => $user->id,
            'created_by' => $user->id,
            'transaction_at' => $at,
            'reversal_of_id' => $reversalOf,
        ]);
    }
}

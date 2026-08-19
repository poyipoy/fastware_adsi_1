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

    public function test_analytics_filter_changes_trend_and_top_usage_but_not_inventory_kpis(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $inPeriodItem = WarehouseConsumable::factory()->create(['item_name' => 'Trend In Period']);
            $defaultUsageItem = WarehouseConsumable::factory()->create(['item_name' => 'Default Usage Item', 'machine_type' => 'Press']);
            $lowStockItem = WarehouseConsumable::factory()->create(['item_name' => 'Low Stock Item', 'current_stock' => '1.000', 'minimum_stock' => '2.000']);

            $this->transaction($employee, $inPeriodItem, WarehouseTransactionType::IN, '2.000', CarbonImmutable::parse('2026-08-07 08:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $defaultUsageItem, WarehouseTransactionType::OUT, '5.000', CarbonImmutable::parse('2026-08-01 08:00:00', 'Asia/Jakarta'));

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard', [
                'trend_date_from' => '2026-08-07',
                'trend_date_to' => '2026-08-07',
            ]));

            $trend = $response->viewData('trend');
            $topUsage = $response->viewData('topUsage');
            $topMachineUsage = $response->viewData('topMachineUsage');
            $lowStock = $response->viewData('lowStock');
            $summary = $response->viewData('summary');

            $response->assertOk()
                ->assertSee('aria-label="Filter tren aktif"', false)
                ->assertSee('value="2026-08-07"', false);
            self::assertSame(['2026-08-07'], $trend->keys()->all());
            self::assertSame('2.000', (string) $trend->get('2026-08-07')->get('IN')->quantity);
            self::assertFalse($topUsage->pluck('id')->contains($defaultUsageItem->id));
            self::assertTrue($topMachineUsage->isEmpty());
            self::assertTrue($lowStock->getCollection()->pluck('id')->contains($lowStockItem->id));
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
            ->assertSee('Belum ada pergerakan')
            ->assertSee('Belum ada data Stock Out pada periode ini.')
            ->assertSee('Tren Stock In/Out')
            ->assertSee('Filter periode')
            ->assertDontSee('Filter Dashboard');
    }

    public function test_dashboard_combines_item_and_machine_stock_out_labels_in_one_chart(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $item = WarehouseConsumable::factory()->create(['item_name' => 'Dashboard Item', 'machine_type' => null]);
            $machineItem = WarehouseConsumable::factory()->create(['item_name' => 'Machine Item', 'machine_type' => 'Press']);

            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '2.000', CarbonImmutable::now('Asia/Jakarta'));
            $this->transaction($employee, $machineItem, WarehouseTransactionType::OUT, '5.000', CarbonImmutable::now('Asia/Jakarta'));

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard'));

            $response->assertOk()
                ->assertSee('warehouse-top-stock-out-data', false)
                ->assertDontSee('warehouse-top-item-data', false)
                ->assertDontSee('warehouse-top-machine-data', false)
                ->assertSee('Item · Dashboard Item', false)
                ->assertSee('Tipe Mesin · Press', false)
                ->assertSee('Dashboard Item')
                ->assertSee('Press');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_dashboard_removes_recent_transactions_panel(): void
    {
        $employee = $this->createUser();

        $response = $this->actingAs($employee)->get(route('warehouse.dashboard'));

        $response->assertOk()
            ->assertDontSee('Transaksi Terbaru')
            ->assertDontSee('Pergerakan bulan berjalan.');
        self::assertArrayNotHasKey('recentTransactions', $response->original->getData());
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
                    'top_usage_by_machine_type',
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

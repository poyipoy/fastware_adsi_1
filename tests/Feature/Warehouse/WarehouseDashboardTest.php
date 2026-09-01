<?php

namespace Tests\Feature\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class WarehouseDashboardTest extends WarehouseTestCase
{
    public function test_stock_attention_note_is_explicitly_saved_and_survives_healthy_status(): void
    {
        $user = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'item_name' => 'Attention Note Item',
            'item_code' => 'ATTENTION-NOTE',
            'current_stock' => '1.000',
            'minimum_stock' => '2.000',
            'stock_attention_note' => null,
        ]);

        $this->actingAs($user)->patch(route('warehouse.dashboard.stock-attention.update', $item), [
            'stock_attention_note' => '  Follow up dengan Purchasing  ',
        ])->assertRedirect(route('warehouse.dashboard'));

        $item->refresh();
        self::assertSame('Follow up dengan Purchasing', $item->stock_attention_note);
        self::assertSame((int) $user->getKey(), (int) $item->updated_by);
        $this->actingAs($user)->get(route('warehouse.dashboard'))
            ->assertOk()
            ->assertSee('Attention Note Item')
            ->assertSee('Follow up dengan Purchasing')
            ->assertSee('Simpan');

        $item->update(['current_stock' => '10.000']);
        $this->actingAs($user)->get(route('warehouse.dashboard'))
            ->assertOk()
            ->assertDontSee('Attention Note Item');
        self::assertSame('Follow up dengan Purchasing', (string) $item->fresh()->stock_attention_note);

        $item->update(['current_stock' => '1.000']);
        $this->actingAs($user)->patch(route('warehouse.dashboard.stock-attention.update', $item), [
            'stock_attention_note' => '',
        ])->assertRedirect(route('warehouse.dashboard'));

        self::assertNull($item->fresh()->stock_attention_note);
    }

    public function test_stock_attention_note_requires_warehouse_access(): void
    {
        $user = $this->createUser([], false);
        $item = WarehouseConsumable::factory()->create(['current_stock' => '1.000', 'minimum_stock' => '2.000']);

        $this->actingAs($user)->patch(route('warehouse.dashboard.stock-attention.update', $item), [
            'stock_attention_note' => 'Tidak boleh',
        ])->assertForbidden();
    }

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
                ->assertSee('Stok Habis')
                ->assertSee('Stok Menipis')
                ->assertSee('Stock In')
                ->assertSee('Stock Out')
                ->assertSee('Hari ini')
                ->assertSee('Bulan ini')
                ->assertDontSee('Barang aktif')
                ->assertDontSee('Stok aman')
                ->assertDontSee('Stock In Bulan Ini')
                ->assertDontSee('Stock Out Bulan Ini')
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

    public function test_analytics_filter_changes_trend_without_altering_stock_out_rankings_or_inventory_kpis(): void
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
            self::assertTrue($topUsage->pluck('id')->contains($defaultUsageItem->id));
            self::assertSame('5.000', (string) $topMachineUsage->firstWhere('machine_type', 'Press')?->quantity);
            self::assertTrue($lowStock->getCollection()->pluck('id')->contains($lowStockItem->id));
            self::assertSame('2026-07-13', $summary['period']['from']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_default_top_stock_out_uses_current_calendar_month(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $julyItem = WarehouseConsumable::factory()->create(['item_name' => 'July Stock Out Item']);
            $augustItem = WarehouseConsumable::factory()->create(['item_name' => 'August Stock Out Item']);

            $this->transaction($employee, $julyItem, WarehouseTransactionType::OUT, '3.000', CarbonImmutable::parse('2026-07-15 10:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $augustItem, WarehouseTransactionType::OUT, '4.000', CarbonImmutable::parse('2026-08-05 10:00:00', 'Asia/Jakarta'));

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard'));

            $response->assertOk();
            $stockOutFilter = $response->viewData('stockOutFilter');
            self::assertSame('2026-08-01', $stockOutFilter->from->toDateString());
            self::assertSame('2026-08-31', $stockOutFilter->to->toDateString());

            $topUsage = $response->viewData('topUsage');
            self::assertTrue($topUsage->pluck('id')->contains($augustItem->id));
            self::assertFalse($topUsage->pluck('id')->contains($julyItem->id));
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_stockout_month_filter_is_independent_from_trend(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $juneItem = WarehouseConsumable::factory()->create(['item_name' => 'June Trend Item']);
            $julyItem = WarehouseConsumable::factory()->create(['item_name' => 'July Stock Out Item']);
            $augustItem = WarehouseConsumable::factory()->create(['item_name' => 'August Stock Out Item']);

            $this->transaction($employee, $juneItem, WarehouseTransactionType::IN, '10.000', CarbonImmutable::parse('2026-06-10 10:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $julyItem, WarehouseTransactionType::OUT, '5.000', CarbonImmutable::parse('2026-07-15 10:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $augustItem, WarehouseTransactionType::OUT, '7.000', CarbonImmutable::parse('2026-08-05 10:00:00', 'Asia/Jakarta'));

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard', [
                'trend_date_from' => '2026-06-01',
                'trend_date_to' => '2026-06-30',
                'stockout_month' => '2026-07',
            ]));

            $response->assertOk();
            $trend = $response->viewData('trend');
            $topUsage = $response->viewData('topUsage');

            self::assertSame(['2026-06-10'], $trend->keys()->all());
            self::assertTrue($topUsage->pluck('id')->contains($julyItem->id));
            self::assertFalse($topUsage->pluck('id')->contains($augustItem->id));
            self::assertFalse($topUsage->pluck('id')->contains($juneItem->id));
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_single_stockout_month_filter_controls_both_item_and_machine_rankings(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $itemA = WarehouseConsumable::factory()->create(['item_name' => 'Item A', 'machine_type' => 'Press']);
            $itemB = WarehouseConsumable::factory()->create(['item_name' => 'Item B', 'machine_type' => 'Cutting']);

            $this->transaction($employee, $itemA, WarehouseTransactionType::OUT, '5.000', CarbonImmutable::parse('2026-07-10 10:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $itemB, WarehouseTransactionType::OUT, '10.000', CarbonImmutable::parse('2026-08-05 10:00:00', 'Asia/Jakarta'));

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard', [
                'stockout_month' => '2026-07',
            ]));

            $response->assertOk();
            $topUsage = $response->viewData('topUsage');
            $topMachineUsage = $response->viewData('topMachineUsage');

            self::assertTrue($topUsage->pluck('id')->contains($itemA->id));
            self::assertFalse($topUsage->pluck('id')->contains($itemB->id));
            self::assertTrue($topMachineUsage->pluck('machine_type')->contains('Press'));
            self::assertFalse($topMachineUsage->pluck('machine_type')->contains('Cutting'));
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_top_stock_out_only_counts_new_condition_and_ignores_used(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $itemMixed = WarehouseConsumable::factory()->create(['item_name' => 'Mixed Item', 'machine_type' => 'Press']);
            $itemUsedOnly = WarehouseConsumable::factory()->create(['item_name' => 'Used Only Item', 'machine_type' => 'Cutting']);

            $this->transaction($employee, $itemMixed, WarehouseTransactionType::OUT, '5.000', CarbonImmutable::parse('2026-08-05 10:00:00', 'Asia/Jakarta'), condition: WarehouseItemCondition::NEW);
            $this->transaction($employee, $itemMixed, WarehouseTransactionType::OUT, '20.000', CarbonImmutable::parse('2026-08-05 11:00:00', 'Asia/Jakarta'), condition: WarehouseItemCondition::USED);
            $this->transaction($employee, $itemUsedOnly, WarehouseTransactionType::OUT, '50.000', CarbonImmutable::parse('2026-08-06 10:00:00', 'Asia/Jakarta'), condition: WarehouseItemCondition::USED);

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard'));

            $response->assertOk();
            $topUsage = $response->viewData('topUsage');
            $topMachineUsage = $response->viewData('topMachineUsage');

            $mixedUsage = $topUsage->firstWhere('id', $itemMixed->id);
            self::assertNotNull($mixedUsage);
            self::assertSame('5.000', (string) $mixedUsage->quantity);
            self::assertFalse($topUsage->pluck('id')->contains($itemUsedOnly->id));

            $pressUsage = $topMachineUsage->firstWhere('machine_type', 'Press');
            self::assertNotNull($pressUsage);
            self::assertSame('5.000', (string) $pressUsage->quantity);
            self::assertFalse($topMachineUsage->pluck('machine_type')->contains('Cutting'));
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_reversal_pair_is_excluded_from_top_stock_out_rankings(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $item = WarehouseConsumable::factory()->create(['item_name' => 'Reversal Test Item']);

            $original = $this->transaction($employee, $item, WarehouseTransactionType::OUT, '10.000', CarbonImmutable::parse('2026-08-02 10:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $item, WarehouseTransactionType::REVERSAL, '10.000', CarbonImmutable::parse('2026-08-02 10:05:00', 'Asia/Jakarta'), reversalOf: $original->id);
            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '3.000', CarbonImmutable::parse('2026-08-05 10:00:00', 'Asia/Jakarta'));

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard'));

            $response->assertOk();
            $topUsage = $response->viewData('topUsage');
            $usage = $topUsage->firstWhere('id', $item->id);

            self::assertNotNull($usage);
            self::assertSame('3.000', (string) $usage->quantity);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_dashboard_rejects_future_stockout_month(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();

            $this->actingAs($employee)->get(route('warehouse.dashboard', ['stockout_month' => '2026-09']))
                ->assertSessionHasErrors('stockout_month');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_dashboard_rejects_invalid_stockout_month_format(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();

            $this->actingAs($employee)->get(route('warehouse.dashboard', ['stockout_month' => '2026-08-01']))
                ->assertSessionHasErrors('stockout_month');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_average_consume_starts_from_item_created_month_including_zero_and_current_month(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $item = WarehouseConsumable::factory()->create([
                'item_name' => 'Average Consume Item',
                'created_at' => CarbonImmutable::parse('2026-05-20 14:00:00', 'Asia/Jakarta'),
                'current_stock' => '1.000',
                'minimum_stock' => '5.000',
            ]);

            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '10.000', CarbonImmutable::parse('2026-05-22 10:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '20.000', CarbonImmutable::parse('2026-06-15 10:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '30.000', CarbonImmutable::parse('2026-07-10 10:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '5.000', CarbonImmutable::parse('2026-08-05 10:00:00', 'Asia/Jakarta'));

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard'));

            $response->assertOk();
            $lowStock = $response->viewData('lowStock');
            $loadedItem = $lowStock->getCollection()->firstWhere('id', $item->id);

            self::assertNotNull($loadedItem);
            // 65 total / 4 months (May, Jun, Jul, Aug) = 16.25 -> ceil = 17
            self::assertSame(17, $loadedItem->average_consume);
            $response->assertSee('17 pcs/bulan');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_average_consume_includes_zero_consumption_months_in_denominator(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-20 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $item = WarehouseConsumable::factory()->create([
                'item_name' => 'Zero Months Item',
                'created_at' => CarbonImmutable::parse('2026-01-10 09:00:00', 'Asia/Jakarta'),
                'current_stock' => '1.000',
                'minimum_stock' => '5.000',
            ]);

            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '30.000', CarbonImmutable::parse('2026-03-15 10:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '60.000', CarbonImmutable::parse('2026-05-02 10:00:00', 'Asia/Jakarta'));

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard'));

            $response->assertOk();
            $loadedItem = $response->viewData('lowStock')->getCollection()->firstWhere('id', $item->id);

            self::assertNotNull($loadedItem);
            self::assertSame(18, $loadedItem->average_consume);
            $response->assertSee('18 pcs/bulan');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_average_consume_is_always_ceiled_to_integer(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $itemFraction = WarehouseConsumable::factory()->create([
                'item_name' => 'Fraction Item',
                'created_at' => CarbonImmutable::parse('2026-05-01 00:00:00', 'Asia/Jakarta'),
                'current_stock' => '1.000',
                'minimum_stock' => '5.000',
            ]);
            $itemExact = WarehouseConsumable::factory()->create([
                'item_name' => 'Exact Item',
                'created_at' => CarbonImmutable::parse('2026-05-01 00:00:00', 'Asia/Jakarta'),
                'current_stock' => '1.000',
                'minimum_stock' => '5.000',
            ]);

            $this->transaction($employee, $itemFraction, WarehouseTransactionType::OUT, '65.000', CarbonImmutable::parse('2026-08-01 10:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $itemExact, WarehouseTransactionType::OUT, '68.000', CarbonImmutable::parse('2026-08-01 10:00:00', 'Asia/Jakarta'));

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard'));

            $response->assertOk();
            $lowStock = $response->viewData('lowStock');
            $loadedFraction = $lowStock->getCollection()->firstWhere('id', $itemFraction->id);
            $loadedExact = $lowStock->getCollection()->firstWhere('id', $itemExact->id);

            self::assertSame(17, $loadedFraction->average_consume);
            self::assertSame(17, $loadedExact->average_consume);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_average_consume_is_zero_when_item_has_no_stock_out_history(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $item = WarehouseConsumable::factory()->create([
                'item_name' => 'No History Item',
                'created_at' => CarbonImmutable::parse('2026-03-01 00:00:00', 'Asia/Jakarta'),
                'current_stock' => '0.000',
                'minimum_stock' => '5.000',
            ]);

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard'));

            $response->assertOk();
            $loadedItem = $response->viewData('lowStock')->getCollection()->firstWhere('id', $item->id);

            self::assertNotNull($loadedItem);
            self::assertSame(0, $loadedItem->average_consume);
            $response->assertSee('0 pcs/bulan');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_average_consume_counts_only_new_condition(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $item = WarehouseConsumable::factory()->create([
                'item_name' => 'New vs Used Average Item',
                'created_at' => CarbonImmutable::parse('2026-08-01 00:00:00', 'Asia/Jakarta'),
                'current_stock' => '1.000',
                'minimum_stock' => '5.000',
            ]);

            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '5.000', CarbonImmutable::parse('2026-08-05 10:00:00', 'Asia/Jakarta'), condition: WarehouseItemCondition::NEW);
            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '50.000', CarbonImmutable::parse('2026-08-05 11:00:00', 'Asia/Jakarta'), condition: WarehouseItemCondition::USED);

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard'));

            $response->assertOk();
            $loadedItem = $response->viewData('lowStock')->getCollection()->firstWhere('id', $item->id);

            self::assertNotNull($loadedItem);
            self::assertSame(5, $loadedItem->average_consume);
            $response->assertSee('5 pcs/bulan');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_average_consume_excludes_reversed_movements(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $item = WarehouseConsumable::factory()->create([
                'item_name' => 'Reversed Movement Average Item',
                'created_at' => CarbonImmutable::parse('2026-08-01 00:00:00', 'Asia/Jakarta'),
                'current_stock' => '1.000',
                'minimum_stock' => '5.000',
            ]);

            $original = $this->transaction($employee, $item, WarehouseTransactionType::OUT, '20.000', CarbonImmutable::parse('2026-08-02 10:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $item, WarehouseTransactionType::REVERSAL, '20.000', CarbonImmutable::parse('2026-08-02 10:05:00', 'Asia/Jakarta'), reversalOf: $original->id);
            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '4.000', CarbonImmutable::parse('2026-08-05 10:00:00', 'Asia/Jakarta'));

            $response = $this->actingAs($employee)->get(route('warehouse.dashboard'));

            $response->assertOk();
            $loadedItem = $response->viewData('lowStock')->getCollection()->firstWhere('id', $item->id);

            self::assertNotNull($loadedItem);
            self::assertSame(4, $loadedItem->average_consume);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_average_consume_is_not_affected_by_stockout_month_filter(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $item = WarehouseConsumable::factory()->create([
                'item_name' => 'Filter Immune Item',
                'created_at' => CarbonImmutable::parse('2026-05-01 00:00:00', 'Asia/Jakarta'),
                'current_stock' => '1.000',
                'minimum_stock' => '5.000',
            ]);

            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '20.000', CarbonImmutable::parse('2026-07-15 10:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '20.000', CarbonImmutable::parse('2026-08-05 10:00:00', 'Asia/Jakarta'));

            $resA = $this->actingAs($employee)->get(route('warehouse.dashboard', ['stockout_month' => '2026-07']));
            $resB = $this->actingAs($employee)->get(route('warehouse.dashboard', ['stockout_month' => '2026-08']));

            $avgA = $resA->viewData('lowStock')->getCollection()->firstWhere('id', $item->id)?->average_consume;
            $avgB = $resB->viewData('lowStock')->getCollection()->firstWhere('id', $item->id)?->average_consume;

            self::assertSame($avgA, $avgB);
            self::assertSame(10, $avgA);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_average_consume_is_not_affected_by_trend_filter(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $item = WarehouseConsumable::factory()->create([
                'item_name' => 'Trend Immune Item',
                'created_at' => CarbonImmutable::parse('2026-05-01 00:00:00', 'Asia/Jakarta'),
                'current_stock' => '1.000',
                'minimum_stock' => '5.000',
            ]);

            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '20.000', CarbonImmutable::parse('2026-07-15 10:00:00', 'Asia/Jakarta'));
            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '20.000', CarbonImmutable::parse('2026-08-05 10:00:00', 'Asia/Jakarta'));

            $resA = $this->actingAs($employee)->get(route('warehouse.dashboard', ['trend_date_from' => '2026-06-01', 'trend_date_to' => '2026-06-30']));
            $resB = $this->actingAs($employee)->get(route('warehouse.dashboard', ['trend_date_from' => '2026-07-01', 'trend_date_to' => '2026-07-31']));

            $avgA = $resA->viewData('lowStock')->getCollection()->firstWhere('id', $item->id)?->average_consume;
            $avgB = $resB->viewData('lowStock')->getCollection()->firstWhere('id', $item->id)?->average_consume;

            self::assertSame($avgA, $avgB);
            self::assertSame(10, $avgA);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_low_stock_table_displays_maximum_column_and_value(): void
    {
        $employee = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'item_name' => 'Max Stock Item',
            'current_stock' => '2.000',
            'minimum_stock' => '5.000',
            'maximum_stock' => '20.000',
        ]);

        $response = $this->actingAs($employee)->get(route('warehouse.dashboard'));

        $response->assertOk()
            ->assertSee('Maximum')
            ->assertSee('Average Consume')
            ->assertSee('Max Stock Item');
    }

    public function test_low_stock_table_displays_dash_for_null_maximum(): void
    {
        $employee = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'item_name' => 'Null Max Item',
            'current_stock' => '2.000',
            'minimum_stock' => '5.000',
            'maximum_stock' => null,
        ]);

        $response = $this->actingAs($employee)->get(route('warehouse.dashboard'));

        $response->assertOk()
            ->assertSee('Null Max Item')
            ->assertSee('—');
    }

    public function test_existing_stock_status_logic_is_not_changed_by_maximum_stock(): void
    {
        $healthy = WarehouseConsumable::factory()->make([
            'current_stock' => '10.000',
            'minimum_stock' => '5.000',
            'maximum_stock' => '8.000',
        ]);
        $low = WarehouseConsumable::factory()->make([
            'current_stock' => '2.000',
            'minimum_stock' => '5.000',
            'maximum_stock' => '8.000',
        ]);
        $out = WarehouseConsumable::factory()->make([
            'current_stock' => '0.000',
            'minimum_stock' => '5.000',
            'maximum_stock' => '8.000',
        ]);

        self::assertSame('HEALTHY', $healthy->stock_status);
        self::assertSame('LOW', $low->stock_status);
        self::assertSame('OUT', $out->stock_status);
    }

    public function test_stockout_filter_form_preserves_trend_parameters(): void
    {
        $employee = $this->createUser();

        $response = $this->actingAs($employee)->get(route('warehouse.dashboard', [
            'trend_date_from' => '2026-06-01',
            'trend_date_to' => '2026-06-30',
            'stockout_month' => '2026-07',
        ]));

        $response->assertOk()
            ->assertSee('name="trend_date_from" value="2026-06-01"', false)
            ->assertSee('name="trend_date_to" value="2026-06-30"', false)
            ->assertSee('value="2026-07"', false);
    }

    public function test_trend_filter_form_preserves_stockout_month_parameter(): void
    {
        $employee = $this->createUser();

        $response = $this->actingAs($employee)->get(route('warehouse.dashboard', [
            'stockout_month' => '2026-07',
            'trend_date_from' => '2026-08-01',
            'trend_date_to' => '2026-08-05',
        ]));

        $response->assertOk()
            ->assertSee('name="stockout_month" value="2026-07"', false);
    }

    public function test_low_stock_pagination_preserves_independent_filter_query_state(): void
    {
        $employee = $this->createUser();
        WarehouseConsumable::factory()->count(12)->create([
            'current_stock' => '1.000',
            'minimum_stock' => '5.000',
        ]);

        $response = $this->actingAs($employee)->get(route('warehouse.dashboard', [
            'stockout_month' => '2026-07',
            'trend_date_from' => '2026-06-01',
            'trend_date_to' => '2026-06-30',
        ]));

        $response->assertOk();
        $content = $response->getContent();
        self::assertStringContainsString('stockout_month=2026-07', $content);
        self::assertStringContainsString('trend_date_from=2026-06-01', $content);
        self::assertStringContainsString('trend_date_to=2026-06-30', $content);
    }

    public function test_dashboard_is_empty_state_safe(): void
    {
        $employee = $this->createUser();

        $this->actingAs($employee)->get(route('warehouse.dashboard'))
            ->assertOk()
            ->assertSee('Belum ada pergerakan')
            ->assertSee('Belum ada data Stock Out item pada periode ini.')
            ->assertSee('Belum ada data Stock Out tipe mesin pada periode ini.')
            ->assertSee('Tren Stock In/Out')
            ->assertSee('Filter periode')
            ->assertDontSee('Filter Dashboard');
    }

    public function test_dashboard_splits_item_and_machine_stock_out_charts(): void
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
                ->assertSee('warehouse-top-item-data', false)
                ->assertSee('warehouse-top-machine-data', false)
                ->assertDontSee('warehouse-top-stock-out-data', false)
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

    public function test_dashboard_data_endpoint_remains_backward_compatible_and_supports_unconditioned_aggregation(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $employee = $this->createUser();
            $item = WarehouseConsumable::factory()->create(['item_name' => 'API Compat Item']);

            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '3.000', CarbonImmutable::parse('2026-08-05 10:00:00', 'Asia/Jakarta'), condition: WarehouseItemCondition::NEW);
            $this->transaction($employee, $item, WarehouseTransactionType::OUT, '4.000', CarbonImmutable::parse('2026-08-05 11:00:00', 'Asia/Jakarta'), condition: WarehouseItemCondition::USED);

            $response = $this->actingAs($employee)->getJson(route('warehouse.dashboard.data'));

            $response->assertOk()
                ->assertJsonStructure([
                    'summary',
                    'trend',
                    'top_usage',
                    'top_usage_by_machine_type',
                ]);

            $topUsage = collect($response->json('top_usage'));
            $itemUsage = $topUsage->firstWhere('id', $item->id);

            self::assertNotNull($itemUsage);
            self::assertSame('7.000', (string) $itemUsage['quantity']);
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
    ): WarehouseStockTransaction {
        $before = $type === WarehouseTransactionType::OUT ? '10.000' : '0.000';
        $after = $type === WarehouseTransactionType::OUT ? '9.000' : $quantity;

        return WarehouseStockTransaction::factory()->create([
            'transaction_number' => 'WH-DASH-'.Str::upper(Str::random(10)),
            'idempotency_key' => (string) Str::uuid(),
            'transaction_type' => $type,
            'item_condition' => $condition,
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

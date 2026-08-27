<?php

namespace Tests\Feature\Warehouse;

use App\Enums\Warehouse\WarehouseTransactionType;
use App\Enums\Warehouse\WarehouseItemCondition;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use App\Services\Warehouse\WarehouseTransactionNumberGenerator;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

class WarehouseRevisionThreeFoundationTest extends WarehouseTestCase
{
    public function test_transaction_numbers_use_one_yearly_sequence_across_types_and_reset_each_year(): void
    {
        $generator = app(WarehouseTransactionNumberGenerator::class);
        Carbon::setTestNow(CarbonImmutable::parse('2026-08-26 10:00:00', 'Asia/Jakarta'));

        try {
            self::assertSame('WH-20260001', $generator->generate());
            self::assertSame('WH-20260002', $generator->generate());

            Carbon::setTestNow(CarbonImmutable::parse('2027-01-01 00:00:00', 'Asia/Jakarta'));
            self::assertSame('WH-20270001', $generator->generate());

            $this->assertDatabaseHas('wh_transaction_sequences', [
                'year' => 2026,
                'last_number' => 2,
            ]);
            $this->assertDatabaseHas('wh_transaction_sequences', [
                'year' => 2027,
                'last_number' => 1,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_sequence_migration_backfills_existing_revision_three_numbers_without_rewriting_ledger_rows(): void
    {
        $user = $this->createUser();
        $item = WarehouseConsumable::factory()->create();
        $migration = require database_path('migrations/2026_08_26_000002_create_wh_transaction_sequences_table.php');
        $migration->down();

        $historical = WarehouseStockTransaction::factory()->create([
            'transaction_number' => 'WH-20260007',
            'consumable_id' => $item->id,
            'verified_user_id' => $user->id,
            'created_by' => $user->id,
        ]);

        $migration->up();
        Carbon::setTestNow(CarbonImmutable::parse('2026-08-26 10:00:00', 'Asia/Jakarta'));

        try {
            self::assertSame('WH-20260008', app(WarehouseTransactionNumberGenerator::class)->generate());
            self::assertSame('WH-20260007', $historical->refresh()->transaction_number);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_new_stock_helper_excludes_used_balance_from_current_stock(): void
    {
        $item = WarehouseConsumable::factory()->create([
            'current_stock' => '20.000',
            'stock_ds8' => '12.000',
            'stock_deltamas' => '8.000',
            'stock_used_ds8' => '3.000',
            'stock_used_deltamas' => '2.000',
        ]);

        self::assertSame('15.000', $item->newStock());
    }

    public function test_annual_report_ignores_used_movements_and_uses_new_stock_with_ceiling_average(): void
    {
        $user = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'REPORT-NEW-ONLY',
            'current_stock' => '17.000',
            'stock_used_ds8' => '3.000',
            'stock_used_deltamas' => '2.000',
        ]);

        $this->transaction($user, $item, WarehouseTransactionType::IN, [
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity' => '10.000',
            'stock_before' => '0.000',
            'stock_after' => '10.000',
            'transaction_at' => CarbonImmutable::parse('2026-01-10 08:00:00', 'Asia/Jakarta'),
        ]);
        $this->transaction($user, $item, WarehouseTransactionType::OUT, [
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity' => '3.000',
            'stock_before' => '10.000',
            'stock_after' => '7.000',
            'transaction_at' => CarbonImmutable::parse('2026-02-10 08:00:00', 'Asia/Jakarta'),
        ]);
        $this->transaction($user, $item, WarehouseTransactionType::IN, [
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity' => '5.000',
            'stock_before' => '7.000',
            'stock_after' => '12.000',
            'transaction_at' => CarbonImmutable::parse('2026-03-10 08:00:00', 'Asia/Jakarta'),
        ]);
        $this->transaction($user, $item, WarehouseTransactionType::IN, [
            'item_condition' => WarehouseItemCondition::USED,
            'quantity' => '5.000',
            'stock_before' => '12.000',
            'stock_after' => '17.000',
            'transaction_at' => CarbonImmutable::parse('2026-12-10 08:00:00', 'Asia/Jakarta'),
        ]);

        $report = app(\App\Services\Warehouse\WarehouseReportService::class)->build(2026);
        $row = $report['items']->firstWhere('item_code', 'REPORT-NEW-ONLY');

        self::assertCount(3, $report['months']);
        self::assertSame(['10.000', '7.000', '12.000'], $row['months']->pluck('ending')->all());
        self::assertSame('12.000', $row['current_stock']);
        self::assertSame('29.000', $row['total']);
        self::assertSame(10, $row['average']);
    }

    public function test_annual_reporting_scopes_cutoff_baseline_and_average_by_all_new_and_used_conditions(): void
    {
        $user = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'REPORT-CONDITION-TABS',
            'current_stock' => '20.000',
            'stock_ds8' => '12.000',
            'stock_deltamas' => '8.000',
            'stock_used_ds8' => '3.000',
            'stock_used_deltamas' => '2.000',
        ]);

        $this->transaction($user, $item, WarehouseTransactionType::IN, [
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity' => '5.000',
            'stock_before' => '10.000',
            'stock_after' => '15.000',
            'transaction_at' => CarbonImmutable::parse('2026-01-15 08:00:00', 'Asia/Jakarta'),
        ]);
        $this->transaction($user, $item, WarehouseTransactionType::IN, [
            'item_condition' => WarehouseItemCondition::USED,
            'quantity' => '2.000',
            'stock_before' => '17.000',
            'stock_after' => '19.000',
            'transaction_at' => CarbonImmutable::parse('2026-02-15 08:00:00', 'Asia/Jakarta'),
        ]);
        $this->transaction($user, $item, WarehouseTransactionType::OUT, [
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity' => '2.000',
            'stock_before' => '17.000',
            'stock_after' => '15.000',
            'transaction_at' => CarbonImmutable::parse('2026-03-15 08:00:00', 'Asia/Jakarta'),
        ]);
        $this->transaction($user, $item, WarehouseTransactionType::OUT, [
            'item_condition' => WarehouseItemCondition::USED,
            'quantity' => '1.000',
            'stock_before' => '21.000',
            'stock_after' => '20.000',
            'transaction_at' => CarbonImmutable::parse('2026-05-15 08:00:00', 'Asia/Jakarta'),
        ]);

        $service = app(\App\Services\Warehouse\WarehouseReportService::class);
        $new = $service->build(2026, 'NEW')['items']->firstWhere('item_code', $item->item_code);
        $used = $service->build(2026, 'USED')['items']->firstWhere('item_code', $item->item_code);
        $all = $service->build(2026, 'ALL')['items']->firstWhere('item_code', $item->item_code);

        self::assertSame(['17.000', '17.000', '15.000'], $new['months']->pluck('ending')->all());
        self::assertSame('15.000', $new['current_stock']);
        self::assertSame('49.000', $new['total']);
        self::assertSame(17, $new['average']);

        self::assertSame(['4.000', '6.000', '6.000', '6.000', '5.000'], $used['months']->pluck('ending')->all());
        self::assertSame('5.000', $used['current_stock']);
        self::assertSame('27.000', $used['total']);
        self::assertSame(6, $used['average']);

        self::assertSame(['21.000', '23.000', '21.000', '21.000', '20.000'], $all['months']->pluck('ending')->all());
        self::assertSame('20.000', $all['current_stock']);
        self::assertSame('106.000', $all['total']);
        self::assertSame(22, $all['average']);
    }

    public function test_display_location_returns_the_final_user_facing_movement_location(): void
    {
        $user = $this->createUser();
        $item = WarehouseConsumable::factory()->create();

        $in = $this->transaction($user, $item, WarehouseTransactionType::IN, [
            'from_location' => null,
            'to_location' => 'Deltamas',
            'usage_location' => 'Deltamas',
        ]);
        $out = $this->transaction($user, $item, WarehouseTransactionType::OUT, [
            'from_location' => 'DS8',
            'to_location' => null,
            'usage_location' => 'DS8',
            'stock_before' => '10.000',
            'stock_after' => '9.000',
        ]);
        $adjustment = $this->transaction($user, $item, WarehouseTransactionType::ADJUSTMENT, [
            'from_location' => null,
            'to_location' => 'DS8',
            'usage_location' => 'DS8',
        ]);
        $transfer = $this->transaction($user, $item, WarehouseTransactionType::TRANSFER, [
            'from_location' => 'DS8',
            'to_location' => 'Deltamas',
            'usage_location' => 'Deltamas',
            'stock_before' => '10.000',
            'stock_after' => '10.000',
        ]);

        self::assertSame('Deltamas', $in->display_location);
        self::assertSame('DS8', $out->display_location);
        self::assertSame('DS8', $adjustment->display_location);
        self::assertSame('Deltamas', $transfer->display_location);
    }

    private function transaction($user, WarehouseConsumable $item, WarehouseTransactionType $type, array $attributes = []): WarehouseStockTransaction
    {
        return WarehouseStockTransaction::factory()->create(array_merge([
            'transaction_number' => 'WH-FOUNDATION-'.uniqid(),
            'transaction_type' => $type,
            'consumable_id' => $item->getKey(),
            'verified_user_id' => $user->getKey(),
            'created_by' => $user->getKey(),
            'stock_before' => '0.000',
            'stock_after' => '1.000',
            'usage_location' => 'DS8',
        ], $attributes));
    }
}

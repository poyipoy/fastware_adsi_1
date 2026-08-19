<?php

namespace Tests\Feature\Warehouse;

use App\Data\Warehouse\WarehouseStockCommand;
use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Exceptions\WarehouseDomainException;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use App\Services\Warehouse\WarehouseReportService;
use App\Services\Warehouse\WarehouseStockService;
use App\Services\Warehouse\WarehouseTransactionQueryService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WarehouseRevisionTwoTest extends WarehouseTestCase
{
    public function test_new_stock_out_and_different_used_return_are_atomic_and_idempotent(): void
    {
        $actor = $this->createUser();
        $verified = $this->createUser();
        $newItem = WarehouseConsumable::factory()->create([
            'item_code' => 'NEW-ISSUE',
            'barcode' => 'NEW-ISSUE',
            'current_stock' => '10.000',
            'storage_location' => 'DS8',
        ]);
        $returnItem = WarehouseConsumable::factory()->create([
            'item_code' => 'USED-RETURN',
            'barcode' => 'USED-RETURN',
            'current_stock' => '1.000',
            'storage_location' => 'Deltamas',
        ]);
        $key = (string) Str::uuid();
        $payload = [
            'type' => 'OUT',
            'item_condition' => 'NEW',
            'item_barcode' => $newItem->barcode,
            'quantity' => '2',
            'source_location' => 'DS8',
            'return_used' => true,
            'used_return_item_barcode' => $returnItem->barcode,
            'used_return_quantity' => '1',
            'used_return_location' => 'Deltamas',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => $key,
        ];

        $first = $this->actingAs($actor)->postJson(route('warehouse.transactions.store'), $payload);
        $first->assertCreated()
            ->assertJsonPath('data.item_condition', 'NEW')
            ->assertJsonPath('data.from_location', 'DS8')
            ->assertJsonCount(1, 'related_transactions')
            ->assertJsonPath('related_transactions.0.item_condition', 'USED')
            ->assertJsonPath('related_transactions.0.to_location', 'Deltamas');

        $operationKey = $first->json('data.operation_key');
        self::assertSame($key, $operationKey);
        $this->assertDatabaseHas('mst_wh_consumables', [
            'id' => $newItem->id,
            'current_stock' => '8.000',
            'stock_ds8' => '8.000',
            'stock_used_ds8' => '0.000',
        ]);
        $this->assertDatabaseHas('mst_wh_consumables', [
            'id' => $returnItem->id,
            'current_stock' => '2.000',
            'stock_deltamas' => '2.000',
            'stock_used_deltamas' => '1.000',
        ]);
        $this->assertSame(2, WarehouseStockTransaction::query()->where('operation_key', $operationKey)->count());

        $this->actingAs($actor)->postJson(route('warehouse.transactions.store'), $payload)
            ->assertOk()
            ->assertJsonPath('idempotent_replay', true);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 2);
    }

    public function test_new_stock_out_is_rolled_back_when_used_return_leg_fails(): void
    {
        $actor = $this->createUser();
        $verified = $this->createUser();
        $newItem = WarehouseConsumable::factory()->create([
            'current_stock' => '5.000',
            'storage_location' => 'DS8',
        ]);
        $inactiveReturnItem = WarehouseConsumable::factory()->create([
            'current_stock' => '0.000',
            'storage_location' => 'Deltamas',
            'is_active' => false,
        ]);
        $operationKey = (string) Str::uuid();

        try {
            app(WarehouseStockService::class)->executeWithUsedReturn(
                new WarehouseStockCommand(
                    type: WarehouseTransactionType::OUT,
                    consumableId: $newItem->id,
                    quantity: '2',
                    verifiedUserId: $verified->id,
                    idempotencyKey: $operationKey,
                    createdBy: $actor->id,
                    itemCondition: WarehouseItemCondition::NEW,
                    sourceLocation: 'DS8',
                    operationKey: $operationKey,
                ),
                new WarehouseStockCommand(
                    type: WarehouseTransactionType::IN,
                    consumableId: $inactiveReturnItem->id,
                    quantity: '1',
                    verifiedUserId: $verified->id,
                    idempotencyKey: (string) Str::uuid(),
                    createdBy: $actor->id,
                    itemCondition: WarehouseItemCondition::USED,
                    toLocation: 'Deltamas',
                    operationKey: $operationKey,
                ),
            );
            self::fail('Expected the inactive return item to reject the atomic operation.');
        } catch (WarehouseDomainException $exception) {
            self::assertSame('Consumable tidak aktif atau tidak ditemukan.', $exception->getMessage());
        }

        $newItem->refresh();
        self::assertSame('5.000', (string) $newItem->current_stock);
        self::assertSame('5.000', (string) $newItem->stock_ds8);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }

    public function test_transfer_moves_selected_condition_without_changing_total_and_requires_restricted_verifier(): void
    {
        $actor = $this->createUser();
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'current_stock' => '7.000',
            'storage_location' => 'DS8',
        ]);

        $this->actingAs($actor)->post(route('warehouse.transfers.store'), [
            'consumable_id' => $item->id,
            'item_condition' => 'NEW',
            'quantity' => '3',
            'from_location' => 'DS8',
            'to_location' => 'Deltamas',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->assertDatabaseHas('mst_wh_consumables', [
            'id' => $item->id,
            'current_stock' => '7.000',
            'stock_ds8' => '4.000',
            'stock_deltamas' => '3.000',
        ]);
        $this->assertDatabaseHas('trs_wh_stock_transactions', [
            'consumable_id' => $item->id,
            'transaction_type' => 'TRANSFER',
            'item_condition' => 'NEW',
            'from_location' => 'DS8',
            'to_location' => 'Deltamas',
            'stock_before' => '7.000',
            'stock_after' => '7.000',
        ]);

        $this->actingAs($actor)->post(route('warehouse.transfers.store'), [
            'consumable_id' => $item->id,
            'item_condition' => 'NEW',
            'quantity' => '5',
            'from_location' => 'DS8',
            'to_location' => 'Deltamas',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('transfer');
        $this->assertDatabaseHas('mst_wh_consumables', [
            'id' => $item->id,
            'current_stock' => '7.000',
            'stock_ds8' => '4.000',
            'stock_deltamas' => '3.000',
        ]);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 1);

        DB::table('mst_wh_restricted_verifiers')->where('user_id', $verified->id)->delete();
        $this->actingAs($actor)->post(route('warehouse.transfers.store'), [
            'consumable_id' => $item->id,
            'item_condition' => 'NEW',
            'quantity' => '1',
            'from_location' => 'DS8',
            'to_location' => 'Deltamas',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('transfer');
        $this->assertDatabaseCount('trs_wh_stock_transactions', 1);
    }

    public function test_annual_report_includes_all_items_and_stops_at_latest_data_month(): void
    {
        $user = $this->createUser();
        $moving = WarehouseConsumable::factory()->create([
            'item_code' => 'REPORT-MOVING',
            'current_stock' => '15.000',
            'storage_location' => 'DS8',
        ]);
        $stationary = WarehouseConsumable::factory()->create([
            'item_code' => 'REPORT-STATIONARY',
            'current_stock' => '2.000',
            'storage_location' => 'Deltamas',
            'is_active' => false,
        ]);
        WarehouseStockTransaction::factory()->create([
            'consumable_id' => $moving->id,
            'verified_user_id' => $user->id,
            'created_by' => $user->id,
            'quantity' => '10.000',
            'stock_before' => '0.000',
            'stock_after' => '10.000',
            'transaction_at' => CarbonImmutable::parse('2026-01-10 08:00:00', 'Asia/Jakarta'),
        ]);
        WarehouseStockTransaction::factory()->create([
            'consumable_id' => $moving->id,
            'verified_user_id' => $user->id,
            'created_by' => $user->id,
            'quantity' => '5.000',
            'stock_before' => '10.000',
            'stock_after' => '15.000',
            'transaction_at' => CarbonImmutable::parse('2026-03-05 08:00:00', 'Asia/Jakarta'),
        ]);

        $report = app(WarehouseReportService::class)->build(2026);
        self::assertCount(3, $report['months']);
        self::assertCount(2, $report['items']);
        $movingRow = $report['items']->firstWhere('item_code', 'REPORT-MOVING');
        $stationaryRow = $report['items']->firstWhere('item_code', 'REPORT-STATIONARY');
        self::assertSame(['10.000', '10.000', '15.000'], $movingRow['months']->pluck('ending')->all());
        self::assertSame('35.000', $movingRow['total']);
        self::assertSame('11.667', $movingRow['average']);
        self::assertSame(['2.000', '2.000', '2.000'], $stationaryRow['months']->pluck('ending')->all());
        self::assertFalse($stationaryRow['is_active']);
    }

    public function test_used_transfer_moves_total_and_used_ledgers_and_cannot_be_reversed(): void
    {
        $actor = $this->createUser();
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'current_stock' => '4.000',
            'stock_ds8' => '3.000',
            'stock_used_ds8' => '2.000',
            'stock_deltamas' => '1.000',
            'stock_used_deltamas' => '1.000',
            'storage_location' => 'DS8',
        ]);

        $this->actingAs($actor)->post(route('warehouse.transfers.store'), [
            'consumable_id' => $item->id,
            'item_condition' => 'USED',
            'quantity' => '1',
            'from_location' => 'DS8',
            'to_location' => 'Deltamas',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $item->refresh();
        self::assertSame('4.000', (string) $item->current_stock);
        self::assertSame('2.000', (string) $item->stock_ds8);
        self::assertSame('1.000', (string) $item->stock_used_ds8);
        self::assertSame('2.000', (string) $item->stock_deltamas);
        self::assertSame('2.000', (string) $item->stock_used_deltamas);

        $transfer = WarehouseStockTransaction::query()->where('transaction_type', 'TRANSFER')->firstOrFail();
        $this->actingAs($actor)->get(route('warehouse.transactions.reverse-form', $transfer))->assertStatus(422);
    }

    public function test_reversal_restores_the_original_used_condition_balance(): void
    {
        $actor = $this->createUser();
        $verified = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'current_stock' => '5.000',
            'stock_ds8' => '5.000',
            'stock_used_ds8' => '3.000',
            'storage_location' => 'DS8',
        ]);

        $this->actingAs($actor)->post(route('warehouse.transactions.store'), [
            'type' => 'OUT',
            'item_condition' => 'USED',
            'item_barcode' => $item->barcode,
            'quantity' => '2',
            'source_location' => 'DS8',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();
        $original = WarehouseStockTransaction::query()->where('transaction_type', 'OUT')->firstOrFail();

        $this->actingAs($actor)->post(route('warehouse.transactions.reverse', $original), [
            'reason' => 'Barang bekas dikembalikan ke saldo',
            'verified_code' => (string) $verified->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $item->refresh();
        self::assertSame('5.000', (string) $item->current_stock);
        self::assertSame('5.000', (string) $item->stock_ds8);
        self::assertSame('3.000', (string) $item->stock_used_ds8);
        $this->assertDatabaseHas('trs_wh_stock_transactions', [
            'transaction_type' => 'REVERSAL',
            'item_condition' => 'USED',
            'from_location' => null,
            'to_location' => 'DS8',
        ]);
    }

    public function test_foreman_workspace_overrides_manual_verifier_filter(): void
    {
        $foreman = $this->createUser();
        $other = $this->createUser();
        $item = WarehouseConsumable::factory()->create();
        Config::set('warehouse.history_workspaces.foreman_1.npk', $foreman->npk);
        WarehouseStockTransaction::factory()->create([
            'transaction_number' => 'WH-FOREMAN-ONLY',
            'consumable_id' => $item->id,
            'verified_user_id' => $foreman->id,
            'verified_user_name' => $foreman->name,
            'created_by' => $foreman->id,
        ]);
        WarehouseStockTransaction::factory()->create([
            'transaction_number' => 'WH-OTHER-HIDDEN',
            'consumable_id' => $item->id,
            'verified_user_id' => $other->id,
            'verified_user_name' => $other->name,
            'created_by' => $other->id,
        ]);

        $query = app(WarehouseTransactionQueryService::class)->build([
            'workspace' => 'foreman_1',
            'verified_user_id' => $other->id,
        ]);
        self::assertSame(['WH-FOREMAN-ONLY'], $query->pluck('transaction_number')->all());
    }

    public function test_master_photo_is_stored_and_retained_when_update_has_no_new_upload(): void
    {
        Storage::fake('public');
        $actor = $this->createUser();

        $this->actingAs($actor)->post(route('warehouse.consumables.store'), [
            'item_code' => 'PHOTO-ITEM',
            'item_name' => 'Photo Item',
            'machine_type' => 'Press',
            'minimum_stock' => '1',
            'maximum_stock' => '10',
            'storage_location' => 'DS8',
            'photo' => UploadedFile::fake()->image('item.png', 320, 220),
        ])->assertRedirect(route('warehouse.consumables.index'));

        $item = WarehouseConsumable::query()->where('item_code', 'PHOTO-ITEM')->firstOrFail();
        self::assertSame('Press', $item->machine_type);
        self::assertNotNull($item->photo_path);
        Storage::disk('public')->assertExists($item->photo_path);
        $originalPath = $item->photo_path;

        $this->actingAs($actor)->put(route('warehouse.consumables.update', $item), [
            'item_code' => 'PHOTO-ITEM',
            'item_name' => 'Photo Item Updated',
            'machine_type' => 'Press 2',
            'minimum_stock' => '1',
            'maximum_stock' => '10',
            'storage_location' => 'DS8',
        ])->assertRedirect(route('warehouse.consumables.index'));

        self::assertSame($originalPath, $item->refresh()->photo_path);
        Storage::disk('public')->assertExists($originalPath);
        $this->actingAs($actor)->getJson(route('warehouse.catalog.index'))
            ->assertOk()
            ->assertJsonPath('data.0.machine_type', 'Press 2');
    }
}

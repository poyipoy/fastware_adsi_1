<?php

namespace Tests\Feature\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseStockInStatus;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockIn;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class WarehouseRevisionFourTest extends WarehouseTestCase
{
    public function test_stock_in_both_new_and_used_create_waiting_validation_without_stock_mutation(): void
    {
        $creator = $this->createUser(['name' => 'Operator Stock In']);
        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'TEST-REV4-01',
            'current_stock' => '0.000',
            'stock_ds8' => '0.000',
            'stock_used_ds8' => '0.000',
        ]);

        // 1. Stock In BARU
        $resNew = $this->actingAs($creator)->postJson(route('warehouse.transactions.store'), [
            'type' => 'IN',
            'item_barcode' => $item->barcode,
            'quantity' => '10',
            'item_condition' => 'NEW',
            'location' => 'DS8',
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $resNew->assertCreated()->assertJsonPath('data.status', 'WAITING_VALIDATION');

        $stockInNewId = $resNew->json('data.id');
        $this->assertDatabaseHas('trs_wh_stock_ins', [
            'id' => $stockInNewId,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION->value,
            'item_condition' => WarehouseItemCondition::NEW->value,
            'quantity_expected' => '10.000',
        ]);

        // Stok fisik TIDAK boleh bertambah
        $item->refresh();
        self::assertSame('0.000', (string) $item->current_stock);
        self::assertSame('0.000', (string) $item->stock_ds8);

        // 2. Stock In BEKAS
        $resUsed = $this->actingAs($creator)->postJson(route('warehouse.transactions.store'), [
            'type' => 'IN',
            'item_barcode' => $item->barcode,
            'quantity' => '5',
            'item_condition' => 'USED',
            'location' => 'DS8',
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $resUsed->assertCreated()->assertJsonPath('data.status', 'WAITING_VALIDATION');

        $stockInUsedId = $resUsed->json('data.id');
        $this->assertDatabaseHas('trs_wh_stock_ins', [
            'id' => $stockInUsedId,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION->value,
            'item_condition' => WarehouseItemCondition::USED->value,
            'quantity_expected' => '5.000',
        ]);

        // Stok fisik bekas TIDAK boleh bertambah sebelum divalidasi
        $item->refresh();
        self::assertSame('0.000', (string) $item->current_stock);
        self::assertSame('0.000', (string) $item->stock_used_ds8);
    }

    public function test_authorized_validators_ragil_yusuf_rodjo_can_approve_stock_in(): void
    {
        // Setup 3 authorized validators with correct NPKs: Ragil (5639), Yusuf (5472), Rodjo (5439)
        $validatorRagil = $this->createUser(['npk' => 5639, 'name' => 'Ragil']);
        $validatorYusuf = $this->createUser(['npk' => 5472, 'name' => 'Yusuf']);
        $validatorRodjo = $this->createUser(['npk' => 5439, 'name' => 'Rodjo']);

        $item = WarehouseConsumable::factory()->create([
            'current_stock' => '0.000',
            'stock_ds8' => '0.000',
        ]);

        // 1. Validator Yusuf can approve (and self-approval is allowed)
        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'destination_location' => 'DS8',
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity_expected' => '12.000',
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
            'created_by' => $validatorYusuf->id,
        ]);

        $this->actingAs($validatorYusuf)->postJson(route('warehouse.stock-in.validate', $stockIn), [
            'quantity_received' => '12',
            'result' => 'MATCH',
            'verified_code' => (string) $validatorYusuf->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSuccessful();

        $stockIn->refresh();
        self::assertSame(WarehouseStockInStatus::VALIDATED, $stockIn->status);
        $item->refresh();
        self::assertSame('12.000', (string) $item->current_stock);
        self::assertSame('12.000', (string) $item->stock_ds8);

        // 2. Validator Ragil can approve
        $stockIn2 = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'destination_location' => 'DS8',
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity_expected' => '8.000',
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
            'created_by' => $validatorRagil->id,
        ]);

        $this->actingAs($validatorRagil)->postJson(route('warehouse.stock-in.validate', $stockIn2), [
            'quantity_received' => '8',
            'result' => 'MATCH',
            'verified_code' => (string) $validatorRagil->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSuccessful();

        $stockIn2->refresh();
        self::assertSame(WarehouseStockInStatus::VALIDATED, $stockIn2->status);
        $item->refresh();
        self::assertSame('20.000', (string) $item->current_stock);

        // 3. Validator Rodjo can approve
        $stockIn3 = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'destination_location' => 'DS8',
            'item_condition' => WarehouseItemCondition::USED,
            'quantity_expected' => '5.000',
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
            'created_by' => $validatorRodjo->id,
        ]);

        $this->actingAs($validatorRodjo)->postJson(route('warehouse.stock-in.validate', $stockIn3), [
            'quantity_received' => '5',
            'result' => 'MATCH',
            'verified_code' => (string) $validatorRodjo->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSuccessful();

        $stockIn3->refresh();
        self::assertSame(WarehouseStockInStatus::VALIDATED, $stockIn3->status);
        $item->refresh();
        self::assertSame('25.000', (string) $item->current_stock);
        self::assertSame('5.000', (string) $item->stock_used_ds8);
    }

    public function test_rejection_requires_reason_and_is_terminal(): void
    {
        $validator = $this->createUser(['npk' => 5472, 'name' => 'Yusuf']);
        $item = WarehouseConsumable::factory()->create([
            'current_stock' => '0.000',
            'stock_ds8' => '0.000',
        ]);

        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'destination_location' => 'DS8',
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity_expected' => '10.000',
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
            'created_by' => $validator->id,
        ]);

        // Rejection without reason fails validation
        $this->actingAs($validator)->postJson(route('warehouse.stock-in.cancel', $stockIn), [
            'cancellation_reason' => '',
        ])->assertUnprocessable();

        // Rejection with reason succeeds
        $this->actingAs($validator)->post(route('warehouse.stock-in.cancel', $stockIn), [
            'cancellation_reason' => 'Barang rusak saat pengiriman',
            'redirect_to' => 'validations',
        ])->assertRedirect(route('warehouse.validations.index'));

        $stockIn->refresh();
        self::assertSame(WarehouseStockInStatus::CANCELLED, $stockIn->status);
        self::assertSame('Barang rusak saat pengiriman', $stockIn->cancellation_reason);
        self::assertSame($validator->id, $stockIn->cancelled_by_user_id);

        // Stok tidak berubah
        $item->refresh();
        self::assertSame('0.000', (string) $item->current_stock);

        // Terminal: attempting to validate after cancellation fails
        $this->actingAs($validator)->postJson(route('warehouse.stock-in.validate', $stockIn), [
            'quantity_received' => '10',
            'result' => 'MATCH',
            'verified_code' => (string) $validator->npk,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertStatus(409);
    }

    public function test_stock_out_requires_multi_machine_allocation_and_validates_constraints(): void
    {
        $actor = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'current_stock' => '10.000',
            'stock_ds8' => '10.000',
        ]);

        // 1. Without machine_allocations (empty selection) when OUT fails
        $this->actingAs($actor)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT',
            'item_barcode' => $item->barcode,
            'location' => 'DS8',
            'verified_code' => (string) $actor->npk,
            'machine_allocations' => [],
            'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['machine_allocations']);

        // 2. Invalid machine code (not M1..M6, M13) fails
        $this->actingAs($actor)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT',
            'item_barcode' => $item->barcode,
            'quantity' => '3',
            'location' => 'DS8',
            'verified_code' => (string) $actor->npk,
            'machine_allocations' => [
                ['machine_code' => 'M99', 'quantity' => 3],
            ],
            'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable();

        // 3. Zero or negative quantity fails
        $this->actingAs($actor)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT',
            'item_barcode' => $item->barcode,
            'quantity' => '0',
            'location' => 'DS8',
            'verified_code' => (string) $actor->npk,
            'machine_allocations' => [
                ['machine_code' => 'M1', 'quantity' => 0],
            ],
            'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable();

        // 4. Decimal quantity fails (pcs only, integer strictly >= 1)
        $this->actingAs($actor)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT',
            'item_barcode' => $item->barcode,
            'quantity' => '1.5',
            'location' => 'DS8',
            'verified_code' => (string) $actor->npk,
            'machine_allocations' => [
                ['machine_code' => 'M1', 'quantity' => 1.5],
            ],
            'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable();

        // 5. Duplicate machine codes fail
        $this->actingAs($actor)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT',
            'item_barcode' => $item->barcode,
            'quantity' => '4',
            'location' => 'DS8',
            'verified_code' => (string) $actor->npk,
            'machine_allocations' => [
                ['machine_code' => 'M1', 'quantity' => 2],
                ['machine_code' => 'M1', 'quantity' => 2],
            ],
            'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable();

        // 6. Exceeding available stock fails
        $this->actingAs($actor)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT',
            'item_barcode' => $item->barcode,
            'quantity' => '15',
            'location' => 'DS8',
            'verified_code' => (string) $actor->npk,
            'machine_allocations' => [
                ['machine_code' => 'M1', 'quantity' => 10],
                ['machine_code' => 'M2', 'quantity' => 5],
            ],
            'idempotency_key' => (string) Str::uuid(),
        ])->assertStatus(422);
    }

    public function test_stock_out_persists_machine_allocations_atomically_in_child_table(): void
    {
        $actor = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'current_stock' => '20.000',
            'stock_ds8' => '20.000',
        ]);

        $res = $this->actingAs($actor)->postJson(route('warehouse.transactions.store'), [
            'type' => 'OUT',
            'item_barcode' => $item->barcode,
            'quantity' => '7',
            'location' => 'DS8',
            'verified_code' => (string) $actor->npk,
            'machine_allocations' => [
                ['machine_code' => 'M1', 'quantity' => 3],
                ['machine_code' => 'M3', 'quantity' => 2],
                ['machine_code' => 'M13', 'quantity' => 2],
            ],
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $res->assertCreated();
        $trxId = $res->json('data.id');

        // Check parent transaction
        $this->assertDatabaseHas('trs_wh_stock_transactions', [
            'id' => $trxId,
            'transaction_type' => 'OUT',
            'quantity' => '7.000',
            'from_location' => 'DS8',
        ]);

        // Check child machine usages in trs_wh_stock_transaction_machine_usages
        $this->assertDatabaseHas('trs_wh_stock_transaction_machine_usages', [
            'transaction_id' => $trxId,
            'machine_code' => 'M1',
            'quantity' => '3.000',
        ]);
        $this->assertDatabaseHas('trs_wh_stock_transaction_machine_usages', [
            'transaction_id' => $trxId,
            'machine_code' => 'M3',
            'quantity' => '2.000',
        ]);
        $this->assertDatabaseHas('trs_wh_stock_transaction_machine_usages', [
            'transaction_id' => $trxId,
            'machine_code' => 'M13',
            'quantity' => '2.000',
        ]);

        $item->refresh();
        self::assertSame('13.000', (string) $item->current_stock);
        self::assertSame('13.000', (string) $item->stock_ds8);
    }

    public function test_master_consumable_crud_without_machine_type(): void
    {
        $actor = $this->createUser();

        // 1. Create consumable
        $response = $this->actingAs($actor)->post(route('warehouse.consumables.store'), [
            'item_name' => 'Consumable Rev 4',
            'item_code' => 'REV4-ITEM',
            'barcode' => '089888777666',
            'unit' => 'pcs',
            'allow_fraction' => '0',
            'minimum_stock' => '5',
            'maximum_stock' => '50',
            'is_active' => '1',
        ]);
        $response->assertRedirect(route('warehouse.consumables.index'));

        $item = WarehouseConsumable::query()->where('item_code', 'REV4-ITEM')->first();
        self::assertNotNull($item);
        self::assertSame('Consumable Rev 4', $item->item_name);

        // 2. View index and show without errors
        $this->actingAs($actor)->get(route('warehouse.consumables.index'))
            ->assertOk()
            ->assertSee('Consumable Rev 4')
            ->assertDontSee('Tipe Mesin');

        $this->actingAs($actor)->get(route('warehouse.consumables.show', $item))
            ->assertOk()
            ->assertSee('Consumable Rev 4')
            ->assertDontSee('Tipe mesin belum diisi');
    }

    public function test_dashboard_dual_tab_synchronized_stock_out_metrics(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-06 10:00:00', 'Asia/Jakarta'));

        try {
            $actor = $this->createUser();
            $item = WarehouseConsumable::factory()->create([
                'current_stock' => '100.000',
                'stock_ds8' => '50.000',
                'stock_used_ds8' => '50.000',
            ]);

            // Stock Out BARU: M1: 10, M2: 5 (Total 15)
            $this->actingAs($actor)->postJson(route('warehouse.transactions.store'), [
                'type' => 'OUT',
                'item_barcode' => $item->barcode,
                'item_condition' => 'NEW',
                'quantity' => '15',
                'location' => 'DS8',
                'verified_code' => (string) $actor->npk,
                'machine_allocations' => [
                    ['machine_code' => 'M1', 'quantity' => 10],
                    ['machine_code' => 'M2', 'quantity' => 5],
                ],
                'idempotency_key' => (string) Str::uuid(),
            ])->assertCreated();

            // Stock Out BEKAS: M3: 4, M13: 6 (Total 10)
            $this->actingAs($actor)->postJson(route('warehouse.transactions.store'), [
                'type' => 'OUT',
                'item_barcode' => $item->barcode,
                'item_condition' => 'USED',
                'location' => 'DS8',
                'quantity' => '10',
                'verified_code' => (string) $actor->npk,
                'machine_allocations' => [
                    ['machine_code' => 'M3', 'quantity' => 4],
                    ['machine_code' => 'M13', 'quantity' => 6],
                ],
                'idempotency_key' => (string) Str::uuid(),
            ])->assertCreated();

            $response = $this->actingAs($actor)->get(route('warehouse.dashboard'));
            $response->assertOk();

            $newMetrics = $response->viewData('newStockOutMetrics');
            $usedMetrics = $response->viewData('usedStockOutMetrics');

            // BARU Tab verification
            self::assertSame(15.0, (float) $newMetrics['total_stock_out']);
            self::assertSame(15.0, (float) array_sum($newMetrics['machine_breakdown']));
            self::assertArrayHasKey('M1', $newMetrics['machine_breakdown']);
            self::assertArrayHasKey('M2', $newMetrics['machine_breakdown']);
            self::assertSame(10.0, (float) $newMetrics['machine_breakdown']['M1']);
            self::assertSame(5.0, (float) $newMetrics['machine_breakdown']['M2']);
            // Zero-usage machines must be omitted
            self::assertArrayNotHasKey('M3', $newMetrics['machine_breakdown']);
            self::assertArrayNotHasKey('M4', $newMetrics['machine_breakdown']);
            self::assertArrayNotHasKey('M5', $newMetrics['machine_breakdown']);
            self::assertArrayNotHasKey('M6', $newMetrics['machine_breakdown']);
            self::assertArrayNotHasKey('M13', $newMetrics['machine_breakdown']);

            // BEKAS Tab verification
            self::assertSame(10.0, (float) $usedMetrics['total_stock_out']);
            self::assertSame(10.0, (float) array_sum($usedMetrics['machine_breakdown']));
            self::assertArrayHasKey('M3', $usedMetrics['machine_breakdown']);
            self::assertArrayHasKey('M13', $usedMetrics['machine_breakdown']);
            self::assertSame(4.0, (float) $usedMetrics['machine_breakdown']['M3']);
            self::assertSame(6.0, (float) $usedMetrics['machine_breakdown']['M13']);
            // Zero-usage machines must be omitted
            self::assertArrayNotHasKey('M1', $usedMetrics['machine_breakdown']);
            self::assertArrayNotHasKey('M2', $usedMetrics['machine_breakdown']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_dashboard_stok_baru_and_bekas_render_dedicated_views_and_metrics(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-06 10:00:00', 'Asia/Jakarta'));

        try {
            $actor = $this->createUser();
            $item = WarehouseConsumable::factory()->create([
                'item_name' => 'Insert CNMG 120408',
                'current_stock' => '25.000',
                'stock_ds8' => '25.000',
                'stock_used_ds8' => '14.000',
                'stock_used_deltamas' => '6.000',
            ]);

            // 1. Dashboard Stok Baru (default / condition=NEW)
            $resBaru = $this->actingAs($actor)->get(route('warehouse.dashboard', ['condition' => 'NEW']));
            $resBaru->assertOk();
            $resBaru->assertViewHas('activeCondition', 'NEW');
            $resBaru->assertSee('Warehouse Dashboard — Stok Baru');
            $resBaru->assertSee('Status Stok Baru');
            $resBaru->assertSee('Total Stock Out Baru');
            $resBaru->assertSee('Insert CNMG 120408');

            $summaryBaru = $resBaru->viewData('summary');
            self::assertSame('NEW', $summaryBaru['condition']);

            // 2. Dashboard Stok Bekas (condition=USED)
            $resBekas = $this->actingAs($actor)->get(route('warehouse.dashboard', ['condition' => 'USED']));
            $resBekas->assertOk();
            $resBekas->assertViewHas('activeCondition', 'USED');
            $resBekas->assertSee('Warehouse Dashboard — Stok Bekas');
            $resBekas->assertSee('Total Saldo Bekas');
            $resBekas->assertSee('Item Bekas Aktif');
            $resBekas->assertSee('Status Inventori Bekas');
            $resBekas->assertSee('Total Stock Out Bekas');
            $resBekas->assertSee('Insert CNMG 120408');

            $summaryBekas = $resBekas->viewData('summary');
            self::assertSame('USED', $summaryBekas['condition']);
            self::assertSame(20.0, (float) $summaryBekas['total_used_stock']);
            self::assertSame(1, (int) $summaryBekas['active_used_items']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}

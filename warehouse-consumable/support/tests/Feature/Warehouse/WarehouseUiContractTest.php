<?php

namespace Tests\Feature\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseLocationShipmentStatus;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseLocationShipment;
use App\Models\Warehouse\WarehouseStockIn;
use App\Models\Warehouse\WarehouseStockTransaction;
use Illuminate\Support\Str;

class WarehouseUiContractTest extends WarehouseTestCase
{
    public function test_dashboard_moves_all_permitted_shortcuts_to_header_and_hides_quick_access_block(): void
    {
        $operator = $this->createUser([], false);
        $this->createPicPosition($operator);

        $operatorDashboard = $this->actingAs($operator)->get(route('warehouse.dashboard'));
        $operatorDashboard
            ->assertOk()
            ->assertSee('Stock In Baru')
            ->assertSee('Stock Out Baru')
            ->assertSee('Stock In/Out Bekas')
            ->assertSee('Master Consumable')
            ->assertSee('Riwayat')
            ->assertSee('Reporting')
            ->assertSee('Penyesuaian')
            ->assertSee('Export')
            ->assertDontSee('Validasi Stok')
            ->assertDontSee('Akses Cepat')
            ->assertDontSee('warehouse-quick-actions', false)
            ->assertDontSee('warehouse-quick-action', false);

        $validator = $this->createUser();
        $this->actingAs($validator)->get(route('warehouse.dashboard'))
            ->assertOk()
            ->assertSee('Validasi Stok');
    }

    public function test_new_and_used_catalogs_expose_internal_source_and_persistent_selection_contract(): void
    {
        $user = $this->createUser([], false);
        $this->createPicPosition($user);

        foreach ([
            $this->actingAs($user)->get(route('warehouse.transactions.create')),
            $this->actingAs($user)->get(route('warehouse.transactions-used.create')),
        ] as $response) {
            $response
                ->assertOk()
                ->assertSee('data-warehouse-transaction-form', false)
                ->assertSee('data-warehouse-catalog="primary"', false)
                ->assertSee('name="source_location"', false)
                ->assertSee('Sumber internal');
        }

        $script = file_get_contents(base_path('resources/js/warehouse/transaction-form.js'));
        self::assertStringContainsString('data-warehouse-catalog-item-key', $script);
        self::assertStringContainsString('syncCatalogSelection', $script);
        self::assertStringContainsString("syncCatalogSelection('primary')", $script);
        self::assertStringContainsString("syncCatalogSelection('return')", $script);
        self::assertStringContainsString("card.setAttribute('aria-pressed'", $script);

        $css = file_get_contents(base_path('resources/css/warehouse/transaction-form.css'));
        self::assertStringContainsString('.warehouse-catalog-card.is-selected', $css);
    }

    public function test_validation_workspace_reads_stock_ins_only_and_labels_internal_sources_as_transfers(): void
    {
        $creator = $this->createUser([], false);
        $this->createPicPosition($creator);
        $validator = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'VALIDATION-TRANSFER-ITEM',
            'current_stock' => '5.000',
            'stock_ds8' => '5.000',
        ]);
        $stockIn = WarehouseStockIn::factory()->create([
            'stock_in_number' => 'WH-IN-VALIDATION-TRANSFER',
            'consumable_id' => $item->id,
            'item_condition' => WarehouseItemCondition::USED,
            'quantity_expected' => '1.000',
            'source_location' => 'DS8',
            'destination_location' => 'Deltamas',
            'created_by' => $creator->id,
        ]);
        $legacy = $this->legacyShipment($creator, $item, 'WH-SHIP-ARCHIVE-ONLY');

        $response = $this->actingAs($validator)->get(route('warehouse.validations.index'));
        $response
            ->assertOk()
            ->assertSee($stockIn->stock_in_number)
            ->assertSee('Transfer Antar Lokasi')
            ->assertSee('DS8 → Deltamas')
            ->assertDontSee($legacy->shipment_number);
        self::assertTrue($response->viewData('pending')->contains(
            fn (array $record): bool => $record['reference'] === $stockIn->stock_in_number && $record['kind'] === 'Transfer Antar Lokasi',
        ));
    }

    public function test_reporting_tabs_default_to_new_and_preserve_active_year(): void
    {
        $user = $this->createUser();
        WarehouseConsumable::factory()->create(['item_name' => 'Reporting Tabs Item']);

        $default = $this->actingAs($user)->get(route('warehouse.reports.index', ['year' => 2026]));
        $default
            ->assertOk()
            ->assertSee('>ALL<', false)
            ->assertSee('>BARU<', false)
            ->assertSee('>BEKAS<', false)
            ->assertSee('condition=NEW', false)
            ->assertSee('year=2026', false);

        $this->actingAs($user)->get(route('warehouse.reports.index', ['year' => 2026, 'condition' => 'USED']))
            ->assertOk()
            ->assertSee('kondisi BEKAS')
            ->assertSee('condition=ALL', false)
            ->assertSee('year=2026', false);

        $css = file_get_contents(base_path('resources/css/warehouse/reporting.css'));
        self::assertStringContainsString('warehouse-report-tabs', $css);
        self::assertStringContainsString('warehouse-report-tab.is-active', $css);
    }

    public function test_historic_shipment_reference_is_read_only_without_an_operational_route(): void
    {
        $user = $this->createUser();
        $item = WarehouseConsumable::factory()->create();
        $shipment = $this->legacyShipment($user, $item, 'WH-SHIP-HISTORIC');
        $transaction = WarehouseStockTransaction::factory()->create([
            'consumable_id' => $item->id,
            'verified_user_id' => $user->id,
            'created_by' => $user->id,
            'transaction_type' => WarehouseTransactionType::TRANSFER,
            'location_shipment_id' => $shipment->id,
            'from_location' => 'DS8',
            'to_location' => 'Deltamas',
            'stock_before' => '5.000',
            'stock_after' => '5.000',
        ]);

        $this->actingAs($user)->get(route('warehouse.transactions.show', $transaction))
            ->assertOk()
            ->assertSee('Arsip Transfer Antar Lokasi')
            ->assertSee($shipment->shipment_number)
            ->assertDontSee('warehouse.location-shipments.show', false);
        $this->actingAs($user)->get('/warehouse/stock-in/shipments/'.$shipment->id)->assertNotFound();
    }

    private function legacyShipment($sender, WarehouseConsumable $item, string $number): WarehouseLocationShipment
    {
        return WarehouseLocationShipment::query()->create([
            'shipment_number' => $number,
            'consumable_id' => $item->id,
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity_sent' => '1.000',
            'from_location' => 'DS8',
            'to_location' => 'Deltamas',
            'status' => WarehouseLocationShipmentStatus::WAITING_VALIDATION,
            'sent_by_user_id' => $sender->id,
            'sender_npk_snapshot' => (string) $sender->npk,
            'sender_name_snapshot' => $sender->name,
            'sender_notes' => 'Arsip transaksi lama.',
            'sent_at' => now(),
            'creation_idempotency_key' => (string) Str::uuid(),
        ]);
    }
}

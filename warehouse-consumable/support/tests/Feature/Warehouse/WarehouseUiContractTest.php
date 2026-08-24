<?php

namespace Tests\Feature\Warehouse;

use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockIn;
use App\Models\Warehouse\WarehouseStockTransaction;

class WarehouseUiContractTest extends WarehouseTestCase
{
    public function test_stock_in_pending_validation_is_integrated_into_new_transaction_workspace(): void
    {
        $user = $this->createUser([], false);
        $this->createPicPosition($user);
        $workspace = $this->actingAs($user)->get(route('warehouse.transactions.create'));
        $workspace
            ->assertOk()
            ->assertSee('Stock In/Out Baru')
            ->assertSee('warehouse-stock-in-workspace', false)
            ->assertDontSee('Buat Baru')
            ->assertSee('Menunggu Validasi')
            ->assertSee('Tervalidasi')
            ->assertSee('name="source_location"', false)
            ->assertSee('name="notes"', false)
            ->assertDontSee('/warehouse/stock-in/create');

        $validationView = file_get_contents(base_path('resources/views/warehouse/stock-in/validate.blade.php'));
        self::assertStringNotContainsString('name="validator_code"', $validationView);
        self::assertStringNotContainsString('name="received_condition"', $validationView);
        self::assertStringNotContainsString('Scan NPK Validator', $validationView);
        self::assertStringContainsString('step="1"', $validationView);

        $item = WarehouseConsumable::factory()->create(['item_name' => 'Integer Validation Item']);
        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'quantity_expected' => '15.000',
            'created_by' => $user->id,
        ]);

        $layout = $workspace->getContent();
        self::assertStringContainsString('Stock In/Out Baru', $layout);
        self::assertStringNotContainsString('>Stock In</a>', $layout);
        self::assertStringNotContainsString('Menunggu Validasi</a>', $layout);
        self::assertStringNotContainsString('Pengiriman Antar Lokasi</a>', $layout);

        $this->actingAs($user)->get(route('warehouse.stock-in.index'))
            ->assertRedirect(route('warehouse.transactions.create'));
        $this->actingAs($user)->get(route('warehouse.stock-in.create'))
            ->assertRedirect(route('warehouse.transactions.create'));

        $validator = $this->createUser();
        $validatorWorkspace = $this->actingAs($validator)->get(route('warehouse.transactions.create'));
        $validatorWorkspace
            ->assertOk()
            ->assertSee('Menunggu Validasi')
            ->assertSee('Validasi')
            ->assertSee('data-warehouse-transaction-form', false)
            ->assertSee('data-warehouse-type="IN"', false)
            ->assertSee('data-warehouse-type="OUT"', false)
            ->assertDontSee('warehouse-stock-in-validator-notice', false);
        $validatorUsedWorkspace = $this->actingAs($validator)->get(route('warehouse.transactions-used.create'));
        $validatorUsedWorkspace
            ->assertOk()
            ->assertSee('data-warehouse-transaction-form', false)
            ->assertSee('data-warehouse-type="IN"', false)
            ->assertSee('data-warehouse-type="OUT"', false)
            ->assertDontSee('warehouse-stock-in-validator-notice', false);
        $this->actingAs($validator)->get(route('warehouse.stock-in.show', $stockIn))
            ->assertOk()
            ->assertSee('Batalkan Stock In');
        $this->actingAs($validator)->get(route('warehouse.stock-in.validate-form', $stockIn))
            ->assertOk()
            ->assertSee('value="15"', false)
            ->assertDontSee('value="15.000"', false)
            ->assertSee('step="1"', false);
        $this->actingAs($validator)->get(route('warehouse.stock-in.index'))
            ->assertRedirect(route('warehouse.transactions.create'));
        self::assertFileDoesNotExist(base_path('resources/views/warehouse/stock-in/index.blade.php'));
        self::assertFileDoesNotExist(base_path('resources/views/warehouse/stock-in/create.blade.php'));

        $script = file_get_contents(base_path('resources/js/warehouse/transaction-form.js'));
        self::assertStringContainsString("const inbound = type === 'IN';", $script);
        $stockInScript = file_get_contents(base_path('resources/js/warehouse/stock-in.js'));
        self::assertStringNotContainsString("STOCK_IN_VALIDATE", $stockInScript);
        self::assertStringContainsString('received_item_barcode', $stockInScript);
        $stockInCss = file_get_contents(base_path('resources/css/warehouse/stock-in.css'));
        self::assertStringContainsString('prefers-reduced-motion', $stockInCss);
    }

    public function test_new_and_used_transaction_forms_expose_catalog_and_three_step_accessible_flow(): void
    {
        $user = $this->createUser([], false);
        $this->createPicPosition($user);
        $new = $this->actingAs($user)->get(route('warehouse.transactions.create'));
        $used = $this->actingAs($user)->get(route('warehouse.transactions-used.create'));

        $new->assertOk()
            ->assertSee('data-warehouse-step="1"', false)
            ->assertSee('data-warehouse-step="2"', false)
            ->assertSee('data-warehouse-step="3"', false)
            ->assertDontSee('data-warehouse-step="4"', false)
            ->assertSee('data-warehouse-catalog="primary"', false)
            ->assertSee('data-warehouse-catalog="return"', false)
            ->assertSee('data-warehouse-return-used', false)
            ->assertSee('name="location"', false)
            ->assertSee('name="source_location"', false)
            ->assertSee('name="notes"', false)
            ->assertDontSee('name="storage_location"', false)
            ->assertSee('name="item_condition" value="NEW"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('aria-atomic="true"', false)
            ->assertSee('Maksimal 16 item')
            ->assertSee('Barang baru keluar disertai pengembalian barang bekas');

        $used->assertOk()
            ->assertSee('Stock In/Out Bekas')
            ->assertSee('name="item_condition" value="USED"', false)
            ->assertDontSee('data-warehouse-return-used', false);

        $script = file_get_contents(base_path('resources/js/warehouse/transaction-form.js'));
        self::assertIsString($script);
        self::assertStringContainsString('new AbortController()', $script);
        self::assertStringContainsString('setTimeout(() => load(false), 300)', $script);
        self::assertStringContainsString("event.key === 'Enter'", $script);
        self::assertStringContainsString('loading="lazy"', $script);
        self::assertStringContainsString('const syncVerifierAvailability', $script);
        self::assertStringContainsString('const inbound = type === \'IN\';', $script);
        self::assertStringContainsString('Status: Menunggu Validasi', $script);
        self::assertStringContainsString('const appendPreview', $script);
        self::assertStringContainsString('const invalidateApproval', $script);
        self::assertStringContainsString('Verifikasi ulang setelah NPK diubah.', $script);
        self::assertStringContainsString('updateStep(3)', $script);
        self::assertStringNotContainsString('updateStep(4)', $script);
        self::assertStringNotContainsString('window.confirm', $script);

        $stylesheet = file_get_contents(base_path('resources/css/warehouse/transaction-form.css'));
        self::assertStringContainsString('.warehouse-catalog-grid', $stylesheet);
        self::assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr))', $stylesheet);
        self::assertStringContainsString('aspect-ratio: 16 / 11', $stylesheet);
        self::assertStringContainsString('min-height: 44px', $stylesheet);
    }

    public function test_scanner_prefill_only_resolves_in_browser_and_unknown_type_is_rejected(): void
    {
        $user = $this->createUser([], false);
        $this->createPicPosition($user);
        $item = WarehouseConsumable::factory()->create(['item_name' => 'Starter Query Item', 'barcode' => '000-STARTER-01']);

        $this->actingAs($user)->get(route('warehouse.transactions.create', ['type' => 'DELETE', 'barcode' => $item->barcode]))
            ->assertOk()
            ->assertSee('data-warehouse-initial-type="IN"', false)
            ->assertSee('data-warehouse-initial-barcode="000-STARTER-01"', false)
            ->assertDontSee('Starter Query Item');
    }

    public function test_dashboard_uses_separate_item_and_machine_stock_out_charts_and_removes_recent_panel(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get(route('warehouse.dashboard'))
            ->assertOk()
            ->assertSee('Analitik Pergerakan')
            ->assertSee('Top Item Stock Out')
            ->assertSee('Top Tipe Mesin Stock Out')
            ->assertSee('data-warehouse-bar-chart', false)
            ->assertSee('warehouse-top-item-data', false)
            ->assertSee('warehouse-top-machine-data', false)
            ->assertDontSee('warehouse-top-stock-out-data', false)
            ->assertSee('Lihat data tabel')
            ->assertSee('Nama Item')
            ->assertSee('Tipe Mesin')
            ->assertSee('Jumlah')
            ->assertSee('name="trend_date_from"', false)
            ->assertSee('name="trend_date_to"', false)
            ->assertDontSee('Transaksi Terbaru')
            ->assertDontSee('Pergerakan bulan berjalan.');

        $view = file_get_contents(base_path('resources/views/warehouse/dashboard/index.blade.php'));
        self::assertStringContainsString('warehouse-top-item-data', $view);
        self::assertStringContainsString('warehouse-top-machine-data', $view);
        self::assertStringNotContainsString('warehouse-top-stock-out-data', $view);
        self::assertStringNotContainsString('recentTransactions', $view);
        $script = file_get_contents(base_path('resources/js/warehouse/dashboard.js'));
        self::assertStringContainsString("indexAxis: 'y'", $script);
        self::assertStringContainsString('prefers-reduced-motion: reduce', $script);
        self::assertStringContainsString('Collapse.getOrCreateInstance', $script);

        self::assertSame(2, substr_count($response->getContent(), 'data-warehouse-bar-chart'));
    }

    public function test_master_shipment_reporting_and_history_pages_expose_revision_two_fields(): void
    {
        $user = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['item_name' => 'UI Contract Item']);
        WarehouseConsumable::factory()->create(['item_name' => 'UI Contract Item Two']);
        WarehouseStockTransaction::factory()->create([
            'consumable_id' => $item->id,
            'verified_user_id' => $user->id,
            'created_by' => $user->id,
            'transaction_at' => '2026-01-15 08:00:00',
        ]);

        $this->actingAs($user)->get(route('warehouse.consumables.create'))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="machine_type"', false)
            ->assertSee('name="photo"', false)
            ->assertSee('Maksimal 5 MB');
        $this->actingAs($user)->get(route('warehouse.location-shipments.create'))
            ->assertOk()
            ->assertSee('name="item_condition"', false)
            ->assertSee('name="from_location"', false)
            ->assertSee('name="to_location"', false)
            ->assertSee('Pengiriman Antar Lokasi')
            ->assertSee('di-reserve');
        $this->actingAs($user)->get(route('warehouse.reports.index', ['year' => 2026]))
            ->assertOk()
            ->assertSee('Reporting Stok Tahunan')
            ->assertSee('warehouse-report-matrix', false)
            ->assertSee('Nama Barang')
            ->assertSee('Jan')
            ->assertSee('Stok Awal')
            ->assertSee('Mutasi (+)')
            ->assertSee('Mutasi (-)')
            ->assertSee('Stok Akhir')
            ->assertSee('Total')
            ->assertSee('Average')
            ->assertSee('colspan="4"', false)
            ->assertSee('rowspan="2"', false)
            ->assertSee('warehouse-report-mobile-cards', false)
            ->assertSee('Minimum')
            ->assertSee('Maksimum')
            ->assertSee('UI Contract Item')
            ->assertSee('UI Contract Item Two');
        $this->actingAs($user)->get(route('warehouse.reports.index', ['year' => 2025]))
            ->assertOk()
            ->assertSee('Belum ada transaksi Warehouse pada tahun 2025')
            ->assertSee('warehouse-report-matrix', false)
            ->assertSee('UI Contract Item')
            ->assertSee('UI Contract Item Two')
            ->assertSee('Total')
            ->assertSee('Average')
            ->assertDontSee('colspan="4"', false)
            ->assertDontSee('Stok Awal');

        $reportingCss = file_get_contents(base_path('resources/css/warehouse/reporting.css'));
        self::assertStringContainsString('overflow-x: auto', $reportingCss);
        self::assertStringContainsString('position: sticky', $reportingCss);
        self::assertStringContainsString('warehouse-report-mobile-card', $reportingCss);
        $this->actingAs($user)->get(route('warehouse.transactions.index'))
            ->assertOk()
            ->assertSee('Foreman 1')
            ->assertSee('Foreman 2')
            ->assertSee('Operation key')
            ->assertSee('Kondisi')
            ->assertSee('warehouse-history-totals-footer', false)
            ->assertSee('warehouse-history-totals-mobile', false);

        self::assertFileExists(base_path('resources/css/warehouse/location-shipments.css'));
    }

    public function test_major_pages_share_shell_and_transaction_detail_shows_location_condition_and_operation(): void
    {
        $user = $this->createUser();
        $item = WarehouseConsumable::factory()->create();
        $transaction = WarehouseStockTransaction::factory()->create([
            'consumable_id' => $item->id,
            'verified_user_id' => $user->id,
            'created_by' => $user->id,
            'transaction_type' => WarehouseTransactionType::IN,
        ]);

        foreach ([
            $this->actingAs($user)->get(route('warehouse.dashboard')),
            $this->actingAs($user)->get(route('warehouse.consumables.index')),
            $this->actingAs($user)->get(route('warehouse.adjustments.create')),
            $this->actingAs($user)->get(route('warehouse.transactions.show', $transaction)),
            $this->actingAs($user)->get(route('warehouse.location-shipments.index')),
            $this->actingAs($user)->get(route('warehouse.reports.index')),
        ] as $response) {
            $response->assertOk()->assertSee('warehouse-shell', false)->assertSee('warehouse-page', false);
        }

        $this->actingAs($user)->get(route('warehouse.transactions.show', $transaction))
            ->assertSee('Operation key')
            ->assertSee('Kondisi')
            ->assertSee('Lokasi asal')
            ->assertSee('Lokasi tujuan');
    }

    public function test_warehouse_pagination_and_status_badges_keep_bootstrap_contract(): void
    {
        foreach ([
            'resources/views/warehouse/dashboard/index.blade.php',
            'resources/views/warehouse/categories.blade.php',
            'resources/views/warehouse/consumables/index.blade.php',
            'resources/views/warehouse/transactions/index.blade.php',
        ] as $viewPath) {
            $source = file_get_contents(base_path($viewPath));
            self::assertIsString($source);
            self::assertStringContainsString("links('pagination::warehouse-bootstrap-5')", $source, $viewPath);
        }

        $badge = file_get_contents(base_path('resources/views/components/warehouse/status-badge.blade.php'));
        self::assertStringContainsString("'TRANSFER' => 'Pengiriman Antar Lokasi'", $badge);
        self::assertStringContainsString("'OUT' => 'Habis'", $badge);
    }
}

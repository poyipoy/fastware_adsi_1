<?php

namespace Tests\Feature\Warehouse;

use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;

class WarehouseUiContractTest extends WarehouseTestCase
{
    public function test_transaction_flow_exposes_three_state_contract_and_merges_verification_into_detail(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);

        $response = $this->actingAs($pic)->get(route('warehouse.transactions.create'));

        $response->assertOk()
            ->assertSee('warehouse-shell', false)
            ->assertSee('data-warehouse-step="1"', false)
            ->assertSee('data-warehouse-step="2"', false)
            ->assertSee('data-warehouse-step="3"', false)
            ->assertDontSee('data-warehouse-step="4"', false)
            ->assertSee('warehouse-stepper', false)
            ->assertSee('Rincian & verifikasi', false)
            ->assertSee('data-warehouse-verifier-panel', false)
            ->assertSee('data-warehouse-verifier-lock-message', false)
            ->assertDontSee('data-warehouse-next-detail', false)
            ->assertSee('data-warehouse-summary', false)
            ->assertSee('data-warehouse-summary-item-code', false)
            ->assertSee('data-warehouse-summary-item-barcode', false)
            ->assertSee('data-warehouse-summary-item-barcode-visual', false)
            ->assertSee('data-warehouse-summary-item-barcode-svg', false)
            ->assertSee('data-warehouse-summary-current-stock', false)
            ->assertSee('data-warehouse-summary-stock-status', false)
            ->assertSee('data-warehouse-summary-user-meta', false)
            ->assertSee('aria-atomic="true"', false)
            ->assertSee('Perkiraan stok', false)
            ->assertSee('data-warehouse-storage-location', false)
            ->assertSee('<select class="form-select" id="warehouse-storage-location" name="storage_location"', false)
            ->assertSee('<option value="DS8">DS8</option>', false)
            ->assertSee('<option value="Deltamas">Deltamas</option>', false)
            ->assertSee('storageLocationForIn', false)
            ->assertSee('Pindai barcode NPK karyawan')
            ->assertDontSee('name="reference_number"', false)
            ->assertDontSee('name="purpose"', false)
            ->assertDontSee('name="usage_location"', false)
            ->assertDontSee('name="notes"', false)
            ->assertDontSee('window.confirm', false)
            ->assertDontSee('card_code=', false);

        $html = $response->getContent();
        $stepTwoStart = strpos($html, 'data-warehouse-step="2"');
        $stepThreeStart = strpos($html, 'data-warehouse-step="3"');
        $this->assertNotFalse($stepTwoStart);
        $this->assertNotFalse($stepThreeStart);
        foreach (['data-warehouse-quantity', 'data-warehouse-storage-location', 'data-warehouse-user-input', 'data-warehouse-confirm-check', 'data-warehouse-submit'] as $selector) {
            $position = strpos($html, $selector, $stepTwoStart);
            $this->assertNotFalse($position);
            $this->assertGreaterThan($stepTwoStart, $position);
            $this->assertLessThan($stepThreeStart, $position);
        }

        $script = file_get_contents(base_path('resources/js/warehouse/transaction-form.js'));
        $this->assertStringContainsString('const syncVerifierAvailability', $script);
        $this->assertStringContainsString('const appendPreview', $script);
        $this->assertStringContainsString('warehouse-preview-facts', $script);
        $this->assertStringContainsString("['Item Code'", $script);
        $this->assertStringContainsString("['Stok saat ini'", $script);
        $this->assertStringContainsString("['Status stok'", $script);
        $this->assertStringContainsString("['Lokasi'", $script);
        $this->assertStringContainsString("['NPK'", $script);
        $this->assertStringContainsString("['Bagian'", $script);
        $this->assertStringContainsString("['Tipe'", $script);
        $this->assertStringContainsString("['Jumlah'", $script);
        $this->assertStringContainsString("['Karyawan verifikator'", $script);
        $this->assertStringContainsString("const stockStatusLabels", $script);
        $this->assertStringContainsString("const stockStatusTones", $script);
        $this->assertStringContainsString("LOW: 'warning'", $script);
        $this->assertStringContainsString("OUT: 'danger'", $script);
        $this->assertStringContainsString('const stockStatusPresentation', $script);
        $this->assertStringContainsString('renderStockStatusBadge', $script);
        $this->assertStringContainsString('warehouse-status-badge-${tone}', $script);
        $this->assertStringContainsString('warehouse-status-badge-${presentation.tone}', $script);
        $this->assertStringContainsString('badge.textContent = presentation.label', $script);
        $this->assertStringContainsString('valueElement.textContent = value', $script);
        $this->assertStringContainsString('stockStatus.replaceChildren()', $script);
        $this->assertStringContainsString("const transactionTypeLabels", $script);
        $this->assertStringContainsString("toLocaleString('id-ID')", $script);
        $this->assertStringNotContainsString('verified.card_code', $script);
        $this->assertStringContainsString('currentStep !== 2', $script);
        $this->assertStringContainsString('updateStep(3)', $script);
        $this->assertStringNotContainsString('updateStep(4)', $script);

        $dashboardView = file_get_contents(base_path('resources/views/warehouse/dashboard/index.blade.php'));
        $this->assertStringContainsString('warehouse-trend-panel', $dashboardView);
        $this->assertStringContainsString('warehouse-trend-filter', $dashboardView);
        $this->assertStringContainsString('trend_date_from', $dashboardView);
        $this->assertStringContainsString('trend_date_to', $dashboardView);
        $this->assertStringContainsString('data-warehouse-trend-filter-toggle', $dashboardView);
        $this->assertStringNotContainsString('data-bs-toggle="collapse"', $dashboardView);
        $this->assertStringContainsString('data-bs-target="#warehouse-trend-filter"', $dashboardView);
        $this->assertStringContainsString('bi bi-funnel', $dashboardView);
        $this->assertStringNotContainsString('warehouse-filter-disclosure', $dashboardView);
        $this->assertStringNotContainsString('Filter Dashboard', $dashboardView);
        $this->assertStringContainsString("->get('IN')?->quantity", $dashboardView);
        $this->assertStringContainsString("->get('OUT')?->quantity", $dashboardView);
        $this->assertStringNotContainsString("firstWhere('transaction_type', 'IN')", $dashboardView);
        $this->assertStringNotContainsString("firstWhere('transaction_type', 'OUT')", $dashboardView);

        $recentTransactionsView = file_get_contents(base_path('resources/views/warehouse/dashboard/partials/recent-transactions.blade.php'));
        $this->assertStringContainsString('$adjustmentDirection', $recentTransactionsView);
        $this->assertStringContainsString('WarehouseQuantity::compare($transaction->stock_after, $transaction->stock_before)', $recentTransactionsView);
        $this->assertStringContainsString('warehouse-transaction-direction', $recentTransactionsView);

        $stylesheet = file_get_contents(base_path('resources/css/warehouse/transaction-form.css'));
        $this->assertStringContainsString('.warehouse-preview-facts', $stylesheet);
        $this->assertStringContainsString('.warehouse-preview-fact-value .warehouse-status-badge', $stylesheet);
        $this->assertStringContainsString('.warehouse-summary-stock-status .warehouse-status-badge', $stylesheet);
        $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr))', $stylesheet);
        $this->assertStringContainsString('overflow-wrap: anywhere', $stylesheet);

        $dashboardStylesheet = file_get_contents(base_path('resources/css/warehouse/dashboard.css'));
        $this->assertStringContainsString('.warehouse-trend-filter-grid', $dashboardStylesheet);
        $this->assertStringContainsString('.warehouse-trend-filter-toggle', $dashboardStylesheet);
        $this->assertStringContainsString('grid-template-columns: 1fr', $dashboardStylesheet);

        $dashboardScript = file_get_contents(base_path('resources/js/warehouse/dashboard.js'));
        $this->assertStringContainsString('Collapse.getOrCreateInstance', $dashboardScript);
        $this->assertStringContainsString('{ toggle: false }', $dashboardScript);
        $this->assertStringContainsString('collapse.toggle()', $dashboardScript);
        $this->assertStringContainsString('shown.bs.collapse', $dashboardScript);
        $this->assertStringContainsString('hidden.bs.collapse', $dashboardScript);
        $this->assertStringContainsString("setAttribute('aria-expanded', 'true')", $dashboardScript);
        $this->assertStringContainsString("setAttribute('aria-expanded', 'false')", $dashboardScript);
    }

    public function test_starter_query_rejects_unknown_type_and_only_prefills_barcode_for_lookup(): void
    {
        $employee = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'item_name' => 'Starter Query Item',
            'barcode' => '000-STARTER-01',
        ]);

        $response = $this->actingAs($employee)->get(route('warehouse.transactions.create', [
            'type' => 'DELETE',
            'barcode' => $item->barcode,
        ]));

        $response->assertOk()
            ->assertSee('data-warehouse-initial-type="IN"', false)
            ->assertSee('data-warehouse-initial-barcode="000-STARTER-01"', false)
            ->assertDontSee('Starter Query Item');
    }

    public function test_permission_based_dashboard_actions_and_mobile_contract(): void
    {
        $employee = $this->createUser();
        $employeeResponse = $this->actingAs($employee)->get(route('warehouse.dashboard'));

        $employeeResponse->assertOk()
            ->assertDontSee('warehouse-filter-disclosure', false)
            ->assertSee('Tren Stock In/Out')
            ->assertSee('warehouse-trend-filter', false)
            ->assertSee('data-warehouse-trend-filter-toggle', false)
            ->assertSee('name="trend_date_from"', false)
            ->assertSee('name="trend_date_to"', false)
            ->assertDontSee('Filter Dashboard')
            ->assertSee('warehouse-kpi-grid', false)
            ->assertSee('Stock In Bulan Ini')
            ->assertSee('Stock Out Bulan Ini')
            ->assertSee('Pergerakan bulan berjalan.')
            ->assertDontSee('Stock In Today')
            ->assertDontSee('Stock Out Today')
            ->assertSee('type=IN', false)
            ->assertSee('type=OUT', false)
            ->assertSee('warehouse-page-actions', false);
    }

    public function test_master_and_direct_npk_scanner_follow_the_approved_contract(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);

        $masterForm = $this->actingAs($pic)->get(route('warehouse.consumables.create'));
        $masterForm->assertOk()
            ->assertSee('name="item_code"', false)
            ->assertSee('name="item_name"', false)
            ->assertSee('name="minimum_stock"', false)
            ->assertSee('name="maximum_stock"', false)
            ->assertSee('name="storage_location"', false)
            ->assertSee('<select class="form-select" id="storage_location" name="storage_location"', false)
            ->assertSee('<option value="DS8"', false)
            ->assertSee('<option value="Deltamas"', false)
            ->assertSee('Item Code adalah nilai yang dibaca scanner', false);

        $masterFormSource = file_get_contents(base_path('resources/views/warehouse/consumables/form.blade.php'));
        self::assertIsString($masterFormSource);
        foreach (['barcode', 'category_id', 'unit', 'allow_fraction', 'description'] as $removedField) {
            self::assertStringNotContainsString('name="'.$removedField.'"', $masterFormSource);
        }

        $masterIndex = $this->actingAs($pic)->get(route('warehouse.consumables.index'));
        $masterIndex->assertOk()
            ->assertSee('<th scope="col">Item Code</th>', false)
            ->assertSee('<th scope="col">Nama barang</th>', false)
            ->assertSee('<th scope="col">Minimum</th>', false)
            ->assertSee('<th scope="col">Maksimum</th>', false)
            ->assertSee('<th scope="col">Lokasi</th>', false)
            ->assertDontSee('<th scope="col">Barcode</th>', false)
            ->assertDontSee('<th scope="col">Kategori</th>', false)
            ->assertDontSee('<th scope="col">Unit</th>', false);

        self::assertFalse(\Illuminate\Support\Facades\Route::has('warehouse.user-cards.index'));
        self::assertFileDoesNotExist(base_path('resources/views/warehouse/user-cards/index.blade.php'));
        $masterIndex->assertDontSee('Pemetaan ID Karyawan');

        $script = file_get_contents(base_path('resources/js/warehouse/transaction-form.js'));
        self::assertIsString($script);
        self::assertStringContainsString("requestJson('/warehouse/scans/user', { code, type: typeInput.value })", $script);
        self::assertStringContainsString('Barcode NPK karyawan wajib diverifikasi', $script);
        self::assertStringNotContainsString('belum dipetakan', $script);
    }

    public function test_warehouse_pagination_uses_bootstrap_five_markup(): void
    {
        foreach ([
            'resources/views/warehouse/dashboard/index.blade.php',
            'resources/views/warehouse/categories/index.blade.php',
            'resources/views/warehouse/consumables/index.blade.php',
            'resources/views/warehouse/transactions/index.blade.php',
        ] as $viewPath) {
            $source = file_get_contents(base_path($viewPath));

            self::assertIsString($source);
            self::assertStringContainsString("links('pagination::warehouse-bootstrap-5')", $source, $viewPath);
            self::assertStringNotContainsString('->links()', $source, $viewPath);
        }

        $badge = file_get_contents(base_path('resources/views/components/warehouse/status-badge.blade.php'));
        self::assertStringContainsString("'transaction'", $badge);
        self::assertStringContainsString("'stock'", $badge);
        self::assertStringContainsString("'activity'", $badge);
        self::assertStringContainsString("'Keluar'", $badge);
        self::assertStringContainsString("'Habis'", $badge);
        self::assertStringContainsString("\$context === 'stock' && \$value === 'OUT'", $badge);
        self::assertStringContainsString("? 'danger'", $badge);
        self::assertStringContainsString("'OUT', 'LOW', 'WARNING', 'PENDING' => 'warning'", $badge);

        $pagination = file_get_contents(base_path('resources/views/vendor/pagination/warehouse-bootstrap-5.blade.php'));
        self::assertStringContainsString('Sebelumnya', $pagination);
        self::assertStringContainsString('Berikutnya', $pagination);
        self::assertStringContainsString('Menampilkan', $pagination);
    }

    public function test_major_warehouse_pages_share_shell_and_responsive_contract(): void
    {
        $pic = $this->createUser();
        $this->createPicPosition($pic);
        $admin = $this->createUser(['role_id' => 1]);
        $verified = $this->createUser(['name' => 'UI Verified Employee']);
        $item = WarehouseConsumable::factory()->create(['item_name' => 'UI Contract Item']);
        $transaction = WarehouseStockTransaction::factory()->create([
            'consumable_id' => $item->id,
            'verified_user_id' => $verified->id,
            'created_by' => $admin->id,
            'transaction_type' => WarehouseTransactionType::IN,
        ]);

        $requests = [
            $this->actingAs($pic)->get(route('warehouse.consumables.index')),
            $this->actingAs($pic)->get(route('warehouse.consumables.create')),
            $this->actingAs($pic)->get(route('warehouse.consumables.show', $item)),
            $this->actingAs($pic)->get(route('warehouse.categories.index')),
            $this->actingAs($pic)->get(route('warehouse.adjustments.create')),
            $this->actingAs($admin)->get(route('warehouse.transactions.index')),
            $this->actingAs($admin)->get(route('warehouse.transactions.show', $transaction)),
            $this->actingAs($admin)->get(route('warehouse.transactions.reverse-form', $transaction)),
        ];

        foreach ($requests as $response) {
            $response->assertOk()->assertSee('warehouse-shell', false)->assertSee('warehouse-page', false);
        }
    }
}

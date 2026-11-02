<?php

namespace Tests\Feature;

use App\Http\Controllers\OutstandingMaterialController;
use App\Models\OutstandingMaterial;
use App\Models\OutstandingMaterialInvoice;
use App\Models\User;
use App\Services\OutstandingMaterialAccessService;
use App\Services\OutstandingMaterialBatchService;
use App\Services\OutstandingMaterialDocumentService;
use App\Services\OutstandingMaterialIdentityService;
use App\Services\OutstandingMaterialImportPreviewService;
use App\Services\OutstandingMaterialInvoiceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class OutstandingMaterialInvoiceArchiveUiTest extends TestCase
{
    use DatabaseTransactions;

    private OutstandingMaterialIdentityService $identity;

    private OutstandingMaterialInvoiceService $invoices;

    private OutstandingMaterialController $controller;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $this->invoices = new OutstandingMaterialInvoiceService($this->identity);

        $this->controller = new OutstandingMaterialController(
            new TestUiArchiveAccessService(),
            $documents,
            $this->identity,
            new OutstandingMaterialBatchService(
                $this->identity,
                $documents,
                $this->invoices,
            ),
            new OutstandingMaterialImportPreviewService($this->identity, $documents, $this->invoices),
            $this->invoices,
        );

        $this->adminUser = new User(['id' => 1, 'name' => 'ADMINISTRATOR']);
        Auth::setUser($this->adminUser);
    }

    public function test_archived_invoices_page_renders_for_authorized_user(): void
    {
        $response = $this->controller->archivedInvoiceIndex();

        $this->assertEquals('outstanding_materials.archived_invoices', $response->name());
        $this->assertTrue($response->getData()['canManageOutstandingMaterials']);
    }

    public function test_archived_invoices_data_returns_archived_invoices_only(): void
    {
        // 1 Active invoice
        $activeKey = $this->identity->invoiceKey('Supplier Active', 'INV-ACT-UI-01');
        $activeHeader = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier Active',
            'number_invoice' => 'INV-ACT-UI-01',
            'invoice_identity_key' => $activeKey,
            'archived_at' => null,
        ]);
        $activeMat = OutstandingMaterial::create([
            'supplier' => 'Supplier Active',
            'number_invoice' => 'INV-ACT-UI-01',
            'invoice_id' => $activeHeader->id,
            'invoice_identity_key' => $activeKey,
            'type' => 'Mat Active',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        // 1 Archived invoice
        $archKey = $this->identity->invoiceKey('Supplier Archived', 'INV-ARCH-UI-01');
        $archHeader = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier Archived',
            'number_invoice' => 'INV-ARCH-UI-01',
            'invoice_identity_key' => $archKey,
            'archived_at' => now()->subHour(),
        ]);
        $archMat = OutstandingMaterial::create([
            'supplier' => 'Supplier Archived',
            'number_invoice' => 'INV-ARCH-UI-01',
            'invoice_id' => $archHeader->id,
            'invoice_identity_key' => $archKey,
            'type' => 'Mat Archived',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $request = Request::create('/outstanding-materials/archived-invoices/data', 'GET', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]);

        $response = $this->controller->archivedInvoiceData($request);
        $data = $response->getData(true);

        $this->assertEquals(1, $data['recordsTotal']);
        $this->assertCount(1, $data['data']);
        $this->assertEquals('INV-ARCH-UI-01', $data['data'][0]['number_invoice']);
        $this->assertEquals('Supplier Archived', $data['data'][0]['supplier']);
    }

    public function test_archived_invoices_data_row_contains_detail_and_restore_action_and_no_edit_or_delete(): void
    {
        $archKey = $this->identity->invoiceKey('Supplier Safe', 'INV-ARCH-SAFE-01');
        $archHeader = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier Safe',
            'number_invoice' => 'INV-ARCH-SAFE-01',
            'invoice_identity_key' => $archKey,
            'archived_at' => now(),
        ]);
        $archMat = OutstandingMaterial::create([
            'supplier' => 'Supplier Safe',
            'number_invoice' => 'INV-ARCH-SAFE-01',
            'invoice_id' => $archHeader->id,
            'invoice_identity_key' => $archKey,
            'type' => 'Mat Safe',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $request = Request::create('/outstanding-materials/archived-invoices/data', 'GET');
        $response = $this->controller->archivedInvoiceData($request);
        $data = $response->getData(true);

        $actionsHtml = $data['data'][0]['actions'];
        $this->assertStringContainsString('om-action-btn--detail', $actionsHtml);
        $this->assertStringContainsString('om-action-btn--restore', $actionsHtml);
        $this->assertStringNotContainsString('js-open-invoice-modal', $actionsHtml);
        $this->assertStringNotContainsString('om-action-btn--delete', $actionsHtml);
    }

    public function test_active_invoice_data_shows_archive_action_enabled_when_eligible_and_disabled_when_ineligible(): void
    {
        // Eligible invoice
        $keyEligible = $this->identity->invoiceKey('Supplier El', 'INV-EL-01');
        $headerEligible = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier El',
            'number_invoice' => 'INV-EL-01',
            'invoice_identity_key' => $keyEligible,
            'archived_at' => null,
        ]);
        $matEligible = OutstandingMaterial::create([
            'supplier' => 'Supplier El',
            'number_invoice' => 'INV-EL-01',
            'invoice_id' => $headerEligible->id,
            'invoice_identity_key' => $keyEligible,
            'type' => 'Mat El',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        // Ineligible invoice (status = Production)
        $keyIneligible = $this->identity->invoiceKey('Supplier Inel', 'INV-INEL-01');
        $headerIneligible = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier Inel',
            'number_invoice' => 'INV-INEL-01',
            'invoice_identity_key' => $keyIneligible,
            'archived_at' => null,
        ]);
        $matIneligible = OutstandingMaterial::create([
            'supplier' => 'Supplier Inel',
            'number_invoice' => 'INV-INEL-01',
            'invoice_id' => $headerIneligible->id,
            'invoice_identity_key' => $keyIneligible,
            'type' => 'Mat Inel',
            'status' => 'Production',
            'keterangan' => 'On Schedule',
        ]);

        $request = Request::create('/outstanding-materials/show-based-on-invoice/data', 'GET');
        $response = $this->controller->invoiceData($request);
        $data = $response->getData(true);

        $rows = collect($data['data'])->keyBy('number_invoice');

        // Eligible row: has archive form with submit button
        $this->assertArrayHasKey('INV-EL-01', $rows);
        $this->assertStringContainsString('js-outstanding-invoice-archive-form', $rows['INV-EL-01']['actions']);
        $this->assertStringNotContainsString('disabled', $rows['INV-EL-01']['actions']);

        // Ineligible row: archive button is disabled with explanation tooltip
        $this->assertArrayHasKey('INV-INEL-01', $rows);
        $this->assertStringContainsString('disabled', $rows['INV-INEL-01']['actions']);
        $this->assertStringContainsString('Invoice hanya dapat diarsipkan jika semua material Received dan Closed', $rows['INV-INEL-01']['actions']);
    }

    public function test_archive_invoice_endpoint_archives_eligible_group(): void
    {
        $supplier = 'Archive Controller Supplier';
        $invoice = 'INV-ARCH-CTRL-01';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);
        $mat = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Type 1',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $response = $this->controller->archiveInvoice($mat);

        $this->assertEquals(302, $response->getStatusCode());
        $header->refresh();
        $this->assertNotNull($header->archived_at);
        $this->assertTrue($header->isArchived());
    }

    public function test_archive_invoice_endpoint_rejects_ineligible_group_with_error_flash(): void
    {
        $supplier = 'Archive Controller Ineligible Supplier';
        $invoice = 'INV-ARCH-CTRL-INEL-01';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);
        $mat = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Type 1',
            'status' => 'On Shipment',
            'keterangan' => 'Delay',
        ]);

        $response = $this->controller->archiveInvoice($mat);

        $this->assertEquals(302, $response->getStatusCode());
        $header->refresh();
        $this->assertNull($header->archived_at);
        $this->assertFalse($header->isArchived());
    }

    public function test_restore_invoice_endpoint_restores_archived_group(): void
    {
        $supplier = 'Restore Controller Supplier';
        $invoice = 'INV-REST-CTRL-01';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'archived_at' => now()->subDay(),
        ]);
        $mat = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Type 1',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $response = $this->controller->restoreInvoice($mat);

        $this->assertEquals(302, $response->getStatusCode());
        $header->refresh();
        $this->assertNull($header->archived_at);
        $this->assertFalse($header->isArchived());
    }

    public function test_archived_invoice_detail_view_remains_accessible(): void
    {
        $supplier = 'Detail View Supplier';
        $invoice = 'INV-DET-01';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'archived_at' => now(),
        ]);
        $mat = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Detail Material',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $response = $this->controller->show($mat);
        $this->assertEquals('outstanding_materials.show', $response->name());
        $this->assertEquals($mat->id, $response->getData()['material']->id);
    }
}

class TestUiArchiveAccessService extends OutstandingMaterialAccessService
{
    public function canView(?User $user): bool
    {
        return true;
    }

    public function canManage(?User $user): bool
    {
        return true;
    }

    public function canExport(?User $user): bool
    {
        return true;
    }

    public function isSales(?User $user): bool
    {
        return false;
    }

    public function canUploadInvoiceDocuments(?User $user): bool
    {
        return true;
    }

    public function canDownloadInvoiceDocuments(?User $user): bool
    {
        return true;
    }
}

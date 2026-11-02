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
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OutstandingMaterialPerMaterialArchiveTest extends TestCase
{
    use DatabaseTransactions;

    private OutstandingMaterialIdentityService $identity;

    private OutstandingMaterialInvoiceService $invoices;

    private OutstandingMaterialController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $this->invoices = new OutstandingMaterialInvoiceService($this->identity);

        $this->controller = new OutstandingMaterialController(
            new TestPerMaterialArchiveAccessService(),
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

        Auth::setUser(new User(['name' => 'ADMINISTRATOR']));
    }

    public function test_can_archive_individual_material_when_received_and_closed(): void
    {
        $supplier = 'PT Steel Jaya';
        $invoiceNo = 'INV-PARTIAL-01';
        $key = $this->identity->invoiceKey($supplier, $invoiceNo);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);

        $matA = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Sheet A',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'qty_pcs' => 10,
            'est_qty_kg' => 100,
        ]);

        $matB = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Sheet B',
            'status' => 'On Shipment',
            'keterangan' => 'On Schedule',
            'qty_pcs' => 20,
            'est_qty_kg' => 200,
        ]);

        $request = Request::create('/outstanding-materials/materials/archive', 'POST', [
            'material_ids' => [$matA->id],
        ]);

        $response = $this->controller->archiveMaterials($request);
        $this->assertSame(200, $response->getStatusCode());

        $matA->refresh();
        $matB->refresh();
        $header->refresh();

        $this->assertNotNull($matA->archived_at, 'Material A should have archived_at set');
        $this->assertTrue($matA->isArchived());
        $this->assertNull($matB->archived_at, 'Material B should remain active');
        $this->assertFalse($matB->isArchived());
        $this->assertNull($header->archived_at, 'Invoice header should remain active because Material B is active');
    }

    public function test_cannot_archive_material_if_not_received_and_closed(): void
    {
        $supplier = 'PT Steel Jaya';
        $invoiceNo = 'INV-PARTIAL-02';
        $key = $this->identity->invoiceKey($supplier, $invoiceNo);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);

        $mat = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Sheet X',
            'status' => 'On Shipment',
            'keterangan' => 'On Schedule',
            'qty_pcs' => 10,
            'est_qty_kg' => 100,
        ]);

        $request = Request::create('/outstanding-materials/materials/archive', 'POST', [
            'material_ids' => [$mat->id],
        ]);

        $this->expectException(ValidationException::class);
        $this->controller->archiveMaterials($request);
    }

    public function test_partial_archiving_leaves_invoice_in_both_active_and_archived_tables_with_accurate_counts(): void
    {
        $supplier = 'PT Multi Parts';
        $invoiceNo = 'INV-SPLIT-99';
        $key = $this->identity->invoiceKey($supplier, $invoiceNo);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);

        $mat1 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Pipe 1',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'qty_pcs' => 5,
            'est_qty_kg' => 50,
        ]);

        $mat2 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Pipe 2',
            'status' => 'On Production',
            'keterangan' => 'Delay',
            'qty_pcs' => 15,
            'est_qty_kg' => 150,
        ]);

        // Archive mat1 only
        $this->invoices->archiveMaterials([$mat1->id]);

        // Active invoice data
        $activeRequest = Request::create('/outstanding-materials/show-based-on-invoice/data', 'GET', [
            'search' => ['value' => $invoiceNo],
        ]);
        $activeResponse = $this->controller->invoiceData($activeRequest);
        $activeData = json_decode($activeResponse->getContent(), true)['data'];

        $this->assertCount(1, $activeData);
        $this->assertSame('1', $activeData[0]['material_count'], 'Active list should count only 1 unarchived material');

        // Archived invoice data
        $archivedRequest = Request::create('/outstanding-materials/archived-invoices/data', 'GET', [
            'search' => ['value' => $invoiceNo],
        ]);
        $archivedResponse = $this->controller->archivedInvoiceData($archivedRequest);
        $archivedData = json_decode($archivedResponse->getContent(), true)['data'];

        $this->assertCount(1, $archivedData);
        $this->assertSame('1', $archivedData[0]['material_count'], 'Archived list should count 1 archived material');

        // Scoped materials for modal:
        // Active endpoint should return ONLY active mat2
        $activeScopedResponse = $this->controller->invoiceMaterialsForAnchor($mat2);
        $activeScopedRows = json_decode($activeScopedResponse->getContent(), true);
        $this->assertCount(1, $activeScopedRows);
        $this->assertSame($mat2->id, $activeScopedRows[0]['id']);

        // Archived endpoint should return ONLY archived mat1
        $archivedScopedResponse = $this->controller->archivedInvoiceMaterialsForAnchor($mat1);
        $archivedScopedRows = json_decode($archivedScopedResponse->getContent(), true);
        $this->assertCount(1, $archivedScopedRows);
        $this->assertSame($mat1->id, $archivedScopedRows[0]['id']);
    }

    public function test_archiving_all_materials_in_invoice_archives_header_and_removes_from_active_list(): void
    {
        $supplier = 'PT Full Complete';
        $invoiceNo = 'INV-FULL-01';
        $key = $this->identity->invoiceKey($supplier, $invoiceNo);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);

        $mat1 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Item 1',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'qty_pcs' => 10,
            'est_qty_kg' => 100,
        ]);

        $mat2 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Item 2',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'qty_pcs' => 20,
            'est_qty_kg' => 200,
        ]);

        // Archive both materials
        $this->invoices->archiveMaterials([$mat1->id, $mat2->id]);

        $header->refresh();
        $this->assertNotNull($header->archived_at, 'Invoice header should be stamped archived when all materials are archived');

        // Active invoice data query should not include this invoice
        $activeRequest = Request::create('/outstanding-materials/show-based-on-invoice/data', 'GET', [
            'search' => ['value' => $invoiceNo],
        ]);
        $activeResponse = $this->controller->invoiceData($activeRequest);
        $activeData = json_decode($activeResponse->getContent(), true)['data'];
        $this->assertCount(0, $activeData);

        // Archived invoice data query should show material_count = 2
        $archivedRequest = Request::create('/outstanding-materials/archived-invoices/data', 'GET', [
            'search' => ['value' => $invoiceNo],
        ]);
        $archivedResponse = $this->controller->archivedInvoiceData($archivedRequest);
        $archivedData = json_decode($archivedResponse->getContent(), true)['data'];
        $this->assertCount(1, $archivedData);
        $this->assertSame('2', $archivedData[0]['material_count']);
    }

    public function test_can_restore_individual_material_which_returns_it_to_active_list(): void
    {
        $supplier = 'PT Full Complete';
        $invoiceNo = 'INV-FULL-02';
        $key = $this->identity->invoiceKey($supplier, $invoiceNo);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_identity_key' => $key,
            'archived_at' => now(),
        ]);

        $mat1 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Item 1',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'qty_pcs' => 10,
            'est_qty_kg' => 100,
            'archived_at' => now(),
        ]);

        $mat2 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Item 2',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'qty_pcs' => 20,
            'est_qty_kg' => 200,
            'archived_at' => now(),
        ]);

        $restoreRequest = Request::create('/outstanding-materials/materials/restore', 'POST', [
            'material_ids' => [$mat1->id],
        ]);

        $response = $this->controller->restoreMaterials($restoreRequest);
        $this->assertSame(200, $response->getStatusCode());

        $mat1->refresh();
        $mat2->refresh();
        $header->refresh();

        $this->assertNull($mat1->archived_at, 'Material 1 should be active');
        $this->assertNotNull($mat2->archived_at, 'Material 2 should remain archived');
        $this->assertNull($header->archived_at, 'Invoice header archived_at must be reset to NULL because Material 1 is restored');

        // Check active invoices: count is 1
        $activeRequest = Request::create('/outstanding-materials/show-based-on-invoice/data', 'GET', [
            'search' => ['value' => $invoiceNo],
        ]);
        $activeResponse = $this->controller->invoiceData($activeRequest);
        $activeData = json_decode($activeResponse->getContent(), true)['data'];
        $this->assertCount(1, $activeData);
        $this->assertSame('1', $activeData[0]['material_count']);
    }

    public function test_mutation_protection_rejects_updating_or_deleting_archived_material(): void
    {
        $supplier = 'PT Protected';
        $invoiceNo = 'INV-PROT-01';
        $key = $this->identity->invoiceKey($supplier, $invoiceNo);

        $mat = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_identity_key' => $key,
            'type' => 'Bar Protected',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'qty_pcs' => 10,
            'est_qty_kg' => 100,
            'archived_at' => now(),
        ]);

        // Attempting update on archived material should throw ValidationException
        $updateRequest = Request::create('/outstanding-materials/'.$mat->id, 'PUT', [
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'type' => 'Bar Protected Modified',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'qty_pcs' => 15,
            'est_qty_kg' => 150,
        ]);

        try {
            $this->controller->update($updateRequest, $mat);
            $this->fail('Expected ValidationException when updating archived material');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('sudah diarsipkan', $e->getMessage());
        }

        // Attempting destroy on archived material should throw ValidationException
        try {
            $this->controller->destroy($mat);
            $this->fail('Expected ValidationException when deleting archived material');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('sudah diarsipkan', $e->getMessage());
        }
    }

    public function test_auto_archive_option_when_updating_invoice_materials(): void
    {
        $supplier = 'PT Auto Archive';
        $invoiceNo = 'INV-AUTO-01';
        $key = $this->identity->invoiceKey($supplier, $invoiceNo);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);

        $mat = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoiceNo,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Coil Auto',
            'status' => 'On Shipment',
            'keterangan' => 'Delay',
            'qty_pcs' => 10,
            'est_qty_kg' => 100,
            'archived_at' => null,
        ]);

        $request = Request::create('/outstanding-materials/show-based-on-invoice/'.$mat->id.'/update', 'POST', [
            'material_ids' => [$mat->id],
            'status' => 'Received',
            'keterangan' => 'Closed',
            'auto_archive' => 1,
        ]);

        $response = $this->controller->updateInvoiceFieldsForAnchor($request, $mat);
        $this->assertSame(200, $response->getStatusCode());

        $mat->refresh();
        $this->assertSame('Received', $mat->status);
        $this->assertSame('Closed', $mat->keterangan);
        // Gate 1 & Gate 2 requirement: auto_archive is removed; no automatic archiving occurs
        $this->assertNull($mat->archived_at, 'Material must not be auto-archived per Gate 2 rules');
    }
}

class TestPerMaterialArchiveAccessService extends OutstandingMaterialAccessService
{
    public function canView(?User $user): bool
    {
        return true;
    }

    public function canManage(?User $user): bool
    {
        return true;
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

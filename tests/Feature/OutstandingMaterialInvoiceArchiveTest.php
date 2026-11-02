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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OutstandingMaterialInvoiceArchiveTest extends TestCase
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
            new TestArchiveAccessService(),
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

    public function test_scope_unarchived_excludes_archived_materials(): void
    {
        $headerActive = OutstandingMaterialInvoice::create([
            'supplier' => 'Alpha Supplier',
            'number_invoice' => 'INV-ACTIVE-01',
            'invoice_identity_key' => $this->identity->invoiceKey('Alpha Supplier', 'INV-ACTIVE-01'),
            'archived_at' => null,
        ]);

        $headerArchived = OutstandingMaterialInvoice::create([
            'supplier' => 'Beta Supplier',
            'number_invoice' => 'INV-ARCHIVED-01',
            'invoice_identity_key' => $this->identity->invoiceKey('Beta Supplier', 'INV-ARCHIVED-01'),
            'archived_at' => now(),
        ]);

        $matActive = OutstandingMaterial::create([
            'supplier' => 'Alpha Supplier',
            'number_invoice' => 'INV-ACTIVE-01',
            'invoice_id' => $headerActive->id,
            'invoice_identity_key' => $headerActive->invoice_identity_key,
            'type' => 'Steel Plate',
            'status' => 'On Shipment',
            'keterangan' => 'On Schedule',
            'qty_pcs' => 10,
            'est_qty_kg' => 100,
        ]);

        $matArchived = OutstandingMaterial::create([
            'supplier' => 'Beta Supplier',
            'number_invoice' => 'INV-ARCHIVED-01',
            'invoice_id' => $headerArchived->id,
            'invoice_identity_key' => $headerArchived->invoice_identity_key,
            'type' => 'Steel Bar',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'qty_pcs' => 20,
            'est_qty_kg' => 200,
        ]);

        $unarchivedIds = OutstandingMaterial::unarchived()->pluck('id')->all();
        $this->assertContains($matActive->id, $unarchivedIds);
        $this->assertNotContains($matArchived->id, $unarchivedIds);

        $archivedIds = OutstandingMaterial::archived()->pluck('id')->all();
        $this->assertNotContains($matActive->id, $archivedIds);
        $this->assertContains($matArchived->id, $archivedIds);
    }

    public function test_archived_materials_disappear_from_active_material_index_and_kpis(): void
    {
        $headerActive = OutstandingMaterialInvoice::create([
            'supplier' => 'Gamma Supplier',
            'number_invoice' => 'INV-ACT-INDEX',
            'invoice_identity_key' => $this->identity->invoiceKey('Gamma Supplier', 'INV-ACT-INDEX'),
            'archived_at' => null,
        ]);

        $headerArchived = OutstandingMaterialInvoice::create([
            'supplier' => 'Delta Supplier',
            'number_invoice' => 'INV-ARC-INDEX',
            'invoice_identity_key' => $this->identity->invoiceKey('Delta Supplier', 'INV-ARC-INDEX'),
            'archived_at' => now(),
        ]);

        $matActive = OutstandingMaterial::create([
            'supplier' => 'Gamma Supplier',
            'number_invoice' => 'INV-ACT-INDEX',
            'invoice_id' => $headerActive->id,
            'invoice_identity_key' => $headerActive->invoice_identity_key,
            'type' => 'Plate',
            'status' => 'On Production',
            'keterangan' => 'On Schedule',
            'qty_pcs' => 5,
            'est_qty_kg' => 50,
        ]);

        $matArchived = OutstandingMaterial::create([
            'supplier' => 'Delta Supplier',
            'number_invoice' => 'INV-ARC-INDEX',
            'invoice_id' => $headerArchived->id,
            'invoice_identity_key' => $headerArchived->invoice_identity_key,
            'type' => 'Bar',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'qty_pcs' => 50,
            'est_qty_kg' => 500,
        ]);

        $request = Request::create('/outstanding-materials/data', 'GET', ['length' => 100]);
        $response = $this->controller->data($request);
        $json = $response->getData(true);

        $returnedInvoices = array_column($json['data'], 'number_invoice');
        $this->assertContains('INV-ACT-INDEX', $returnedInvoices);
        $this->assertNotContains('INV-ARC-INDEX', $returnedInvoices);

        // Test invoice list grouping isolation
        $invoiceRequest = Request::create('/outstanding-materials/invoice/data', 'GET', ['length' => 100]);
        $invoiceResponse = $this->controller->invoiceData($invoiceRequest);
        $invoiceJson = $invoiceResponse->getData(true);
        $groupedInvoices = array_column($invoiceJson['data'], 'number_invoice');
        $this->assertContains('INV-ACT-INDEX', $groupedInvoices);
        $this->assertNotContains('INV-ARC-INDEX', $groupedInvoices);
    }

    public function test_legacy_unlinked_materials_are_isolated_and_reconciled_on_archive(): void
    {
        $identityKey = $this->identity->invoiceKey('Legacy Supplier', 'INV-LEGACY-01');

        $headerArchived = OutstandingMaterialInvoice::create([
            'supplier' => 'Legacy Supplier',
            'number_invoice' => 'INV-LEGACY-01',
            'invoice_identity_key' => $identityKey,
            'archived_at' => now(),
        ]);

        // Unlinked legacy material with invoice_id = null but same identity key
        $legacyMat = OutstandingMaterial::create([
            'supplier' => 'Legacy Supplier',
            'number_invoice' => 'INV-LEGACY-01',
            'invoice_id' => null,
            'invoice_identity_key' => $identityKey,
            'type' => 'Legacy Bar',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'qty_pcs' => 15,
            'est_qty_kg' => 150,
        ]);

        $unarchived = OutstandingMaterial::unarchived()->whereKey($legacyMat->id)->exists();
        $this->assertFalse($unarchived, 'Legacy material matching archived identity key must be excluded from unarchived scope');

        $archived = OutstandingMaterial::archived()->whereKey($legacyMat->id)->exists();
        $this->assertTrue($archived, 'Legacy material matching archived identity key must be included in archived scope');
    }

    public function test_cross_supplier_identical_invoices_remain_isolated(): void
    {
        $invNumber = 'COLLISION-INV-99';

        $keyA = $this->identity->invoiceKey('Supplier A', $invNumber);
        $keyB = $this->identity->invoiceKey('Supplier B', $invNumber);

        $this->assertNotEquals($keyA, $keyB);

        $headerArchivedA = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier A',
            'number_invoice' => $invNumber,
            'invoice_identity_key' => $keyA,
            'archived_at' => now(),
        ]);

        $headerActiveB = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier B',
            'number_invoice' => $invNumber,
            'invoice_identity_key' => $keyB,
            'archived_at' => null,
        ]);

        $matA = OutstandingMaterial::create([
            'supplier' => 'Supplier A',
            'number_invoice' => $invNumber,
            'invoice_id' => $headerArchivedA->id,
            'invoice_identity_key' => $keyA,
            'type' => 'Type A',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $matB = OutstandingMaterial::create([
            'supplier' => 'Supplier B',
            'number_invoice' => $invNumber,
            'invoice_id' => $headerActiveB->id,
            'invoice_identity_key' => $keyB,
            'type' => 'Type B',
            'status' => 'On Shipment',
            'keterangan' => 'On Schedule',
        ]);

        $unarchivedIds = OutstandingMaterial::unarchived()->whereIn('id', [$matA->id, $matB->id])->pluck('id')->all();
        $this->assertNotContains($matA->id, $unarchivedIds);
        $this->assertContains($matB->id, $unarchivedIds);
    }

    public function test_archived_materials_remain_accessible_through_historical_views(): void
    {
        $headerArchived = OutstandingMaterialInvoice::create([
            'supplier' => 'Historical Supplier',
            'number_invoice' => 'INV-HIST-01',
            'invoice_identity_key' => $this->identity->invoiceKey('Historical Supplier', 'INV-HIST-01'),
            'archived_at' => now(),
        ]);

        $mat = OutstandingMaterial::create([
            'supplier' => 'Historical Supplier',
            'number_invoice' => 'INV-HIST-01',
            'invoice_id' => $headerArchived->id,
            'invoice_identity_key' => $headerArchived->invoice_identity_key,
            'type' => 'Historical Bar',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'qty_pcs' => 10,
        ]);

        $request = Request::create('/outstanding-materials/invoice-detail/'.$mat->id.'/data', 'GET');
        $response = $this->controller->invoiceDetailData($request, $mat);

        $this->assertEquals(200, $response->getStatusCode());
        $json = $response->getData(true);
        $this->assertEquals(1, $json['recordsTotal']);
        $this->assertCount(1, $json['data']);
    }

    public function test_no_physical_deletion_occurs_when_archived(): void
    {
        $header = OutstandingMaterialInvoice::create([
            'supplier' => 'Safe Supplier',
            'number_invoice' => 'INV-SAFE-01',
            'invoice_identity_key' => $this->identity->invoiceKey('Safe Supplier', 'INV-SAFE-01'),
            'archived_at' => now(),
        ]);

        $mat = OutstandingMaterial::create([
            'supplier' => 'Safe Supplier',
            'number_invoice' => 'INV-SAFE-01',
            'invoice_id' => $header->id,
            'invoice_identity_key' => $header->invoice_identity_key,
            'type' => 'Safe Bar',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'qty_pcs' => 30,
            'est_qty_kg' => 300,
        ]);

        $this->assertDatabaseHas('outstanding_materials', [
            'id' => $mat->id,
            'qty_pcs' => 30,
            'status' => 'Received',
            'keterangan' => 'Closed',
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('outstanding_material_invoices', [
            'id' => $header->id,
            'number_invoice' => 'INV-SAFE-01',
        ]);
    }

    public function test_add_material_to_archived_invoice_is_rejected_with_422(): void
    {
        $headerArchived = OutstandingMaterialInvoice::create([
            'supplier' => 'Archived Supplier',
            'number_invoice' => 'INV-MUT-01',
            'invoice_identity_key' => $this->identity->invoiceKey('Archived Supplier', 'INV-MUT-01'),
            'archived_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        $request = Request::create('/outstanding-materials', 'POST', [
            'supplier' => 'Archived Supplier',
            'number_invoice' => 'INV-MUT-01',
            'type' => 'New Type',
            'status' => 'Received',
        ]);

        $this->controller->store($request);
    }

    public function test_batch_add_to_archived_invoice_is_rejected_with_422(): void
    {
        $headerArchived = OutstandingMaterialInvoice::create([
            'supplier' => 'Archived Supplier',
            'number_invoice' => 'INV-BATCH-MUT',
            'invoice_identity_key' => $this->identity->invoiceKey('Archived Supplier', 'INV-BATCH-MUT'),
            'archived_at' => now(),
        ]);

        $batchService = new OutstandingMaterialBatchService(
            $this->identity,
            new OutstandingMaterialDocumentService(),
            new OutstandingMaterialInvoiceService($this->identity),
        );

        $this->expectException(ValidationException::class);

        $batchService->create(
            [
                'supplier' => 'Archived Supplier',
                'number_invoice' => 'INV-BATCH-MUT',
                'status' => 'Received',
            ],
            [
                ['type' => 'Batch Type 1', 'qty_pcs' => 5],
            ],
            null,
            1,
        );
    }

    public function test_import_add_targeting_archived_invoice_is_rejected_with_422(): void
    {
        $key = $this->identity->invoiceKey('Import Supplier', 'INV-IMP-01');
        OutstandingMaterialInvoice::create([
            'supplier' => 'Import Supplier',
            'number_invoice' => 'INV-IMP-01',
            'invoice_identity_key' => $key,
            'archived_at' => now(),
        ]);

        $importService = new OutstandingMaterialImportPreviewService(
            $this->identity,
            new OutstandingMaterialDocumentService(),
            new OutstandingMaterialInvoiceService($this->identity),
        );

        $this->expectException(ValidationException::class);

        // Direct executeAdd invocation simulating execution of a row targeting archived invoice
        $refMethod = new \ReflectionMethod($importService, 'executeAdd');
        $refMethod->setAccessible(true);
        $refMethod->invoke($importService, [
            'rows' => [
                [
                    'payload' => [
                        'supplier' => 'Import Supplier',
                        'number_invoice' => 'INV-IMP-01',
                        'invoice_identity_key' => $key,
                        'type' => 'Type Imp',
                        'status' => 'Received',
                    ],
                ],
            ],
        ], 1);
    }

    public function test_import_replace_targeting_archived_material_is_rejected_with_422(): void
    {
        $key = $this->identity->invoiceKey('Replace Supplier', 'INV-REP-01');
        $header = OutstandingMaterialInvoice::create([
            'supplier' => 'Replace Supplier',
            'number_invoice' => 'INV-REP-01',
            'invoice_identity_key' => $key,
            'archived_at' => now(),
        ]);

        $mat = OutstandingMaterial::create([
            'supplier' => 'Replace Supplier',
            'number_invoice' => 'INV-REP-01',
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Replace Bar',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'thickness' => 10,
            'width' => 20,
            'diameter' => 30,
        ]);

        $importService = new OutstandingMaterialImportPreviewService(
            $this->identity,
            new OutstandingMaterialDocumentService(),
            new OutstandingMaterialInvoiceService($this->identity),
        );

        $refClassify = new \ReflectionMethod($importService, 'classifyReplace');
        $refClassify->setAccessible(true);
        $result = $refClassify->invoke($importService, [
            [
                'source_row' => 2,
                'supplier' => 'Replace Supplier',
                'number_invoice' => 'INV-REP-01',
                'type' => 'Replace Bar',
                'thickness' => 10,
                'width' => 20,
                'diameter' => 30,
                'status' => 'Received',
            ],
        ], [], []);

        $this->assertGreaterThan(0, count($result['errors']), 'ClassifyReplace must produce errors for archived material');
        $this->assertEquals(0, $result['summary']['matched']);
        $this->assertEquals(1, $result['summary']['unmatched']);
    }

    public function test_document_upload_targeting_archived_invoice_is_rejected_with_422(): void
    {
        $key = $this->identity->invoiceKey('Doc Supplier', 'INV-DOC-01');
        $header = OutstandingMaterialInvoice::create([
            'supplier' => 'Doc Supplier',
            'number_invoice' => 'INV-DOC-01',
            'invoice_identity_key' => $key,
            'archived_at' => now(),
        ]);

        $mat = OutstandingMaterial::create([
            'supplier' => 'Doc Supplier',
            'number_invoice' => 'INV-DOC-01',
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Doc Bar',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $docService = new OutstandingMaterialDocumentService();

        $this->expectException(ValidationException::class);

        $docService->uploadForIdentityKey(
            $key,
            UploadedFile::fake()->create('packing.pdf', 100),
            null,
            1,
        );
    }

    public function test_edit_material_on_archived_invoice_is_rejected_with_422(): void
    {
        $key = $this->identity->invoiceKey('Edit Supplier', 'INV-EDIT-01');
        $header = OutstandingMaterialInvoice::create([
            'supplier' => 'Edit Supplier',
            'number_invoice' => 'INV-EDIT-01',
            'invoice_identity_key' => $key,
            'archived_at' => now(),
        ]);

        $mat = OutstandingMaterial::create([
            'supplier' => 'Edit Supplier',
            'number_invoice' => 'INV-EDIT-01',
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Edit Bar',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $this->expectException(ValidationException::class);

        $request = Request::create('/outstanding-materials/'.$mat->id, 'PUT', [
            'supplier' => 'Edit Supplier',
            'number_invoice' => 'INV-EDIT-01',
            'type' => 'Updated Bar',
            'status' => 'Received',
        ]);

        $this->controller->update($request, $mat);
    }

    public function test_reassign_material_to_archived_invoice_is_rejected_with_422(): void
    {
        $keyActive = $this->identity->invoiceKey('Reassign Supplier', 'INV-ACT-REASSIGN');
        $headerActive = OutstandingMaterialInvoice::create([
            'supplier' => 'Reassign Supplier',
            'number_invoice' => 'INV-ACT-REASSIGN',
            'invoice_identity_key' => $keyActive,
            'archived_at' => null,
        ]);

        $keyArchived = $this->identity->invoiceKey('Reassign Supplier', 'INV-ARC-REASSIGN');
        $headerArchived = OutstandingMaterialInvoice::create([
            'supplier' => 'Reassign Supplier',
            'number_invoice' => 'INV-ARC-REASSIGN',
            'invoice_identity_key' => $keyArchived,
            'archived_at' => now(),
        ]);

        $mat = OutstandingMaterial::create([
            'supplier' => 'Reassign Supplier',
            'number_invoice' => 'INV-ACT-REASSIGN',
            'invoice_id' => $headerActive->id,
            'invoice_identity_key' => $keyActive,
            'type' => 'Reassign Bar',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $this->expectException(ValidationException::class);

        $request = Request::create('/outstanding-materials/'.$mat->id, 'PUT', [
            'supplier' => 'Reassign Supplier',
            'number_invoice' => 'INV-ARC-REASSIGN',
            'type' => 'Reassign Bar',
            'status' => 'Received',
        ]);

        $this->controller->update($request, $mat);
    }

    public function test_delete_material_on_archived_invoice_is_rejected_with_422(): void
    {
        $key = $this->identity->invoiceKey('Del Supplier', 'INV-DEL-01');
        $header = OutstandingMaterialInvoice::create([
            'supplier' => 'Del Supplier',
            'number_invoice' => 'INV-DEL-01',
            'invoice_identity_key' => $key,
            'archived_at' => now(),
        ]);

        $mat = OutstandingMaterial::create([
            'supplier' => 'Del Supplier',
            'number_invoice' => 'INV-DEL-01',
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Del Bar',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $this->expectException(ValidationException::class);

        $this->controller->destroy($mat);
    }

    public function test_delete_archived_invoice_permanently_is_rejected_with_422(): void
    {
        $key = $this->identity->invoiceKey('Del Inv Supplier', 'INV-DELINV-01');
        $header = OutstandingMaterialInvoice::create([
            'supplier' => 'Del Inv Supplier',
            'number_invoice' => 'INV-DELINV-01',
            'invoice_identity_key' => $key,
            'archived_at' => now(),
        ]);

        $mat = OutstandingMaterial::create([
            'supplier' => 'Del Inv Supplier',
            'number_invoice' => 'INV-DELINV-01',
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'DelInv Bar',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $this->expectException(ValidationException::class);

        $this->controller->destroyInvoice($mat);
    }

    public function test_mutations_succeed_on_active_invoice(): void
    {
        $key = $this->identity->invoiceKey('Healthy Supplier', 'INV-HLT-01');
        $header = OutstandingMaterialInvoice::create([
            'supplier' => 'Healthy Supplier',
            'number_invoice' => 'INV-HLT-01',
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);

        $mat = OutstandingMaterial::create([
            'supplier' => 'Healthy Supplier',
            'number_invoice' => 'INV-HLT-01',
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Healthy Bar',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        // Edit on active invoice succeeds
        $request = Request::create('/outstanding-materials/'.$mat->id, 'PUT', [
            'supplier' => 'Healthy Supplier',
            'number_invoice' => 'INV-HLT-01',
            'type' => 'Updated Healthy Bar',
            'status' => 'Received',
        ]);
        $response = $this->controller->update($request, $mat);
        $this->assertEquals(302, $response->getStatusCode());

        $mat->refresh();
        $this->assertEquals('Updated Healthy Bar', $mat->type);

        // Delete material on active invoice succeeds
        $deleteResponse = $this->controller->destroy($mat);
        $this->assertEquals(302, $deleteResponse->getStatusCode());
        $this->assertSoftDeleted('outstanding_materials', ['id' => $mat->id]);
    }

    public function test_archive_succeeds_when_all_materials_are_received_and_closed(): void
    {
        $supplier = 'Archive Success Supplier';
        $invoice = 'INV-ARCH-OK-01';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);

        $mat1 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Part A',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $mat2 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Part B',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $archivedHeader = $this->invoices->archiveInvoiceGroup($mat1);

        $this->assertNotNull($archivedHeader->archived_at);
        $this->assertTrue($archivedHeader->isArchived());

        $header->refresh();
        $this->assertNotNull($header->archived_at);
        $this->assertTrue($header->isArchived());

        // Child material rows remain unchanged
        $mat1->refresh();
        $mat2->refresh();
        $this->assertEquals('Received', $mat1->status);
        $this->assertEquals('Closed', $mat1->keterangan);
        $this->assertEquals('Received', $mat2->status);
        $this->assertEquals('Closed', $mat2->keterangan);
    }

    public function test_archive_fails_if_any_material_status_is_not_received(): void
    {
        $supplier = 'Archive Ineligible Supplier 1';
        $invoice = 'INV-ARCH-FAIL-01';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);

        $mat1 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Part A',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $mat2 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Part B',
            'status' => 'On Shipment',
            'keterangan' => 'Closed',
        ]);

        $this->expectException(ValidationException::class);
        $this->invoices->archiveInvoiceGroup($mat1);
    }

    public function test_archive_fails_if_any_material_keterangan_is_not_closed(): void
    {
        $supplier = 'Archive Ineligible Supplier 2';
        $invoice = 'INV-ARCH-FAIL-02';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);

        $mat1 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Part A',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $mat2 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Part B',
            'status' => 'Received',
            'keterangan' => 'Open',
        ]);

        $this->expectException(ValidationException::class);
        $this->invoices->archiveInvoiceGroup($mat1);
    }

    public function test_archive_fails_for_empty_invoice(): void
    {
        $supplier = 'Empty Invoice Supplier';
        $invoice = 'INV-EMPTY-01';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);

        $anchor = new OutstandingMaterial([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
        ]);

        $this->expectException(ValidationException::class);
        $this->invoices->archiveInvoiceGroup($anchor);
    }

    public function test_archive_fails_if_already_archived(): void
    {
        $supplier = 'Already Archived Supplier';
        $invoice = 'INV-ALREADY-ARCH-01';
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
            'type' => 'Part X',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $this->expectException(ValidationException::class);
        $this->invoices->archiveInvoiceGroup($mat);
    }

    public function test_restore_resets_archived_at_to_null(): void
    {
        $supplier = 'Restore Supplier';
        $invoice = 'INV-RESTORE-01';
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
            'type' => 'Part Y',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $restored = $this->invoices->restoreInvoiceGroup($mat);

        $this->assertNull($restored->archived_at);
        $this->assertFalse($restored->isArchived());

        $header->refresh();
        $this->assertNull($header->archived_at);
        $this->assertFalse($header->isArchived());

        // Material attributes remain untouched
        $mat->refresh();
        $this->assertEquals('Received', $mat->status);
        $this->assertEquals('Closed', $mat->keterangan);
    }

    public function test_restore_is_safe_and_idempotent(): void
    {
        $supplier = 'Restore Idempotent Supplier';
        $invoice = 'INV-RESTORE-IDEMP-01';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'archived_at' => null, // already active
        ]);

        $mat = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Part Z',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        // Calling restore on an already active invoice returns header without error
        $result = $this->invoices->restoreInvoiceGroup($mat);
        $this->assertNull($result->archived_at);
        $this->assertFalse($result->isArchived());
    }

    public function test_legacy_unlinked_materials_are_reconciled_to_invoice_id_on_archive(): void
    {
        $supplier = 'Legacy Reconcile Supplier';
        $invoice = 'INV-LEGACY-REC-01';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);

        // Mat 1 linked
        $mat1 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Legacy 1',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        // Mat 2 unlinked (legacy: invoice_id is null)
        $mat2 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_id' => null,
            'invoice_identity_key' => $key,
            'type' => 'Legacy 2',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $this->assertNull($mat2->invoice_id);

        $this->invoices->archiveInvoiceGroup($mat1);

        $mat2->refresh();
        $this->assertEquals($header->id, $mat2->invoice_id);
    }

    public function test_cross_supplier_isolation_on_archive_and_restore(): void
    {
        $invoiceNum = 'INV-CROSS-SAME-NUM';

        $keyA = $this->identity->invoiceKey('Supplier A', $invoiceNum);
        $headerA = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier A',
            'number_invoice' => $invoiceNum,
            'invoice_identity_key' => $keyA,
            'archived_at' => null,
        ]);
        $matA = OutstandingMaterial::create([
            'supplier' => 'Supplier A',
            'number_invoice' => $invoiceNum,
            'invoice_id' => $headerA->id,
            'invoice_identity_key' => $keyA,
            'type' => 'Material A',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $keyB = $this->identity->invoiceKey('Supplier B', $invoiceNum);
        $headerB = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier B',
            'number_invoice' => $invoiceNum,
            'invoice_identity_key' => $keyB,
            'archived_at' => null,
        ]);
        $matB = OutstandingMaterial::create([
            'supplier' => 'Supplier B',
            'number_invoice' => $invoiceNum,
            'invoice_id' => $headerB->id,
            'invoice_identity_key' => $keyB,
            'type' => 'Material B',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        // Archive Supplier A's invoice
        $this->invoices->archiveInvoiceGroup($matA);

        $headerA->refresh();
        $headerB->refresh();

        $this->assertTrue($headerA->isArchived());
        $this->assertFalse($headerB->isArchived());

        // Restore Supplier A's invoice
        $this->invoices->restoreInvoiceGroup($matA);

        $headerA->refresh();
        $headerB->refresh();

        $this->assertFalse($headerA->isArchived());
        $this->assertFalse($headerB->isArchived());
    }
}

class TestArchiveAccessService extends OutstandingMaterialAccessService
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
}

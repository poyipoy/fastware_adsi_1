<?php

namespace Tests\Feature;

use App\Http\Controllers\OutstandingMaterialController;
use App\Models\OutstandingMaterial;
use App\Models\OutstandingMaterialInvoice;
use App\Models\User;
use App\Services\OutstandingMaterialBatchService;
use App\Services\OutstandingMaterialDocumentService;
use App\Services\OutstandingMaterialIdentityService;
use App\Services\OutstandingMaterialInvoiceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OutstandingMaterialBatchServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_batch_add_creates_multiple_materials_under_one_invoice_header(): void
    {
        if (! Schema::hasTable('outstanding_materials') || ! Schema::hasTable('outstanding_material_invoices')) {
            $this->markTestSkipped('Outstanding Material schema is not available in this test database.');
        }

        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $service = new OutstandingMaterialBatchService(
            $identity,
            $documents,
            new OutstandingMaterialInvoiceService($identity),
        );

        $created = $service->create(
            [
                'supplier' => 'Acme Steel',
                'number_invoice' => ' inv-batch-1 ',
                'status' => 'Received',
            ],
            [
                ['type' => 'Plate', 'qty_pcs' => 1],
                ['type' => 'Plate', 'qty_pcs' => 1],
            ],
            null,
            null,
        );

        $this->assertCount(2, $created);
        $this->assertSame('INV-BATCH-1', $created->first()->number_invoice);
        $this->assertSame($created[0]->invoice_id, $created[1]->invoice_id);
        $third = $service->create(
            [
                'supplier' => 'Acme Steel',
                'number_invoice' => 'INV-BATCH-1',
                'status' => 'Received',
            ],
            [['type' => 'Plate', 'qty_pcs' => 1]],
            null,
            null,
        )->first();
        $this->assertSame($created[0]->invoice_id, $third->invoice_id);
        $this->assertDatabaseHas('outstanding_material_invoices', [
            'id' => $created[0]->invoice_id,
            'invoice_identity_key' => $identity->invoiceKey('Acme Steel', 'INV-BATCH-1'),
        ]);
    }

    public function test_import_preview_is_append_only_and_accepts_duplicate_rows(): void
    {
        if (! Schema::hasTable('outstanding_materials') || ! Schema::hasTable('outstanding_material_invoices')) {
            $this->markTestSkipped('Outstanding Material schema is not available in this test database.');
        }

        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $service = new \App\Services\OutstandingMaterialImportPreviewService(
            $identity,
            $documents,
            new OutstandingMaterialInvoiceService($identity),
        );
        $csv = "NO,Supplier,Number Invoice,TYPE,Thickness,Width,Diameter,Length,QTY (PCS),Est QTY (KG),Status\n"
            ."1,Acme,INV-CSV-1,Plate,1,2,,10,5,12,Received\n"
            ."2,Acme,INV-CSV-1,Plate,1,2,,10,5,12,Received\n";

        $first = $service->preview(UploadedFile::fake()->createWithContent('materials.csv', $csv), 901);
        $this->assertSame(2, $first['summary']['new']);
        $this->assertSame(0, $first['summary']['errors']);
        $created = $service->execute($first['token'], 901, null);
        $this->assertSame(2, $created['inserted']);

        $this->assertDatabaseHas('outstanding_materials', [
            'invoice_identity_key' => $identity->invoiceKey('Acme', 'INV-CSV-1'),
            'type' => 'Plate',
            'status' => 'Received',
        ]);
        $second = $service->preview(UploadedFile::fake()->createWithContent('materials.csv', $csv), 901);
        $this->assertSame(2, $second['summary']['new']);
        $this->assertSame(2, $service->execute($second['token'], 901, null)['inserted']);
        $this->assertSame(
            4,
            \App\Models\OutstandingMaterial::query()
                ->where('invoice_identity_key', $identity->invoiceKey('Acme', 'INV-CSV-1'))
                ->count(),
        );

        $invalid = $service->preview(
            UploadedFile::fake()->createWithContent('materials.csv', str_replace('Plate', '', $csv)),
            902,
        );
        $this->assertGreaterThan(0, $invalid['summary']['errors']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->execute($invalid['token'], 902, null);
    }

    public function test_invoice_document_upload_stores_files_and_syncs_header(): void
    {
        if (! Schema::hasTable('outstanding_materials') || ! Schema::hasTable('outstanding_material_invoices')) {
            $this->markTestSkipped('Outstanding Material schema is not available in this test database.');
        }

        Storage::fake('local');
        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $batch = new OutstandingMaterialBatchService(
            $identity,
            $documents,
            new OutstandingMaterialInvoiceService($identity),
        );
        $material = $batch->create(
            ['supplier' => 'Upload Supplier', 'number_invoice' => 'INV-UPLOAD-1', 'status' => 'Received'],
            [['type' => 'Plate']],
            null,
            null,
        )->first();

        $result = $documents->uploadForIdentityKey(
            $material->invoice_identity_key,
            UploadedFile::fake()->create('packing-list.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            UploadedFile::fake()->create('mtc.pdf', 10, 'application/pdf'),
            null,
        );

        $material->refresh();
        $header = $material->invoiceHeader()->first();

        $this->assertSame(1, $result['updated']);
        $this->assertNotEmpty($material->packing_list_path);
        $this->assertNotEmpty($material->mtc_path);
        $this->assertSame($material->packing_list_path, $header?->packing_list_path);
        $this->assertSame($material->mtc_path, $header?->mtc_path);
        Storage::disk('local')->assertExists($material->packing_list_path);
        Storage::disk('local')->assertExists($material->mtc_path);
    }

    public function test_invoice_grouping_and_bulk_update_are_supplier_scoped(): void
    {
        if (! Schema::hasTable('outstanding_materials') || ! Schema::hasTable('outstanding_material_invoices')) {
            $this->markTestSkipped('Outstanding Material schema is not available in this test database.');
        }

        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $batch = new OutstandingMaterialBatchService($identity, $documents, new OutstandingMaterialInvoiceService($identity));
        $first = $batch->create(['supplier' => 'Supplier A', 'number_invoice' => 'INV-SAME', 'status' => 'Received'], [['type' => 'Plate']], null, null)->first();
        $second = $batch->create(['supplier' => 'Supplier B', 'number_invoice' => 'INV-SAME', 'status' => 'Received'], [['type' => 'Pipe']], null, null)->first();

        $access = new class extends \App\Services\OutstandingMaterialAccessService
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
        };
        Auth::setUser(new User(['name' => 'ADMINISTRATOR']));
        $controller = new OutstandingMaterialController($access, $documents, $identity, $batch);

        $response = $controller->invoiceData(Request::create('/outstanding-materials/show-based-on-invoice/data', 'GET', [
            'start' => 0,
            'length' => 25,
            'draw' => 1,
        ]));
        $payload = $response->getData(true);
        $this->assertSame(2, $payload['recordsTotal']);
        $this->assertCount(2, $payload['data']);

        $detail = $controller->invoiceDetailData(Request::create('/outstanding-materials/'.$first->id.'/invoice-data', 'GET', [
            'start' => 0,
            'length' => 25,
            'draw' => 1,
        ]), $first);
        $detailPayload = $detail->getData(true);
        $this->assertSame(1, $detailPayload['recordsTotal']);
        $this->assertArrayNotHasKey('line_ref', $detailPayload['data'][0]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->updateInvoiceFieldsForAnchor(Request::create('/outstanding-materials/show-based-on-invoice/'.$first->id.'/update', 'POST', [
            'material_ids' => [$second->id],
            'status' => 'On Shipment',
        ]), $first);
    }

    public function test_invoice_delete_permanently_removes_exact_scope_and_only_unreferenced_documents(): void
    {
        if (! Schema::hasTable('outstanding_materials') || ! Schema::hasTable('outstanding_material_invoices')) {
            $this->markTestSkipped('Outstanding Material schema is not available in this test database.');
        }

        Storage::fake('local');
        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $batch = new OutstandingMaterialBatchService($identity, $documents, new OutstandingMaterialInvoiceService($identity));
        $invoiceMaterials = $batch->create(
            ['supplier' => 'Delete Supplier', 'number_invoice' => 'INV-DELETE', 'status' => 'Received'],
            [['type' => 'Plate'], ['type' => 'Pipe']],
            null,
            null,
        );
        $otherSupplierMaterial = $batch->create(
            ['supplier' => 'Other Supplier', 'number_invoice' => 'INV-DELETE', 'status' => 'Received'],
            [['type' => 'Bar']],
            null,
            null,
        )->first();

        $packingPath = 'private/outstanding-materials/packing-list/delete-packing.pdf';
        $attachmentPath = 'private/outstanding-materials/packing-list/delete-attachment.pdf';
        $sharedMtcPath = 'private/outstanding-materials/mtc/keep-shared.pdf';
        Storage::disk('local')->put($packingPath, 'packing');
        Storage::disk('local')->put($attachmentPath, 'attachment');
        Storage::disk('local')->put($sharedMtcPath, 'shared mtc');

        $invoiceId = (int) $invoiceMaterials->first()->invoice_id;
        OutstandingMaterial::query()
            ->where('invoice_id', $invoiceId)
            ->update([
                'attachment_path' => $attachmentPath,
                'packing_list_path' => $packingPath,
                'mtc_path' => $sharedMtcPath,
            ]);
        OutstandingMaterialInvoice::query()
            ->whereKey($invoiceId)
            ->update([
                'packing_list_path' => $packingPath,
                'mtc_path' => $sharedMtcPath,
            ]);
        $otherSupplierMaterial->update(['mtc_path' => $sharedMtcPath]);

        $alreadyDeleted = $invoiceMaterials->first()->fresh();
        $alreadyDeleted->delete();
        $anchor = $invoiceMaterials->last()->fresh();

        $access = new class extends \App\Services\OutstandingMaterialAccessService
        {
            public function canView(?User $user): bool
            {
                return true;
            }

            public function canManage(?User $user): bool
            {
                return true;
            }
        };
        Auth::setUser(new User(['name' => 'ADMINISTRATOR']));
        $controller = new OutstandingMaterialController($access, $documents, $identity, $batch);

        $response = $controller->destroyInvoice($anchor);

        $this->assertSame(route('outstanding-materials.invoice.index'), $response->getTargetUrl());
        $this->assertDatabaseMissing('outstanding_materials', ['id' => $invoiceMaterials->first()->id]);
        $this->assertDatabaseMissing('outstanding_materials', ['id' => $invoiceMaterials->last()->id]);
        $this->assertDatabaseMissing('outstanding_material_invoices', ['id' => $invoiceId]);
        $this->assertDatabaseHas('outstanding_materials', ['id' => $otherSupplierMaterial->id]);
        Storage::disk('local')->assertMissing($packingPath);
        Storage::disk('local')->assertMissing($attachmentPath);
        Storage::disk('local')->assertExists($sharedMtcPath);
    }

    public function test_eta_warehouse_search_matches_material_detail_and_invoice_latest_value(): void
    {
        if (! Schema::hasTable('outstanding_materials') || ! Schema::hasTable('outstanding_material_invoices')) {
            $this->markTestSkipped('Outstanding Material schema is not available in this test database.');
        }

        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $batch = new OutstandingMaterialBatchService($identity, $documents, new OutstandingMaterialInvoiceService($identity));
        $invoiceMaterials = $batch->create(
            ['supplier' => 'ETA Supplier', 'number_invoice' => 'INV-ETA', 'status' => 'Received'],
            [['type' => 'Plate'], ['type' => 'Pipe']],
            null,
            null,
        );
        $invoiceMaterials->first()->update(['estimasi_eta_warehouse' => '2026-08-12']);
        $invoiceMaterials->last()->update(['estimasi_eta_warehouse' => '2026-09-30']);
        $batch->create(
            ['supplier' => 'Other ETA Supplier', 'number_invoice' => 'INV-ETA-OTHER', 'status' => 'Received'],
            [['type' => 'Bar', 'estimasi_eta_warehouse' => '2026-07-01']],
            null,
            null,
        );

        $access = new class extends \App\Services\OutstandingMaterialAccessService
        {
            public function canView(?User $user): bool
            {
                return true;
            }

            public function canManage(?User $user): bool
            {
                return true;
            }
        };
        Auth::setUser(new User(['name' => 'ADMINISTRATOR']));
        $controller = new OutstandingMaterialController($access, $documents, $identity, $batch);

        $invoiceResponse = $controller->invoiceData(Request::create('/outstanding-materials/show-based-on-invoice/data', 'GET', [
            'q' => '2026-09-30',
            'start' => 0,
            'length' => 25,
            'draw' => 1,
        ]));
        $invoicePayload = $invoiceResponse->getData(true);
        $this->assertSame(1, $invoicePayload['recordsFiltered']);
        $this->assertSame('INV-ETA', html_entity_decode($invoicePayload['data'][0]['number_invoice']));
        $this->assertSame('2026-09-30', $invoicePayload['data'][0]['latest_eta_warehouse']);

        $detailResponse = $controller->invoiceDetailData(Request::create('/outstanding-materials/'.$invoiceMaterials->first()->id.'/invoice-data', 'GET', [
            'q' => '2026-09-30',
            'start' => 0,
            'length' => 25,
            'draw' => 1,
        ]), $invoiceMaterials->first()->fresh());
        $detailPayload = $detailResponse->getData(true);
        $this->assertSame(1, $detailPayload['recordsFiltered']);
        $this->assertSame('2026-09-30', $detailPayload['data'][0]['estimasi_eta_warehouse']);
    }
}

<?php

namespace Tests\Feature;

use App\Services\OutstandingMaterialBatchService;
use App\Services\OutstandingMaterialDocumentService;
use App\Services\OutstandingMaterialIdentityService;
use App\Services\OutstandingMaterialInvoiceService;
use App\Http\Controllers\OutstandingMaterialController;
use App\Models\User;
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
        if (!Schema::hasTable('outstanding_materials') || !Schema::hasTable('outstanding_material_invoices')) {
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
        if (!Schema::hasTable('outstanding_materials') || !Schema::hasTable('outstanding_material_invoices')) {
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
            . "1,Acme,INV-CSV-1,Plate,1,2,,10,5,12,Received\n"
            . "2,Acme,INV-CSV-1,Plate,1,2,,10,5,12,Received\n";

        $first = $service->preview(UploadedFile::fake()->createWithContent('materials.csv', $csv), 901);
        $this->assertSame(2, $first['summary']['new']);
        $this->assertSame(0, $first['summary']['errors']);
        $created = $service->execute($first['token'], 901, null);
        $this->assertSame(['inserted' => 2], $created);

        $this->assertDatabaseHas('outstanding_materials', [
            'invoice_identity_key' => $identity->invoiceKey('Acme', 'INV-CSV-1'),
            'type' => 'Plate',
            'status' => 'Received',
        ]);
        $second = $service->preview(UploadedFile::fake()->createWithContent('materials.csv', $csv), 901);
        $this->assertSame(2, $second['summary']['new']);
        $this->assertSame(['inserted' => 2], $service->execute($second['token'], 901, null));
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
        if (!Schema::hasTable('outstanding_materials') || !Schema::hasTable('outstanding_material_invoices')) {
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
        if (!Schema::hasTable('outstanding_materials') || !Schema::hasTable('outstanding_material_invoices')) {
            $this->markTestSkipped('Outstanding Material schema is not available in this test database.');
        }

        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $batch = new OutstandingMaterialBatchService($identity, $documents, new OutstandingMaterialInvoiceService($identity));
        $first = $batch->create(['supplier' => 'Supplier A', 'number_invoice' => 'INV-SAME', 'status' => 'Received'], [['type' => 'Plate']], null, null)->first();
        $second = $batch->create(['supplier' => 'Supplier B', 'number_invoice' => 'INV-SAME', 'status' => 'Received'], [['type' => 'Pipe']], null, null)->first();

        $access = new class extends \App\Services\OutstandingMaterialAccessService {
            public function canView(?User $user): bool { return true; }
            public function canManage(?User $user): bool { return true; }
            public function canExport(?User $user): bool { return true; }
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

        $detail = $controller->invoiceDetailData(Request::create('/outstanding-materials/' . $first->id . '/invoice-data', 'GET', [
            'start' => 0,
            'length' => 25,
            'draw' => 1,
        ]), $first);
        $detailPayload = $detail->getData(true);
        $this->assertSame(1, $detailPayload['recordsTotal']);
        $this->assertArrayNotHasKey('line_ref', $detailPayload['data'][0]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->updateInvoiceFieldsForAnchor(Request::create('/outstanding-materials/show-based-on-invoice/' . $first->id . '/update', 'POST', [
            'material_ids' => [$second->id],
            'status' => 'On Shipment',
        ]), $first);
    }
}

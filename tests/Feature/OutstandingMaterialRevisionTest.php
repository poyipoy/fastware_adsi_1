<?php

namespace Tests\Feature;

use App\Exports\OutstandingMaterialExport;
use App\Exports\OutstandingMaterialTemplateExport;
use App\Http\Controllers\OutstandingMaterialController;
use App\Imports\OutstandingMaterialImport;
use App\Models\OutstandingMaterial;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OutstandingMaterialRevisionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_port_options_are_valid(): void
    {
        $options = OutstandingMaterial::portOptions();
        $this->assertContains('Tanjung Priok', $options);
        $this->assertContains('Tanjung Perak', $options);
    }

    public function test_template_and_export_headings_include_new_fields_before_end(): void
    {
        $template = new OutstandingMaterialTemplateExport();
        $headings = $template->headings();
        $this->assertContains('Port', $headings);
        $this->assertContains('Nomor PO', $headings);
        $this->assertContains('Month Order', $headings);
        $this->assertContains('Remarks', $headings);

        $material = new OutstandingMaterial([
            'supplier' => 'PT Test',
            'number_invoice' => 'INV-001',
            'type' => 'Steel Plate',
            'thickness' => 10,
            'width' => 100,
            'diameter' => 50,
            'length' => '6000',
            'qty_pcs' => 5,
            'est_qty_kg' => 500,
            'status' => 'Received',
            'port' => 'Tanjung Priok',
            'number_po' => 'PO-12345',
            'month_order' => 'May 2026',
            'remarks' => 'Test remarks for export',
        ]);

        $export = new OutstandingMaterialExport(new Collection([$material]));
        $exportHeadings = $export->headings();
        $this->assertContains('Port', $exportHeadings);
        $this->assertContains('Nomor PO', $exportHeadings);
        $this->assertContains('Month Order', $exportHeadings);
        $this->assertContains('Remarks', $exportHeadings);

        $mapped = $export->map($material);
        $this->assertContains('Tanjung Priok', $mapped);
        $this->assertContains('PO-12345', $mapped);
        $this->assertContains('May 2026', $mapped);
        $this->assertContains('Test remarks for export', $mapped);
    }

    public function test_import_parser_reads_port_number_po_and_remarks(): void
    {
        $import = new OutstandingMaterialImport(1);
        $import->collection(new Collection([
            ['NO', 'Supplier', 'Number Invoice', 'TYPE', 'Thickness', 'Width', 'Diameter', 'Length', 'QTY (PCS)', 'Est QTY (KG)', 'Status', 'Port', 'Nomor PO', 'Month Order', 'Remarks'],
            [1, 'PT Test', 'INV-IMPORT-1', 'Plate', 5, 50, null, '2000', 10, 100, 'Received', 'Tanjung Priok', 'PO-999', 'June 2026', 'Import Remarks'],
        ]));

        $rows = $import->rows();
        $this->assertCount(1, $rows);
        $this->assertSame([], $import->errors());
        $this->assertSame('Tanjung Priok', $rows[0]['port']);
        $this->assertSame('PO-999', $rows[0]['number_po']);
        $this->assertSame('June 2026', $rows[0]['month_order']);
        $this->assertSame('Import Remarks', $rows[0]['remarks']);
    }

    public function test_import_replace_mode_matches_composite_key_and_updates_without_overwriting_documents(): void
    {
        if (! Schema::hasTable('outstanding_materials') || ! Schema::hasTable('outstanding_material_invoices')) {
            $this->markTestSkipped('Database schema not available.');
        }

        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $invoices = new OutstandingMaterialInvoiceService($identity);
        $previewService = new OutstandingMaterialImportPreviewService($identity, $documents, $invoices);

        // Seed existing material with packing list & MTC
        $existing = OutstandingMaterial::create([
            'supplier' => 'PT Existing',
            'number_invoice' => 'INV-REPLACE-01',
            'invoice_identity_key' => $identity->invoiceKey('PT Existing', 'INV-REPLACE-01'),
            'type' => 'SS400',
            'thickness' => 12.00,
            'width' => 1500.00,
            'diameter' => null,
            'length' => '6000',
            'qty_pcs' => 10,
            'est_qty_kg' => 2000,
            'status' => 'On Production',
            'port' => null,
            'number_po' => null,
            'remarks' => 'Old remarks',
            'packing_list_path' => 'private/packing/test.pdf',
            'mtc_path' => 'private/mtc/test.pdf',
        ]);

        $csv = "NO,Supplier,Number Invoice,TYPE,Thickness,Width,Diameter,Length,QTY (PCS),Est QTY (KG),Status,Port,Nomor PO,Remarks\n"
            ."1,PT Existing,INV-REPLACE-01,SS400,12,1500,,6000,20,4000,On Shipment,Tanjung Perak,PO-REPLACED-88,New updated remarks\n";

        // Preview in REPLACE mode
        $preview = $previewService->preview(
            UploadedFile::fake()->createWithContent('replace.csv', $csv),
            999,
            'replace',
        );

        $this->assertSame('replace', $preview['mode']);
        $this->assertSame(1, $preview['summary']['matched']);
        $this->assertSame(0, $preview['summary']['unmatched']);
        $this->assertSame(0, $preview['summary']['errors']);
        $this->assertSame('matched', $preview['rows'][0]['classification']);

        // Execute replace
        $result = $previewService->execute($preview['token'], 999, 1);
        $this->assertSame(1, $result['replaced']);

        // Verify material was updated but documents preserved
        $existing->refresh();
        $this->assertSame('On Shipment', $existing->status);
        $this->assertEquals(20, $existing->qty_pcs);
        $this->assertEquals(4000, $existing->est_qty_kg);
        $this->assertSame('Tanjung Perak', $existing->port);
        $this->assertSame('PO-REPLACED-88', $existing->number_po);
        $this->assertSame('New updated remarks', $existing->remarks);
        $this->assertSame('private/packing/test.pdf', $existing->packing_list_path);
        $this->assertSame('private/mtc/test.pdf', $existing->mtc_path);
    }

    public function test_import_replace_mode_blocks_unmatched_rows_without_fallback_to_add(): void
    {
        if (! Schema::hasTable('outstanding_materials') || ! Schema::hasTable('outstanding_material_invoices')) {
            $this->markTestSkipped('Database schema not available.');
        }

        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $invoices = new OutstandingMaterialInvoiceService($identity);
        $previewService = new OutstandingMaterialImportPreviewService($identity, $documents, $invoices);

        $csv = "NO,Supplier,Number Invoice,TYPE,Thickness,Width,Diameter,Length,QTY (PCS),Est QTY (KG),Status,Port,Nomor PO,Remarks\n"
            ."1,PT Nonexistent,INV-NONEXISTENT,NoSuchType,99,99,,6000,1,100,On Production,Tanjung Priok,PO-NONE,None\n";

        $preview = $previewService->preview(
            UploadedFile::fake()->createWithContent('unmatched.csv', $csv),
            999,
            'replace',
        );

        $this->assertSame(0, $preview['summary']['matched']);
        $this->assertSame(1, $preview['summary']['unmatched']);
        $this->assertSame(1, $preview['summary']['errors']);
        $this->assertSame('unmatched', $preview['rows'][0]['classification']);

        // Execution must fail with ValidationException
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $previewService->execute($preview['token'], 999, 1);
    }

    public function test_default_sorting_eta_warehouse_nulls_last(): void
    {
        if (! Schema::hasTable('outstanding_materials') || ! Schema::hasTable('outstanding_material_invoices')) {
            $this->markTestSkipped('Database schema not available.');
        }

        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $batch = new OutstandingMaterialBatchService($identity, $documents, new OutstandingMaterialInvoiceService($identity));

        $today = now()->toDateString();
        $tomorrow = now()->addDays(1)->toDateString();
        $nextWeek = now()->addDays(7)->toDateString();
        $yesterday = now()->subDays(1)->toDateString();
        $lastWeek = now()->subDays(7)->toDateString();

        // 1: ETA $today / INV-ORDER-A / Supplier A (Tier 0: today)
        // 2: ETA $today / INV-ORDER-A / Supplier B (Tier 0: tie-break supplier)
        // 3: ETA $tomorrow / INV-ORDER-B / Supplier A (Tier 0: tomorrow)
        // 4: ETA $nextWeek / INV-ORDER-C / Supplier A (Tier 0: next week)
        // 5: ETA $yesterday / INV-ORDER-D / Supplier A (Tier 1: yesterday, most recent past)
        // 6: ETA $lastWeek / INV-ORDER-E / Supplier A (Tier 1: last week, older past)
        // 7: ETA NULL / INV-ORDER-NULL / Supplier A (Tier 2: nulls last)
        $m1 = OutstandingMaterial::create([
            'supplier' => 'Supplier A',
            'number_invoice' => 'INV-ORDER-A',
            'invoice_identity_key' => $identity->invoiceKey('Supplier A', 'INV-ORDER-A'),
            'type' => 'Plate',
            'status' => 'Received',
            'estimasi_eta_warehouse' => $today,
        ]);
        $m2 = OutstandingMaterial::create([
            'supplier' => 'Supplier B',
            'number_invoice' => 'INV-ORDER-A',
            'invoice_identity_key' => $identity->invoiceKey('Supplier B', 'INV-ORDER-A'),
            'type' => 'Plate',
            'status' => 'Received',
            'estimasi_eta_warehouse' => $today,
        ]);
        $m3 = OutstandingMaterial::create([
            'supplier' => 'Supplier A',
            'number_invoice' => 'INV-ORDER-B',
            'invoice_identity_key' => $identity->invoiceKey('Supplier A', 'INV-ORDER-B'),
            'type' => 'Plate',
            'status' => 'Received',
            'estimasi_eta_warehouse' => $tomorrow,
        ]);
        $m4 = OutstandingMaterial::create([
            'supplier' => 'Supplier A',
            'number_invoice' => 'INV-ORDER-C',
            'invoice_identity_key' => $identity->invoiceKey('Supplier A', 'INV-ORDER-C'),
            'type' => 'Plate',
            'status' => 'Received',
            'estimasi_eta_warehouse' => $nextWeek,
        ]);
        $m5 = OutstandingMaterial::create([
            'supplier' => 'Supplier A',
            'number_invoice' => 'INV-ORDER-D',
            'invoice_identity_key' => $identity->invoiceKey('Supplier A', 'INV-ORDER-D'),
            'type' => 'Plate',
            'status' => 'Received',
            'estimasi_eta_warehouse' => $yesterday,
        ]);
        $m6 = OutstandingMaterial::create([
            'supplier' => 'Supplier A',
            'number_invoice' => 'INV-ORDER-E',
            'invoice_identity_key' => $identity->invoiceKey('Supplier A', 'INV-ORDER-E'),
            'type' => 'Plate',
            'status' => 'Received',
            'estimasi_eta_warehouse' => $lastWeek,
        ]);
        $m7 = OutstandingMaterial::create([
            'supplier' => 'Supplier A',
            'number_invoice' => 'INV-ORDER-NULL',
            'invoice_identity_key' => $identity->invoiceKey('Supplier A', 'INV-ORDER-NULL'),
            'type' => 'Plate',
            'status' => 'Received',
            'estimasi_eta_warehouse' => null,
        ]);

        $access = new class extends OutstandingMaterialAccessService
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

        // Fetch data without custom ordering parameter (triggers default order)
        $response = $controller->data(Request::create('/outstanding-materials/data', 'GET', [
            'start' => 0,
            'length' => 100,
            'draw' => 1,
        ]));

        $payload = $response->getData(true);
        $data = $payload['data'];

        $ids = array_map(fn ($r) => (int) $r['id'], $data);

        $pos1 = array_search($m1->id, $ids);
        $pos2 = array_search($m2->id, $ids);
        $pos3 = array_search($m3->id, $ids);
        $pos4 = array_search($m4->id, $ids);
        $pos5 = array_search($m5->id, $ids);
        $pos6 = array_search($m6->id, $ids);
        $pos7 = array_search($m7->id, $ids);

        $this->assertNotFalse($pos1);
        $this->assertNotFalse($pos2);
        $this->assertNotFalse($pos3);
        $this->assertNotFalse($pos4);
        $this->assertNotFalse($pos5);
        $this->assertNotFalse($pos6);
        $this->assertNotFalse($pos7);

        // Tie breaker for same date $today: Supplier A < Supplier B
        $this->assertLessThan($pos2, $pos1);
        // Future ASC: Today < Tomorrow
        $this->assertLessThan($pos3, $pos2);
        // Future ASC: Tomorrow < Next Week
        $this->assertLessThan($pos4, $pos3);
        // Future before Past: Next Week < Yesterday
        $this->assertLessThan($pos5, $pos4);
        // Past DESC (most recent past first): Yesterday < Last Week
        $this->assertLessThan($pos6, $pos5);
        // Past before NULL: Last Week < NULL
        $this->assertLessThan($pos7, $pos6);
    }

    public function test_interactive_sorting_eta_warehouse_asc_places_nulls_last(): void
    {
        if (! Schema::hasTable('outstanding_materials') || ! Schema::hasTable('outstanding_material_invoices')) {
            $this->markTestSkipped('Database schema not available.');
        }

        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $batch = new OutstandingMaterialBatchService($identity, $documents, new OutstandingMaterialInvoiceService($identity));

        $mNull = OutstandingMaterial::create([
            'supplier' => 'Supplier X',
            'number_invoice' => 'INV-INTER-NULL',
            'invoice_identity_key' => $identity->invoiceKey('Supplier X', 'INV-INTER-NULL'),
            'type' => 'Plate',
            'status' => 'Received',
            'estimasi_eta_warehouse' => null,
        ]);
        $mDate = OutstandingMaterial::create([
            'supplier' => 'Supplier Y',
            'number_invoice' => 'INV-INTER-DATE',
            'invoice_identity_key' => $identity->invoiceKey('Supplier Y', 'INV-INTER-DATE'),
            'type' => 'Plate',
            'status' => 'Received',
            'estimasi_eta_warehouse' => '2026-11-15',
        ]);

        $access = new class extends OutstandingMaterialAccessService
        {
            public function canView(?User $user): bool
            {
                return true;
            }
        };
        Auth::setUser(new User(['name' => 'ADMINISTRATOR']));
        $controller = new OutstandingMaterialController($access, $documents, $identity, $batch);

        // Column 12 is estimasi_eta_warehouse
        $response = $controller->data(Request::create('/outstanding-materials/data', 'GET', [
            'start' => 0,
            'length' => 100,
            'draw' => 1,
            'order' => [
                ['column' => 12, 'dir' => 'asc'],
            ],
        ]));

        $payload = $response->getData(true);
        $ids = array_map(fn ($r) => (int) $r['id'], $payload['data']);

        $posDate = array_search($mDate->id, $ids);
        $posNull = array_search($mNull->id, $ids);

        $this->assertNotFalse($posDate);
        $this->assertNotFalse($posNull);
        $this->assertLessThan($posNull, $posDate);
    }

    public function test_invoice_group_default_sorting_places_latest_eta_warehouse_first(): void
    {
        if (! Schema::hasTable('outstanding_materials') || ! Schema::hasTable('outstanding_material_invoices')) {
            $this->markTestSkipped('Database schema not available.');
        }

        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $batch = new OutstandingMaterialBatchService($identity, $documents, new OutstandingMaterialInvoiceService($identity));

        $tomorrow = now()->addDays(1)->toDateString();
        $nextMonth = now()->addDays(30)->toDateString();
        $yesterday = now()->subDays(1)->toDateString();
        $lastMonth = now()->subDays(30)->toDateString();

        // Invoice 1: upcoming tomorrow (closest future)
        OutstandingMaterial::create([
            'supplier' => 'Supplier G1',
            'number_invoice' => 'INV-GRP-TOMORROW',
            'invoice_identity_key' => $identity->invoiceKey('Supplier G1', 'INV-GRP-TOMORROW'),
            'type' => 'Plate',
            'status' => 'Received',
            'estimasi_eta_warehouse' => $tomorrow,
        ]);
        // Invoice 2: next month (further future)
        OutstandingMaterial::create([
            'supplier' => 'Supplier G2',
            'number_invoice' => 'INV-GRP-NEXTMONTH',
            'invoice_identity_key' => $identity->invoiceKey('Supplier G2', 'INV-GRP-NEXTMONTH'),
            'type' => 'Plate',
            'status' => 'Received',
            'estimasi_eta_warehouse' => $nextMonth,
        ]);
        // Invoice 3: yesterday (closest past)
        OutstandingMaterial::create([
            'supplier' => 'Supplier G3',
            'number_invoice' => 'INV-GRP-YESTERDAY',
            'invoice_identity_key' => $identity->invoiceKey('Supplier G3', 'INV-GRP-YESTERDAY'),
            'type' => 'Plate',
            'status' => 'Received',
            'estimasi_eta_warehouse' => $yesterday,
        ]);
        // Invoice 4: last month (further past)
        OutstandingMaterial::create([
            'supplier' => 'Supplier G4',
            'number_invoice' => 'INV-GRP-LASTMONTH',
            'invoice_identity_key' => $identity->invoiceKey('Supplier G4', 'INV-GRP-LASTMONTH'),
            'type' => 'Plate',
            'status' => 'Received',
            'estimasi_eta_warehouse' => $lastMonth,
        ]);
        // Invoice 5: null ETA
        OutstandingMaterial::create([
            'supplier' => 'Supplier G5',
            'number_invoice' => 'INV-GRP-NULL',
            'invoice_identity_key' => $identity->invoiceKey('Supplier G5', 'INV-GRP-NULL'),
            'type' => 'Plate',
            'status' => 'Received',
            'estimasi_eta_warehouse' => null,
        ]);

        $access = new class extends OutstandingMaterialAccessService
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
            'length' => 100,
            'draw' => 1,
        ]));

        $payload = $response->getData(true);
        $invoices = array_map(fn ($r) => html_entity_decode(strip_tags((string) $r['number_invoice'])), $payload['data']);

        $posTomorrow = array_search('INV-GRP-TOMORROW', $invoices);
        $posNextMonth = array_search('INV-GRP-NEXTMONTH', $invoices);
        $posYesterday = array_search('INV-GRP-YESTERDAY', $invoices);
        $posLastMonth = array_search('INV-GRP-LASTMONTH', $invoices);
        $posNull = array_search('INV-GRP-NULL', $invoices);

        $this->assertNotFalse($posTomorrow);
        $this->assertNotFalse($posNextMonth);
        $this->assertNotFalse($posYesterday);
        $this->assertNotFalse($posLastMonth);
        $this->assertNotFalse($posNull);

        // Future ASC: Tomorrow before Next Month
        $this->assertLessThan($posNextMonth, $posTomorrow);
        // Future before Past: Next Month before Yesterday
        $this->assertLessThan($posYesterday, $posNextMonth);
        // Past DESC (most recent past first): Yesterday before Last Month
        $this->assertLessThan($posLastMonth, $posYesterday);
        // Past before NULL: Last Month before NULL
        $this->assertLessThan($posNull, $posLastMonth);
    }

    public function test_batch_add_persists_port_number_po_and_remarks(): void
    {
        if (! Schema::hasTable('outstanding_materials') || ! Schema::hasTable('outstanding_material_invoices')) {
            $this->markTestSkipped('Database schema not available.');
        }

        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $batch = new OutstandingMaterialBatchService($identity, $documents, new OutstandingMaterialInvoiceService($identity));

        $created = $batch->create(
            [
                'supplier' => 'PT Batch',
                'number_invoice' => 'INV-BATCH-01',
                'status' => 'Received',
                'port' => 'Tanjung Priok',
                'number_po' => 'PO-BATCH-123',
                'month_order' => 'July 2026',
                'remarks' => 'Batch remarks test',
            ],
            [
                ['type' => 'Plate', 'qty_pcs' => 10],
                ['type' => 'Pipe', 'qty_pcs' => 5],
            ],
            null,
            null,
        );

        $this->assertCount(2, $created);
        $this->assertSame('Tanjung Priok', $created[0]->port);
        $this->assertSame('PO-BATCH-123', $created[0]->number_po);
        $this->assertSame('July 2026', $created[0]->month_order);
        $this->assertSame('Batch remarks test', $created[0]->remarks);
        $this->assertSame('Tanjung Priok', $created[1]->port);
        $this->assertSame('PO-BATCH-123', $created[1]->number_po);
        $this->assertSame('July 2026', $created[1]->month_order);
        $this->assertSame('Batch remarks test', $created[1]->remarks);
    }

    public function test_data_table_row_renders_port_number_po_and_truncated_remarks(): void
    {
        Auth::setUser(new User(['name' => 'ADMINISTRATOR']));
        $access = new class extends OutstandingMaterialAccessService
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
        $controller = new OutstandingMaterialController($access);

        $longRemarks = str_repeat('A very long remark string exceeding eighty characters limit for testing truncation. ', 3);
        $material = new OutstandingMaterial([
            'supplier' => 'Supplier',
            'type' => 'TYPE',
            'number_invoice' => 'INV-1',
            'status' => OutstandingMaterial::STATUS_RECEIVED,
            'port' => 'Tanjung Perak',
            'number_po' => 'PO-777',
            'month_order' => 'August 2026',
            'remarks' => $longRemarks,
        ]);
        $material->id = 100;

        $reflection = new \ReflectionMethod($controller, 'dataTableRow');
        $reflection->setAccessible(true);

        $row = $reflection->invoke($controller, $material, 'index');

        $this->assertSame('Tanjung Perak', $row['port']);
        $this->assertSame('PO-777', $row['number_po']);
        $this->assertSame('August 2026', $row['month_order']);
        $this->assertStringContainsString('title="', $row['remarks']);
        $this->assertStringContainsString('…', $row['remarks']);
    }

    public function test_import_endpoint_accepts_mode_or_import_mode_with_default_add(): void
    {
        Auth::setUser(new User(['name' => 'ADMINISTRATOR']));
        $access = new class extends OutstandingMaterialAccessService
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

        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $importPreviews = new OutstandingMaterialImportPreviewService($identity, $documents);
        $controller = new OutstandingMaterialController($access, $documents, $identity, null, $importPreviews);

        $csv = "NO,Supplier,Number Invoice,TYPE,Thickness,Width,Diameter,Length,QTY (PCS),Est QTY (KG),Status,Port,Nomor PO,Remarks\n"
            ."1,PT Test,INV-TEST,Plate,10,100,,6000,5,500,Received,Tanjung Priok,PO-1,Remarks\n";
        $file = UploadedFile::fake()->createWithContent('test.csv', $csv);

        // Request with mode='add'
        $request = Request::create('/outstanding-materials/import', 'POST', ['mode' => 'add'], [], ['import_file' => $file]);
        $response = $controller->import($request);
        $this->assertSame(302, $response->getStatusCode());

        // Request without mode parameter (defaults to add)
        $requestWithoutMode = Request::create('/outstanding-materials/import', 'POST', [], [], ['import_file' => $file]);
        $responseWithoutMode = $controller->import($requestWithoutMode);
        $this->assertSame(302, $responseWithoutMode->getStatusCode());
    }

    public function test_export_generates_exact_column_order_and_mapping(): void
    {
        $expectedHeadings = [
            'NO',
            'Supplier',
            'Number Invoice',
            'TYPE',
            'Thickness',
            'Width',
            'Diameter',
            'Length',
            'QTY (PCS)',
            'Est QTY (KG)',
            'Status',
            'Estimasi ETA Port',
            'Estimasi ETA Warehouse',
            'Estimasi Bulan ETA',
            'Keterangan',
            'Estimasi Delay ETA Port',
            'Estimasi Delay ETA Warehouse',
            'Port',
            'Nomor PO',
            'Month Order',
            'Remarks',
        ];

        $export = new OutstandingMaterialExport(new Collection([]));
        $this->assertSame($expectedHeadings, $export->headings());
        $this->assertCount(count($expectedHeadings), $export->columnWidths());

        $material = new OutstandingMaterial([
            'supplier' => 'PT Baja Sejahtera',
            'number_invoice' => 'INV-EX-001',
            'type' => 'Plate SS400',
            'thickness' => 12.50,
            'width' => 1500,
            'diameter' => null,
            'length' => '6000',
            'qty_pcs' => 10,
            'est_qty_kg' => 2500,
            'status' => 'Received',
            'estimasi_eta_port' => '2026-06-01',
            'estimasi_eta_warehouse' => '2026-06-15',
            'estimasi_bulan_eta' => 'June 2026',
            'keterangan' => 'On Schedule',
            'estimasi_delay_eta_port' => null,
            'estimasi_delay_eta_warehouse' => null,
            'port' => 'Tanjung Priok',
            'number_po' => 'PO-BAJA-99',
            'month_order' => 'May 2026',
            'remarks' => 'High quality steel batch',
        ]);

        $exportWithData = new OutstandingMaterialExport(new Collection([$material]));
        $mapped = $exportWithData->map($material);

        $this->assertCount(count($expectedHeadings), $mapped);
        $this->assertSame(1, $mapped[0]); // NO
        $this->assertSame('PT Baja Sejahtera', $mapped[1]); // Supplier
        $this->assertSame('INV-EX-001', $mapped[2]); // Number Invoice
        $this->assertSame('Plate SS400', $mapped[3]); // TYPE
        $this->assertSame(12.5, $mapped[4]); // Thickness
        $this->assertSame(1500.0, $mapped[5]); // Width
        $this->assertNull($mapped[6]); // Diameter
        $this->assertSame('6000', $mapped[7]); // Length
        $this->assertSame(10.0, $mapped[8]); // QTY (PCS)
        $this->assertSame(2500.0, $mapped[9]); // Est QTY (KG)
        $this->assertSame('Received', $mapped[10]); // Status
        $this->assertSame('2026-06-01', $mapped[11]); // Estimasi ETA Port
        $this->assertSame('2026-06-15', $mapped[12]); // Estimasi ETA Warehouse
        $this->assertSame('June 2026', $mapped[13]); // Estimasi Bulan ETA
        $this->assertSame('On Schedule', $mapped[14]); // Keterangan
        $this->assertNull($mapped[15]); // Estimasi Delay ETA Port
        $this->assertNull($mapped[16]); // Estimasi Delay ETA Warehouse
        $this->assertSame('Tanjung Priok', $mapped[17]); // Port
        $this->assertSame('PO-BAJA-99', $mapped[18]); // Nomor PO
        $this->assertSame('May 2026', $mapped[19]); // Month Order
        $this->assertSame('High quality steel batch', $mapped[20]); // Remarks
    }

    public function test_template_export_headings_and_widths_match_data_export(): void
    {
        $template = new OutstandingMaterialTemplateExport();
        $export = new OutstandingMaterialExport(new Collection([]));

        $this->assertSame($export->headings(), $template->headings());
        $this->assertSame(array_keys($export->columnWidths()), array_keys($template->columnWidths()));
    }

    public function test_controller_batch_store_persists_header_port_number_po_month_order_and_remarks(): void
    {
        if (! Schema::hasTable('outstanding_materials') || ! Schema::hasTable('outstanding_material_invoices')) {
            $this->markTestSkipped('Database schema not available.');
        }

        $access = new class extends OutstandingMaterialAccessService
        {
            public function canManage(?User $user): bool
            {
                return true;
            }
        };
        $identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $batch = new OutstandingMaterialBatchService($identity, $documents, new OutstandingMaterialInvoiceService($identity));
        $controller = new OutstandingMaterialController($access, $documents, $identity, $batch);

        $user = new User(['name' => 'ADMINISTRATOR']);
        $user->id = 1;
        Auth::setUser($user);

        $request = Request::create(route('outstanding-materials.bulk-store'), 'POST', [
            'supplier' => 'PT Batch Controller',
            'number_invoice' => 'INV-CTRL-01',
            'status' => 'Received',
            'port' => 'Tanjung Priok',
            'number_po' => 'PO-CTRL-123',
            'month_order' => 'July 2026',
            'remarks' => 'Batch remarks test from controller',
            'materials' => [
                ['type' => 'Plate', 'qty_pcs' => 10],
                ['type' => 'Pipe', 'qty_pcs' => 5],
            ],
        ]);

        $response = $controller->storeBatch($request);
        $this->assertEquals(302, $response->getStatusCode());

        $materials = OutstandingMaterial::where('number_invoice', 'INV-CTRL-01')->get();
        $this->assertCount(2, $materials);
        $this->assertSame('Tanjung Priok', $materials[0]->port);
        $this->assertSame('PO-CTRL-123', $materials[0]->number_po);
        $this->assertSame('July 2026', $materials[0]->month_order);
        $this->assertSame('Batch remarks test from controller', $materials[0]->remarks);
        $this->assertSame('Tanjung Priok', $materials[1]->port);
        $this->assertSame('PO-CTRL-123', $materials[1]->number_po);
        $this->assertSame('July 2026', $materials[1]->month_order);
        $this->assertSame('Batch remarks test from controller', $materials[1]->remarks);
    }
}

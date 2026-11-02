<?php

namespace Tests\Feature;

use App\Exports\OutstandingMaterialExport;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class OutstandingMaterialAdditionalRevisionsTest extends TestCase
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
            new TestGate2AccessService(),
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

        $this->adminUser = new User(['name' => 'ADMINISTRATOR']);
        Auth::setUser($this->adminUser);
    }

    // =========================================================================
    // TRACK A1: Excel Export Numeric Zero Handling
    // =========================================================================

    public function test_export_implements_with_strict_null_comparison(): void
    {
        $export = new OutstandingMaterialExport(new Collection([]));
        $this->assertInstanceOf(WithStrictNullComparison::class, $export);
    }

    public function test_export_maps_null_and_empty_numeric_values_to_float_zero(): void
    {
        $material = new OutstandingMaterial([
            'supplier' => 'PT Export Zero Test',
            'number_invoice' => 'INV-ZERO-01',
            'type' => 'Plate SS400',
            'thickness' => null,
            'width' => null,
            'diameter' => null,
            'length' => '6000',
            'qty_pcs' => null,
            'est_qty_kg' => null,
            'status' => 'Received',
            'estimasi_eta_port' => null,
            'estimasi_eta_warehouse' => '2026-06-15',
            'estimasi_bulan_eta' => 'June 2026',
            'keterangan' => 'On Schedule',
            'port' => 'Tanjung Priok',
            'number_po' => 'PO-ZERO-1',
            'month_order' => 'May 2026',
            'remarks' => 'Zero preservation check',
        ]);

        $export = new OutstandingMaterialExport(new Collection([$material]));
        $mapped = $export->map($material);

        // Numeric fields must strictly map to 0.0
        $this->assertSame(0.0, $mapped[4]); // Thickness
        $this->assertSame(0.0, $mapped[5]); // Width
        $this->assertSame(0.0, $mapped[6]); // Diameter
        $this->assertSame(0.0, $mapped[8]); // QTY (PCS)
        $this->assertSame(0.0, $mapped[9]); // Est QTY (KG)

        // Non-numeric fields must not be altered to zero
        $this->assertSame('6000', $mapped[7]); // Length (text)
        $this->assertSame('Received', $mapped[10]); // Status
        $this->assertNull($mapped[11]); // Estimasi ETA Port (nullable date)
        $this->assertSame('2026-06-15', $mapped[12]); // Estimasi ETA Warehouse
        $this->assertSame('June 2026', $mapped[13]); // Estimasi Bulan ETA
        $this->assertSame('On Schedule', $mapped[14]); // Keterangan
        $this->assertSame('Tanjung Priok', $mapped[17]); // Port
        $this->assertSame('PO-ZERO-1', $mapped[18]); // Nomor PO
        $this->assertSame('May 2026', $mapped[19]); // Month Order
        $this->assertSame('Zero preservation check', $mapped[20]); // Remarks
    }

    public function test_export_preserves_existing_zero_and_nonzero_numeric_values(): void
    {
        $material = new OutstandingMaterial([
            'supplier' => 'PT Preserved Values',
            'number_invoice' => 'INV-PRESERVE-01',
            'type' => 'Pipe Carbon',
            'thickness' => 0,
            'width' => 1250.75,
            'diameter' => 0.0,
            'length' => '12000',
            'qty_pcs' => 15,
            'est_qty_kg' => 3450.5,
            'status' => 'On Shipment',
        ]);

        $export = new OutstandingMaterialExport(new Collection([$material]));
        $mapped = $export->map($material);

        $this->assertSame(0.0, $mapped[4]); // Thickness 0
        $this->assertSame(1250.75, $mapped[5]); // Width 1250.75
        $this->assertSame(0.0, $mapped[6]); // Diameter 0.0
        $this->assertSame(15.0, $mapped[8]); // QTY 15
        $this->assertSame(3450.5, $mapped[9]); // Est QTY 3450.5
    }

    public function test_actual_generated_xlsx_binary_cell_types_and_values_using_phpspreadsheet(): void
    {
        $material = new OutstandingMaterial([
            'supplier' => 'PT Spreadsheet Check',
            'number_invoice' => 'INV-XLSX-01',
            'type' => 'H-Beam',
            'thickness' => null, // null -> 0.0 numeric
            'width' => 200.5,   // float -> 200.5 numeric
            'diameter' => null, // null -> 0.0 numeric
            'length' => '6000 mm', // text length -> '6000 mm' string
            'qty_pcs' => 0,     // 0 -> 0.0 numeric
            'est_qty_kg' => null, // null -> 0.0 numeric
            'status' => 'On Production',
        ]);

        $fileName = 'test_zero_export_'.uniqid().'.xlsx';
        Storage::disk('local')->delete($fileName);

        Excel::store(new OutstandingMaterialExport(new Collection([$material])), $fileName, 'local');
        $filePath = Storage::disk('local')->path($fileName);

        $this->assertFileExists($filePath);

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Row 1 is headings, Row 2 is data
        // Column E = Thickness (index 4)
        $this->assertEquals(0, $sheet->getCell('E2')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('E2')->getDataType());

        // Column F = Width (index 5)
        $this->assertEquals(200.5, $sheet->getCell('F2')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('F2')->getDataType());

        // Column G = Diameter (index 6)
        $this->assertEquals(0, $sheet->getCell('G2')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('G2')->getDataType());

        // Column H = Length (index 7)
        $this->assertEquals('6000 mm', $sheet->getCell('H2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('H2')->getDataType());

        // Column I = QTY (PCS) (index 8)
        $this->assertEquals(0, $sheet->getCell('I2')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('I2')->getDataType());

        // Column J = Est QTY (KG) (index 9)
        $this->assertEquals(0, $sheet->getCell('J2')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('J2')->getDataType());

        // Cleanup
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        Storage::disk('local')->delete($fileName);
    }

    // =========================================================================
    // TRACK A2: Bulk ETA Update
    // =========================================================================

    public function test_invoice_materials_for_anchor_returns_5_eta_fields(): void
    {
        $supplier = 'PT Modal Scoped Test';
        $invoice = 'INV-MODAL-01';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        $mat = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'type' => 'Plate',
            'status' => 'On Production',
            'estimasi_eta_port' => '2026-11-05',
            'estimasi_eta_warehouse' => '2026-11-20',
            'estimasi_bulan_eta' => 'November 2026',
            'estimasi_delay_eta_port' => '2026-11-10',
            'estimasi_delay_eta_warehouse' => '2026-11-25',
        ]);

        $response = $this->controller->invoiceMaterialsForAnchor($mat);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertNotEmpty($data);
        $first = $data[0];

        $this->assertArrayHasKey('estimasi_eta_port', $first);
        $this->assertArrayHasKey('estimasi_eta_warehouse', $first);
        $this->assertArrayHasKey('estimasi_bulan_eta', $first);
        $this->assertArrayHasKey('estimasi_delay_eta_port', $first);
        $this->assertArrayHasKey('estimasi_delay_eta_warehouse', $first);
        $this->assertSame('2026-11-05', $first['estimasi_eta_port']);
        $this->assertSame('2026-11-20', $first['estimasi_eta_warehouse']);
        $this->assertSame('November 2026', $first['estimasi_bulan_eta']);
        $this->assertSame('2026-11-10', $first['estimasi_delay_eta_port']);
        $this->assertSame('2026-11-25', $first['estimasi_delay_eta_warehouse']);
    }

    public function test_bulk_eta_update_single_field_and_multi_field_with_keep_current(): void
    {
        $supplier = 'PT Bulk Update Test';
        $invoice = 'INV-BULK-01';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        $mat1 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'type' => 'Mat 1',
            'status' => 'On Production',
            'keterangan' => 'Delay',
            'estimasi_eta_port' => '2026-10-01',
            'estimasi_eta_warehouse' => '2026-10-15',
            'estimasi_bulan_eta' => 'October 2026',
        ]);

        $mat2 = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'type' => 'Mat 2',
            'status' => 'On Production',
            'keterangan' => 'Delay',
            'estimasi_eta_port' => '2026-10-05',
            'estimasi_eta_warehouse' => '2026-10-20',
            'estimasi_bulan_eta' => 'October 2026',
        ]);

        $mat3Untouched = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'type' => 'Mat 3',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'estimasi_eta_port' => '2026-09-01',
            'estimasi_eta_warehouse' => '2026-09-10',
            'estimasi_bulan_eta' => 'September 2026',
        ]);

        // Update ETA Port and Bulan ETA for mat1 and mat2 only. Omit status, keterangan, eta_warehouse, delay fields.
        $request = Request::create(route('outstanding-materials.invoice.update.scoped', $mat1), 'POST', [
            'material_ids' => [$mat1->id, $mat2->id],
            'estimasi_eta_port' => '2026-12-01',
            'estimasi_bulan_eta' => 'Desember 2026',
            // status, keterangan, eta_warehouse, delay fields are omitted/null
        ]);

        $response = $this->controller->updateInvoiceFieldsForAnchor($request, $mat1);
        $this->assertEquals(200, $response->getStatusCode());

        $mat1->refresh();
        $mat2->refresh();
        $mat3Untouched->refresh();

        // mat1: ETA Port and Bulan ETA updated; other fields kept current
        $this->assertSame('2026-12-01', $mat1->estimasi_eta_port);
        $this->assertSame('Desember 2026', $mat1->estimasi_bulan_eta);
        $this->assertSame('On Production', $mat1->status); // preserved
        $this->assertSame('Delay', $mat1->keterangan); // preserved
        $this->assertSame('2026-10-15', $mat1->estimasi_eta_warehouse); // preserved

        // mat2: ETA Port and Bulan ETA updated; other fields kept current
        $this->assertSame('2026-12-01', $mat2->estimasi_eta_port);
        $this->assertSame('Desember 2026', $mat2->estimasi_bulan_eta);
        $this->assertSame('On Production', $mat2->status); // preserved
        $this->assertSame('Delay', $mat2->keterangan); // preserved
        $this->assertSame('2026-10-20', $mat2->estimasi_eta_warehouse); // preserved

        // mat3: untouched
        $this->assertSame('Received', $mat3Untouched->status);
        $this->assertSame('Closed', $mat3Untouched->keterangan);
        $this->assertSame('2026-09-01', $mat3Untouched->estimasi_eta_port);
        $this->assertSame('2026-09-10', $mat3Untouched->estimasi_eta_warehouse);
    }

    public function test_bulk_eta_update_rejects_empty_update(): void
    {
        $supplier = 'PT Empty Test';
        $invoice = 'INV-EMPTY-01';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        $mat = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'type' => 'Plate',
            'status' => 'On Production',
        ]);

        $request = Request::create(route('outstanding-materials.invoice.update.scoped', $mat), 'POST', [
            'material_ids' => [$mat->id],
            'status' => null,
            'keterangan' => null,
            'estimasi_eta_port' => '',
            'estimasi_eta_warehouse' => '',
            'estimasi_bulan_eta' => '',
            'estimasi_delay_eta_port' => '',
            'estimasi_delay_eta_warehouse' => '',
        ]);

        $this->expectException(ValidationException::class);
        $this->controller->updateInvoiceFieldsForAnchor($request, $mat);
    }

    public function test_bulk_eta_update_rejects_invalid_calendar_date(): void
    {
        $supplier = 'PT Date Test';
        $invoice = 'INV-DATE-01';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        $mat = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'type' => 'Plate',
            'status' => 'On Production',
        ]);

        // 2026-02-31 is impossible calendar date
        $request = Request::create(route('outstanding-materials.invoice.update.scoped', $mat), 'POST', [
            'material_ids' => [$mat->id],
            'estimasi_eta_port' => '2026-02-31',
        ]);

        $this->expectException(ValidationException::class);
        $this->controller->updateInvoiceFieldsForAnchor($request, $mat);
    }

    public function test_bulk_eta_update_rejects_cross_invoice_materials(): void
    {
        $key1 = $this->identity->invoiceKey('Supplier A', 'INV-CROSS-A');
        $mat1 = OutstandingMaterial::create([
            'supplier' => 'Supplier A',
            'number_invoice' => 'INV-CROSS-A',
            'invoice_identity_key' => $key1,
            'type' => 'Mat A',
            'status' => 'On Production',
        ]);

        $key2 = $this->identity->invoiceKey('Supplier B', 'INV-CROSS-B');
        $mat2 = OutstandingMaterial::create([
            'supplier' => 'Supplier B',
            'number_invoice' => 'INV-CROSS-B',
            'invoice_identity_key' => $key2,
            'type' => 'Mat B',
            'status' => 'On Production',
        ]);

        // Attempt to update mat2 through anchor mat1
        $request = Request::create(route('outstanding-materials.invoice.update.scoped', $mat1), 'POST', [
            'material_ids' => [$mat1->id, $mat2->id],
            'status' => 'On Shipment',
        ]);

        $this->expectException(ValidationException::class);
        $this->controller->updateInvoiceFieldsForAnchor($request, $mat1);
    }

    public function test_bulk_eta_update_rejects_update_on_archived_invoice(): void
    {
        $supplier = 'Supplier Archived Update';
        $invoice = 'INV-ARCH-UPD-01';
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
            'type' => 'Mat Arch',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $request = Request::create(route('outstanding-materials.invoice.update.scoped', $mat), 'POST', [
            'material_ids' => [$mat->id],
            'status' => 'On Shipment',
        ]);

        $this->expectException(ValidationException::class);
        $this->controller->updateInvoiceFieldsForAnchor($request, $mat);
    }

    // =========================================================================
    // TRACK B1: Archived Materials Page & Read-Only Actions
    // =========================================================================

    public function test_archived_materials_page_renders_for_authorized_user(): void
    {
        $response = $this->controller->archivedMaterialIndex();

        $this->assertEquals('outstanding_materials.archived_materials', $response->name());
        $data = $response->getData();
        $this->assertArrayHasKey('filterOptions', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('canExportOutstandingMaterials', $data);
        $this->assertArrayHasKey('canDownloadDocuments', $data);
    }

    public function test_archived_materials_data_returns_only_materials_from_archived_invoices(): void
    {
        // 1 Active Invoice with 2 materials
        $activeKey = $this->identity->invoiceKey('Supplier Active B', 'INV-ACT-B');
        $activeHeader = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier Active B',
            'number_invoice' => 'INV-ACT-B',
            'invoice_identity_key' => $activeKey,
            'archived_at' => null,
        ]);
        $actMat1 = OutstandingMaterial::create([
            'supplier' => 'Supplier Active B',
            'number_invoice' => 'INV-ACT-B',
            'invoice_id' => $activeHeader->id,
            'invoice_identity_key' => $activeKey,
            'type' => 'Active Mat 1',
            'status' => 'On Production',
        ]);
        $actMat2 = OutstandingMaterial::create([
            'supplier' => 'Supplier Active B',
            'number_invoice' => 'INV-ACT-B',
            'invoice_id' => $activeHeader->id,
            'invoice_identity_key' => $activeKey,
            'type' => 'Active Mat 2',
            'status' => 'On Shipment',
        ]);

        // 1 Archived Invoice with 2 materials
        $archKey = $this->identity->invoiceKey('Supplier Archived B', 'INV-ARCH-B');
        $archHeader = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier Archived B',
            'number_invoice' => 'INV-ARCH-B',
            'invoice_identity_key' => $archKey,
            'archived_at' => now(),
        ]);
        $archMat1 = OutstandingMaterial::create([
            'supplier' => 'Supplier Archived B',
            'number_invoice' => 'INV-ARCH-B',
            'invoice_id' => $archHeader->id,
            'invoice_identity_key' => $archKey,
            'type' => 'Archived Mat 1',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);
        $archMat2 = OutstandingMaterial::create([
            'supplier' => 'Supplier Archived B',
            'number_invoice' => 'INV-ARCH-B',
            'invoice_id' => $archHeader->id,
            'invoice_identity_key' => $archKey,
            'type' => 'Archived Mat 2',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $request = Request::create(route('outstanding-materials.materials.archived.data'), 'GET');
        $response = $this->controller->archivedMaterialData($request);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = $response->getData(true);

        $returnedIds = collect($payload['data'])->pluck('id')->all();

        // Must contain only materials from the archived invoice
        $this->assertContains($archMat1->id, $returnedIds);
        $this->assertContains($archMat2->id, $returnedIds);
        $this->assertNotContains($actMat1->id, $returnedIds);
        $this->assertNotContains($actMat2->id, $returnedIds);
    }

    public function test_archived_material_row_actions_are_read_only_and_downloads_respect_permissions(): void
    {
        $supplier = 'Supplier ReadOnly Row';
        $invoice = 'INV-RO-01';
        $key = $this->identity->invoiceKey($supplier, $invoice);

        $header = OutstandingMaterialInvoice::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_identity_key' => $key,
            'archived_at' => now(),
        ]);

        $docPath = OutstandingMaterialDocumentService::PRIVATE_PREFIX.'packing-lists/sample_'.uniqid().'.pdf';
        $mat = OutstandingMaterial::create([
            'supplier' => $supplier,
            'number_invoice' => $invoice,
            'invoice_id' => $header->id,
            'invoice_identity_key' => $key,
            'type' => 'Mat RO',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'packing_list_path' => $docPath,
        ]);

        Storage::disk('local')->put($docPath, 'fake pdf');

        $request = Request::create(route('outstanding-materials.materials.archived.data'), 'GET', [
            'number_invoice' => $invoice,
        ]);
        $response = $this->controller->archivedMaterialData($request);
        $payload = $response->getData(true);

        $row = collect($payload['data'])->firstWhere('id', $mat->id);
        $this->assertNotNull($row);

        // Action column contains Detail button, but NO edit, NO delete, NO restore button
        $this->assertStringContainsString('om-action-btn--detail', $row['actions']);
        $this->assertStringNotContainsString('om-action-btn--edit', $row['actions']);
        $this->assertStringNotContainsString('om-action-btn--delete', $row['actions']);
        $this->assertStringNotContainsString('om-action-btn--restore', $row['actions']);

        // Packing list link is rendered because file exists and user has download permission
        $this->assertStringContainsString('Download Packing List', $row['packing_list']);
        $this->assertStringContainsString('bi-download', $row['packing_list']);

        Storage::disk('local')->delete($docPath);
    }

    public function test_archived_material_detail_page_is_read_only(): void
    {
        $supplier = 'Supplier Archived Detail';
        $invoice = 'INV-ARCH-DET-01';
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
            'type' => 'Mat Arch Detail',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        // 1. show() view flags
        $response = $this->controller->show($mat);
        $this->assertEquals('outstanding_materials.show', $response->name());
        $viewData = $response->getData();
        $this->assertFalse($viewData['canManageOutstandingMaterials']); // suppresses Add, Import, Template buttons

        // 2. invoiceDetailData() returns rows with context 'archived'
        $dataRequest = Request::create(route('outstanding-materials.invoice-detail.data', $mat), 'GET');
        $dataResponse = $this->controller->invoiceDetailData($dataRequest, $mat);
        $payload = $dataResponse->getData(true);

        $row = collect($payload['data'])->firstWhere('id', $mat->id);
        $this->assertNotNull($row);
        $this->assertStringContainsString('om-action-btn--detail', $row['actions']);
        $this->assertStringNotContainsString('om-action-btn--edit', $row['actions']);
        $this->assertStringNotContainsString('om-action-btn--delete', $row['actions']);
    }

    public function test_restoring_invoice_automatically_moves_materials_back_to_active(): void
    {
        $supplier = 'Supplier Lifecycle';
        $invoice = 'INV-LIFECYCLE-01';
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
            'type' => 'Mat Lifecycle',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        // Step 1: Active
        $activeRequest = Request::create(route('outstanding-materials.data'), 'GET', ['number_invoice' => $invoice]);
        $activeRes = $this->controller->data($activeRequest)->getData(true);
        $this->assertContains($mat->id, collect($activeRes['data'])->pluck('id')->all());

        $archRequest = Request::create(route('outstanding-materials.materials.archived.data'), 'GET', ['number_invoice' => $invoice]);
        $archRes = $this->controller->archivedMaterialData($archRequest)->getData(true);
        $this->assertNotContains($mat->id, collect($archRes['data'])->pluck('id')->all());

        // Step 2: Archive invoice
        $this->invoices->archiveInvoiceGroup($mat, $this->adminUser);

        $activeRes = $this->controller->data($activeRequest)->getData(true);
        $this->assertNotContains($mat->id, collect($activeRes['data'])->pluck('id')->all());

        $archRes = $this->controller->archivedMaterialData($archRequest)->getData(true);
        $this->assertContains($mat->id, collect($archRes['data'])->pluck('id')->all());

        // Step 3: Restore invoice
        $this->invoices->restoreInvoiceGroup($mat, $this->adminUser);

        $activeRes = $this->controller->data($activeRequest)->getData(true);
        $this->assertContains($mat->id, collect($activeRes['data'])->pluck('id')->all());

        $archRes = $this->controller->archivedMaterialData($archRequest)->getData(true);
        $this->assertNotContains($mat->id, collect($archRes['data'])->pluck('id')->all());
    }
}

class TestGate2AccessService extends OutstandingMaterialAccessService
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

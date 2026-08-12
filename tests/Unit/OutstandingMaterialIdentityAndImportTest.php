<?php

namespace Tests\Unit;

use App\Imports\OutstandingMaterialImport;
use App\Services\OutstandingMaterialIdentityService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class OutstandingMaterialIdentityAndImportTest extends TestCase
{
    public function test_identity_normalizes_invoice_without_changing_supplier_display(): void
    {
        $service = new OutstandingMaterialIdentityService();

        $first = $service->canonicalizeInvoice('  Acme   Steel ', ' inv-01 ');
        $second = $service->canonicalizeInvoice('Acme Steel', 'INV-01');

        $this->assertSame('Acme Steel', $first['supplier']);
        $this->assertSame('INV-01', $first['number_invoice']);
        $this->assertSame($first['invoice_identity_key'], $second['invoice_identity_key']);
        $this->assertNotSame(
            $first['invoice_identity_key'],
            $service->invoiceKey('Other Supplier', 'INV-01'),
        );
    }

    public function test_named_template_accepts_duplicate_material_rows_without_line_ref(): void
    {
        $import = new OutstandingMaterialImport(10);
        $import->collection(new Collection([
            ['NO', 'Supplier', 'Number Invoice', 'TYPE', 'Thickness', 'Width', 'Diameter', 'Length', 'QTY (PCS)', 'Est QTY (KG)', 'Status'],
            [1, 'Acme', 'INV-1', 'Plate', 1, 2, null, '10', 5, 12, 'Received'],
            [2, 'Acme', 'INV-1', 'Plate', 1, 2, null, '10', 5, 12, 'Received'],
        ]));

        $this->assertCount(2, $import->rows());
        $this->assertSame([], $import->errors());
        $this->assertSame('INV-1', $import->rows()[0]['number_invoice']);
        $first = $import->rows()[0];
        $second = $import->rows()[1];
        unset($first['_row_number'], $second['_row_number']);
        $this->assertSame($first, $second);
    }

    public function test_legacy_positional_template_without_line_ref_is_accepted(): void
    {
        $import = new OutstandingMaterialImport(10);
        $import->collection(new Collection([
            ['NO', 'Supplier', 'TYPE', 'Thickness', 'Width', 'Diameter', 'Length', 'QTY (PCS)', 'Est QTY (KG)', 'Number Invoice', 'Status'],
            [1, 'Acme', 'Plate', 1, 2, null, '10', 5, 12, 'INV-1', 'Received'],
        ]));

        $this->assertCount(1, $import->rows());
        $this->assertSame([], $import->errors());
        $this->assertSame('INV-1', $import->rows()[0]['number_invoice']);
        $this->assertArrayNotHasKey('line_ref', $import->rows()[0]);
    }
}

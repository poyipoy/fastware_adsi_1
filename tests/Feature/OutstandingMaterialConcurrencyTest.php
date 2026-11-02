<?php

namespace Tests\Feature;

use App\Models\OutstandingMaterial;
use App\Models\OutstandingMaterialInvoice;
use App\Services\OutstandingMaterialIdentityService;
use App\Services\OutstandingMaterialInvoiceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OutstandingMaterialConcurrencyTest extends TestCase
{
    use DatabaseTransactions;

    private OutstandingMaterialIdentityService $identity;

    private OutstandingMaterialInvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->identity = new OutstandingMaterialIdentityService();
        $this->invoiceService = new OutstandingMaterialInvoiceService($this->identity);
    }

    public function test_simultaneous_archive_requests_handled_safely(): void
    {
        $key = $this->identity->invoiceKey('Supplier Concurrent', 'INV-CONCUR-01');
        $header = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier Concurrent',
            'number_invoice' => 'INV-CONCUR-01',
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);

        $material = OutstandingMaterial::create([
            'supplier' => 'Supplier Concurrent',
            'number_invoice' => 'INV-CONCUR-01',
            'invoice_identity_key' => $key,
            'invoice_id' => $header->id,
            'type' => 'Pipe',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        // First archive call succeeds
        $firstResult = $this->invoiceService->archiveInvoiceGroup($material);
        $this->assertTrue($firstResult->isArchived());

        // Second simultaneous/subsequent archive call is rejected safely with ValidationException
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invoice ini sudah diarsipkan');
        $this->invoiceService->archiveInvoiceGroup($material);
    }

    public function test_archive_locks_and_verifies_state_atomically(): void
    {
        $key = $this->identity->invoiceKey('Supplier Concur Atom', 'INV-CONCUR-02');
        $header = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier Concur Atom',
            'number_invoice' => 'INV-CONCUR-02',
            'invoice_identity_key' => $key,
            'archived_at' => null,
        ]);

        $material = OutstandingMaterial::create([
            'supplier' => 'Supplier Concur Atom',
            'number_invoice' => 'INV-CONCUR-02',
            'invoice_identity_key' => $key,
            'invoice_id' => $header->id,
            'type' => 'Pipe',
            'status' => 'On Shipment', // Ineligible
            'keterangan' => null,
        ]);

        // Attempting to archive in transaction rolls back cleanly on ineligibility
        try {
            $this->invoiceService->archiveInvoiceGroup($material);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('received', strtolower($e->getMessage()));
        }

        // Verify header was not partially marked as archived
        $freshHeader = $header->fresh();
        $this->assertNull($freshHeader->archived_at);
        $this->assertFalse($freshHeader->isArchived());
    }

    public function test_restore_and_subsequent_archive_cycle(): void
    {
        $key = $this->identity->invoiceKey('Supplier Concur Cycle', 'INV-CONCUR-03');
        $header = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier Concur Cycle',
            'number_invoice' => 'INV-CONCUR-03',
            'invoice_identity_key' => $key,
            'archived_at' => now(),
        ]);

        $material = OutstandingMaterial::create([
            'supplier' => 'Supplier Concur Cycle',
            'number_invoice' => 'INV-CONCUR-03',
            'invoice_identity_key' => $key,
            'invoice_id' => $header->id,
            'type' => 'Plate',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        // Restore
        $restored = $this->invoiceService->restoreInvoiceGroup($material);
        $this->assertFalse($restored->isArchived());
        $this->assertNull($restored->fresh()->archived_at);

        // Can archive again cleanly
        $reArchived = $this->invoiceService->archiveInvoiceGroup($material);
        $this->assertTrue($reArchived->isArchived());
        $this->assertNotNull($reArchived->fresh()->archived_at);
    }
}

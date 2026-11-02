<?php

namespace Tests\Feature;

use App\Models\OutstandingMaterial;
use App\Models\OutstandingMaterialInvoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class OutstandingMaterialMigrationTest extends TestCase
{
    private function guardTestingDatabase(): void
    {
        $environment = (string) app()->environment();
        $database = (string) config('database.connections.'.config('database.default').'.database');

        if (strtolower($environment) !== 'testing' || ! str_ends_with(strtolower($database), '_testing')) {
            throw new LogicException(
                'Migration test requires APP_ENV=testing and DB_DATABASE ending in _testing. Current: '.$database
            );
        }
    }

    private function getMigration(): Migration
    {
        return require database_path(
            'migrations/2026_10_15_000001_add_archived_at_to_outstanding_material_invoices_table.php'
        );
    }

    private function getMaterialMigration(): Migration
    {
        return require database_path(
            'migrations/2026_10_15_000002_add_archived_at_to_outstanding_materials_table.php'
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->guardTestingDatabase();
    }

    public function test_environment_is_guarded_and_isolated(): void
    {
        $this->assertSame('testing', app()->environment());
        $database = (string) DB::connection()->getDatabaseName();
        $this->assertStringEndsWith('_testing', $database);
    }

    public function test_migration_adds_archived_at_column_and_indexes(): void
    {
        $migration = $this->getMigration();

        // Run migration up
        $migration->up();

        // 1. Verify archived_at column on outstanding_material_invoices
        $this->assertTrue(
            Schema::hasColumn('outstanding_material_invoices', 'archived_at'),
            'Column archived_at must exist on outstanding_material_invoices.'
        );

        // 2. Verify index om_invoices_archived_at_idx
        $archiveIndexes = DB::select(
            "SHOW INDEX FROM `outstanding_material_invoices` WHERE Key_name = 'om_invoices_archived_at_idx'"
        );
        $this->assertNotEmpty($archiveIndexes, 'Index om_invoices_archived_at_idx must exist on outstanding_material_invoices.');

        // 3. Verify index om_delay_eta_wh_idx on outstanding_materials
        $delayIndexes = DB::select(
            "SHOW INDEX FROM `outstanding_materials` WHERE Key_name = 'om_delay_eta_wh_idx'"
        );
        $this->assertNotEmpty($delayIndexes, 'Index om_delay_eta_wh_idx must exist on outstanding_materials.');
    }

    public function test_migration_up_is_idempotent(): void
    {
        $migration = $this->getMigration();

        // Executing up() again should not fail or duplicate objects
        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasColumn('outstanding_material_invoices', 'archived_at'));

        $archiveIndexes = DB::select(
            "SHOW INDEX FROM `outstanding_material_invoices` WHERE Key_name = 'om_invoices_archived_at_idx'"
        );
        $this->assertCount(1, $archiveIndexes);

        $delayIndexes = DB::select(
            "SHOW INDEX FROM `outstanding_materials` WHERE Key_name = 'om_delay_eta_wh_idx'"
        );
        $this->assertCount(1, $delayIndexes);
    }

    public function test_migration_is_pure_ddl_and_preserves_existing_data(): void
    {
        $migration = $this->getMigration();
        $migration->up();

        $uniq = uniqid();
        $invNum = 'INV-DDL-'.$uniq;
        $key = hash('sha256', 'PT TEST SUPPLIER DDL '.$uniq."\x1F".$invNum);

        $invoice = null;
        $material = null;

        try {
            // Create test invoice and material
            $invoice = OutstandingMaterialInvoice::create([
                'supplier' => 'PT Test Supplier DDL '.$uniq,
                'number_invoice' => $invNum,
                'invoice_identity_key' => $key,
                'document_review_required' => false,
            ]);

            $material = OutstandingMaterial::create([
                'supplier' => 'PT Test Supplier DDL '.$uniq,
                'type' => 'Steel Plate',
                'number_invoice' => $invNum,
                'invoice_id' => $invoice->id,
                'invoice_identity_key' => $invoice->invoice_identity_key,
                'status' => OutstandingMaterial::STATUS_ON_SHIPMENT,
                'keterangan' => OutstandingMaterial::KETERANGAN_ON_SCHEDULE,
                'estimasi_delay_eta_warehouse' => '2026-11-20',
            ]);

            // Re-run migration up to ensure it is pure DDL and preserves data
            $migration->up();

            $freshInvoice = $invoice->fresh();
            $freshMaterial = $material->fresh();

            // Assert existing invoices remain active by default (archived_at is NULL)
            $this->assertNull($freshInvoice->archived_at, 'Existing invoice must remain active by default (archived_at is NULL).');
            $this->assertSame($invNum, $freshInvoice->number_invoice);

            // Assert existing materials remain untouched
            $this->assertSame(OutstandingMaterial::STATUS_ON_SHIPMENT, $freshMaterial->status);
            $this->assertSame(OutstandingMaterial::KETERANGAN_ON_SCHEDULE, $freshMaterial->keterangan);
            $this->assertSame('2026-11-20', $freshMaterial->estimasi_delay_eta_warehouse);

            // Assert relationship is intact
            $this->assertSame($freshInvoice->id, $freshMaterial->invoice_id);
            $this->assertTrue($freshInvoice->materials->contains($freshMaterial));
        } finally {
            if ($material) {
                $material->forceDelete();
            }
            if ($invoice) {
                $invoice->delete();
            }
            // Clean up any residual test records from previous attempts
            OutstandingMaterialInvoice::where('number_invoice', 'like', 'INV-DDL-%')->delete();
        }
    }

    public function test_migration_down_cleanly_drops_archived_at_and_delay_eta_index(): void
    {
        $migration = $this->getMigration();

        // Ensure up state
        $migration->up();
        $this->assertTrue(Schema::hasColumn('outstanding_material_invoices', 'archived_at'));

        // Execute down()
        $migration->down();

        // 1. Column archived_at must be dropped
        $this->assertFalse(
            Schema::hasColumn('outstanding_material_invoices', 'archived_at'),
            'Column archived_at must be dropped after down().'
        );

        // 2. Index om_invoices_archived_at_idx must be dropped
        $archiveIndexes = DB::select(
            "SHOW INDEX FROM `outstanding_material_invoices` WHERE Key_name = 'om_invoices_archived_at_idx'"
        );
        $this->assertEmpty($archiveIndexes, 'Index om_invoices_archived_at_idx must be dropped.');

        // 3. Index om_delay_eta_wh_idx must be dropped
        $delayIndexes = DB::select(
            "SHOW INDEX FROM `outstanding_materials` WHERE Key_name = 'om_delay_eta_wh_idx'"
        );
        $this->assertEmpty($delayIndexes, 'Index om_delay_eta_wh_idx must be dropped.');

        // Re-apply up() to leave testing database in expected post-migration state
        $migration->up();
        $this->assertTrue(Schema::hasColumn('outstanding_material_invoices', 'archived_at'));
    }

    public function test_material_migration_adds_archived_at_column_and_index(): void
    {
        $migration = $this->getMaterialMigration();
        $migration->up();

        $this->assertTrue(
            Schema::hasColumn('outstanding_materials', 'archived_at'),
            'Column archived_at must exist on outstanding_materials.'
        );

        $indexes = DB::select(
            "SHOW INDEX FROM `outstanding_materials` WHERE Key_name = 'om_materials_archived_at_idx'"
        );
        $this->assertNotEmpty($indexes, 'Index om_materials_archived_at_idx must exist on outstanding_materials.');
    }

    public function test_material_migration_is_idempotent(): void
    {
        $migration = $this->getMaterialMigration();

        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasColumn('outstanding_materials', 'archived_at'));

        $indexes = DB::select(
            "SHOW INDEX FROM `outstanding_materials` WHERE Key_name = 'om_materials_archived_at_idx'"
        );
        $this->assertCount(1, $indexes);
    }

    public function test_material_migration_down_cleanly_drops_archived_at_and_index(): void
    {
        $migration = $this->getMaterialMigration();

        $migration->up();
        $this->assertTrue(Schema::hasColumn('outstanding_materials', 'archived_at'));

        $migration->down();

        $this->assertFalse(
            Schema::hasColumn('outstanding_materials', 'archived_at'),
            'Column archived_at must be dropped after down().'
        );

        $indexes = DB::select(
            "SHOW INDEX FROM `outstanding_materials` WHERE Key_name = 'om_materials_archived_at_idx'"
        );
        $this->assertEmpty($indexes, 'Index om_materials_archived_at_idx must be dropped.');

        // Re-apply up() to leave testing DB in post-migration state
        $migration->up();
        $this->assertTrue(Schema::hasColumn('outstanding_materials', 'archived_at'));
    }
}

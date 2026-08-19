<?php

namespace Tests\Feature\Warehouse;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WarehouseSchemaTest extends WarehouseTestCase
{
    public function test_all_domain_tables_and_required_columns_exist(): void
    {
        foreach ([
            'mst_wh_consumable_categories',
            'mst_wh_consumables',
            'mst_wh_user_cards',
            'trs_wh_stock_transactions',
            'log_wh_verifications',
            'mst_wh_restricted_verifiers',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table.' must exist');
        }

        $this->assertTrue(Schema::hasColumns('mst_wh_consumables', [
            'item_code', 'barcode', 'current_stock', 'stock_deltamas', 'stock_ds8', 'stock_used_deltamas',
            'stock_used_ds8', 'machine_type', 'photo_path', 'minimum_stock', 'maximum_stock', 'allow_fraction',
        ]));
        $this->assertTrue(Schema::hasColumns('mst_wh_user_cards', [
            'can_verify_stock_in', 'can_verify_stock_out',
        ]));
        $this->assertTrue(Schema::hasColumns('trs_wh_stock_transactions', [
            'idempotency_key', 'operation_key', 'item_condition', 'from_location', 'to_location',
            'stock_before', 'stock_after', 'reversal_of_id', 'verified_user_section',
        ]));
        $this->assertTrue(Schema::hasColumns('log_wh_verifications', [
            'scanned_code_hash', 'status', 'failure_reason', 'transaction_id',
        ]));
    }

    public function test_warehouse_migration_can_be_rolled_back_and_reapplied(): void
    {
        $migrationFiles = [
            '2026_08_07_000001_create_mst_wh_consumable_categories_table.php',
            '2026_08_07_000002_create_mst_wh_consumables_table.php',
            '2026_08_07_000003_create_mst_wh_user_cards_table.php',
            '2026_08_07_000004_create_trs_wh_stock_transactions_table.php',
            '2026_08_07_000005_create_log_wh_verifications_table.php',
            '2026_08_11_000001_add_verification_permissions_to_mst_wh_user_cards_table.php',
            '2026_08_18_000001_add_revision_two_inventory_fields_to_mst_wh_consumables_table.php',
            '2026_08_18_000002_add_revision_two_audit_fields_to_trs_wh_stock_transactions_table.php',
            '2026_08_18_000003_create_mst_wh_restricted_verifiers_table.php',
        ];

        foreach (array_reverse($migrationFiles) as $migrationFile) {
            (require database_path('migrations/'.$migrationFile))->down();
        }

        foreach (['mst_wh_consumable_categories', 'mst_wh_consumables', 'mst_wh_user_cards', 'trs_wh_stock_transactions', 'log_wh_verifications', 'mst_wh_restricted_verifiers'] as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }

        foreach ($migrationFiles as $migrationFile) {
            (require database_path('migrations/'.$migrationFile))->up();
        }

        $this->assertTrue(Schema::hasTable('log_wh_verifications'));
    }

    public function test_existing_card_mappings_are_fail_closed_when_permission_columns_are_added(): void
    {
        $user = $this->createUser();
        $migration = require database_path('migrations/2026_08_11_000001_add_verification_permissions_to_mst_wh_user_cards_table.php');
        $migration->down();

        $cardId = DB::table('mst_wh_user_cards')->insertGetId([
            'user_id' => $user->getKey(),
            'card_code' => 'LEGACY-WITHOUT-DIRECTION',
            'is_active' => true,
            'registered_by' => null,
            'registered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();

        $mapping = DB::table('mst_wh_user_cards')->where('id', $cardId)->first();
        self::assertSame(0, (int) $mapping->can_verify_stock_in);
        self::assertSame(0, (int) $mapping->can_verify_stock_out);
    }

    public function test_revision_two_backfills_existing_stock_as_new_at_the_legacy_location(): void
    {
        $migration = require database_path('migrations/2026_08_18_000001_add_revision_two_inventory_fields_to_mst_wh_consumables_table.php');
        $migration->down();
        $id = DB::table('mst_wh_consumables')->insertGetId([
            'category_id' => null,
            'item_code' => 'BACKFILL-DS8',
            'barcode' => 'BACKFILL-DS8',
            'item_name' => 'Backfill DS8',
            'unit' => 'pcs',
            'allow_fraction' => false,
            'current_stock' => '4.000',
            'minimum_stock' => '0.000',
            'maximum_stock' => null,
            'storage_location' => 'DS8',
            'description' => null,
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();

        $this->assertDatabaseHas('mst_wh_consumables', [
            'id' => $id,
            'stock_ds8' => '4.000',
            'stock_deltamas' => '0.000',
            'stock_used_ds8' => '0.000',
            'stock_used_deltamas' => '0.000',
        ]);
    }

    public function test_revision_two_stops_before_schema_change_when_nonzero_legacy_stock_has_no_location(): void
    {
        $migration = require database_path('migrations/2026_08_18_000001_add_revision_two_inventory_fields_to_mst_wh_consumables_table.php');
        $migration->down();
        DB::table('mst_wh_consumables')->insert([
            'category_id' => null,
            'item_code' => 'BACKFILL-BLOCKED',
            'barcode' => 'BACKFILL-BLOCKED',
            'item_name' => 'Backfill Blocked',
            'unit' => 'pcs',
            'allow_fraction' => false,
            'current_stock' => '1.000',
            'minimum_stock' => '0.000',
            'maximum_stock' => null,
            'storage_location' => null,
            'description' => null,
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $migration->up();
            self::fail('Migration must reject nonzero stock without a valid location.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('stok nonzero tanpa lokasi', $exception->getMessage());
            self::assertFalse(Schema::hasColumn('mst_wh_consumables', 'stock_ds8'));
        }
    }

    public function test_restricted_verifier_seed_resolves_exact_active_name_and_npk_idempotently(): void
    {
        $resolveFixture = function (string $name, int $npk): User {
            User::query()
                ->where('name', $name)
                ->where('npk', $npk)
                ->where('username', 'like', 'warehouse-%')
                ->delete();

            $existing = User::query()
                ->where('name', $name)
                ->where('npk', $npk)
                ->where('is_active', config('warehouse.identity.active_user_value', 0))
                ->get();
            self::assertLessThanOrEqual(1, $existing->count(), 'Testing fixture contains duplicate non-test identities.');

            return $existing->first() ?? $this->createUser(['name' => $name, 'npk' => $npk], false);
        };

        $ragil = $resolveFixture('RAGIL ISHA RAHMANTO', 5639);
        $rodjo = $resolveFixture('ARY RODJO PRASETYO', 5439);
        $migration = require database_path('migrations/2026_08_18_000004_seed_mst_wh_restricted_verifiers.php');

        $migration->up();
        $migration->up();

        $this->assertDatabaseHas('mst_wh_restricted_verifiers', [
            'user_id' => $ragil->id,
            'scope' => 'ALL',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('mst_wh_restricted_verifiers', [
            'user_id' => $rodjo->id,
            'scope' => 'ALL',
            'is_active' => true,
        ]);
        self::assertSame(2, DB::table('mst_wh_restricted_verifiers')->count());
    }
}

<?php

namespace Tests\Feature\Warehouse;

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
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table.' must exist');
        }

        $this->assertTrue(Schema::hasColumns('mst_wh_consumables', [
            'item_code', 'barcode', 'current_stock', 'minimum_stock', 'maximum_stock', 'allow_fraction',
        ]));
        $this->assertTrue(Schema::hasColumns('mst_wh_user_cards', [
            'can_verify_stock_in', 'can_verify_stock_out',
        ]));
        $this->assertTrue(Schema::hasColumns('trs_wh_stock_transactions', [
            'idempotency_key', 'stock_before', 'stock_after', 'reversal_of_id', 'verified_user_section',
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
        ];

        foreach (array_reverse($migrationFiles) as $migrationFile) {
            (require database_path('migrations/'.$migrationFile))->down();
        }

        foreach (['mst_wh_consumable_categories', 'mst_wh_consumables', 'mst_wh_user_cards', 'trs_wh_stock_transactions', 'log_wh_verifications'] as $table) {
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
}

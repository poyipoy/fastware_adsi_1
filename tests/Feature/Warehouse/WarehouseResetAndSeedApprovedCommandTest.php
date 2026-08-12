<?php

namespace Tests\Feature\Warehouse;

use App\Models\User;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseConsumableCategory;
use App\Models\Warehouse\WarehouseStockTransaction;
use App\Services\Warehouse\WarehouseResetBackupService;
use Database\Seeders\WarehouseApprovedBarcodeDataSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class WarehouseResetAndSeedApprovedCommandTest extends WarehouseTestCase
{
    public function test_dry_run_and_missing_confirmation_do_not_change_warehouse_rows(): void
    {
        $this->seedLegacyRows(true);
        $before = $this->warehouseCounts();

        $this->artisan('warehouse:reset-and-seed-approved', ['--dry-run' => true])
            ->assertExitCode(0);
        self::assertSame($before, $this->warehouseCounts());

        $this->artisan('warehouse:reset-and-seed-approved')
            ->assertExitCode(1);
        self::assertSame($before, $this->warehouseCounts());
    }

    public function test_environment_database_and_backup_guards_stop_before_delete(): void
    {
        $this->seedLegacyRows(false);
        $before = $this->warehouseCounts();
        $originalEnvironment = (string) app()->environment();

        app()->detectEnvironment(static fn (): string => 'production');
        try {
            $this->artisan('warehouse:reset-and-seed-approved', [
                '--confirm' => $this->confirmationToken(),
            ])->assertExitCode(1);
        } finally {
            app()->detectEnvironment(fn (): string => $originalEnvironment);
        }
        self::assertSame($before, $this->warehouseCounts());

        app()->detectEnvironment(static fn (): string => 'local');
        try {
            $this->artisan('warehouse:reset-and-seed-approved', [
                '--confirm' => 'RESET-WAREHOUSE-dms_adasi_rev1',
            ])->assertExitCode(1);
        } finally {
            app()->detectEnvironment(fn (): string => $originalEnvironment);
        }
        self::assertSame($before, $this->warehouseCounts());

        $this->fakeBackupFailure();
        $this->artisan('warehouse:reset-and-seed-approved', [
            '--confirm' => $this->confirmationToken(),
        ])->assertExitCode(1);
        self::assertSame($before, $this->warehouseCounts());
    }

    public function test_successful_reset_deletes_old_rows_and_seeds_exact_approved_state(): void
    {
        $this->seedLegacyRows(true);
        $usersBefore = $this->snapshotUsers();
        $this->fakeBackupSuccess();

        $this->artisan('warehouse:reset-and-seed-approved', [
            '--confirm' => $this->confirmationToken(),
        ])->assertExitCode(0);

        self::assertSame([
            'mst_wh_consumable_categories' => 0,
            'mst_wh_consumables' => 4,
            'mst_wh_user_cards' => 0,
            'trs_wh_stock_transactions' => 0,
            'log_wh_verifications' => 0,
        ], $this->warehouseCounts());
        self::assertSame($usersBefore, $this->snapshotUsers());

        $expectedItems = [
            'TFHINSR-000000008' => 'Insert Widia HNPJ0704ANSNGD WS40PM',
            'TFHINSR-000000005' => 'Insert Pramet HNGX 0906ANSN-M M9315',
            'TFHINSR-000000066' => 'Insert Moldino SEK53TN-C9 GX2140',
            'TFHINSR-000000004' => 'Insert Sumitomo SDEN1203AESN',
        ];
        foreach ($expectedItems as $code => $name) {
            $this->assertDatabaseHas('mst_wh_consumables', [
                'item_code' => $code,
                'barcode' => $code,
                'item_name' => $name,
                'unit' => 'pcs',
                'allow_fraction' => false,
                'current_stock' => '0.000',
                'minimum_stock' => '0.000',
                'maximum_stock' => null,
                'storage_location' => null,
                'category_id' => null,
                'description' => null,
                'is_active' => true,
            ]);
        }

        $this->fakeBackupSuccess();
        $this->artisan('warehouse:reset-and-seed-approved', [
            '--confirm' => $this->confirmationToken(),
        ])->assertExitCode(0);

        self::assertSame(4, WarehouseConsumable::query()->count());
        self::assertSame(0, DB::table('mst_wh_user_cards')->count());
        self::assertSame(0, WarehouseStockTransaction::query()->count());
    }

    public function test_seed_failure_rolls_back_deletion_and_partial_insert(): void
    {
        $this->seedLegacyRows(true);
        $before = $this->warehouseCounts();
        $this->fakeBackupSuccess();
        $this->app->instance(WarehouseApprovedBarcodeDataSeeder::class, new class extends WarehouseApprovedBarcodeDataSeeder {
            public function run(): void
            {
                WarehouseConsumable::query()->create([
                    'item_code' => 'PARTIAL-RESET',
                    'barcode' => 'PARTIAL-RESET',
                    'item_name' => 'Partial reset row',
                    'unit' => 'pcs',
                    'allow_fraction' => false,
                    'current_stock' => '0.000',
                    'minimum_stock' => '0.000',
                    'is_active' => true,
                ]);

                throw new RuntimeException('Simulasi kegagalan seed approved.');
            }
        });

        $this->artisan('warehouse:reset-and-seed-approved', [
            '--confirm' => $this->confirmationToken(),
        ])->assertExitCode(1);

        self::assertSame($before, $this->warehouseCounts());
        $this->assertDatabaseMissing('mst_wh_consumables', ['item_code' => 'PARTIAL-RESET']);
    }

    public function test_duplicate_global_npks_are_preserved_because_reset_does_not_manage_users(): void
    {
        $npk = $this->freshNpk();
        $this->createUser(['npk' => $npk]);
        $this->createUser(['npk' => $npk]);
        $this->seedLegacyRows(true);
        $usersBefore = $this->snapshotUsers();
        $this->fakeBackupSuccess();

        $this->artisan('warehouse:reset-and-seed-approved', [
            '--confirm' => $this->confirmationToken(),
        ])->assertExitCode(0);

        self::assertSame(2, User::query()->where('npk', $npk)->count());
        self::assertSame($usersBefore, $this->snapshotUsers());
        self::assertSame(0, DB::table('mst_wh_user_cards')->count());
    }

    /** @return array<int, array{id: int, name: string, npk: ?string, section: ?string, role_id: ?int, is_active: int}> */
    private function snapshotUsers(): array
    {
        $snapshot = [];
        foreach (User::query()->orderBy('id')->get() as $user) {
            $snapshot[(int) $user->getKey()] = [
                'id' => (int) $user->getKey(),
                'name' => (string) $user->name,
                'npk' => $user->npk === null ? null : (string) $user->npk,
                'section' => $user->section === null ? null : (string) $user->section,
                'role_id' => $user->role_id === null ? null : (int) $user->role_id,
                'is_active' => (int) $user->is_active,
            ];
        }

        return $snapshot;
    }

    private function seedLegacyRows(bool $withReversal): void
    {
        $legacyUser = $this->createUser();
        $category = WarehouseConsumableCategory::factory()->create(['code' => 'OLD-CATEGORY']);
        $item = WarehouseConsumable::factory()->create([
            'category_id' => $category->getKey(),
            'item_code' => 'OLD-ITEM',
            'barcode' => 'OLD-BARCODE',
            'current_stock' => '4.000',
        ]);
        DB::table('mst_wh_user_cards')->insert([
            'user_id' => $legacyUser->getKey(),
            'card_code' => 'OLD-CARD-'.$legacyUser->npk,
            'is_active' => true,
            'can_verify_stock_in' => true,
            'can_verify_stock_out' => true,
            'registered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $original = WarehouseStockTransaction::factory()->create([
            'transaction_type' => 'IN',
            'consumable_id' => $item->getKey(),
            'quantity' => '4.000',
            'stock_before' => '0.000',
            'stock_after' => '4.000',
            'verified_user_id' => $legacyUser->getKey(),
            'created_by' => $legacyUser->getKey(),
        ]);
        if ($withReversal) {
            WarehouseStockTransaction::factory()->create([
                'transaction_type' => 'REVERSAL',
                'consumable_id' => $item->getKey(),
                'quantity' => '4.000',
                'stock_before' => '4.000',
                'stock_after' => '0.000',
                'verified_user_id' => $legacyUser->getKey(),
                'created_by' => $legacyUser->getKey(),
                'reversal_of_id' => $original->getKey(),
            ]);
        }
        DB::table('log_wh_verifications')->insert([
            'scanned_code_hash' => hash('sha256', Str::random(16)),
            'user_id' => $legacyUser->getKey(),
            'transaction_id' => $original->getKey(),
            'status' => 'SUCCESS',
            'verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, int> */
    private function warehouseCounts(): array
    {
        return [
            'mst_wh_consumable_categories' => (int) DB::table('mst_wh_consumable_categories')->count(),
            'mst_wh_consumables' => (int) DB::table('mst_wh_consumables')->count(),
            'mst_wh_user_cards' => (int) DB::table('mst_wh_user_cards')->count(),
            'trs_wh_stock_transactions' => (int) DB::table('trs_wh_stock_transactions')->count(),
            'log_wh_verifications' => (int) DB::table('log_wh_verifications')->count(),
        ];
    }

    private function confirmationToken(): string
    {
        return 'RESET-WAREHOUSE-'.DB::connection()->getDatabaseName();
    }

    private function fakeBackupSuccess(): void
    {
        $this->app->instance(WarehouseResetBackupService::class, new class extends WarehouseResetBackupService {
            public function create(array $tables, array $counts): array
            {
                return [
                    'path' => 'testing-backup.sql',
                    'manifest_path' => 'testing-backup.json',
                    'sha256' => str_repeat('a', 64),
                    'bytes' => 1024,
                ];
            }
        });
    }

    private function fakeBackupFailure(): void
    {
        $this->app->instance(WarehouseResetBackupService::class, new class extends WarehouseResetBackupService {
            public function create(array $tables, array $counts): array
            {
                throw new RuntimeException('Backup gagal untuk pengujian.');
            }
        });
    }
}

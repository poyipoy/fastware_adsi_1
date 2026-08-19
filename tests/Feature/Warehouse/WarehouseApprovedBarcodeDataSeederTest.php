<?php

namespace Tests\Feature\Warehouse;

use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use Database\Seeders\WarehouseApprovedBarcodeDataSeeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WarehouseApprovedBarcodeDataSeederTest extends WarehouseTestCase
{
    /** @var array<string, string> */
    private const ITEMS = [
        'TFHINSR-000000008' => 'Insert Widia HNPJ0704ANSNGD WS40PM',
        'TFHINSR-000000005' => 'Insert Pramet HNGX 0906ANSN-M M9315',
        'TFHINSR-000000066' => 'Insert Moldino SEK53TN-C9 GX2140',
        'TFHINSR-000000004' => 'Insert Sumitomo SDEN1203AESN',
    ];

    public function test_seeder_creates_only_approved_items_idempotently_and_leaves_legacy_mapping_inert(): void
    {
        $legacyUser = $this->createUser();
        DB::table('mst_wh_user_cards')->insert([
            'user_id' => $legacyUser->id,
            'card_code' => 'LEGACY-SEEDER-CARD',
            'is_active' => true,
            'can_verify_stock_in' => true,
            'can_verify_stock_out' => true,
            'registered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(WarehouseApprovedBarcodeDataSeeder::class);

        self::assertSame(4, WarehouseConsumable::query()->whereIn('item_code', array_keys(self::ITEMS))->count());
        foreach (self::ITEMS as $itemCode => $itemName) {
            $this->assertDatabaseHas('mst_wh_consumables', [
                'item_code' => $itemCode,
                'barcode' => $itemCode,
                'item_name' => $itemName,
                'unit' => 'pcs',
                'allow_fraction' => false,
                'current_stock' => '0.000',
                'minimum_stock' => '0.000',
                'maximum_stock' => null,
                'is_active' => true,
            ]);
        }
        $this->assertDatabaseHas('mst_wh_user_cards', [
            'user_id' => $legacyUser->id,
            'card_code' => 'LEGACY-SEEDER-CARD',
            'is_active' => true,
        ]);
        self::assertSame(1, DB::table('mst_wh_user_cards')->count());
        self::assertSame(0, WarehouseStockTransaction::query()->count());

        $this->seed(WarehouseApprovedBarcodeDataSeeder::class);

        self::assertSame(4, WarehouseConsumable::query()->count());
        self::assertSame(1, DB::table('mst_wh_user_cards')->count());
        self::assertSame(0, WarehouseStockTransaction::query()->count());
    }

    public function test_master_conflict_rolls_back_without_creating_other_items(): void
    {
        WarehouseConsumable::factory()->create([
            'item_code' => 'TFHINSR-000000008',
            'barcode' => 'DIFFERENT-BARCODE',
            'item_name' => 'Different master data',
        ]);

        try {
            $this->seed(WarehouseApprovedBarcodeDataSeeder::class);
            self::fail('Seeder seharusnya berhenti ketika master barang bertabrakan.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('berbeda', $exception->getMessage());
        }

        self::assertSame(1, WarehouseConsumable::query()->count());
        self::assertSame(0, DB::table('mst_wh_user_cards')->count());
        self::assertSame(0, WarehouseStockTransaction::query()->count());
    }

    public function test_production_environment_guard_stops_before_any_write(): void
    {
        $originalEnvironment = (string) app()->environment();
        app()->detectEnvironment(static fn (): string => 'production');

        try {
            try {
                app(WarehouseApprovedBarcodeDataSeeder::class)->run();
                self::fail('Seeder seharusnya ditolak pada environment production.');
            } catch (RuntimeException $exception) {
                self::assertStringContainsString('APP_ENV=local', $exception->getMessage());
            }
        } finally {
            app()->detectEnvironment(fn (): string => $originalEnvironment);
        }

        self::assertSame(0, WarehouseConsumable::query()->count());
        self::assertSame(0, DB::table('mst_wh_user_cards')->count());
    }

    public function test_database_seeder_does_not_call_approved_barcode_seeder(): void
    {
        $databaseSeeder = file_get_contents(database_path('seeders/DatabaseSeeder.php'));

        self::assertIsString($databaseSeeder);
        self::assertStringNotContainsString('WarehouseApprovedBarcodeDataSeeder', $databaseSeeder);
    }
}

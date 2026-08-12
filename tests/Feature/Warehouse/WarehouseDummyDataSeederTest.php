<?php

namespace Tests\Feature\Warehouse;

use App\Data\Warehouse\WarehouseDashboardFilter;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\User;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseConsumableCategory;
use App\Models\Warehouse\WarehouseStockTransaction;
use Carbon\CarbonImmutable;
use Database\Seeders\WarehouseDummyDataSeeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WarehouseDummyDataSeederTest extends WarehouseTestCase
{
    public function test_seeder_creates_25_in_and_25_out_with_consistent_integer_stock_snapshots(): void
    {
        $this->ensureAdministratorRole();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 10:00:00', 'Asia/Jakarta'));

        try {
            $this->seed(WarehouseDummyDataSeeder::class);

            $month = WarehouseDashboardFilter::currentMonth();
            $transactions = WarehouseStockTransaction::query()->get();

            self::assertSame(1, WarehouseConsumableCategory::query()->where('code', 'WH-DUMMY')->count());
            self::assertSame(5, WarehouseConsumable::query()->where('description', 'like', '%[WAREHOUSE DUMMY]%')->count());
            self::assertSame(1, User::query()->where('username', 'warehouse_dummy_verifier')->count());
            self::assertSame(1, (int) User::query()->where('username', 'warehouse_dummy_verifier')->value('role_id'));
            self::assertSame(0, DB::table('mst_wh_user_cards')->count());
            self::assertSame(50, $transactions->count());
            self::assertSame(25, $transactions->where('transaction_type', WarehouseTransactionType::IN)->count());
            self::assertSame(25, $transactions->where('transaction_type', WarehouseTransactionType::OUT)->count());
            self::assertSame(50, WarehouseStockTransaction::query()->whereBetween('transaction_at', [$month->from, $month->to])->count());

            self::assertTrue($transactions->every(function (WarehouseStockTransaction $transaction): bool {
                $quantity = (string) $transaction->quantity;
                $expectedAfter = $transaction->transaction_type === WarehouseTransactionType::IN
                    ? (float) $transaction->stock_before + (float) $transaction->quantity
                    : (float) $transaction->stock_before - (float) $transaction->quantity;

                return in_array($quantity, ['2.000', '5.000'], true)
                    && (float) $transaction->stock_before >= 0
                    && (float) $transaction->stock_after >= 0
                    && abs((float) $transaction->stock_after - $expectedAfter) < 0.0001
                    && trim((string) $transaction->verified_user_name) !== ''
                    && trim((string) $transaction->verified_user_npk) !== '';
            }));

            $this->seed(WarehouseDummyDataSeeder::class);

            self::assertSame(50, WarehouseStockTransaction::query()->count());
            self::assertSame(5, WarehouseConsumable::query()->where('description', 'like', '%[WAREHOUSE DUMMY]%')->count());
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_non_demo_master_conflict_rolls_back_without_writes(): void
    {
        $this->ensureAdministratorRole();
        WarehouseConsumable::factory()->create(['item_code' => 'WH-DUMMY-001', 'barcode' => 'NON-DEMO-CONFLICT']);
        $usersBefore = User::query()->count();
        $categoriesBefore = WarehouseConsumableCategory::query()->count();

        try {
            $this->seed(WarehouseDummyDataSeeder::class);
            self::fail('Seeder seharusnya berhenti ketika item code demo bertabrakan dengan master non-demo.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('non-demo', $exception->getMessage());
        }

        self::assertSame($usersBefore, User::query()->count());
        self::assertSame($categoriesBefore, WarehouseConsumableCategory::query()->count());
        self::assertSame(0, WarehouseStockTransaction::query()->count());
    }

    public function test_production_environment_guard_stops_before_any_write(): void
    {
        $usersBefore = User::query()->count();
        $categoriesBefore = WarehouseConsumableCategory::query()->count();
        $originalEnvironment = (string) app()->environment();
        app()->detectEnvironment(static fn (): string => 'production');

        try {
            try {
                app(WarehouseDummyDataSeeder::class)->run();
                self::fail('Seeder seharusnya ditolak pada environment production.');
            } catch (RuntimeException $exception) {
                self::assertStringContainsString('APP_ENV=local', $exception->getMessage());
            }
        } finally {
            app()->detectEnvironment(fn (): string => $originalEnvironment);
        }

        self::assertSame($usersBefore, User::query()->count());
        self::assertSame($categoriesBefore, WarehouseConsumableCategory::query()->count());
        self::assertSame(0, WarehouseStockTransaction::query()->count());
    }

    public function test_database_seeder_does_not_call_warehouse_dummy_seeder(): void
    {
        $databaseSeeder = file_get_contents(database_path('seeders/DatabaseSeeder.php'));

        self::assertIsString($databaseSeeder);
        self::assertStringNotContainsString('WarehouseDummyDataSeeder', $databaseSeeder);
    }

    private function ensureAdministratorRole(): void
    {
        if (! DB::table('roles')->where('id', 1)->exists()) {
            DB::table('roles')->insert([
                'id' => 1,
                'role' => 'Administrator',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

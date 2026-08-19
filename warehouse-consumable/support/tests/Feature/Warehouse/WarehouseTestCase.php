<?php

namespace Tests\Feature\Warehouse;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Concerns\GuardsWarehouseTestingDatabase;
use Tests\TestCase;

abstract class WarehouseTestCase extends TestCase
{
    use GuardsWarehouseTestingDatabase;

    /** @var array<int, string> */
    private array $createdSupportTables = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->guardWarehouseTestingDatabase();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Warehouse tests require MySQL.');
        }

        if (! str_ends_with(strtolower((string) DB::connection()->getDatabaseName()), '_testing')) {
            $this->markTestSkipped('Warehouse tests require a database ending in _testing.');
        }

        $this->createSupportSchemaIfMissing();
        $this->rebuildWarehouseSchema();
    }

    protected function tearDown(): void
    {
        if (app()->environment('testing')
            && str_ends_with(strtolower((string) DB::connection()->getDatabaseName()), '_testing')) {
            $this->dropWarehouseSchema();

            if ($this->createdSupportTables !== []) {
                Schema::disableForeignKeyConstraints();
                foreach (array_reverse($this->createdSupportTables) as $table) {
                    Schema::dropIfExists($table);
                }
                Schema::enableForeignKeyConstraints();
            }
        }

        parent::tearDown();
    }

    protected function createUser(array $attributes = [], bool $withWarehouseAccess = true): User
    {
        if (! array_key_exists('npk', $attributes)) {
            $attributes['npk'] = $this->freshNpk();
        }

        $user = User::query()->create(array_merge([
            'name' => 'Warehouse Test User '.uniqid(),
            'section' => 'Testing',
            'username' => 'warehouse-'.uniqid(),
            'email' => 'warehouse-'.uniqid().'@example.test',
            'password' => 'password',
            'is_active' => 0,
        ], $attributes));

        if ($withWarehouseAccess) {
            $this->createDepartmentPosition($user);
            DB::table('mst_wh_restricted_verifiers')->insert([
                'user_id' => $user->getKey(),
                'scope' => 'ALL',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $user;
    }

    protected function freshNpk(): int
    {
        do {
            $npk = random_int(100000, 999999999);
        } while (User::query()->where('npk', $npk)->exists());

        return $npk;
    }

    protected function createPicPosition(User $user, string $name = 'Warehouse Staff', string $level = 'staff'): void
    {
        $this->createDepartmentPosition($user, 'Logistic & Warehouse', $name, $level);
    }

    protected function createDepartmentPosition(
        User $user,
        string $departmentName = 'Production',
        ?string $positionName = null,
        string $level = 'staff',
        array $assignmentAttributes = [],
    ): void {
        $departmentId = DB::table('mst_departments')->where('name', $departmentName)->value('id');

        if ($departmentId === null) {
            $departmentId = DB::table('mst_departments')->insertGetId([
                'name' => $departmentName,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('mst_departments')->where('id', $departmentId)->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
        }

        $positionName ??= 'Warehouse Test Position '.uniqid();
        $positionId = DB::table('mst_job_positions')->where('position_name', $positionName)->value('id');

        if ($positionId === null) {
            $positionId = DB::table('mst_job_positions')->insertGetId([
                'position_name' => $positionName,
                'department_id' => $departmentId,
                'job_level' => $level,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('mst_job_positions')->where('id', $positionId)->update([
                'department_id' => $departmentId,
                'job_level' => $level,
                'is_active' => true,
                'updated_at' => now(),
            ]);
        }

        DB::table('user_job_positions')->insert(array_merge([
            'user_id' => $user->getKey(),
            'mst_job_position_id' => $positionId,
            'is_active' => true,
            'effective_from' => today()->toDateString(),
            'effective_until' => null,
            'assignment_source' => 'warehouse_test',
            'created_at' => now(),
            'updated_at' => now(),
        ], $assignmentAttributes));
    }

    private function rebuildWarehouseSchema(): void
    {
        $this->dropWarehouseSchema();

        foreach ([
            '2026_08_07_000001_create_mst_wh_consumable_categories_table.php',
            '2026_08_07_000002_create_mst_wh_consumables_table.php',
            '2026_08_07_000003_create_mst_wh_user_cards_table.php',
            '2026_08_07_000004_create_trs_wh_stock_transactions_table.php',
            '2026_08_07_000005_create_log_wh_verifications_table.php',
            '2026_08_11_000001_add_verification_permissions_to_mst_wh_user_cards_table.php',
            '2026_08_18_000001_add_revision_two_inventory_fields_to_mst_wh_consumables_table.php',
            '2026_08_18_000002_add_revision_two_audit_fields_to_trs_wh_stock_transactions_table.php',
            '2026_08_18_000003_create_mst_wh_restricted_verifiers_table.php',
            '2026_08_19_000001_create_trs_wh_location_shipments_table.php',
            '2026_08_19_000002_add_location_shipment_id_to_trs_wh_stock_transactions_table.php',
            '2026_08_19_000003_drop_storage_location_from_mst_wh_consumables_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    private function dropWarehouseSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'log_wh_verifications',
            'trs_wh_location_shipments',
            'trs_wh_stock_transactions',
            'mst_wh_restricted_verifiers',
            'mst_wh_user_cards',
            'mst_wh_consumables',
            'mst_wh_consumable_categories',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }

    private function createSupportSchemaIfMissing(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table): void {
                $table->id();
                $table->string('role');
                $table->timestamps();
            });
            $this->createdSupportTables[] = 'roles';
        }

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('role_id')->nullable();
                $table->string('name');
                $table->string('section')->nullable();
                $table->integer('npk')->nullable();
                $table->string('username')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->boolean('is_active')->default(0);
                $table->timestamps();
            });
            $this->createdSupportTables[] = 'users';
        }

        if (! Schema::hasTable('mst_departments')) {
            Schema::create('mst_departments', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
            $this->createdSupportTables[] = 'mst_departments';
        }

        if (! Schema::hasTable('mst_job_positions')) {
            Schema::create('mst_job_positions', function (Blueprint $table): void {
                $table->id();
                $table->string('position_name')->unique();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->string('job_level')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
            $this->createdSupportTables[] = 'mst_job_positions';
        }

        if (! Schema::hasTable('user_job_positions')) {
            Schema::create('user_job_positions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('mst_job_position_id');
                $table->boolean('is_active')->default(true);
                $table->date('effective_from')->nullable();
                $table->date('effective_until')->nullable();
                $table->string('assignment_source')->nullable();
                $table->timestamps();
            });
            $this->createdSupportTables[] = 'user_job_positions';
        }
    }
}

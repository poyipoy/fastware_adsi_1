<?php

namespace Database\Seeders;

use App\Data\Warehouse\WarehouseStockCommand;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\User;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseConsumableCategory;
use App\Services\Warehouse\WarehouseAccessService;
use App\Services\Warehouse\WarehouseStockService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;
use RuntimeException;

final class WarehouseDummyDataSeeder extends Seeder
{
    private const CATEGORY_CODE = 'WH-DUMMY';

    private const MARKER = '[WAREHOUSE DUMMY]';

    private const VERIFIER_USERNAME = 'warehouse_dummy_verifier';

    private const ACTOR_USERNAME = 'warehouse_dummy_admin';

    private const IN_COUNT = 25;

    private const OUT_COUNT = 25;

    public function run(): void
    {
        $this->guardEnvironment();

        $month = CarbonImmutable::now(config('app.timezone', 'Asia/Jakarta'))->format('Y-m');
        $summary = DB::transaction(function () use ($month): array {
            $actor = $this->resolveActor();
            $verifier = $this->ensureVerifier();
            $category = $this->ensureCategory($actor);
            $items = $this->ensureItems($category, $actor);

            return $this->seedTransactions($month, $items, $actor, $verifier);
        });

        $this->command?->info(sprintf(
            'Warehouse dummy data %s: %d IN, %d OUT (%d dibuat, %d replay/skip).',
            $month,
            $summary['in'],
            $summary['out'],
            $summary['created'],
            $summary['replayed'],
        ));
    }

    private function guardEnvironment(): void
    {
        $environment = (string) app()->environment();

        if (! in_array($environment, ['local', 'testing'], true)) {
            throw new RuntimeException('Warehouse dummy seeder hanya boleh dijalankan pada APP_ENV=local atau APP_ENV=testing.');
        }

        $database = strtolower((string) DB::connection()->getDatabaseName());
        if ($environment === 'testing' && ! str_ends_with($database, '_testing')) {
            throw new RuntimeException('Warehouse dummy seeder pada APP_ENV=testing membutuhkan DB_DATABASE yang berakhiran _testing.');
        }

        foreach (['users', 'mst_wh_consumable_categories', 'mst_wh_consumables', 'trs_wh_stock_transactions'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException(sprintf('Tabel %s belum tersedia. Jalankan migration Warehouse terlebih dahulu.', $table));
            }
        }

        foreach (['id', 'role_id', 'name', 'npk', 'section', 'username', 'email', 'is_active'] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                throw new RuntimeException(sprintf('Kolom users.%s diperlukan oleh Warehouse dummy seeder.', $column));
            }
        }
    }

    private function resolveActor(): User
    {
        $activeValue = config('warehouse.identity.active_user_value', 0);
        $adminRoleIds = array_map('intval', (array) config('warehouse.authorization.administrator_role_ids', [1]));

        if ($adminRoleIds !== []) {
            $administrator = User::query()
                ->where('is_active', $activeValue)
                ->whereIn('role_id', $adminRoleIds)
                ->orderBy('id')
                ->first();

            if ($administrator !== null) {
                return $administrator;
            }
        }

        $access = app(WarehouseAccessService::class);
        $pic = User::query()
            ->where('is_active', $activeValue)
            ->orderBy('id')
            ->get()
            ->first(fn (User $user): bool => $access->can($user, 'warehouse.stock-in.create'));

        if ($pic !== null) {
            return $pic;
        }

        if ($adminRoleIds === [] || ! Schema::hasTable('roles')) {
            throw new RuntimeException('Tidak ditemukan actor Warehouse aktif yang berhak Stock In, dan role administrator tidak tersedia.');
        }

        $adminRoleId = $adminRoleIds[0];
        if (! DB::table('roles')->where('id', $adminRoleId)->exists()) {
            throw new RuntimeException(sprintf('Role administrator ID %d tidak tersedia sehingga actor demo tidak dapat dibuat.', $adminRoleId));
        }

        $existing = User::query()->where('username', self::ACTOR_USERNAME)->first();
        if ($existing !== null) {
            $this->assertDemoUser($existing, self::ACTOR_USERNAME);

            if ((int) $existing->getAttribute('is_active') !== (int) $activeValue
                || ! in_array((int) $existing->getAttribute('role_id'), $adminRoleIds, true)) {
                throw new RuntimeException('User actor demo sudah ada tetapi tidak aktif atau role-nya tidak sesuai.');
            }

            return $existing;
        }

        return $this->createUser([
            'role_id' => $adminRoleId,
            'name' => self::MARKER.' Admin',
            'npk' => '99000001',
            'section' => 'Warehouse Demo',
            'username' => self::ACTOR_USERNAME,
            'email' => self::ACTOR_USERNAME.'@example.test',
        ]);
    }

    private function ensureVerifier(): User
    {
        $activeValue = config('warehouse.identity.active_user_value', 0);
        $adminRoleIds = array_map('intval', (array) config('warehouse.authorization.administrator_role_ids', [1]));
        $adminRoleId = $this->administratorRoleId($adminRoleIds);
        $existing = User::query()->where('username', self::VERIFIER_USERNAME)->first();

        if ($existing !== null) {
            $this->assertDemoUser($existing, self::VERIFIER_USERNAME);

            if ((int) $existing->getAttribute('is_active') !== (int) $activeValue) {
                throw new RuntimeException('User verifier demo sudah ada tetapi tidak aktif.');
            }

            if (! in_array((int) $existing->getAttribute('role_id'), $adminRoleIds, true)) {
                $existing->forceFill(['role_id' => $adminRoleId])->save();
            }

            return $existing;
        }

        return $this->createUser([
            'role_id' => $adminRoleId,
            'name' => self::MARKER.' Verifier',
            'npk' => '99000002',
            'section' => 'Warehouse Demo',
            'username' => self::VERIFIER_USERNAME,
            'email' => self::VERIFIER_USERNAME.'@example.test',
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function createUser(array $attributes): User
    {
        $userAttributes = [
            'role_id' => $attributes['role_id'] ?? null,
            'name' => $attributes['name'],
            'npk' => $attributes['npk'],
            'section' => $attributes['section'],
            'username' => $attributes['username'],
            'email' => $attributes['email'],
            'is_active' => config('warehouse.identity.active_user_value', 0),
        ];

        if (Schema::hasColumn('users', 'password')) {
            $userAttributes['password'] = Hash::make('warehouse-dummy');
        }
        if (Schema::hasColumn('users', 'pasword')) {
            $userAttributes['pasword'] = Hash::make('warehouse-dummy');
        }
        if (Schema::hasColumn('users', 'pass')) {
            $userAttributes['pass'] = 'warehouse-dummy';
        }
        if (Schema::hasColumn('users', 'telp')) {
            $userAttributes['telp'] = 0;
        }

        $user = new User();
        $user->forceFill($userAttributes);
        $user->save();

        return $user;
    }

    private function assertDemoUser(User $user, string $username): void
    {
        if ((string) $user->getAttribute('username') !== $username
            || ! str_contains((string) $user->getAttribute('name'), self::MARKER)
            || (string) $user->getAttribute('section') !== 'Warehouse Demo') {
            throw new RuntimeException(sprintf('Username %s sudah digunakan oleh user non-demo.', $username));
        }
    }

    /** @param array<int, int> $adminRoleIds */
    private function administratorRoleId(array $adminRoleIds): int
    {
        if ($adminRoleIds === [] || ! Schema::hasTable('roles')) {
            throw new RuntimeException('Role Administrator tidak tersedia untuk verifier demo Warehouse.');
        }

        $roleId = $adminRoleIds[0];
        if (! DB::table('roles')->where('id', $roleId)->exists()) {
            throw new RuntimeException(sprintf('Role Administrator ID %d tidak tersedia untuk verifier demo Warehouse.', $roleId));
        }

        return $roleId;
    }

    private function ensureCategory(User $actor): WarehouseConsumableCategory
    {
        $existing = WarehouseConsumableCategory::query()->where('code', self::CATEGORY_CODE)->first();

        if ($existing !== null) {
            $this->assertMarker($existing->description, 'kategori '.self::CATEGORY_CODE);

            return $existing;
        }

        return WarehouseConsumableCategory::query()->create([
            'code' => self::CATEGORY_CODE,
            'name' => 'Warehouse Dummy',
            'description' => self::MARKER.' Category',
            'is_active' => true,
            'created_by' => $actor->getKey(),
            'updated_by' => $actor->getKey(),
        ]);
    }

    /** @return array<int, WarehouseConsumable> */
    private function ensureItems(WarehouseConsumableCategory $category, User $actor): array
    {
        $items = [];

        foreach ($this->itemDefinitions() as $definition) {
            $byCode = WarehouseConsumable::query()->where('item_code', $definition['item_code'])->first();
            $byBarcode = WarehouseConsumable::query()->where('barcode', $definition['barcode'])->first();

            if ($byCode !== null && $byBarcode !== null && $byCode->getKey() !== $byBarcode->getKey()) {
                throw new RuntimeException(sprintf('Item code %s dan barcode %s menunjuk ke master berbeda.', $definition['item_code'], $definition['barcode']));
            }

            $existing = $byCode ?? $byBarcode;
            if ($existing !== null) {
                $this->assertMarker($existing->description, 'consumable '.$definition['item_code']);

                if ((string) $existing->barcode !== $definition['barcode']
                    || (int) $existing->category_id !== (int) $category->getKey()) {
                    throw new RuntimeException(sprintf('Master demo %s sudah ada tetapi barcode atau kategorinya berubah.', $definition['item_code']));
                }

                $items[] = $existing;

                continue;
            }

            $items[] = WarehouseConsumable::query()->create([
                'category_id' => $category->getKey(),
                'item_code' => $definition['item_code'],
                'barcode' => $definition['barcode'],
                'item_name' => $definition['item_name'],
                'unit' => 'pcs',
                'allow_fraction' => false,
                'current_stock' => '0.000',
                'minimum_stock' => $definition['minimum_stock'],
                'maximum_stock' => $definition['maximum_stock'],
                'storage_location' => $definition['storage_location'],
                'description' => self::MARKER.' Consumable',
                'is_active' => true,
                'created_by' => $actor->getKey(),
                'updated_by' => $actor->getKey(),
            ]);
        }

        return $items;
    }

    /** @return array<int, array<string, string>> */
    private function itemDefinitions(): array
    {
        return [
            ['item_code' => 'WH-DUMMY-001', 'barcode' => '000WH-DUMMY-001', 'item_name' => 'Demo Consumable 01', 'minimum_stock' => '5.000', 'maximum_stock' => '100.000', 'storage_location' => 'DS8'],
            ['item_code' => 'WH-DUMMY-002', 'barcode' => '000WH-DUMMY-002', 'item_name' => 'Demo Consumable 02', 'minimum_stock' => '5.000', 'maximum_stock' => '100.000', 'storage_location' => 'Deltamas'],
            ['item_code' => 'WH-DUMMY-003', 'barcode' => '000WH-DUMMY-003', 'item_name' => 'Demo Consumable 03', 'minimum_stock' => '5.000', 'maximum_stock' => '100.000', 'storage_location' => 'DS8'],
            ['item_code' => 'WH-DUMMY-004', 'barcode' => '000WH-DUMMY-004', 'item_name' => 'Demo Consumable 04', 'minimum_stock' => '5.000', 'maximum_stock' => '100.000', 'storage_location' => 'Deltamas'],
            ['item_code' => 'WH-DUMMY-005', 'barcode' => '000WH-DUMMY-005', 'item_name' => 'Demo Consumable 05', 'minimum_stock' => '5.000', 'maximum_stock' => '100.000', 'storage_location' => 'DS8'],
        ];
    }

    /** @param array<int, WarehouseConsumable> $items */
    private function seedTransactions(string $month, array $items, User $actor, User $verifier): array
    {
        $service = app(WarehouseStockService::class);
        $created = 0;
        $replayed = 0;

        foreach ([
            WarehouseTransactionType::IN->value => ['count' => self::IN_COUNT, 'quantity' => '5'],
            WarehouseTransactionType::OUT->value => ['count' => self::OUT_COUNT, 'quantity' => '2'],
        ] as $typeValue => $configuration) {
            $type = WarehouseTransactionType::from($typeValue);

            for ($index = 1; $index <= $configuration['count']; $index++) {
                $item = $items[($index - 1) % count($items)];
                $reference = sprintf('WH-DUMMY-%s-%s-%02d', $month, $type->value, $index);
                $result = $service->execute(new WarehouseStockCommand(
                    type: $type,
                    consumableId: (int) $item->getKey(),
                    quantity: $configuration['quantity'],
                    verifiedUserId: (int) $verifier->getKey(),
                    referenceNumber: $reference,
                    purpose: 'Warehouse dashboard demo',
                    notes: self::MARKER.' Transaction',
                    idempotencyKey: $this->idempotencyKey($month, $type, $index),
                    createdBy: (int) $actor->getKey(),
                    storageLocation: $type === WarehouseTransactionType::IN ? (string) $item->storage_location : null,
                ));

                if ($result->idempotentReplay) {
                    $replayed++;
                } else {
                    $created++;
                }
            }
        }

        return [
            'in' => self::IN_COUNT,
            'out' => self::OUT_COUNT,
            'created' => $created,
            'replayed' => $replayed,
        ];
    }

    private function idempotencyKey(string $month, WarehouseTransactionType $type, int $index): string
    {
        return Uuid::uuid5(
            Uuid::NAMESPACE_URL,
            sprintf('warehouse-dummy:%s:%s:%d', $month, $type->value, $index),
        )->toString();
    }

    private function assertMarker(?string $description, string $subject): void
    {
        if (! str_contains((string) $description, self::MARKER)) {
            throw new RuntimeException(sprintf('%s sudah digunakan oleh data non-demo.', $subject));
        }
    }
}

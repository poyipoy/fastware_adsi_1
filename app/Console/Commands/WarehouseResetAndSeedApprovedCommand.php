<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Warehouse\WarehouseConsumable;
use App\Services\Warehouse\WarehouseResetBackupService;
use Database\Seeders\WarehouseApprovedBarcodeDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class WarehouseResetAndSeedApprovedCommand extends Command
{
    protected $signature = 'warehouse:reset-and-seed-approved
                            {--dry-run : Jalankan preflight tanpa backup atau perubahan data}
                            {--confirm= : Token wajib RESET-WAREHOUSE-{database} untuk menjalankan reset}';

    protected $description = 'Backup, reset, dan seed ulang master Warehouse berdasarkan data barcode approved.';

    private const TARGET_DATABASE = 'dms_adasi_rev1';

    /** @var array<int, string> */
    private const TABLES = [
        'mst_wh_consumable_categories',
        'mst_wh_consumables',
        'mst_wh_user_cards',
        'trs_wh_stock_transactions',
        'log_wh_verifications',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(
        WarehouseResetBackupService $backupService,
        WarehouseApprovedBarcodeDataSeeder $approvedSeeder,
    ): int
    {
        try {
            $database = $this->guardEnvironment();
            $this->guardSchema();
            $usersBefore = $this->snapshotUsers();
            $countsBefore = $this->tableCounts();
            $this->guardReversalShape();

            if ($this->option('dry-run')) {
                $this->line('Preflight Warehouse reset lulus; tidak ada backup atau perubahan data dilakukan.');
                $this->line('Database: '.$database);
                foreach ($countsBefore as $table => $count) {
                    $this->line(sprintf('%s: %d', $table, $count));
                }

                return self::SUCCESS;
            }

            $expectedToken = 'RESET-WAREHOUSE-'.$database;
            if ((string) $this->option('confirm') !== $expectedToken) {
                throw new RuntimeException('Confirmation token salah. Gunakan --confirm='.$expectedToken);
            }

            $lock = Cache::lock('warehouse-reset:'.$database, 300);
            if (! $lock->get()) {
                throw new RuntimeException('Reset Warehouse lain sedang berjalan.');
            }

            try {
                $backup = $backupService->create(self::TABLES, $countsBefore);
                $this->info('Backup terverifikasi: '.$backup['path']);
                $this->line('SHA-256: '.$backup['sha256']);

                DB::transaction(function () use ($usersBefore, $approvedSeeder): void {
                    $this->deleteWarehouseRows();
                    $approvedSeeder->run();
                    $this->assertResetState($usersBefore);
                });
            } finally {
                $lock->release();
            }

            $this->assertResetState($usersBefore);
            $this->info('Reset dan seed master Warehouse selesai.');
            $this->line('Kategori: 0 | Barang: 4 | Mapping legacy: 0 | Transaksi: 0 | Log: 0');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function guardEnvironment(): string
    {
        $environment = (string) app()->environment();
        if (! in_array($environment, ['local', 'testing'], true)) {
            throw new RuntimeException('Reset Warehouse hanya boleh dijalankan pada APP_ENV=local atau testing.');
        }

        $database = strtolower((string) DB::connection()->getDatabaseName());
        if ($environment === 'local' && $database !== self::TARGET_DATABASE) {
            throw new RuntimeException('Database target tidak sesuai. Reset lokal hanya diizinkan pada '.self::TARGET_DATABASE.'.');
        }
        if ($environment === 'testing' && ! str_ends_with($database, '_testing')) {
            throw new RuntimeException('APP_ENV=testing membutuhkan database berakhiran _testing.');
        }
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Reset Warehouse membutuhkan driver MySQL.');
        }

        return $database;
    }

    private function guardSchema(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException('Tabel Warehouse tidak lengkap: '.$table);
            }
        }
    }

    /** @return array<string, int> */
    private function tableCounts(): array
    {
        $counts = [];
        foreach (self::TABLES as $table) {
            $counts[$table] = (int) DB::table($table)->count();
        }

        return $counts;
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

    private function guardReversalShape(): void
    {
        $nested = DB::table('trs_wh_stock_transactions as child')
            ->join('trs_wh_stock_transactions as parent', 'parent.id', '=', 'child.reversal_of_id')
            ->whereNotNull('parent.reversal_of_id')
            ->exists();

        if ($nested) {
            throw new RuntimeException('Ditemukan rantai reversal bertingkat; reset dihentikan untuk audit manual.');
        }
    }

    private function deleteWarehouseRows(): void
    {
        DB::table('log_wh_verifications')->delete();
        DB::table('trs_wh_stock_transactions')->whereNotNull('reversal_of_id')->delete();
        DB::table('trs_wh_stock_transactions')->delete();
        DB::table('mst_wh_user_cards')->delete();
        DB::table('mst_wh_consumables')->delete();
        DB::table('mst_wh_consumable_categories')->delete();
    }

    /** @param array<int, array{id: int, name: string, npk: ?string, section: ?string, role_id: ?int, is_active: int}> $usersBefore */
    private function assertResetState(array $usersBefore): void
    {
        $counts = $this->tableCounts();
        $expectedCounts = [
            'mst_wh_consumable_categories' => 0,
            'mst_wh_consumables' => 4,
            'mst_wh_user_cards' => 0,
            'trs_wh_stock_transactions' => 0,
            'log_wh_verifications' => 0,
        ];

        foreach ($expectedCounts as $table => $expected) {
            if (($counts[$table] ?? null) !== $expected) {
                throw new RuntimeException(sprintf('Validasi jumlah %s gagal: expected %d, actual %d.', $table, $expected, $counts[$table] ?? -1));
            }
        }

        $items = WarehouseConsumable::query()->orderBy('item_code')->get();
        $expectedItems = collect(WarehouseApprovedBarcodeDataSeeder::approvedItems())->keyBy('item_code');
        foreach ($items as $item) {
            $definition = $expectedItems->get((string) $item->item_code);
            if ($definition === null
                || (string) $item->barcode !== (string) $item->item_code
                || (string) $item->item_name !== $definition['item_name']
                || (string) $item->unit !== 'pcs'
                || (bool) $item->allow_fraction
                || (string) $item->current_stock !== '0.000'
                || (string) $item->minimum_stock !== '0.000'
                || $item->maximum_stock !== null
                || $item->storage_location !== null
                || $item->category_id !== null
                || $item->description !== null
                || ! (bool) $item->is_active) {
                throw new RuntimeException('Validasi master barang PDF gagal.');
            }
        }

        if ($this->snapshotUsers() !== $usersBefore) {
            throw new RuntimeException('Snapshot user global berubah; reset dibatalkan.');
        }
    }
}

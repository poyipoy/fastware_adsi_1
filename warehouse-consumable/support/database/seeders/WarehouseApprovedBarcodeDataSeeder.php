<?php

namespace Database\Seeders;

use App\Models\Warehouse\WarehouseConsumable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class WarehouseApprovedBarcodeDataSeeder extends Seeder
{
    /** @var array<int, array{item_code: string, item_name: string}> */
    private const ITEMS = [
        [
            'item_code' => 'TFHINSR-000000008',
            'item_name' => 'Insert Widia HNPJ0704ANSNGD WS40PM',
        ],
        [
            'item_code' => 'TFHINSR-000000005',
            'item_name' => 'Insert Pramet HNGX 0906ANSN-M M9315',
        ],
        [
            'item_code' => 'TFHINSR-000000066',
            'item_name' => 'Insert Moldino SEK53TN-C9 GX2140',
        ],
        [
            'item_code' => 'TFHINSR-000000004',
            'item_name' => 'Insert Sumitomo SDEN1203AESN',
        ],
    ];

    /** @return array<int, array{item_code: string, item_name: string}> */
    public static function approvedItems(): array
    {
        return self::ITEMS;
    }

    public function run(): void
    {
        $this->guardEnvironment();

        $summary = DB::transaction(function (): array {
            $createdItems = $this->ensureItems();

            return [
                'created_items' => $createdItems,
                'existing_items' => count(self::ITEMS) - $createdItems,
            ];
        });

        $this->command?->info(sprintf(
            'Data barcode barang Warehouse disiapkan: %d barang baru, %d barang existing.',
            $summary['created_items'],
            $summary['existing_items'],
        ));
    }

    private function guardEnvironment(): void
    {
        $environment = (string) app()->environment();

        if (! in_array($environment, ['local', 'testing'], true)) {
            throw new RuntimeException('Seeder barcode Warehouse hanya boleh dijalankan pada APP_ENV=local atau APP_ENV=testing.');
        }

        $database = strtolower((string) DB::connection()->getDatabaseName());
        if ($environment === 'testing' && ! str_ends_with($database, '_testing')) {
            throw new RuntimeException('Seeder barcode Warehouse pada APP_ENV=testing membutuhkan DB_DATABASE yang berakhiran _testing.');
        }

        foreach (['mst_wh_consumables'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException(sprintf('Tabel %s belum tersedia. Jalankan migration Warehouse terlebih dahulu.', $table));
            }
        }
    }

    private function ensureItems(): int
    {
        $created = 0;

        foreach (self::ITEMS as $definition) {
            $matches = WarehouseConsumable::query()
                ->where(function ($query) use ($definition): void {
                    $query->where('item_code', $definition['item_code'])
                        ->orWhere('barcode', $definition['item_code']);
                })
                ->get();

            if ($matches->count() > 1) {
                throw new RuntimeException(sprintf(
                    'Item Code %s bertabrakan dengan lebih dari satu master barang.',
                    $definition['item_code'],
                ));
            }

            $item = $matches->first();
            if ($item instanceof WarehouseConsumable) {
                $isCompatible = (string) $item->item_code === $definition['item_code']
                    && (string) $item->barcode === $definition['item_code']
                    && (string) $item->item_name === $definition['item_name']
                    && (string) $item->unit === 'pcs'
                    && ! (bool) $item->allow_fraction
                    && (bool) $item->is_active;

                if (! $isCompatible) {
                    throw new RuntimeException(sprintf(
                        'Master barang %s sudah ada tetapi identitas atau konfigurasi internalnya berbeda.',
                        $definition['item_code'],
                    ));
                }

                continue;
            }

            WarehouseConsumable::query()->create([
                'category_id' => null,
                'item_code' => $definition['item_code'],
                'barcode' => $definition['item_code'],
                'item_name' => $definition['item_name'],
                'unit' => 'pcs',
                'allow_fraction' => false,
                'current_stock' => '0.000',
                'minimum_stock' => '0.000',
                'maximum_stock' => null,
                'storage_location' => null,
                'description' => null,
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
            ]);
            $created++;
        }

        return $created;
    }
}

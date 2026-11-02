<?php

namespace App\Services\Warehouse;

use App\Exceptions\WarehouseDomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class WarehouseStockInNumberGenerator
{
    public function generate(): string
    {
        $year = (int) now(config('app.timezone', 'Asia/Jakarta'))->format('Y');

        return DB::transaction(function () use ($year): string {
            if (! Schema::hasTable('wh_stock_in_sequences')) {
                Schema::create('wh_stock_in_sequences', function ($table): void {
                    $table->unsignedSmallInteger('year')->primary();
                    $table->unsignedInteger('last_number')->default(0);
                    $table->timestamps();
                });
            }

            DB::table('wh_stock_in_sequences')->insertOrIgnore([
                'year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('wh_stock_in_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                throw new WarehouseDomainException('Sequence nomor Stock In Warehouse tidak tersedia.', 500);
            }

            $number = (int) $sequence->last_number + 1;
            if ($number > 999999) {
                throw new WarehouseDomainException('Sequence nomor Stock In Warehouse untuk tahun ini sudah penuh.', 409);
            }

            DB::table('wh_stock_in_sequences')
                ->where('year', $year)
                ->update([
                    'last_number' => $number,
                    'updated_at' => now(),
                ]);

            $padded = $number > 9999 ? (string) $number : sprintf('%04d', $number);

            return sprintf('WH-IN-%04d%s', $year, $padded);
        }, 3);
    }
}

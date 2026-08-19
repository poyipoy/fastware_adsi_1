<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasInvalidOpeningLocation = DB::table('mst_wh_consumables')
            ->where('current_stock', '>', 0)
            ->where(function ($query): void {
                $query->whereNull('storage_location')
                    ->orWhereNotIn('storage_location', ['DS8', 'Deltamas']);
            })
            ->exists();

        if ($hasInvalidOpeningLocation) {
            throw new \RuntimeException('Backfill stok per lokasi dihentikan: terdapat consumable dengan stok nonzero tanpa lokasi DS8/Deltamas.');
        }

        Schema::table('mst_wh_consumables', function (Blueprint $table): void {
            $table->decimal('stock_deltamas', 15, 3)->default(0)->after('current_stock');
            $table->decimal('stock_ds8', 15, 3)->default(0)->after('stock_deltamas');
            $table->decimal('stock_used_deltamas', 15, 3)->default(0)->after('stock_ds8');
            $table->decimal('stock_used_ds8', 15, 3)->default(0)->after('stock_used_deltamas');
            $table->string('machine_type', 120)->nullable()->after('storage_location');
            $table->string('photo_path')->nullable()->after('description');
        });

        DB::table('mst_wh_consumables')
            ->where('storage_location', 'Deltamas')
            ->update(['stock_deltamas' => DB::raw('current_stock')]);

        DB::table('mst_wh_consumables')
            ->where('storage_location', 'DS8')
            ->update(['stock_ds8' => DB::raw('current_stock')]);
    }

    public function down(): void
    {
        Schema::table('mst_wh_consumables', function (Blueprint $table): void {
            $table->dropColumn([
                'stock_deltamas',
                'stock_ds8',
                'stock_used_deltamas',
                'stock_used_ds8',
                'machine_type',
                'photo_path',
            ]);
        });
    }
};

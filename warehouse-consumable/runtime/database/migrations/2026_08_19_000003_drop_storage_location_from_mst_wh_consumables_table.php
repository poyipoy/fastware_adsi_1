<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $invalid = DB::table('mst_wh_consumables')
            ->where(function ($query): void {
                $query
                    ->whereRaw('current_stock <> (stock_deltamas + stock_ds8)')
                    ->orWhereRaw('stock_deltamas < 0')
                    ->orWhereRaw('stock_ds8 < 0')
                    ->orWhereRaw('stock_used_deltamas < 0')
                    ->orWhereRaw('stock_used_ds8 < 0')
                    ->orWhereRaw('stock_used_deltamas > stock_deltamas')
                    ->orWhereRaw('stock_used_ds8 > stock_ds8');
            })
            ->exists();

        if ($invalid) {
            throw new \RuntimeException('Penghapusan lokasi master dihentikan: integritas saldo DS8/Deltamas gagal.');
        }

        if (Schema::hasColumn('mst_wh_consumables', 'storage_location')) {
            Schema::table('mst_wh_consumables', function (Blueprint $table): void {
                $table->dropColumn('storage_location');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('mst_wh_consumables', 'storage_location')) {
            Schema::table('mst_wh_consumables', function (Blueprint $table): void {
                $table->string('storage_location', 120)->nullable()->after('maximum_stock');
            });
        }
    }
};

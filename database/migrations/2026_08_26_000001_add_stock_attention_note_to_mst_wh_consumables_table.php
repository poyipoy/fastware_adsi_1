<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mst_wh_consumables', 'stock_attention_note')) {
            Schema::table('mst_wh_consumables', function (Blueprint $table): void {
                $table->string('stock_attention_note', 255)
                    ->nullable()
                    ->after('maximum_stock');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mst_wh_consumables', 'stock_attention_note')) {
            Schema::table('mst_wh_consumables', function (Blueprint $table): void {
                $table->dropColumn('stock_attention_note');
            });
        }
    }
};

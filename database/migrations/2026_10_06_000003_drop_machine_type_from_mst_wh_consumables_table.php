<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('mst_wh_consumables', 'machine_type')) {
            Schema::table('mst_wh_consumables', function (Blueprint $table): void {
                $table->dropColumn('machine_type');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('mst_wh_consumables', 'machine_type')) {
            Schema::table('mst_wh_consumables', function (Blueprint $table): void {
                $table->string('machine_type', 120)->nullable()->after('category_id');
            });
        }
    }
};

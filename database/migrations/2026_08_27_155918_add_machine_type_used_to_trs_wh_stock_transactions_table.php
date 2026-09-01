<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trs_wh_stock_transactions', function (Blueprint $table) {
            $table->string('machine_type_used', 50)->nullable()->after('consumable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trs_wh_stock_transactions', function (Blueprint $table) {
            $table->dropColumn('machine_type_used');
        });
    }
};

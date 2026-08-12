<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_wh_user_cards', function (Blueprint $table): void {
            $table->boolean('can_verify_stock_in')->default(false)->after('is_active');
            $table->boolean('can_verify_stock_out')->default(false)->after('can_verify_stock_in');
        });
    }

    public function down(): void
    {
        Schema::table('mst_wh_user_cards', function (Blueprint $table): void {
            $table->dropColumn(['can_verify_stock_in', 'can_verify_stock_out']);
        });
    }
};

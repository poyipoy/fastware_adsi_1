<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trs_wh_stock_transactions', function (Blueprint $table): void {
            $table->uuid('operation_key')->nullable()->after('idempotency_key');
            $table->string('item_condition', 10)->default('NEW')->after('transaction_type');
            $table->string('from_location', 120)->nullable()->after('usage_location');
            $table->string('to_location', 120)->nullable()->after('from_location');
            $table->index('operation_key', 'wh_trs_operation_idx');
            $table->index(['item_condition', 'transaction_type', 'transaction_at'], 'wh_trs_condition_type_at_idx');
        });

        DB::table('trs_wh_stock_transactions')
            ->where('transaction_type', 'IN')
            ->whereNotNull('usage_location')
            ->update(['to_location' => DB::raw('usage_location')]);
    }

    public function down(): void
    {
        Schema::table('trs_wh_stock_transactions', function (Blueprint $table): void {
            $table->dropIndex('wh_trs_operation_idx');
            $table->dropIndex('wh_trs_condition_type_at_idx');
            $table->dropColumn(['operation_key', 'item_condition', 'from_location', 'to_location']);
        });
    }
};

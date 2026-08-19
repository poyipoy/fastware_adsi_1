<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trs_wh_stock_transactions', function (Blueprint $table): void {
            $table->foreignId('location_shipment_id')
                ->nullable()
                ->after('reversal_of_id')
                ->constrained('trs_wh_location_shipments')
                ->nullOnDelete();
            $table->index('location_shipment_id', 'wh_trs_location_shipment_idx');
        });

        Schema::table('trs_wh_location_shipments', function (Blueprint $table): void {
            $table->foreign('stock_transaction_id', 'wh_ship_transaction_fk')
                ->references('id')
                ->on('trs_wh_stock_transactions')
                ->nullOnDelete();
            $table->unique('stock_transaction_id', 'wh_ship_transaction_unique');
        });
    }

    public function down(): void
    {
        Schema::table('trs_wh_location_shipments', function (Blueprint $table): void {
            $table->dropUnique('wh_ship_transaction_unique');
            $table->dropForeign('wh_ship_transaction_fk');
        });

        Schema::table('trs_wh_stock_transactions', function (Blueprint $table): void {
            $table->dropForeign(['location_shipment_id']);
            $table->dropIndex('wh_trs_location_shipment_idx');
            $table->dropColumn('location_shipment_id');
        });
    }
};

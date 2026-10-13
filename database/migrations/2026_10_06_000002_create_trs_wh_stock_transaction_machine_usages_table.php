<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trs_wh_stock_transaction_machine_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')
                ->constrained('trs_wh_stock_transactions')
                ->cascadeOnDelete();
            $table->string('machine_code', 20);
            $table->decimal('quantity', 15, 3);
            $table->timestamps();

            $table->unique(['transaction_id', 'machine_code'], 'wh_trx_machine_usage_unique');
            $table->index('machine_code', 'wh_trx_machine_usage_code_idx');
            $table->index('transaction_id', 'wh_trx_machine_usage_trx_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trs_wh_stock_transaction_machine_usages');
    }
};

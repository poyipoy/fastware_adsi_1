<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trs_wh_stock_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_number', 40)->unique();
            $table->uuid('idempotency_key')->unique();
            $table->string('transaction_type', 10);
            $table->foreignId('consumable_id')->constrained('mst_wh_consumables')->restrictOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->decimal('stock_before', 15, 3);
            $table->decimal('stock_after', 15, 3);
            $table->foreignId('verified_user_id')->constrained('users')->restrictOnDelete();
            $table->string('verified_user_name', 180);
            $table->string('verified_user_npk', 80)->nullable();
            $table->string('verified_user_section', 120)->nullable();
            $table->string('reference_number', 120)->nullable();
            $table->string('purpose', 255)->nullable();
            $table->string('usage_location', 180)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('trs_wh_stock_transactions')->restrictOnDelete();
            $table->timestamp('transaction_at')->useCurrent();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['transaction_type', 'transaction_at'], 'wh_trs_type_at_idx');
            $table->index(['consumable_id', 'transaction_at'], 'wh_trs_item_at_idx');
            $table->index(['verified_user_id', 'transaction_at'], 'wh_trs_user_at_idx');
            $table->index(['verified_user_section', 'transaction_at'], 'wh_trs_section_at_idx');
            $table->unique('reversal_of_id', 'wh_trs_reversal_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trs_wh_stock_transactions');
    }
};

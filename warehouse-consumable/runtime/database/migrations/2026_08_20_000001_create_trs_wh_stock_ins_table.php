<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trs_wh_stock_ins', function (Blueprint $table): void {
            $table->id();
            $table->string('stock_in_number', 50)->unique();
            $table->uuid('creation_idempotency_key')->unique();
            $table->uuid('validation_idempotency_key')->nullable()->unique();
            $table->uuid('cancellation_idempotency_key')->nullable()->unique();
            $table->string('status', 32);
            $table->string('validation_result', 32)->nullable();

            $table->foreignId('consumable_id')->constrained('mst_wh_consumables')->restrictOnDelete();
            $table->string('item_condition', 16);
            $table->decimal('quantity_expected', 15, 3);
            $table->decimal('quantity_received', 15, 3)->nullable();
            $table->foreignId('received_consumable_id')->nullable()->constrained('mst_wh_consumables')->restrictOnDelete();
            $table->string('received_condition', 16)->nullable();
            $table->string('destination_location', 120);
            $table->string('source_location', 120)->nullable();

            $table->text('notes')->nullable();
            $table->text('validation_notes')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('creator_npk_snapshot', 100)->nullable();
            $table->string('creator_name_snapshot', 180)->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('validator_npk_snapshot', 100)->nullable();
            $table->string('validator_name_snapshot', 180)->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            // The foreign key is added after the stock ledger link migration
            // so this table can be installed without a circular reference.
            $table->unsignedBigInteger('stock_transaction_id')->nullable();
            $table->timestamps();

            $table->index('status', 'wh_stock_in_status_idx');
            $table->index(['consumable_id', 'status'], 'wh_stock_in_item_status_idx');
            $table->index(['source_location', 'status'], 'wh_stock_in_source_status_idx');
            $table->index('destination_location', 'wh_stock_in_destination_idx');
            $table->index('created_by', 'wh_stock_in_creator_idx');
            $table->index('validator_user_id', 'wh_stock_in_validator_idx');
            $table->index('stock_transaction_id', 'wh_stock_in_transaction_idx');
        });

        if (Schema::hasTable('trs_wh_stock_transactions')
            && ! Schema::hasColumn('trs_wh_stock_transactions', 'stock_in_id')) {
            Schema::table('trs_wh_stock_transactions', function (Blueprint $table): void {
                $table->foreignId('stock_in_id')
                    ->nullable()
                    ->constrained('trs_wh_stock_ins')
                    ->nullOnDelete();
                $table->index('stock_in_id', 'wh_trs_stock_in_idx');
            });
        }

        Schema::table('trs_wh_stock_ins', function (Blueprint $table): void {
            $table->foreign('stock_transaction_id', 'wh_stock_in_transaction_fk')
                ->references('id')
                ->on('trs_wh_stock_transactions')
                ->nullOnDelete();
            $table->unique('stock_transaction_id', 'wh_stock_in_transaction_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('trs_wh_stock_ins')) {
            Schema::table('trs_wh_stock_ins', function (Blueprint $table): void {
                $table->dropUnique('wh_stock_in_transaction_unique');
                $table->dropForeign('wh_stock_in_transaction_fk');
            });
        }

        if (Schema::hasTable('trs_wh_stock_transactions')
            && Schema::hasColumn('trs_wh_stock_transactions', 'stock_in_id')) {
            Schema::table('trs_wh_stock_transactions', function (Blueprint $table): void {
                $table->dropForeign(['stock_in_id']);
                $table->dropIndex('wh_trs_stock_in_idx');
                $table->dropColumn('stock_in_id');
            });
        }

        Schema::dropIfExists('trs_wh_stock_ins');
    }
};

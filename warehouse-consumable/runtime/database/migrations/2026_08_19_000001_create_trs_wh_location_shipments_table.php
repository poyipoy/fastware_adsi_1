<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trs_wh_location_shipments', function (Blueprint $table): void {
            $table->id();
            $table->string('shipment_number', 50)->unique();
            $table->foreignId('consumable_id')->constrained('mst_wh_consumables')->restrictOnDelete();
            $table->string('item_condition', 16);
            $table->decimal('quantity_sent', 15, 3);
            $table->string('from_location', 120);
            $table->string('to_location', 120);
            $table->string('status', 32);
            $table->foreignId('sent_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('sender_npk_snapshot', 100)->nullable();
            $table->string('sender_name_snapshot', 180)->nullable();
            $table->text('sender_notes')->nullable();
            $table->timestamp('sent_at');
            $table->foreignId('validation_actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('validator_npk_snapshot', 100)->nullable();
            $table->string('validator_name_snapshot', 180)->nullable();
            $table->decimal('received_quantity', 15, 3)->nullable();
            $table->string('received_condition', 16)->nullable();
            $table->text('validation_notes')->nullable();
            $table->timestamp('validated_at')->nullable();
            // Added as a foreign key after the stock-transaction link migration
            // to avoid a circular reference during fresh installation.
            $table->unsignedBigInteger('stock_transaction_id')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->uuid('creation_idempotency_key')->unique();
            $table->uuid('validation_idempotency_key')->nullable()->unique();
            $table->uuid('cancellation_idempotency_key')->nullable()->unique();
            $table->timestamps();

            $table->index('status', 'wh_ship_status_idx');
            $table->index(['consumable_id', 'status'], 'wh_ship_item_status_idx');
            $table->index(['from_location', 'status'], 'wh_ship_from_status_idx');
            $table->index(['to_location', 'status'], 'wh_ship_to_status_idx');
            $table->index(['sent_by_user_id', 'status'], 'wh_ship_sender_status_idx');
            $table->index('validator_user_id', 'wh_ship_validator_idx');
            $table->index('sent_at', 'wh_ship_sent_at_idx');
            $table->index('stock_transaction_id', 'wh_ship_transaction_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trs_wh_location_shipments');
    }
};

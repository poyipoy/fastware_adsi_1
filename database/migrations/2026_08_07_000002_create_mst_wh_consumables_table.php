<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mst_wh_consumables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('mst_wh_consumable_categories')->nullOnDelete();
            $table->string('item_code', 50)->unique();
            $table->string('barcode', 120)->collation('utf8mb4_bin')->unique();
            $table->string('item_name', 180);
            $table->string('unit', 30);
            $table->boolean('allow_fraction')->default(false);
            $table->decimal('current_stock', 15, 3)->default(0);
            $table->decimal('minimum_stock', 15, 3)->default(0);
            $table->decimal('maximum_stock', 15, 3)->nullable();
            $table->string('storage_location', 120)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['category_id', 'is_active'], 'wh_item_category_active_idx');
            $table->index(['is_active', 'current_stock'], 'wh_item_active_stock_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_wh_consumables');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_codes', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['new_product', 'update_price']);
            $table->string('product_code');
            $table->string('description');
            $table->decimal('qty', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->decimal('price_per_pcs', 15, 2);
            $table->enum('currency', ['IDR', 'CNY', 'USD']);
            $table->date('tanggal');

            $table->date('tanggal_lama')->nullable();
            $table->decimal('harga_baru', 15, 2)->nullable();
            $table->date('tanggal_harga_baru')->nullable();

            $table->enum('status', ['draft', 'submitted', 'approved', 'finished'])->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('finished_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('finished_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_codes');
    }
};

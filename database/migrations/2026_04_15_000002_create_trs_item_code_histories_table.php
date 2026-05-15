<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trs_item_code_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_code_id');
            $table->string('action', 50);
            $table->string('status_from', 50)->nullable();
            $table->string('status_to', 50)->nullable();
            $table->text('summary');
            $table->longText('change_set')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamps();

            $table->index(['item_code_id', 'created_at']);
            $table->index('action');

            $table->foreign('item_code_id')->references('id')->on('item_codes')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trs_item_code_histories');
    }
};

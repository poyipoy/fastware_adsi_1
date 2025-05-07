<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('handlings', function (Blueprint $table) {
            $table->id();
            $table->string('no_wo');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('type_id')->nullable()->constrained('type_materials');
            $table->string('thickness')->nullable();
            $table->string('weight')->nullable();
            $table->string('outer_diameter')->nullable();
            $table->string('inner_diameter')->nullable();
            $table->string('lenght')->nullable();
            $table->string('qty');
            $table->string('pcs');
            $table->string('category');
            $table->string('process_type');
            $table->string('type_1');
            $table->string('type_2')->nullable();
            $table->string('image');
            $table->date('schedule_visit')->nullable();
            $table->string('results')->nullable();
            $table->date('due_date')->nullable();
            $table->string('pic')->nullable();
            $table->string('file')->nullable();
            $table->integer('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handlings');
    }
};

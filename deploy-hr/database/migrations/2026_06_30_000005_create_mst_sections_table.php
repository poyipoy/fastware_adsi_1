<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mst_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')
                  ->constrained('mst_departments')
                  ->onDelete('cascade')
                  ->comment('FK ke mst_departments');
            $table->string('name', 150)->comment('Nama section');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['department_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_sections');
    }
};

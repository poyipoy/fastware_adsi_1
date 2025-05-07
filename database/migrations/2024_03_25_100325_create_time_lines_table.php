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
        Schema::create('time_line', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mesin_id')->nullable()->constrained('mesin');
            $table->date('tgl_actual');
            $table->date('tgl_planning');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_line');
    }
};

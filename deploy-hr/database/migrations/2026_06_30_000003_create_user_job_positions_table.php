<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pivot: mapping karyawan (user) ke posisi di mst_job_positions.
     * Menggantikan peran tc_job_positions sebagai tempat mapping user-posisi.
     */
    public function up(): void
    {
        Schema::create('user_job_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('mst_job_position_id')
                  ->constrained('mst_job_positions')
                  ->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'mst_job_position_id'], 'uq_user_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_job_positions');
    }
};

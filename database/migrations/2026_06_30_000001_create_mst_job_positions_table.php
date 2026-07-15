<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel master Job Position perusahaan (terpisah dari tc_job_positions).
     * Berisi daftar posisi jabatan unik beserta info section & department-nya.
     */
    public function up(): void
    {
        Schema::create('mst_job_positions', function (Blueprint $table) {
            $table->id();
            $table->string('position_name', 255)->unique()->comment('Nama posisi, unik');
            $table->string('department', 255)->nullable()->comment('Nama departemen (e.g. Productions)');
            $table->string('section', 255)->nullable()->comment('Nama seksi (e.g. Production Cutting)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_job_positions');
    }
};

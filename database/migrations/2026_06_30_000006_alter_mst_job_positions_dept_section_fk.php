<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ganti kolom string department & section di mst_job_positions
     * dengan kolom foreign key ke tabel master baru.
     */
    public function up(): void
    {
        Schema::table('mst_job_positions', function (Blueprint $table) {
            // Hapus kolom string lama
            $table->dropColumn(['department', 'section']);

            // Tambah kolom FK baru (nullable agar data lama aman)
            $table->foreignId('department_id')
                  ->nullable()
                  ->after('position_name')
                  ->constrained('mst_departments')
                  ->nullOnDelete()
                  ->comment('FK ke mst_departments');

            $table->foreignId('section_id')
                  ->nullable()
                  ->after('department_id')
                  ->constrained('mst_sections')
                  ->nullOnDelete()
                  ->comment('FK ke mst_sections');
        });
    }

    public function down(): void
    {
        Schema::table('mst_job_positions', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['section_id']);
            $table->dropColumn(['department_id', 'section_id']);
            $table->string('department', 255)->nullable()->after('position_name');
            $table->string('section', 255)->nullable()->after('department');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [2] BUAT JOB COMPETENCY — Warning & Deskripsi bertingkat
     * Menambahkan deskripsi level 1,2,3,4 pada masing-masing tabel master,
     * Serta menambahkan sub_kategori pada mst_tcs
     */
    public function up(): void
    {
        // 1. mst_tcs
        Schema::table('mst_tcs', function (Blueprint $table) {
            $table->string('sub_kategori', 50)->nullable()->after('keterangan_tc')
                  ->comment('Produksi, Office/Tekofis, EHS, Additional');
            $table->text('deskripsi_level_1')->nullable()->after('deskripsi_tc');
            $table->text('deskripsi_level_2')->nullable()->after('deskripsi_level_1');
            $table->text('deskripsi_level_3')->nullable()->after('deskripsi_level_2');
            $table->text('deskripsi_level_4')->nullable()->after('deskripsi_level_3');
        });

        // 2. mst_soft_skills
        Schema::table('mst_soft_skills', function (Blueprint $table) {
            $table->text('deskripsi_level_1')->nullable()->after('deskripsi_sk');
            $table->text('deskripsi_level_2')->nullable()->after('deskripsi_level_1');
            $table->text('deskripsi_level_3')->nullable()->after('deskripsi_level_2');
            $table->text('deskripsi_level_4')->nullable()->after('deskripsi_level_3');
        });

        // 3. mst_additionals
        Schema::table('mst_additionals', function (Blueprint $table) {
            $table->text('deskripsi_level_1')->nullable()->after('deskripsi_ad');
            $table->text('deskripsi_level_2')->nullable()->after('deskripsi_level_1');
            $table->text('deskripsi_level_3')->nullable()->after('deskripsi_level_2');
            $table->text('deskripsi_level_4')->nullable()->after('deskripsi_level_3');
        });
    }

    public function down(): void
    {
        Schema::table('mst_tcs', function (Blueprint $table) {
            $table->dropColumn(['sub_kategori', 'deskripsi_level_1', 'deskripsi_level_2', 'deskripsi_level_3', 'deskripsi_level_4']);
        });

        Schema::table('mst_soft_skills', function (Blueprint $table) {
            $table->dropColumn(['deskripsi_level_1', 'deskripsi_level_2', 'deskripsi_level_3', 'deskripsi_level_4']);
        });

        Schema::table('mst_additionals', function (Blueprint $table) {
            $table->dropColumn(['deskripsi_level_1', 'deskripsi_level_2', 'deskripsi_level_3', 'deskripsi_level_4']);
        });
    }
};

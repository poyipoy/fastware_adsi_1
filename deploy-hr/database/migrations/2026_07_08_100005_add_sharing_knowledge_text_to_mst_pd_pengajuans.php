<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modul 4.1 — Tambah kolom teks sharing_knowledge sebagai tindak lanjut pasca training.
     * Berbeda dari is_sharing_knowledge (boolean flag) yang sudah ada.
     */
    public function up(): void
    {
        Schema::table('mst_pd_pengajuans', function (Blueprint $table) {
            if (! Schema::hasColumn('mst_pd_pengajuans', 'sharing_knowledge')) {
                $table->text('sharing_knowledge')
                      ->nullable()
                      ->after('objective_learning')
                      ->comment('Catatan hasil sharing knowledge pasca training (diisi HRGA/karyawan)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mst_pd_pengajuans', function (Blueprint $table) {
            $table->dropColumn('sharing_knowledge');
        });
    }
};

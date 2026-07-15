<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * [2] BUAT JOB COMPETENCY - Rename tc_poin_kategoris
     */
    public function up(): void
    {
        DB::table('tc_poin_kategoris')->where('id', 1)->update(['nama_kategori' => 'Produksi']);
        DB::table('tc_poin_kategoris')->where('id', 2)->update(['nama_kategori' => 'Office/Tekofis']);
        DB::table('tc_poin_kategoris')->where('id', 3)->update(['nama_kategori' => 'EHS']);
    }

    public function down(): void
    {
        DB::table('tc_poin_kategoris')->where('id', 1)->update(['nama_kategori' => 'Skill of Process Plant']);
        DB::table('tc_poin_kategoris')->where('id', 2)->update(['nama_kategori' => 'Skill of Process Office & Quality']);
        DB::table('tc_poin_kategoris')->where('id', 3)->update(['nama_kategori' => 'Skill of EHS']);
    }
};

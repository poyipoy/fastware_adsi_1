<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan UNIQUE constraint pada tabel trs_penilaian_tcs untuk
     * mencegah duplikasi data penilaian per karyawan, job position, tahun,
     * dan butir kompetensi (TC / SK / AD).
     *
     * ATURAN BISNIS:
     * - Setiap karyawan (id_user) hanya boleh memiliki SATU nilai penilaian
     *   per butir kompetensi (id_tc / id_sk / id_ad) per job position per tahun.
     * - NULL values tidak dihitung oleh UNIQUE index, sehingga kolom yang null
     *   (misal id_sk dan id_ad ketika row adalah TC) tidak bertabrakan.
     */
    public function up(): void
    {
        // Sebelum menambahkan constraint, bersihkan dulu duplikat yang ada
        // agar migration tidak gagal karena existing duplicates.
        $this->cleanDuplicates();

        Schema::table('trs_penilaian_tcs', function (Blueprint $table) {
            $table->unique(
                ['id_user', 'id_job_position', 'tahun_penilaian', 'id_tc'],
                'uq_penilaian_tc'
            );
            $table->unique(
                ['id_user', 'id_job_position', 'tahun_penilaian', 'id_sk'],
                'uq_penilaian_sk'
            );
            $table->unique(
                ['id_user', 'id_job_position', 'tahun_penilaian', 'id_ad'],
                'uq_penilaian_ad'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trs_penilaian_tcs', function (Blueprint $table) {
            $table->dropUnique('uq_penilaian_tc');
            $table->dropUnique('uq_penilaian_sk');
            $table->dropUnique('uq_penilaian_ad');
        });
    }

    /**
     * Bersihkan duplikat di trs_penilaian_tcs sebelum menambahkan constraint.
     * Pertahankan baris dengan MIN(id) untuk setiap grup duplikat.
     */
    private function cleanDuplicates(): void
    {
        // Bersihkan duplikat TC
        $tcGroups = DB::table('trs_penilaian_tcs')
            ->whereNotNull('id_tc')
            ->select('id_user', 'id_job_position', 'tahun_penilaian', 'id_tc', DB::raw('MIN(id) as keep_id'))
            ->groupBy('id_user', 'id_job_position', 'tahun_penilaian', 'id_tc')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($tcGroups as $group) {
            DB::table('trs_penilaian_tcs')
                ->where('id_user', $group->id_user)
                ->where('id_job_position', $group->id_job_position)
                ->where('tahun_penilaian', $group->tahun_penilaian)
                ->where('id_tc', $group->id_tc)
                ->where('id', '!=', $group->keep_id)
                ->delete();
        }

        // Bersihkan duplikat SK
        $skGroups = DB::table('trs_penilaian_tcs')
            ->whereNotNull('id_sk')
            ->select('id_user', 'id_job_position', 'tahun_penilaian', 'id_sk', DB::raw('MIN(id) as keep_id'))
            ->groupBy('id_user', 'id_job_position', 'tahun_penilaian', 'id_sk')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($skGroups as $group) {
            DB::table('trs_penilaian_tcs')
                ->where('id_user', $group->id_user)
                ->where('id_job_position', $group->id_job_position)
                ->where('tahun_penilaian', $group->tahun_penilaian)
                ->where('id_sk', $group->id_sk)
                ->where('id', '!=', $group->keep_id)
                ->delete();
        }

        // Bersihkan duplikat AD
        $adGroups = DB::table('trs_penilaian_tcs')
            ->whereNotNull('id_ad')
            ->select('id_user', 'id_job_position', 'tahun_penilaian', 'id_ad', DB::raw('MIN(id) as keep_id'))
            ->groupBy('id_user', 'id_job_position', 'tahun_penilaian', 'id_ad')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($adGroups as $group) {
            DB::table('trs_penilaian_tcs')
                ->where('id_user', $group->id_user)
                ->where('id_job_position', $group->id_job_position)
                ->where('tahun_penilaian', $group->tahun_penilaian)
                ->where('id_ad', $group->id_ad)
                ->where('id', '!=', $group->keep_id)
                ->delete();
        }
    }
};

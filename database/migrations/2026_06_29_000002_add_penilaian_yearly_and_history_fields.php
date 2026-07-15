<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [3] BUAT PENILAIAN — History before-after, penilaian tahunan, koreksi post-approval
     * [4] PENGAJUAN — Status draft (status 0)
     */
    public function up(): void
    {
        // -- trs_penilaian_tcs: tahun penilaian & lock --
        Schema::table('trs_penilaian_tcs', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun_penilaian')
                ->default(now()->year)
                ->after('status')
                ->comment('Tahun periode penilaian');

            $table->boolean('is_locked')->default(false)
                ->after('tahun_penilaian')
                ->comment('True jika data tahun lama terkunci read-only');

            $table->index(['tahun_penilaian', 'is_locked']);
        });

        // -- detail_penilaian_tcs: history BEFORE value & corrected_by info --
        Schema::table('detail_penilaian_tcs', function (Blueprint $table) {
            $table->text('nilai_sebelum')->nullable()
                ->after('keterangan_sebelum')
                ->comment('JSON snapshot nilai BEFORE perubahan');

            $table->string('corrected_by_role', 30)->nullable()
                ->after('modified_at')
                ->comment('section_head|dept_head — siapa yang koreksi post-approval');
        });
    }

    public function down(): void
    {
        Schema::table('trs_penilaian_tcs', function (Blueprint $table) {
            $table->dropIndex(['tahun_penilaian', 'is_locked']);
            $table->dropColumn(['tahun_penilaian', 'is_locked']);
        });

        Schema::table('detail_penilaian_tcs', function (Blueprint $table) {
            $table->dropColumn(['nilai_sebelum', 'corrected_by_role']);
        });
    }
};

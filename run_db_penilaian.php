<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    // -- trs_penilaian_tcs: tahun penilaian & lock --
    if (!Schema::hasColumn('trs_penilaian_tcs', 'tahun_penilaian')) {
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
        echo "trs_penilaian_tcs updated.<br>";
    } else {
        echo "trs_penilaian_tcs already updated.<br>";
    }

    // -- detail_penilaian_tcs: history BEFORE value & corrected_by info --
    if (!Schema::hasColumn('detail_tc_penilaians', 'nilai_sebelum')) {
        Schema::table('detail_tc_penilaians', function (Blueprint $table) {
            $table->text('nilai_sebelum')->nullable()
                ->comment('JSON snapshot nilai BEFORE perubahan');

            $table->string('corrected_by_role', 30)->nullable()
                ->comment('section_head|dept_head — siapa yang koreksi post-approval');
        });
        echo "detail_tc_penilaians updated.<br>";
    } else {
        echo "detail_tc_penilaians already updated.<br>";
    }

    echo "Done.";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TrsPenilaianTc;
use Illuminate\Support\Facades\DB;

echo "=== STARTING DUPLICATE COMPETENCY SCORE CLEANUP ===\n";

// 1. Bersihkan duplikat untuk Technical Competency (TC)
$tcs = DB::table('trs_penilaian_tcs')
    ->whereNotNull('id_tc')
    ->select('id_user', 'id_job_position', 'tahun_penilaian', 'id_tc', DB::raw('COUNT(*) as total'), DB::raw('MIN(id) as keep_id'))
    ->groupBy('id_user', 'id_job_position', 'tahun_penilaian', 'id_tc')
    ->havingRaw('COUNT(*) > 1')
    ->get();

$deletedTc = 0;
foreach ($tcs as $group) {
    $deleted = DB::table('trs_penilaian_tcs')
        ->where('id_user', $group->id_user)
        ->where('id_job_position', $group->id_job_position)
        ->where('tahun_penilaian', $group->tahun_penilaian)
        ->where('id_tc', $group->id_tc)
        ->where('id', '!=', $group->keep_id)
        ->delete();
    $deletedTc += $deleted;
}

// 2. Bersihkan duplikat untuk Soft Skill (SK)
$sks = DB::table('trs_penilaian_tcs')
    ->whereNotNull('id_sk')
    ->select('id_user', 'id_job_position', 'tahun_penilaian', 'id_sk', DB::raw('COUNT(*) as total'), DB::raw('MIN(id) as keep_id'))
    ->groupBy('id_user', 'id_job_position', 'tahun_penilaian', 'id_sk')
    ->havingRaw('COUNT(*) > 1')
    ->get();

$deletedSk = 0;
foreach ($sks as $group) {
    $deleted = DB::table('trs_penilaian_tcs')
        ->where('id_user', $group->id_user)
        ->where('id_job_position', $group->id_job_position)
        ->where('tahun_penilaian', $group->tahun_penilaian)
        ->where('id_sk', $group->id_sk)
        ->where('id', '!=', $group->keep_id)
        ->delete();
    $deletedSk += $deleted;
}

// 3. Bersihkan duplikat untuk Additional Competency (AD)
$ads = DB::table('trs_penilaian_tcs')
    ->whereNotNull('id_ad')
    ->select('id_user', 'id_job_position', 'tahun_penilaian', 'id_ad', DB::raw('COUNT(*) as total'), DB::raw('MIN(id) as keep_id'))
    ->groupBy('id_user', 'id_job_position', 'tahun_penilaian', 'id_ad')
    ->havingRaw('COUNT(*) > 1')
    ->get();

$deletedAd = 0;
foreach ($ads as $group) {
    $deleted = DB::table('trs_penilaian_tcs')
        ->where('id_user', $group->id_user)
        ->where('id_job_position', $group->id_job_position)
        ->where('tahun_penilaian', $group->tahun_penilaian)
        ->where('id_ad', $group->id_ad)
        ->where('id', '!=', $group->keep_id)
        ->delete();
    $deletedAd += $deleted;
}

$totalDeleted = $deletedTc + $deletedSk + $deletedAd;
echo "Deleted {$deletedTc} duplicate TC rows.\n";
echo "Deleted {$deletedSk} duplicate SK rows.\n";
echo "Deleted {$deletedAd} duplicate AD rows.\n";
echo "Total duplicate rows cleaned up: {$totalDeleted}\n";
echo "=== CLEANUP COMPLETED SUCCESSFULLY ===\n";

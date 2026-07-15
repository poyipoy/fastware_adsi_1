<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jobPosition = 46;
$userId = 74; // Lina

$data = \Illuminate\Support\Facades\DB::table('trs_penilaian_tcs as tpt')
    ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
    ->leftJoin('mst_tcs as tc', 'tpt.id_tc', '=', 'tc.id')
    ->select(
        'tpt.id_job_position',
        'u.name',
        'tpt.id_user',
        'tpt.id_tc',
        'tc.keterangan_tc',
        \Illuminate\Support\Facades\DB::raw('MAX(tc.nilai) as tc_nilai'),
        \Illuminate\Support\Facades\DB::raw('SUM(tpt.nilai_tc) as total_nilai_tc')
    )
    ->where('tpt.id_job_position', $jobPosition)
    ->groupBy(
        'tpt.id_user',
        'tpt.id_job_position',
        'u.name',
        'tpt.id_tc',
        'tc.keterangan_tc'
    )
    ->get();

echo "Data for TC:\n";
print_r($data->toArray());

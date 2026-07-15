<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$competencyData = DB::table('trs_penilaian_tcs as tpt')
    ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
    ->leftJoin('mst_job_positions as mjp', 'tpt.id_job_position', '=', 'mjp.id')
    ->select(
        'tpt.id_job_position',
        'mjp.position_name as job_position_name',
        'u.name',
        'tpt.id_user'
    )
    ->where('tpt.id_job_position', 16)
    ->groupBy('tpt.id_user', 'tpt.id_job_position', 'mjp.position_name', 'u.name')
    ->get();

echo json_encode($competencyData, JSON_PRETTY_PRINT);

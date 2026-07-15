<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$data = DB::table('trs_penilaian_tcs as tpt')
    ->leftJoin('mst_job_positions as mjp', 'tpt.id_job_position', '=', 'mjp.id')
    ->select('tpt.id_job_position', 'mjp.position_name')
    ->limit(10)
    ->get();

foreach ($data as $row) {
    echo "ID: " . $row->id_job_position . " -> Name: " . ($row->position_name ?? 'NULL') . "\n";
}

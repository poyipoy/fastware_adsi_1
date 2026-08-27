<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$pd = DB::table('mst_pd_pengajuans')->count();
$pdWithMstJobPos = DB::table('mst_pd_pengajuans')
    ->join('mst_job_positions', 'mst_pd_pengajuans.id_job_position', '=', 'mst_job_positions.id')
    ->count();

$pdWithTcJobPos = DB::table('mst_pd_pengajuans')
    ->join('tc_job_positions', 'mst_pd_pengajuans.id_job_position', '=', 'tc_job_positions.id')
    ->count();

echo "Total PD: $pd\n";
echo "Matching MstJobPosition: $pdWithMstJobPos\n";
echo "Matching TcJobPosition: $pdWithTcJobPos\n";

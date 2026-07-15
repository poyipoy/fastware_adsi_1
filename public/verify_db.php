<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$results = [
    'Penilaian_TCS_Count' => DB::table('trs_penilaian_tcs')->count(),
    'People_Development_Count' => DB::table('mst_pd_pengajuans')->count(),
    'Penilaian_Sample' => DB::table('trs_penilaian_tcs')->orderBy('id', 'desc')->first(),
    'Training_Sample' => DB::table('mst_pd_pengajuans')->orderBy('id', 'desc')->first(),
];

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

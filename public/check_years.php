<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$res = DB::table('trs_penilaian_tcs')
    ->select('tahun_penilaian', DB::raw('count(*) as total'))
    ->groupBy('tahun_penilaian')
    ->get();

echo json_encode($res, JSON_PRETTY_PRINT);

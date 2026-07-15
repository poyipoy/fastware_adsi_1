<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$penilaians = DB::table('trs_penilaian_tcs as p')
    ->select('p.*', 'tc.nilai as tc_nilai', 'sk.nilai as sk_nilai', 'ad.nilai as ad_nilai')
    ->leftJoin('mst_tcs as tc', 'p.id_tc', '=', 'tc.id')
    ->leftJoin('mst_soft_skills as sk', 'p.id_sk', '=', 'sk.id')
    ->leftJoin('mst_additionals as ad', 'p.id_ad', '=', 'ad.id')
    ->where('p.status', 4)
    ->get();

print_r($penilaians->toArray());

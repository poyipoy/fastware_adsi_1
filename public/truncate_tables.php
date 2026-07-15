<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

DB::statement('SET FOREIGN_KEY_CHECKS=0;');

DB::table('mst_pd_pengajuans')->truncate();
DB::table('detail_penilaian_tcs')->truncate();
DB::table('trs_penilaian_tcs')->truncate();

DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "Tables mst_pd_pengajuans, detail_penilaian_tcs, and trs_penilaian_tcs truncated successfully.\n";

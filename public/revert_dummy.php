<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

DB::statement('SET FOREIGN_KEY_CHECKS=0;');
DB::table('mst_pd_pengajuans')->truncate();
DB::table('trs_penilaian_tcs')->truncate();
DB::table('detail_penilaian_tcs')->truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "Reverted dummy data.";

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "mst_tcs total: " . DB::table('mst_tcs')->count() . "\n";
echo "mst_soft_skills total: " . DB::table('mst_soft_skills')->count() . "\n";
echo "mst_additionals total: " . DB::table('mst_additionals')->count() . "\n";

echo "\nSample TC:\n";
print_r(DB::table('mst_tcs')->limit(5)->pluck('keterangan_tc')->toArray());

echo "\nSample SK:\n";
print_r(DB::table('mst_soft_skills')->limit(5)->pluck('keterangan_sk')->toArray());

echo "\nSample AD:\n";
print_r(DB::table('mst_additionals')->limit(5)->pluck('keterangan_ad')->toArray());

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tc = DB::table('mst_tcs')->limit(3)->get();
$sk = DB::table('mst_soft_skills')->limit(3)->get();
$ad = DB::table('mst_additionals')->limit(3)->get();

echo "TC:\n";
print_r($tc);
echo "\nSK:\n";
print_r($sk);
echo "\nAD:\n";
print_r($ad);

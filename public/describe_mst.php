<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tc_columns = DB::select('DESCRIBE mst_tcs');
print_r($tc_columns);

$sk_columns = DB::select('DESCRIBE mst_soft_skills');
print_r($sk_columns);

$ad_columns = DB::select('DESCRIBE mst_additionals');
print_r($ad_columns);

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $res = DB::table('tc_people_developments')
        ->select('tahun_aktual', DB::raw('count(*) as total'), DB::raw('SUM(biaya) as total_biaya'))
        ->groupBy('tahun_aktual')
        ->get();
    echo "People Development:\n" . json_encode($res, JSON_PRETTY_PRINT) . "\n\n";
} catch (\Throwable $e) {
    echo "Error 1: " . $e->getMessage() . "\n";
}

try {
    $sample = DB::table('tc_people_developments')
        ->select('id', 'tahun_aktual', 'biaya', 'status_1')
        ->limit(5)
        ->get();
    echo "Sample:\n" . json_encode($sample, JSON_PRETTY_PRINT) . "\n";
} catch (\Throwable $e) {
    echo "Error 2: " . $e->getMessage() . "\n";
}

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    $tables = [
        'mst_tcs',
        'mst_soft_skills',
        'mst_additionals'
    ];

    foreach ($tables as $table) {
        try {
            DB::table($table)->truncate();
            echo "Truncated $table<br>\n";
        } catch (\Exception $e) {
            echo "Skipped $table: " . $e->getMessage() . "<br>\n";
        }
    }

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    echo "Master tables cleanup completed successfully!<br>\n";
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "<br>\n";
}

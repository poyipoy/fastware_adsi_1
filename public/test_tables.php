<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
echo "All Tables:\n";
foreach ($tables as $table) {
    $tableName = (array)$table;
    echo "- " . array_values($tableName)[0] . "\n";
}

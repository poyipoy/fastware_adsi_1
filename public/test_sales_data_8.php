<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$departments = \App\Models\MstDepartment::where('is_active', true)->get();
echo "Total Active Departments from DB: " . $departments->count() . "\n";
foreach($departments as $i => $dept) {
    if (strpos(strtolower($dept->name), 'sales') !== false) {
        echo "Index " . $i . ": " . $dept->name . "\n";
    }
}

<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$salesDepts = \App\Models\MstDepartment::where('name', 'like', '%Sales%')->get();
$salesJobPositions = \App\Models\MstJobPosition::whereIn('department_id', $salesDepts->pluck('id'))->get();
echo "Sales Job Positions (is_active):\n";
foreach($salesJobPositions as $job) {
    echo $job->id . " - " . $job->position_name . " (is_active: " . ($job->is_active ? 'true' : 'false') . ")\n";
}

<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$uncategorized = \App\Models\MstJobPosition::query()
    ->leftJoin('mst_departments', 'mst_job_positions.department_id', '=', 'mst_departments.id')
    ->select('mst_job_positions.id', 'mst_job_positions.position_name', 'mst_job_positions.department_id', 'mst_departments.name')
    ->whereNull('mst_departments.name')
    ->get();

echo "Uncategorized Job Positions:\n";
foreach ($uncategorized as $job) {
    echo "- ID: {$job->id}, Name: {$job->position_name}, Dept ID: {$job->department_id}\n";
}

<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$app->instance('request', $request);
$kernel->bootstrap();

$jobs = \App\Models\MstJobPosition::query()
    ->leftJoin('mst_departments', 'mst_job_positions.department_id', '=', 'mst_departments.id')
    ->select('mst_job_positions.position_name', 'mst_departments.name as department_name', 'mst_job_positions.is_active')
    ->get();

foreach ($jobs as $job) {
    echo "Job: {$job->position_name} | Dept: {$job->department_name} | Active: {$job->is_active}\n";
}

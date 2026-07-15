<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$app->instance('request', $request);
$kernel->bootstrap();

try {
    $jobs = \App\Models\MstJobPosition::query()
        ->leftJoin('mst_departments', 'mst_job_positions.department_id', '=', 'mst_departments.id')
        ->whereRaw('LOWER(mst_job_positions.position_name) NOT LIKE ?', ['%head%'])
        ->where('mst_departments.name', 'Production')
        ->select('mst_job_positions.position_name', 'mst_job_positions.is_active')
        ->groupBy('mst_job_positions.position_name', 'mst_job_positions.is_active')
        ->get();
        
    foreach ($jobs as $j) {
        echo $j->position_name . ": is_active = " . $j->is_active . "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

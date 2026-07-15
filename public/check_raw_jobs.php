<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$app->instance('request', $request);
$kernel->bootstrap();

try {
    $rawJobPositions = \App\Models\MstJobPosition::query()
        ->leftJoin('mst_departments', 'mst_job_positions.department_id', '=', 'mst_departments.id')
        ->whereRaw('LOWER(mst_job_positions.position_name) NOT LIKE ?', ['%head%'])
        ->where('mst_departments.name', 'Production')
        ->select(
            \Illuminate\Support\Facades\DB::raw('MIN(mst_job_positions.id) as id'),
            'mst_job_positions.position_name as job_position',
            \Illuminate\Support\Facades\DB::raw('MAX(mst_departments.name) as department')
        )
        ->groupBy('mst_job_positions.position_name')
        ->orderBy('mst_job_positions.position_name')
        ->get();
        
    echo "Raw Jobs in Production: " . $rawJobPositions->count() . "\n";
    foreach ($rawJobPositions as $j) {
        echo "- " . $j->job_position . "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

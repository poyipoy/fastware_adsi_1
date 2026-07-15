<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$app->instance('request', $request);
$kernel->bootstrap();

try {
    $dept = App\Models\MstDepartment::where('name', 'Production')->first();
    if (!$dept) {
        echo "Production department not found.\n";
    } else {
        $jobs = App\Models\MstJobPosition::where('department_id', $dept->id)->get();
        echo "Total Jobs in Production: " . $jobs->count() . "\n";
        foreach ($jobs as $job) {
            echo "- " . $job->position_name . "\n";
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

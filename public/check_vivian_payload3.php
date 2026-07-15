<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$app->instance('request', $request);
$kernel->bootstrap();

try {
    $allowed = (new \App\Services\Dashboard\TcpdDashboardService())->getCompetencyPayload(null, null, null);
    
    // Find Production dept
    $prod = null;
    foreach ($allowed['department_summaries'] as $dept) {
        if (($dept['label'] ?? '') === 'Production') {
            $prod = $dept;
            break;
        }
    }
    
    if ($prod) {
        echo "Keys: " . implode(", ", array_keys($prod)) . "\n";
        echo "Entries in Production:\n";
        $entries = $prod['values'] ?? []; // wait, is it in $dept['entries']? Let's check keys
        // Oh right, department_summaries has 'label', 'percentage', 'has_data', 'values'
        // Wait, NO! The frontend uses `dept.entries` but it is mapped from `department_summaries`
        // Let's check how the frontend gets `dept.entries`!
    } else {
        echo "Production not found\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

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
        if ($dept['department'] === 'Production') { // wait, is it 'department' or 'label'? Let me dump keys.
            $prod = $dept;
            break;
        }
    }
    
    if ($prod) {
        echo "Keys: " . implode(", ", array_keys($prod)) . "\n";
        echo "Entries in Production:\n";
        foreach ($prod['entries'] as $entry) {
            echo "- " . $entry['label'] . " (Has Data: " . ($entry['has_data'] ? 'Yes' : 'No') . ")\n";
        }
    } else {
        echo "Production not found\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

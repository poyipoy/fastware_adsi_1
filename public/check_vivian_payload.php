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
        if ($dept['label'] === 'Production') {
            $prod = $dept;
            break;
        }
    }
    
    if ($prod) {
        echo "Production Jobs in Payload: " . count($prod['values']) . "\n";
        $entries = $prod['values'] ?? []; // wait, is it in $dept['entries']? Let's check keys
        echo "Keys: " . implode(", ", array_keys($prod)) . "\n";
        // Ah wait, department_summaries structure is:
        // label, percentage, has_data, values (years), is_company
        // Wait, where are the job entries?
        // Let me dump it.
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

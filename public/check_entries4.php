<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$app->instance('request', $request);
$kernel->bootstrap();

try {
    $request = new \Illuminate\Http\Request();
    $request->setUserResolver(function() {
        return \App\Models\User::first();
    });
    
    $allowed = (new \App\Services\Dashboard\TcpdDashboardService())->getCompetencyPayload($request);
    
    foreach ($allowed['department_summaries'] as $dept) {
        if (($dept['department'] ?? '') === 'Production' || ($dept['label'] ?? '') === 'Production') {
            echo "Found Production!\n";
            echo "Total Entries: " . count($dept['entries']) . "\n";
            foreach ($dept['entries'] as $entry) {
                echo "- " . $entry['label'] . "\n";
            }
            break;
        }
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

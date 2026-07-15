<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$app->instance('request', $request);
$kernel->bootstrap();

try {
    $allowed = (new \App\Services\Dashboard\TcpdDashboardService())->getCompetencyPayload(null, null, null);
    
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

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

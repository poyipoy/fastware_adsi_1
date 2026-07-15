<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $request = \Illuminate\Http\Request::create('/api/tcpd/company', 'GET');
    // Simulate what the controller does
    $service = app(\App\Services\Dashboard\TcpdDashboardService::class);
    // Passing null for user to see if it makes a difference, or get user ID 1
    $user = \App\Models\User::find(1); 
    
    $payload = $service->getCompanyPayload($request, $user);
    
    foreach($payload['department_summaries'] as $dept) {
        if (strpos(strtolower($dept['department']), 'sales') !== false) {
            echo "Department: " . $dept['department'] . "\n";
            $hasData = false;
            foreach ($dept['entries'] as $entry) {
                if ($entry['has_data']) {
                    $hasData = true;
                }
            }
            echo "  -> JS hasData condition: " . ($hasData ? 'TRUE' : 'FALSE') . "\n";
        }
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}

<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = \App\Models\User::first();
    $request = new \Illuminate\Http\Request();
    
    $service = app(\App\Services\Dashboard\TcpdDashboardService::class);
    $payload = $service->getCompanyPayload($request, $user);
    
    echo "Department count: " . $payload['company_department_count'] . "\n";
    foreach($payload['department_summaries'] as $i => $dept) {
        echo "Index " . $i . ": " . $dept['department'] . "\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}

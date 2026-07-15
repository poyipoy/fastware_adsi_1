<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(\App\Services\Dashboard\TcpdDashboardService::class);
$request = \Illuminate\Http\Request::create('/api/tcpd/company', 'GET');

// test with user 1
$user1 = \App\Models\User::find(1);
$payload1 = $service->getCompanyPayload($request, $user1);
echo "User 1 Payload has data for Sales Region 1 & 2: \n";
foreach($payload1['department_summaries'] as $dept) {
    if ($dept['department'] === 'Sales Region 1 & 2') {
        foreach($dept['entries'] as $entry) {
            echo " - " . $entry['label'] . ": " . ($entry['has_data'] ? 'YES' : 'NO') . "\n";
        }
    }
}

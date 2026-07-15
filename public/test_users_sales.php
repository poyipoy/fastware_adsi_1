<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::all();
$request = \Illuminate\Http\Request::create('/api/tcpd/company', 'GET');
$service = app(\App\Services\Dashboard\TcpdDashboardService::class);

echo "Checking all users...\n";
foreach ($users as $user) {
    try {
        $payload = $service->getCompanyPayload($request, $user);
        
        $hasSalesData = null;
        foreach($payload['department_summaries'] as $dept) {
            if ($dept['department'] === 'Sales Region 1 & 2') {
                $hasData = false;
                foreach ($dept['entries'] as $entry) {
                    if ($entry['has_data']) {
                        $hasData = true;
                    }
                }
                $hasSalesData = $hasData;
            }
        }
        
        if ($hasSalesData === false) {
            echo "User ID: " . $user->id . " (" . $user->name . ") -> hasData for Sales Region 1 & 2 is FALSE\n";
        }
    } catch (\Throwable $e) {
        // ignore
    }
}
echo "Done.\n";

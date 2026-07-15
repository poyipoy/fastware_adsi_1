<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user1 = \App\Models\User::find(1);
\Illuminate\Support\Facades\Auth::login($user1);
$service = app(\App\Services\Dashboard\CompetencyDashboardService::class);
$data = $service->getDashboardData();
echo "Job positions for User 1:\n";
foreach ($data['jobPositions'] as $key => $val) {
    echo "Key: $key => Value: $val\n";
}

<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \App\Models\TrsPenilaianTc::whereIn('status', [3, 4])->count();
echo "Total TrsPenilaianTc with status 3 or 4: " . $count . "\n";

// Test for user ID 1 (admin)
$user1 = \App\Models\User::find(1);
\Illuminate\Support\Facades\Auth::login($user1);
$service = app(\App\Services\Dashboard\CompetencyDashboardService::class);
$data = $service->getDashboardData();
echo "Job positions for User 1:\n";
print_r($data['jobPositions']->toArray());

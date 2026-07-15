<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::with('userJobPositions.jobPosition')->where('name', 'LIKE', '%LINA%')->first();
echo json_encode([
    'name' => $user->name,
    'is_active' => $user->is_active,
    'status' => $user->status ?? 'no_status_column',
    'jobs' => $user->userJobPositions->map(function($jp) {
        return $jp->jobPosition ? $jp->jobPosition->position_name : null;
    })
], JSON_PRETTY_PRINT);

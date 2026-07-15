<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::with('jobPositions.jobPosition')->where('status', 1)->limit(10)->get();
foreach ($users as $user) {
    echo $user->name . " | Roles: ";
    $jobs = [];
    foreach ($user->jobPositions as $ujp) {
        if ($ujp->jobPosition) {
            $jobs[] = $ujp->jobPosition->position_name;
        }
    }
    echo implode(', ', $jobs) . "\n";
}

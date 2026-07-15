<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::with('userJobPositions.jobPosition')->get();
$targetUsers = [];
$headUsers = [];

foreach ($users as $user) {
    $isHead = false;
    $hasPosition = false;
    foreach ($user->userJobPositions as $ujp) {
        if ($ujp->jobPosition) {
            $hasPosition = true;
            $posName = strtolower($ujp->jobPosition->position_name);
            if (str_contains($posName, 'section head') || 
                str_contains($posName, 'department head') || 
                str_contains($posName, 'division head')) {
                $isHead = true;
                $headUsers[] = $user;
                break;
            }
        }
    }
    
    if (!$isHead && $hasPosition) {
        $targetUsers[] = $user;
    }
}
echo "Total users: " . count($users) . "\n";
echo "Target non-head: " . count($targetUsers) . "\n";
echo "Target head: " . count($headUsers) . "\n";
foreach ($targetUsers as $user) {
    echo $user->name . " | " . $user->userJobPositions[0]->jobPosition->position_name . "\n";
}

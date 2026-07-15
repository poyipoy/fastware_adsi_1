<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::with('userJobPositions.jobPosition')->where('is_active', 1)->get();
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
                break;
            }
        }
    }
    if (!$isHead && $hasPosition) {
        $primaryJob = null;
        foreach ($user->userJobPositions as $ujp) {
            if ($ujp->jobPosition) {
                $primaryJob = $ujp->jobPosition;
                break;
            }
        }
        $tcCount = \App\Models\MstTc::where('id_job_position', $primaryJob->id)->count();
        $skCount = \App\Models\MstSoftSkill::where('id_job_position', $primaryJob->id)->count();
        $adCount = \App\Models\MstAdditionals::where('id_job_position', $primaryJob->id)->count();
        
        echo "User: " . $user->name . " | Job: " . $primaryJob->position_name . " | TC: $tcCount, SK: $skCount, AD: $adCount\n";
    }
}

<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$user = App\Models\User::with('roles', 'jobPositions')->where('name', 'like', '%ragil%')->first();
if ($user) {
    echo "USER INFO:\n";
    echo "Name: " . $user->name . "\n";
    echo "Role ID: " . $user->role_id . "\n";
    echo "Role Name: " . ($user->roles->role ?? 'N/A') . "\n";
    
    echo "\nJOB POSITIONS (tc_job_positions):\n";
    foreach ($user->jobPositions as $jp) {
        echo "- " . $jp->job_position . " (Dept: " . $jp->department . ", Section Head: " . $jp->section_head_name . ", Dept Head: " . $jp->department_head_name . ")\n";
    }

    echo "\nACCESSIBLE JOB POSITIONS (JobPositionAccessService):\n";
    $jpService = app(\App\Services\HR\JobPositionAccessService::class);
    $accessibleJobs = $jpService->getAccessibleJobPositionNames($user);
    foreach ($accessibleJobs as $job) {
         echo "- " . $job . "\n";
    }
    
    echo "\nIS KASIE: " . (app(\App\Services\HR\HRRoleAccessService::class)->isKaSie($user) ? 'YES' : 'NO') . "\n";
    echo "IS KADEPT: " . (app(\App\Services\HR\HRRoleAccessService::class)->isKaDept($user) ? 'YES' : 'NO') . "\n";
} else {
    echo "User Ragil not found.";
}

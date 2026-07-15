<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$app->instance('request', $request);
$kernel->bootstrap();

try {
    $penilaian = App\Models\TrsPenilaianTc::first();
    echo "Job Position: " . $penilaian->jobPosition->position_name . "\n";
    
    $deptHeadPosition = $penilaian->jobPosition->getApproverPosition(2);
    if ($deptHeadPosition) {
        echo "Dept Head Position: " . $deptHeadPosition->position_name . "\n";
        $user = $deptHeadPosition->activeUsers()->first();
        echo "Dept Head User: " . ($user ? $user->name : 'No active user') . "\n";
    } else {
        echo "No Level 2 Approver found.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

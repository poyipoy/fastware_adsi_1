<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$app->instance('request', $request);
$kernel->bootstrap();

try {
    $jp = App\Models\MstJobPosition::first();
    echo "Sample Job Position: " . $jp->position_name . "\n";
    
    $approvers = App\Models\MstPositionApproval::where('position_id', $jp->id)->with('approverPosition')->get();
    foreach ($approvers as $appr) {
        echo "Level " . $appr->approval_level . ": " . ($appr->approverPosition ? $appr->approverPosition->position_name : 'None') . "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

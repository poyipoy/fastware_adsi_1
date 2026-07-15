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
    echo "Department: " . $jp->department->name . "\n";
    $deptHeads = App\Models\MstJobPosition::where('department_id', $jp->department_id)->where('position_name', 'like', '%head%')->get();
    foreach ($deptHeads as $dh) {
        echo "Dept Head: " . $dh->position_name . "\n";
    }

    $deptHeadName = \Illuminate\Support\Facades\DB::table('mst_job_positions as jp')
        ->join('user_job_positions as ujp', 'jp.id', '=', 'ujp.mst_job_position_id')
        ->join('users as u', 'ujp.user_id', '=', 'u.id')
        ->where('jp.department_id', $jp->department_id)
        ->where('jp.position_name', 'like', '%Dept%Head%')
        ->where('ujp.is_active', true)
        ->select('u.name')
        ->first();

    echo "User Name of Dept Head: " . ($deptHeadName ? $deptHeadName->name : 'Not Found') . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

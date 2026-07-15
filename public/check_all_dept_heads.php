<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$app->instance('request', $request);
$kernel->bootstrap();

try {
    $depts = App\Models\MstDepartment::all();
    foreach ($depts as $dept) {
        $dh = App\Models\MstJobPosition::where('department_id', $dept->id)->where('position_name', 'like', '%Dept%Head%')->first();
        if ($dh) {
            $user = $dh->activeUsers()->first();
            echo "Dept: " . $dept->name . " -> Dept Head: " . ($user ? $user->name : 'No active user') . "\n";
        } else {
            echo "Dept: " . $dept->name . " -> NO DEPT HEAD FOUND\n";
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

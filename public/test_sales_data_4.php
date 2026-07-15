<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$salesDepts = \App\Models\MstDepartment::where('name', 'like', '%Sales%')->get();
$salesJobPositions = \App\Models\MstJobPosition::whereIn('department_id', $salesDepts->pluck('id'))->pluck('id')->toArray();

$tc = \App\Models\MstTc::whereIn('id_job_position', $salesJobPositions)->get();
echo "Technical competencies:\n";
foreach($tc as $t) {
    echo "Job Position ID: " . $t->id_job_position . " | Name: " . $t->keterangan_tc . " | Standard: " . $t->nilai . "\n";
}

$user74 = \Illuminate\Support\Facades\DB::table('user_job_positions')->where('user_id', 74)->first();
echo "\nUser 74 Job Position ID: " . ($user74 ? $user74->mst_job_position_id : 'None') . "\n";

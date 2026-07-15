<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$salesDepts = \App\Models\MstDepartment::where('name', 'like', '%Sales%')->get();
$salesJobPositions = \App\Models\MstJobPosition::whereIn('department_id', $salesDepts->pluck('id'))->pluck('id')->toArray();

$tcCount = \App\Models\MstTc::whereIn('id_job_position', $salesJobPositions)->count();
$skCount = \App\Models\MstSoftSkill::whereIn('id_job_position', $salesJobPositions)->count();
$adCount = \App\Models\MstAdditionals::whereIn('id_job_position', $salesJobPositions)->count();

echo "Competency counts for Sales Job Positions:\n";
echo "Technical: " . $tcCount . "\n";
echo "Soft Skill: " . $skCount . "\n";
echo "Additional: " . $adCount . "\n";


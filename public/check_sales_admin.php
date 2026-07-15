<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$job = \App\Models\MstJobPosition::where('position_name', 'Sales Admin Region 1')->first();
if ($job) {
    $tcCount = \App\Models\MstTc::where('id_job_position', $job->id)->count();
    $skCount = \App\Models\MstSoftSkill::where('id_job_position', $job->id)->count();
    $adCount = \App\Models\MstAdditionals::where('id_job_position', $job->id)->count();
    echo "Sales Admin Region 1: TC=$tcCount, SK=$skCount, AD=$adCount\n";
} else {
    echo "Job not found\n";
}

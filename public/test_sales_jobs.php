<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jobs = \App\Models\MstJobPosition::where('position_name', 'like', '%Sales%')->get();
foreach ($jobs as $job) {
    echo "ID: " . $job->id . ", Name: " . $job->position_name . "\n";
}

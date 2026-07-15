<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \App\Models\TrsPenilaianTc::whereIn('status', [3, 4])->count();
echo "Total TrsPenilaianTc with status 3 or 4: " . $count . "\n";

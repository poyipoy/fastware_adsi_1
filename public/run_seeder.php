<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

try {
    Artisan::call('cache:clear');
    $exitCode = Artisan::call('db:seed', [
        '--class' => 'DummyCompetencyDataSeeder'
    ]);
    echo Artisan::output();
    echo "\nExit Code: " . $exitCode;
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = \Illuminate\Http\Request::create('/get-competency-data', 'GET', ['job_position' => 22]);
$controller = app(\App\Http\Controllers\PenilaianTCController::class);
$response = $controller->getCompetencyData($request);

echo "Response Content:\n";
echo $response->getContent() . "\n";

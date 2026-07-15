<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/get-detail-filter', 'GET', ['id_user' => 4]);
$controller = new App\Http\Controllers\PenilaianTCController(
    app(App\Services\HR\JobPositionAccessService::class),
    app(App\Services\HR\HRRoleAccessService::class)
);
$response = $controller->getDetailCompetency($request);
echo $response->getContent();

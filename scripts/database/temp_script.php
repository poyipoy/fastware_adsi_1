<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
Auth::loginUsingId(1);
$request = Request::create('/createinquiry', 'GET', [
    'format' => 'json',
    'draw' => 1,
    'start' => 0,
    'length' => 10,
]);
$response = $app->handle($request);
Auth::logout();
echo $response->getStatusCode(), PHP_EOL, $response->getContent();

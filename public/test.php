<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$user = \App\Models\User::first();
if ($user) {
    auth()->login($user);
}
$request = Illuminate\Http\Request::create('/outstanding-materials/data', 'GET', [
    'draw' => 1,
    'start' => 0,
    'length' => 100,
]);
$response = $kernel->handle($request);
$content = $response->getContent();
$json = json_decode($content, true);
if (isset($json['data'])) {
    echo "Count of data: " . count($json['data']) . "\n";
    if (count($json['data']) < 5) {
        print_r($json['data']);
    }
} else {
    echo "No data array or error:\n";
    echo substr($content, 0, 1000);
}

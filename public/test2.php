<?php
$request = Illuminate\Http\Request::create('/outstanding-materials/data', 'GET', [
    'draw' => 1,
    'start' => 0,
    'length' => 100,
]);
$controller = new \App\Http\Controllers\OutstandingMaterialController();
$response = $controller->data($request);
$data = json_decode($response->getContent(), true);
echo "Count: " . count($data['data']) . "\n";

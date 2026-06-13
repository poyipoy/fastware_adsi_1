<?php
$request = Illuminate\Http\Request::create('/outstanding-materials/data', 'GET', [
    'draw' => 1,
    'start' => 0,
    'length' => 10,
]);
$controller = new \App\Http\Controllers\OutstandingMaterialController();
$response = $controller->data($request);
$data = json_decode($response->getContent(), true);
echo "Records total: " . $data['recordsTotal'] . "\n";
echo "Records filtered: " . $data['recordsFiltered'] . "\n";
echo "Count data: " . count($data['data']) . "\n";

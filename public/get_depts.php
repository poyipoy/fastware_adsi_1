<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MstDepartment;

$depts = MstDepartment::all();
header('Content-Type: application/json');
echo json_encode($depts, JSON_PRETTY_PRINT);

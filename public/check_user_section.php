<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::select('name', 'section')->get();
header('Content-Type: application/json');
echo json_encode(['count' => $users->count(), 'data' => $users->take(50)]);

<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$updated = App\Models\TcJobPosition::where('department', 'Productions')
    ->update(['department' => 'Production']);

echo json_encode([
    'status' => 'success',
    'rows_updated' => $updated,
    'remaining_productions' => App\Models\TcJobPosition::where('department', 'Productions')->count(),
    'total_production' => App\Models\TcJobPosition::where('department', 'Production')->count()
]);

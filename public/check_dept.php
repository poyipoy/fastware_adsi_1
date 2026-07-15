<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$production1 = App\Models\TcJobPosition::where('department', 'Production')->pluck('job_position')->toArray();
$production2 = App\Models\TcJobPosition::where('department', 'Productions')->pluck('job_position')->toArray();

echo "Production (tanpa s): \n";
print_r(array_unique($production1));
echo "\nProductions (pakai s): \n";
print_r(array_unique($production2));

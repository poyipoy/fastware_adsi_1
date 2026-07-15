<?php
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;
use App\Models\TcJobPosition;

$jp = TcJobPosition::where('job_position', 'MC CUSTOM TESTING JOB')->first();
$u = User::where('id', $jp->id_user)->first();

echo "JP Section: " . ($jp->section ?? 'NULL') . "\n";
echo "User Section: " . ($u->section ?? 'NULL') . "\n";

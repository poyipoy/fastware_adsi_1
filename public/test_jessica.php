<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(70);
echo "User 70: " . $user->name . "\n";
echo "Role ID: " . $user->role_id . "\n";
$jp = \App\Models\MstJobPosition::find($user->job_position_id);
echo "Job Position: " . ($jp ? $jp->position_name : 'None') . "\n";

$access = \App\Services\Dashboard\TcpdUserAccess::resolve($user->name);
echo "Access class: " . ($access ? get_class($access) : 'None') . "\n";
if ($access) {
    echo "Allowed Jobs: " . implode(', ', $access->jobPositions()) . "\n";
}

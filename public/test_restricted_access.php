<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::whereIn('id', [70, 91])->get();
foreach ($users as $user) {
    echo "User: " . $user->name . " (ID " . $user->id . ")\n";
    $access = \App\Services\Dashboard\TcpdUserAccess::resolve($user->name);
    if ($access) {
        echo "Allowed Jobs: " . implode(', ', $access->jobPositions()) . "\n";
    } else {
        echo "No access defined via TcpdUserAccess.\n";
    }
}

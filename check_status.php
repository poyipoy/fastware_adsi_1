<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$data = \App\Models\TcPeopleDevelopment::where('tahun_aktual', 2027)->get(['id', 'status_1', 'tahun_aktual']);
foreach($data as $d) {
    echo "ID: {$d->id}, status_1: " . var_export($d->status_1, true) . "\n";
}

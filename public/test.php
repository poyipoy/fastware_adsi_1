<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo json_encode(\Illuminate\Support\Facades\DB::select('DESCRIBE trs_penilaian_tcs'), JSON_PRETTY_PRINT);

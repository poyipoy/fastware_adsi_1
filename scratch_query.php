<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = DB::table('trs_penilaian_tcs')->orderBy('id', 'desc')->limit(10)->get();
print_r($rows->toArray());

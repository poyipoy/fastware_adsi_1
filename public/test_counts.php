<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$counts = [
    'mst_pd_pengajuans' => \App\Models\TcPeopleDevelopment::count(),
    'trs_penilaian_tcs' => \App\Models\TrsPenilaianTc::count(),
    'detail_penilaian_tcs' => \App\Models\DetailTcPenilaian::count(),
];

echo json_encode($counts, JSON_PRETTY_PRINT);

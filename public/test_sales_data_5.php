<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$transactions = \App\Models\TrsPenilaianTc::where('id_user', 74)->get();
foreach($transactions as $t) {
    echo "TrsPenilaianTc ID: " . $t->id . "\n";
    echo "  id_tc: " . $t->id_tc . " | nilai_tc: " . var_export($t->nilai_tc, true) . "\n";
    echo "  id_sk: " . $t->id_sk . " | nilai_sk: " . var_export($t->nilai_sk, true) . "\n";
    echo "  id_ad: " . $t->id_ad . " | nilai_ad: " . var_export($t->nilai_ad, true) . "\n";
    echo "  status: " . $t->status . "\n";
}

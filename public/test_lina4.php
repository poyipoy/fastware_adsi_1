<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('name', 'like', '%LINA UNIARSIH%')->first();
if ($user) {
    echo "LINA UNIARSIH found. ID: " . $user->id . "\n";
    $penilaian = \App\Models\TrsPenilaianTc::where('id_user', $user->id)->get();
    echo "Total TrsPenilaianTc: " . $penilaian->count() . "\n";
    foreach ($penilaian as $p) {
        echo " - ID: " . $p->id . ", TC: " . $p->id_tc . " (val: " . $p->nilai_tc . "), SK: " . $p->id_sk . " (val: " . $p->nilai_sk . "), AD: " . $p->id_ad . " (val: " . $p->nilai_ad . "), Status: " . $p->status . "\n";
    }

    $tcs = \Illuminate\Support\Facades\DB::table('mst_pd_pengajuans')->where('id_user', $user->id)->get();
    echo "Total mst_pd_pengajuans: " . $tcs->count() . "\n";
    foreach ($tcs as $tc) {
        echo " - ID: " . $tc->id . ", Status 1: " . $tc->status_1 . ", Status 2: " . $tc->status_2 . "\n";
    }
}

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Fix softskill -> nontechnical
$updatedSoftskill = DB::table('mst_pd_pengajuans')
    ->where('kategori_competency', 'nontechnical')
    ->update(['kategori_competency' => 'softskill']);

$pengajuans = DB::table('mst_pd_pengajuans')->get();
$categories = [];
foreach ($pengajuans as $p) {
    if (!isset($categories[$p->kategori_competency])) {
        $categories[$p->kategori_competency] = 0;
    }
    $categories[$p->kategori_competency]++;
}

echo json_encode([
    'status' => 'Fix applied for softskill',
    'updated_softskill' => $updatedSoftskill,
    'current_categories' => $categories
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

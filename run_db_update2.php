<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
// Migration 2: Rename categories in tc_poin_kategoris
DB::table('tc_poin_kategoris')->where('id', 1)->update(['judul_keterangan' => 'Produksi']);
DB::table('tc_poin_kategoris')->where('id', 2)->update(['judul_keterangan' => 'Office/Tekofis']);
DB::table('tc_poin_kategoris')->where('id', 3)->update(['judul_keterangan' => 'EHS']);
echo "tc_poin_kategoris updated.<br>";

echo "Done.";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

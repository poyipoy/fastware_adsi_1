<?php
ini_set('display_errors', 1);
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Check tc_poin_kategoris columns
echo "<h3>tc_poin_kategoris columns:</h3><pre>";
print_r(Schema::getColumnListing('tc_poin_kategoris'));
echo "</pre>";

echo "<h3>tc_poin_kategoris data:</h3><pre>";
print_r(DB::table('tc_poin_kategoris')->get()->toArray());
echo "</pre>";

echo "<h3>mst_tcs columns:</h3><pre>";
print_r(Schema::getColumnListing('mst_tcs'));
echo "</pre>";

echo "<h3>mst_soft_skills columns:</h3><pre>";
print_r(Schema::getColumnListing('mst_soft_skills'));
echo "</pre>";

echo "<h3>mst_additionals columns:</h3><pre>";
print_r(Schema::getColumnListing('mst_additionals'));
echo "</pre>";

echo "<h3>trs_penilaian_tcs columns:</h3><pre>";
print_r(Schema::getColumnListing('trs_penilaian_tcs'));
echo "</pre>";

echo "<h3>mst_pd_pengajuans columns:</h3><pre>";
print_r(Schema::getColumnListing('mst_pd_pengajuans'));
echo "</pre>";

echo "<h3>users columns:</h3><pre>";
print_r(Schema::getColumnListing('users'));
echo "</pre>";

echo "<h3>user_job_positions columns:</h3><pre>";
print_r(Schema::getColumnListing('user_job_positions'));
echo "</pre>";

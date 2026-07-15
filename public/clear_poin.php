<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Now clean up id_poin_kategori as requested by the user in step 3
DB::table('mst_tcs')->update([
    'id_poin_kategori' => 0
]);

DB::table('mst_soft_skills')->update([
    'id_poin_kategori' => 0
]);

DB::table('mst_additionals')->update([
    'id_poin_kategori' => null
]);

echo "Cleaned up id_poin_kategori in all three tables.\n";

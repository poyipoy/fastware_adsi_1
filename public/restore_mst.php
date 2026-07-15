<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$sqlFile = __DIR__.'/../dms_adasi_rev1 (2).sql';
$content = file_get_contents($sqlFile);

// Find insert statements for the three tables
$tables = ['mst_tcs', 'mst_soft_skills', 'mst_additionals'];

DB::statement('SET FOREIGN_KEY_CHECKS=0;');

foreach ($tables as $table) {
    DB::table($table)->truncate(); // ensure it's empty
    
    // Find lines like: INSERT INTO `mst_tcs` (...) VALUES (...);
    // Since SQL dumps usually have one big INSERT or multiple, let's just find and execute them.
    $pattern = "/INSERT INTO `$table`.*?;/is";
    if (preg_match_all($pattern, $content, $matches)) {
        foreach ($matches[0] as $query) {
            DB::statement($query);
        }
        echo "Restored $table.\n";
    } else {
        echo "No insert statements found for $table.\n";
    }
}

// NOW we do what the user ACTUALLY asked: Clean up the columns ONLY (not the rows)
// "cleanup juga kolom keterangan dan deskripsi pada tabel: mst_tcs, mst_soft_skills, dan mst_additionals dan juga kolom nilai"
// Note: keterangan_tc, keterangan_sk are NOT NULL. So we set them to '-' or empty string ''
// nilai is NOT NULL. So we set it to 0.

DB::table('mst_tcs')->update([
    'keterangan_tc' => '-',
    'deskripsi_tc' => '-',
    'nilai' => 0
]);
echo "Cleaned up columns in mst_tcs.\n";

DB::table('mst_soft_skills')->update([
    'keterangan_sk' => '-',
    'deskripsi_sk' => '-',
    'nilai' => 0
]);
echo "Cleaned up columns in mst_soft_skills.\n";

DB::table('mst_additionals')->update([
    'keterangan_ad' => '-',
    'deskripsi_ad' => '-',
    'nilai' => 0
]);
echo "Cleaned up columns in mst_additionals.\n";

DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "All done!\n";

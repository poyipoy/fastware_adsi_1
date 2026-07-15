<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Mismatched Assessment Cleanup ===\n\n";

DB::beginTransaction();

try {
    $tables = ['trs_penilaian_tcs', 'trs_penilaian_sks', 'trs_penilaian_ads'];
    $totalDeleted = 0;

    foreach ($tables as $table) {
        if (!Illuminate\Support\Facades\Schema::hasTable($table)) {
            echo "Table $table does not exist. Skipping.\n";
            continue;
        }

        // We find the IDs to delete first to report them
        $mismatchedIds = DB::table($table . ' as t')
            ->leftJoin('user_job_positions as ujp', function($join) {
                $join->on('t.id_user', '=', 'ujp.user_id')
                     ->on('t.id_job_position', '=', 'ujp.mst_job_position_id');
            })
            ->whereNull('ujp.id')
            ->pluck('t.id');

        if ($mismatchedIds->isEmpty()) {
            echo "- $table: No mismatched records found.\n";
        } else {
            $deletedCount = DB::table($table)->whereIn('id', $mismatchedIds)->delete();
            echo "- $table: Cleaned up $deletedCount mismatched records.\n";
            $totalDeleted += $deletedCount;
        }
    }

    DB::commit();
    echo "\nCleanup Complete! Total records removed across all tables: $totalDeleted\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Error during cleanup: " . $e->getMessage() . "\n";
}

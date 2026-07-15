<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Query to find assessments where the user does not have the corresponding job position mapped
$mismatches = DB::table('trs_penilaian_tcs as t')
    ->leftJoin('user_job_positions as ujp', function($join) {
        $join->on('t.id_user', '=', 'ujp.user_id')
             ->on('t.id_job_position', '=', 'ujp.mst_job_position_id');
    })
    ->join('users as u', 't.id_user', '=', 'u.id')
    ->join('mst_job_positions as mjp', 't.id_job_position', '=', 'mjp.id')
    ->whereNull('ujp.id')
    ->select('t.id_user', 'u.name', 't.id_job_position', 'mjp.position_name', DB::raw('COUNT(t.id) as total_records'))
    ->groupBy('t.id_user', 'u.name', 't.id_job_position', 'mjp.position_name')
    ->get();

echo "=== Mismatched Assessments Found ===\n";
echo "Total distinct user-job combinations mismatched: " . $mismatches->count() . "\n\n";

foreach ($mismatches as $mismatch) {
    echo "- User: {$mismatch->name} (ID: {$mismatch->id_user}) \n";
    echo "  Assessed as Position: {$mismatch->position_name} (ID: {$mismatch->id_job_position})\n";
    echo "  Total Records in trs_penilaian_tcs: {$mismatch->total_records}\n";
    echo "---------------------------------------------------\n";
}

if ($mismatches->isEmpty()) {
    echo "No mismatches found. All assessments align with user job positions.\n";
}

<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $jobPositions = ['sales admin region 1'];
    $query = \App\Models\TrsPenilaianTc::query()
        ->join('tc_people_developments', 'trs_penilaian_tcs.id_tc_people_development', '=', 'tc_people_developments.id')
        ->join('users', 'tc_people_developments.id_user', '=', 'users.id')
        ->join('mst_job_positions', 'users.job_position_id', '=', 'mst_job_positions.id')
        ->select(
            'mst_job_positions.position_name',
            'tc_people_developments.id_user',
            'trs_penilaian_tcs.actual_score',
            'trs_penilaian_tcs.target_score',
            'tc_people_developments.created_at',
            'tc_people_developments.status'
        )
        ->where('tc_people_developments.status', '!=', 'Draft');

    $query->whereIn(DB::raw('LOWER(TRIM(mst_job_positions.position_name))'), $jobPositions);

    $results = collect($query->get());
    echo "Count of results for Sales Admin Region 1: " . $results->count() . "\n";
    if ($results->count() > 0) {
        echo "Sample data:\n";
        print_r($results->first()->toArray());
    }

    $query2 = \App\Models\TrsPenilaianTc::query()
        ->join('tc_people_developments', 'trs_penilaian_tcs.id_tc_people_development', '=', 'tc_people_developments.id')
        ->join('users', 'tc_people_developments.id_user', '=', 'users.id')
        ->join('mst_job_positions', 'users.job_position_id', '=', 'mst_job_positions.id')
        ->select('tc_people_developments.status')
        ->whereIn(DB::raw('LOWER(TRIM(mst_job_positions.position_name))'), $jobPositions);

    $allResults = collect($query2->get());
    echo "Count of ALL results (including Draft): " . $allResults->count() . "\n";
    if ($allResults->count() > 0) {
        echo "Statuses: " . implode(', ', $allResults->pluck('status')->unique()->toArray()) . "\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}

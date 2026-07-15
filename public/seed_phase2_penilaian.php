<?php
/**
 * Phase 2: Generate trs_penilaian_tcs (Competency Assessment)
 * 
 * Rules:
 * - 1 assessment per non-head employee (job_level = 'staff')
 * - Year: 2025, status: 'Completed', is_locked: 1
 * - Distribution: 60% meet target (nilai >= 3), 25% slightly below (nilai=2), 15% far below (nilai=1)
 * - nilai is per-employee, varies for TC, SK, AD
 * - total_nilai = (nilai_tc + nilai_sk + nilai_ad) / 3 rounded to 2 decimals
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Check if already seeded
$existing = DB::table('trs_penilaian_tcs')->count();
if ($existing > 0) {
    echo json_encode(['status' => 'ALREADY SEEDED', 'count' => $existing], JSON_PRETTY_PRINT);
    exit;
}

// Get all non-head employees with their competency master IDs
$employees = DB::table('users as u')
    ->join('user_job_positions as ujp', function($j) {
        $j->on('ujp.user_id', '=', 'u.id')->where('ujp.is_active', 1);
    })
    ->join('mst_job_positions as jp', 'ujp.mst_job_position_id', '=', 'jp.id')
    ->leftJoin('mst_tcs as tc', 'tc.id_job_position', '=', 'jp.id')
    ->leftJoin('mst_soft_skills as sk', 'sk.id_job_position', '=', 'jp.id')
    ->leftJoin('mst_additionals as ad', 'ad.id_job_position', '=', 'jp.id')
    ->where('jp.job_level', 'staff')
    ->select(
        'u.id as user_id', 'u.name as user_name', 'u.role_id',
        'ujp.mst_job_position_id as jp_id',
        'tc.id as tc_id', 'sk.id as sk_id', 'ad.id as ad_id'
    )
    ->orderBy('u.id')
    ->get();

$log = [];
$inserted = 0;
$skipped = 0;

// Distribution: deterministic per user_id to ensure consistency
// User IDs sorted, then assign categories:
// 60% get nilai 3-4, 25% get nilai 2, 15% get nilai 1
$totalEmployees = count($employees);
$log[] = "Total non-head employees: {$totalEmployees}";

// Define realistic date pairs (assessment dates in 2025)
$assessmentDates = [
    ['2025-01-20', '2025-01-22'],
    ['2025-02-10', '2025-02-12'],
    ['2025-03-15', '2025-03-17'],
    ['2025-04-07', '2025-04-09'],
    ['2025-05-12', '2025-05-14'],
    ['2025-06-09', '2025-06-11'],
    ['2025-07-14', '2025-07-16'],
    ['2025-08-11', '2025-08-13'],
    ['2025-09-08', '2025-09-10'],
    ['2025-10-13', '2025-10-15'],
    ['2025-11-10', '2025-11-12'],
    ['2025-12-08', '2025-12-10'],
];

foreach ($employees as $index => $emp) {
    if (!$emp->tc_id || !$emp->sk_id || !$emp->ad_id) {
        $log[] = "⚠️ SKIP {$emp->user_name} (jp_id={$emp->jp_id}) - no competency master";
        $skipped++;
        continue;
    }
    
    // Determine score category based on index (deterministic):
    // 0-59% → meet target (nilai 3 or 4)
    // 60-84% → slightly below (nilai 2)
    // 85-99% → far below (nilai 1)
    $pct = $index / $totalEmployees;
    
    if ($pct < 0.60) {
        // Meet target: TC=3 or 4, SK=3 or 4, AD=3 or 4
        // Vary: some get 4 on TC (every 3rd), rest get 3
        $nilai_tc = ($index % 3 === 0) ? 4 : 3;
        $nilai_sk = ($index % 4 === 0) ? 4 : 3;
        $nilai_ad = ($index % 5 === 0) ? 4 : 3;
    } elseif ($pct < 0.85) {
        // Slightly below: TC=2, SK=2 or 3, AD=2 or 3
        $nilai_tc = 2;
        $nilai_sk = ($index % 2 === 0) ? 2 : 3;
        $nilai_ad = ($index % 3 === 0) ? 2 : 3;
    } else {
        // Far below: TC=1, SK=1 or 2, AD=1 or 2
        $nilai_tc = 1;
        $nilai_sk = ($index % 2 === 0) ? 1 : 2;
        $nilai_ad = ($index % 2 === 0) ? 2 : 1;
    }
    
    $total_nilai = round(($nilai_tc + $nilai_sk + $nilai_ad) / 3, 2);
    
    // Pick date pair (cycle through)
    $datePair = $assessmentDates[$index % count($assessmentDates)];
    $assessDate = $datePair[0];
    $modDate    = $datePair[1];
    
    DB::table('trs_penilaian_tcs')->insert([
        'id_tc'           => $emp->tc_id,
        'id_sk'           => $emp->sk_id,
        'id_ad'           => $emp->ad_id,
        'id_job_position' => $emp->jp_id,
        'id_user'         => $emp->user_id,
        'nilai_tc'        => $nilai_tc,
        'nilai_sk'        => $nilai_sk,
        'nilai_ad'        => $nilai_ad,
        'total_nilai'     => $total_nilai,
        'status'          => 3,
        'tahun_penilaian' => 2025,
        'is_locked'       => 1,
        'modified_at'     => $emp->user_id, // stores user ID as modifier
        'modified_updated' => $modDate . ' 08:00:00',
        'created_at'      => $assessDate . ' 08:00:00',
        'updated_at'      => $modDate . ' 08:00:00',
    ]);
    
    $inserted++;
}

$log[] = "✅ Inserted trs_penilaian_tcs: {$inserted} records";
$log[] = "⚠️ Skipped (no master): {$skipped} records";

// Summary by category
$meet  = DB::table('trs_penilaian_tcs')->where('nilai_tc', '>=', 3)->count();
$below = DB::table('trs_penilaian_tcs')->where('nilai_tc', 2)->count();
$far   = DB::table('trs_penilaian_tcs')->where('nilai_tc', 1)->count();
$log[] = "📊 Distribution: Meet target (nilai_tc>=3)={$meet}, Below={$below}, Far Below={$far}";

// Employees with gap (any nilai < 3) = candidates for training
$gap = DB::table('trs_penilaian_tcs')
    ->where(function($q) { $q->where('nilai_tc', '<', 3)->orWhere('nilai_sk', '<', 3)->orWhere('nilai_ad', '<', 3); })
    ->count();
$log[] = "🎯 Employees with gap (need training): {$gap}";

echo json_encode([
    'status'   => 'Phase 2 DONE - Competency Assessment Seeded',
    'inserted' => $inserted,
    'skipped'  => $skipped,
    'log'      => $log
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

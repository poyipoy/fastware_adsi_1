<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(120);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$results = [];

// 1. Column listing for key tables
$results['trs_penilaian_tcs_columns'] = Schema::getColumnListing('trs_penilaian_tcs');
$results['mst_pd_pengajuans_columns'] = Schema::getColumnListing('mst_pd_pengajuans');
$results['mst_tcs_columns'] = Schema::getColumnListing('mst_tcs');
$results['mst_soft_skills_columns'] = Schema::getColumnListing('mst_soft_skills');
$results['mst_additionals_columns'] = Schema::getColumnListing('mst_additionals');

// 2. DESCRIBE tables
$results['trs_types'] = DB::select("DESCRIBE trs_penilaian_tcs");
$results['pd_types'] = DB::select("DESCRIBE mst_pd_pengajuans");

// 3. ALL non-head employees with their competency master IDs (for seeding)
$employees = DB::table('users as u')
    ->join('user_job_positions as ujp', function($j) {
        $j->on('ujp.user_id', '=', 'u.id')->where('ujp.is_active', 1);
    })
    ->join('mst_job_positions as jp', 'ujp.mst_job_position_id', '=', 'jp.id')
    ->leftJoin('mst_departments as d', 'jp.department_id', '=', 'd.id')
    ->leftJoin('mst_sections as s', 'jp.section_id', '=', 's.id')
    ->leftJoin('mst_tcs as tc', 'tc.id_job_position', '=', 'jp.id')
    ->leftJoin('mst_soft_skills as sk', 'sk.id_job_position', '=', 'jp.id')
    ->leftJoin('mst_additionals as ad', 'ad.id_job_position', '=', 'jp.id')
    ->where('jp.job_level', 'staff')
    ->select(
        'u.id as user_id', 'u.name as user_name', 'u.npk',
        'ujp.mst_job_position_id as jp_id', 'jp.position_name',
        'd.id as dept_id', 'd.name as dept_name',
        's.id as sec_id', 's.name as sec_name',
        'tc.id as tc_id', 'tc.id_poin_kategori as tc_kategori',
        'sk.id as sk_id', 'sk.id_poin_kategori as sk_kategori',
        'ad.id as ad_id', 'ad.id_poin_kategori as ad_kategori'
    )
    ->orderBy('d.name')->orderBy('s.name')->orderBy('jp.position_name')->orderBy('u.name')
    ->get()->toArray();

$results['non_head_employees'] = $employees;
$results['non_head_count'] = count($employees);

// Count those without competency masters
$missing = array_filter($employees, fn($r) => $r->tc_id === null);
$results['missing_competency_count'] = count($missing);
$results['missing_competency_users'] = array_values(array_map(fn($r) => ['user' => $r->user_name, 'jp' => $r->position_name], $missing));

// 4. FK constraints on transaction tables
$results['trs_fk'] = DB::select("
    SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trs_penilaian_tcs'
      AND REFERENCED_TABLE_NAME IS NOT NULL
");

$results['pd_fk'] = DB::select("
    SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mst_pd_pengajuans'
      AND REFERENCED_TABLE_NAME IS NOT NULL
");

// 5. div_head positions
$results['div_head_users'] = DB::table('user_job_positions as ujp')
    ->join('mst_job_positions as jp', 'ujp.mst_job_position_id', '=', 'jp.id')
    ->join('users as u', 'ujp.user_id', '=', 'u.id')
    ->leftJoin('mst_departments as d', 'jp.department_id', '=', 'd.id')
    ->where('jp.job_level', 'div_head')
    ->where('ujp.is_active', 1)
    ->select('u.id', 'u.name', 'jp.id as jp_id', 'jp.position_name', 'd.name as dept_name')
    ->get()->toArray();

// 6. Head employee list
$results['head_employees'] = DB::table('users as u')
    ->join('user_job_positions as ujp', function($j) {
        $j->on('ujp.user_id', '=', 'u.id')->where('ujp.is_active', 1);
    })
    ->join('mst_job_positions as jp', 'ujp.mst_job_position_id', '=', 'jp.id')
    ->leftJoin('mst_departments as d', 'jp.department_id', '=', 'd.id')
    ->whereIn('jp.job_level', ['div_head', 'dept_head', 'sec_head'])
    ->select('u.id', 'u.name', 'jp.position_name', 'jp.job_level', 'd.name as dept')
    ->orderBy('d.name')->orderBy('jp.job_level')->orderBy('u.name')
    ->get()->toArray();

$results['head_count'] = count($results['head_employees']);

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

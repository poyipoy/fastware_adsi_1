<?php
/**
 * Phase 3: Generate mst_pd_pengajuans (Training/People Development)
 * 
 * Rules:
 * - Only for employees with gaps in their assessment (nilai_tc < 3 OR nilai_sk < 3 OR nilai_ad < 3)
 * - Create 1 training record per gap found.
 * - Final dataset: status_1 = 3 (Approved), status_2 = 'Done'
 * - Complete all fields including evaluation fields (relevansi, tgl_konfirm, dll).
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\TrsPenilaianTc;

// Check if already seeded
$existing = DB::table('mst_pd_pengajuans')->count();
if ($existing > 0) {
    echo json_encode(['status' => 'ALREADY SEEDED', 'count' => $existing], JSON_PRETTY_PRINT);
    exit;
}

$assessments = DB::table('trs_penilaian_tcs as t')
    ->join('users as u', 't.id_user', '=', 'u.id')
    ->join('mst_job_positions as jp', 't.id_job_position', '=', 'jp.id')
    ->leftJoin('mst_tcs as tc', 't.id_tc', '=', 'tc.id')
    ->leftJoin('mst_soft_skills as sk', 't.id_sk', '=', 'sk.id')
    ->leftJoin('mst_additionals as ad', 't.id_ad', '=', 'ad.id')
    ->where(function($q) {
        $q->where('t.nilai_tc', '<', 3)
          ->orWhere('t.nilai_sk', '<', 3)
          ->orWhere('t.nilai_ad', '<', 3);
    })
    ->select(
        't.id as id_trs', 't.id_user', 't.id_job_position', 't.nilai_tc', 't.nilai_sk', 't.nilai_ad',
        't.id_tc', 't.id_sk', 't.id_ad', 'u.role_id', 'jp.section_id', 'jp.department_id',
        'tc.keterangan_tc', 'sk.keterangan_sk', 'ad.keterangan_ad'
    )
    ->get();

$log = [];
$inserted = 0;

$vendors = [
    'Astra Management Development Institute (AMDI)',
    'Global Edu-Tech Solutions',
    'LPK Bintang Terang',
    'In-House Training',
    'Badan Nasional Sertifikasi Profesi (BNSP)'
];

foreach ($assessments as $index => $ass) {
    $gaps = [];
    if ($ass->nilai_tc < 3) $gaps[] = ['type' => 'Technical', 'id' => $ass->id_tc, 'name' => $ass->keterangan_tc, 'col' => 'id_tc'];
    if ($ass->nilai_sk < 3) $gaps[] = ['type' => 'Soft Skill', 'id' => $ass->id_sk, 'name' => $ass->keterangan_sk, 'col' => 'id_sk'];
    if ($ass->nilai_ad < 3) $gaps[] = ['type' => 'Additional', 'id' => $ass->id_ad, 'name' => $ass->keterangan_ad, 'col' => 'id_ad'];
    
    foreach ($gaps as $gapIndex => $gap) {
        $trainingName = "Pelatihan " . $gap['name'];
        $vendor = $vendors[array_rand($vendors)];
        $biaya = rand(10, 50) * 100000;
        
        $months = ['02', '04', '06', '08', '10', '12'];
        $month = $months[($index + $gapIndex) % count($months)];
        $tglUsulan = "2025-{$month}-10";
        $tglPelaksanaan = "2025-{$month}-15";
        
        $data = [
            'id_role'           => $ass->role_id,
            'id_job_position'   => $ass->id_job_position,
            'id_user'           => $ass->id_user,
            'section_id'        => $ass->section_id,
            'id_tc'             => null,
            'id_sk'             => null,
            'id_ad'             => null,
            'id_trs'            => $ass->id_trs,
            'program_training'  => $trainingName,
            'program_training_plan' => $trainingName,
            'kategori_competency' => strtolower(str_replace(' ', '', $gap['type'])),
            'competency'        => $gap['name'],
            'due_date'          => $tglUsulan,
            'due_date_plan'     => $tglPelaksanaan,
            'lembaga'           => $vendor,
            'lembaga_plan'      => $vendor,
            'keterangan_tujuan' => "Meningkatkan kemampuan " . $gap['name'],
            'keterangan_plan'   => "Meningkatkan kemampuan " . $gap['name'],
            'biaya'             => $biaya,
            'biaya_plan'        => $biaya,
            'tahun_aktual'      => 2025,
            'tahun_usulan'      => 2025,
            'status_1'          => 3,
            'status_2'          => 'Done',
            'modified_at'       => (string)$ass->id_user, // Convention string per model doc
            'relevansi'         => 'Ya',
            'alasan_relevansi'  => 'Sangat sesuai dengan kebutuhan pekerjaan saat ini.',
            'rekomendasi'       => 'Sangat Direkomendasikan',
            'alasan_rekomendasi'=> 'Materi sangat aplikatif.',
            'kelengkapan_materi'=> 4,
            'lokasi'            => 4,
            'metode_pengajaran' => 4,
            'fasilitas'         => 4,
            'metode_evaluasi'   => 'Pre-test & Post-test',
            'minat'             => 4,
            'daya_serap'        => 4,
            'penerapan'         => 4,
            'diketahui'         => 'Trainee',
            'dievaluasi'        => 'Atasan Langsung',
            'tgl_pengajuan'     => $tglPelaksanaan,
            'tgl_konfirm'       => $tglPelaksanaan,
            'efektif'           => 'Ya',
            'created_at'        => $tglUsulan . " 09:00:00",
            'updated_at'        => $tglPelaksanaan . " 16:00:00",
        ];
        
        $data[$gap['col']] = $gap['id'];
        
        DB::table('mst_pd_pengajuans')->insert($data);
        $inserted++;
    }
}

$log[] = "✅ Inserted mst_pd_pengajuans: {$inserted} records";

echo json_encode([
    'status'   => 'Phase 3 DONE - Training Generated',
    'inserted' => $inserted,
    'log'      => $log
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

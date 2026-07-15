<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

DB::statement('SET FOREIGN_KEY_CHECKS=0;');
DB::table('mst_tcs')->truncate();
DB::table('mst_soft_skills')->truncate();
DB::table('mst_additionals')->truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

$jobs = DB::table('mst_job_positions')->get();
$targetJobs = [];

foreach ($jobs as $job) {
    $posName = strtolower($job->position_name);
    if (!str_contains($posName, 'section head') && 
        !str_contains($posName, 'department head') && 
        !str_contains($posName, 'division head')) {
        $targetJobs[] = $job;
    }
}

echo "Found " . count($targetJobs) . " non-head job positions.<br>";

$count = 0;
foreach ($targetJobs as $job) {
    // Insert into mst_tcs
    DB::table('mst_tcs')->insert([
        'id_job_position' => $job->id,
        'id_poin_kategori' => 1,
        'keterangan_tc' => '',
        'deskripsi_tc' => '',
        'sub_kategori' => '',
        'nilai' => 0,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // Insert into mst_soft_skills
    DB::table('mst_soft_skills')->insert([
        'id_job_position' => $job->id,
        'id_poin_kategori' => 2,
        'keterangan_sk' => '',
        'deskripsi_sk' => '',
        'nilai' => 0,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // Insert into mst_additionals
    DB::table('mst_additionals')->insert([
        'id_job_position' => $job->id,
        'id_poin_kategori' => 3,
        'keterangan_ad' => '',
        'deskripsi_ad' => '',
        'nilai' => 0,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    $count++;
}

echo "Successfully seeded $count blank competency rows for each non-head job position!<br>";

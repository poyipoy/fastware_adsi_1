<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\TcPeopleDevelopment;
use Faker\Factory as Faker;

$faker = Faker::create('id_ID');

$users = User::with('userJobPositions.jobPosition')->get();
$targetUsers = [];

foreach ($users as $user) {
    $isHead = false;
    $hasPosition = false;
    $primaryJob = null;
    $sectionId = null;
    
    foreach ($user->userJobPositions as $ujp) {
        if ($ujp->jobPosition) {
            $hasPosition = true;
            $primaryJob = $ujp->jobPosition;
            $sectionId = $ujp->jobPosition->id_section;
            $posName = strtolower($ujp->jobPosition->position_name);
            if (str_contains($posName, 'section head') || 
                str_contains($posName, 'department head') || 
                str_contains($posName, 'division head')) {
                $isHead = true;
                break;
            }
        }
    }
    
    if (!$isHead && $hasPosition) {
        $user->primary_job_id = $primaryJob->id;
        $user->primary_section_id = $sectionId;
        $targetUsers[] = $user;
    }
}

echo "Found " . count($targetUsers) . " non-head users for dummy generation.<br>";

DB::statement('SET FOREIGN_KEY_CHECKS=0;');
TcPeopleDevelopment::truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

try {
    $count = 0;

    $categories = ['technical', 'nontechnical', 'additional', 'Others'];
    $programs = ['Training Keselamatan K3', 'Pelatihan Komunikasi Efektif', 'Workshop 5S', 'Excel Advanced', 'Manajemen Waktu'];

    foreach ($targetUsers as $user) {
        $num = rand(1, 3);
        for ($i=0; $i<$num; $i++) {
            $year = rand(2024, 2026);
            $cat = $categories[array_rand($categories)];
            $prog = $programs[array_rand($programs)];
            
            if ($year < 2026) {
                $s1 = 3; 
                $s2 = 'Done';
            } else {
                $s1 = rand(1, 3);
                $s2 = ($s1 == 3) ? 'On Progress' : 'Pending';
            }

            // Fill all fields just in case they are not nullable
            TcPeopleDevelopment::create([
                'id_user' => $user->id,
                'id_job_position' => $user->primary_job_id,
                'section_id' => $user->primary_section_id,
                'id_role' => $user->id_role ?? 2,
                'program_training' => $prog,
                'program_training_plan' => $prog,
                'kategori_competency' => $cat,
                'competency' => 'Dummy Competency for ' . $cat,
                'due_date' => $year . '-' . rand(1,12) . '-15',
                'due_date_plan' => $year . '-' . rand(1,12) . '-20',
                'biaya' => rand(100, 500) * 10000,
                'biaya_plan' => rand(100, 500) * 10000,
                'lembaga' => 'Lembaga ' . $faker->company,
                'lembaga_plan' => 'Lembaga ' . $faker->company,
                'keterangan_tujuan' => 'Meningkatkan skill dummy',
                'status_1' => $s1,
                'status_2' => $s2,
                'tahun_aktual' => $year,
                'tahun_usulan' => $year,
                'modified_at' => 'System', 
            ]);
            $count++;
        }
    }
    echo "Successfully seeded $count Pengajuan Competency (People Development) records!<br>";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "<br><pre>" . $e->getTraceAsString() . "</pre>";
}

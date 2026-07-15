<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\MstJobPosition;
use App\Models\MstTc;
use App\Models\MstSoftSkill;
use App\Models\MstAdditionals;
use App\Models\TrsPenilaianTc;
use App\Models\DetailTcPenilaian;
use App\Models\TcPeopleDevelopment;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class DummyCompetencyDataSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');
        $years = [2024, 2025, 2026];

        // Ensure foreign key checks are off during cleanup/seeding just in case
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clean tables (to be safe, though already truncated)
        TcPeopleDevelopment::truncate();
        DetailTcPenilaian::truncate();
        TrsPenilaianTc::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $users = User::with('userJobPositions.jobPosition')->get();
        $targetUsers = [];
        $headUsers = [];

        foreach ($users as $user) {
            $isHead = false;
            $hasPosition = false;
            
            foreach ($user->userJobPositions as $ujp) {
                if ($ujp->jobPosition) {
                    $hasPosition = true;
                    $posName = strtolower($ujp->jobPosition->position_name);
                    // Explicitly filter out specific head roles
                    if (str_contains($posName, 'section head') || 
                        str_contains($posName, 'department head') || 
                        str_contains($posName, 'division head')) {
                        $isHead = true;
                        $headUsers[] = $user;
                        break;
                    }
                }
            }
            
            if (!$isHead && $hasPosition) {
                $targetUsers[] = $user;
            }
        }

        echo "Found " . count($targetUsers) . " non-head users to seed.\n";
        $defaultAssessorId = count($headUsers) > 0 ? $headUsers[0]->id : 1;
        $defaultAssessorName = count($headUsers) > 0 ? $headUsers[0]->name : 'System Admin';

        DB::beginTransaction();
        try {
            foreach ($targetUsers as $user) {
                $primaryJob = null;
                foreach ($user->userJobPositions as $ujp) {
                    if ($ujp->jobPosition) {
                        $primaryJob = $ujp->jobPosition;
                        break;
                    }
                }

                if (!$primaryJob) continue;

                $tcs = MstTc::where('id_job_position', $primaryJob->id)->get();
                $sks = MstSoftSkill::where('id_job_position', $primaryJob->id)->get();
                $ads = MstAdditionals::where('id_job_position', $primaryJob->id)->get();

                if ($tcs->isEmpty() && $sks->isEmpty() && $ads->isEmpty()) continue;

                foreach ($years as $year) {
                    // 85% chance user was assessed this year
                    if ($faker->boolean(85)) {
                        $isLocked = ($year < 2026); 
                        // For older years, Mostly status 4 (final). For current year, spread across 1,2,3,4.
                        if ($year < 2026) {
                            $status = $faker->randomElement([3, 4]);
                        } else {
                            $status = $faker->randomElement([1, 2, 3, 4]);
                        }
                        
                        $keteranganDetailChanges = [];

                        // TC
                        foreach ($tcs as $tc) {
                            $target = $tc->nilai ?? 3;
                            $actual = $faker->numberBetween(max(1, $target - 1), 4);
                            
                            $trs = TrsPenilaianTc::create([
                                'id_user' => $user->id,
                                'id_job_position' => $primaryJob->id,
                                'id_tc' => $tc->id,
                                'id_sk' => null,
                                'id_ad' => null,
                                'nilai_tc' => $actual,
                                'nilai_sk' => null,
                                'nilai_ad' => null,
                                'status' => $status,
                                'tahun_penilaian' => $year,
                                'is_locked' => $isLocked,
                                'modified_at' => $defaultAssessorId,
                                'modified_updated' => $defaultAssessorName,
                            ]);

                            $keteranganDetailChanges[] = "Technical Competency: " . $tc->keterangan_tc . " = " . $actual;

                            // Create PD if score is low
                            if ($actual < $target && $faker->boolean(60) && $status >= 3) {
                                TcPeopleDevelopment::create([
                                    'id_role' => $user->role_id ?? 1,
                                    'id_job_position' => $primaryJob->id,
                                    'id_user' => $user->id,
                                    'section_id' => $primaryJob->department_id ?? 1, // Assuming section is linked to department
                                    'id_tc' => $tc->id,
                                    'id_sk' => null,
                                    'id_ad' => null,
                                    'id_trs' => $trs->id,
                                    'program_training' => 'Training on ' . $tc->keterangan_tc,
                                    'program_training_plan' => 'Training on ' . $tc->keterangan_tc,
                                    'kategori_competency' => 'Technical',
                                    'competency' => $tc->keterangan_tc,
                                    'due_date' => \Carbon\Carbon::createFromDate($year, $faker->numberBetween(1, 12), $faker->numberBetween(1, 28))->format('Y-m-d'),
                                    'due_date_plan' => \Carbon\Carbon::createFromDate($year, $faker->numberBetween(1, 12), $faker->numberBetween(1, 28))->format('Y-m-d'),
                                    'lembaga' => $faker->company,
                                    'lembaga_plan' => $faker->company,
                                    'keterangan_tujuan' => 'To improve ' . $tc->keterangan_tc,
                                    'keterangan_plan' => 'To improve ' . $tc->keterangan_tc,
                                    'biaya' => $faker->randomElement([500000, 1000000, 1500000]),
                                    'biaya_plan' => $faker->randomElement([500000, 1000000, 1500000]),
                                    'status_1' => $faker->randomElement([1, 2, 3]),
                                    'status_2' => 'Pending',
                                    'modified_at' => (string)$defaultAssessorId,
                                    'tahun_aktual' => $year,
                                    'tahun_usulan' => $year,
                                ]);
                            }
                        }

                        // SK
                        foreach ($sks as $sk) {
                            $target = $sk->nilai ?? 3;
                            $actual = $faker->numberBetween(max(1, $target - 1), 4);
                            
                            $trs = TrsPenilaianTc::create([
                                'id_user' => $user->id,
                                'id_job_position' => $primaryJob->id,
                                'id_tc' => null,
                                'id_sk' => $sk->id,
                                'id_ad' => null,
                                'nilai_tc' => null,
                                'nilai_sk' => $actual,
                                'nilai_ad' => null,
                                'status' => $status,
                                'tahun_penilaian' => $year,
                                'is_locked' => $isLocked,
                                'modified_at' => $defaultAssessorId,
                                'modified_updated' => $defaultAssessorName,
                            ]);
                            $keteranganDetailChanges[] = "Soft Skill: " . $sk->keterangan_sk . " = " . $actual;
                        }

                        // AD
                        foreach ($ads as $ad) {
                            $target = $ad->nilai ?? 3;
                            $actual = $faker->numberBetween(max(1, $target - 1), 4);
                            
                            $trs = TrsPenilaianTc::create([
                                'id_user' => $user->id,
                                'id_job_position' => $primaryJob->id,
                                'id_tc' => null,
                                'id_sk' => null,
                                'id_ad' => $ad->id,
                                'nilai_tc' => null,
                                'nilai_sk' => null,
                                'nilai_ad' => $actual,
                                'status' => $status,
                                'tahun_penilaian' => $year,
                                'is_locked' => $isLocked,
                                'modified_at' => $defaultAssessorId,
                                'modified_updated' => $defaultAssessorName,
                            ]);
                            $keteranganDetailChanges[] = "Additional: " . $ad->keterangan_ad . " = " . $actual;
                        }

                        // Create DetailTcPenilaian log entry for this year's assessment
                        if (count($keteranganDetailChanges) > 0) {
                            DetailTcPenilaian::create([
                                'id_job_position' => $primaryJob->id,
                                'name' => $user->name,
                                'keterangan_detail' => 'Initial assessment generated by seeder for year ' . $year,
                                'nilai_sebelum' => json_encode([]), 
                                'catatan' => 'Seeder Data',
                                'modified_at' => $defaultAssessorName,
                            ]);
                        }
                    }
                }
            }
            
            DB::commit();
            echo "Successfully generated dummy data.\n";
            
            // Print out counts
            echo "TcPeopleDevelopment: " . TcPeopleDevelopment::count() . "\n";
            echo "TrsPenilaianTc: " . TrsPenilaianTc::count() . "\n";
            echo "DetailTcPenilaian: " . DetailTcPenilaian::count() . "\n";

        } catch (\Exception $e) {
            DB::rollBack();
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}

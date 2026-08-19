<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\MstDepartment;
use App\Models\MstSection;
use App\Models\MstJobPosition;
use App\Models\TcJobPosition;
use App\Models\UserJobPosition;
use App\Models\MstPositionApproval;
use App\Models\User;

class MigrateFullArchitectureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        try {
            $this->command->info('Memulai migrasi arsitektur penuh...');

            // 1 & 2. Master Data: Dept, Section, and Job Position
            $this->command->info('Memigrasikan Master Departemen, Section, dan Job Position...');
            $legacyJobs = TcJobPosition::all();

            // Peta dari id_job_position lama (tc_job_positions.id) ke ID master baru (mst_job_positions.id)
            $jobPositionMap = []; 

            foreach ($legacyJobs as $lj) {
                // Dept
                $deptId = null;
                if (!empty($lj->department)) {
                    $dept = MstDepartment::firstOrCreate(
                        ['name' => trim($lj->department)],
                        ['is_active' => true]
                    );
                    $deptId = $dept->id;
                }

                // Section
                $secId = null;
                if (!empty($lj->section) && $deptId) {
                    $sec = MstSection::firstOrCreate(
                        ['name' => trim($lj->section), 'department_id' => $deptId],
                        ['is_active' => true]
                    );
                    $secId = $sec->id;
                }

                // Master Job Position
                $mstPos = MstJobPosition::firstOrCreate(
                    ['position_name' => trim($lj->job_position)],
                    [
                        'department_id' => $deptId,
                        'section_id' => $secId,
                        'is_active' => true
                    ]
                );

                $jobPositionMap[$lj->id] = $mstPos->id;

                // 3. User Mapping
                if (!empty($lj->id_user)) {
                    // Pastikan user masih ada di tabel users (mencegah error FK orphan record)
                    $userExists = User::where('id', $lj->id_user)->exists();
                    
                    if ($userExists) {
                        // Cek apakah mapping sudah ada
                        $exists = UserJobPosition::where('user_id', $lj->id_user)
                            ->where('mst_job_position_id', $mstPos->id)
                            ->exists();

                        if (!$exists) {
                            UserJobPosition::create([
                                'user_id' => $lj->id_user,
                                'mst_job_position_id' => $mstPos->id,
                                'is_active' => true,
                            ]);
                        }
                    } else {
                        $this->command->warn("User ID {$lj->id_user} (Posisi: {$lj->job_position}) sudah tidak ada di tabel Users. Mapping dilewati.");
                    }
                }
            }
            $this->command->info('Mapping Master Data dan User Selesai.');

            // 4. Approval Routes
            $this->command->info('Memigrasikan Approval Routes...');
            $processedMstPosIds = [];
            
            // Helper untuk mencari mst_job_position_id berdasarkan nama user
            $getApproverPosId = function($name) {
                if (empty($name) || trim($name) === '-' || trim($name) === 'N/A') return null;
                
                $user = User::where('name', 'LIKE', '%' . trim($name) . '%')->first();
                if ($user) {
                    $userPos = UserJobPosition::where('user_id', $user->id)
                        ->where('is_active', true)
                        ->first();
                    return $userPos ? $userPos->mst_job_position_id : null;
                }
                return null;
            };

            foreach ($legacyJobs as $lj) {
                $mstPosId = $jobPositionMap[$lj->id];
                
                if (in_array($mstPosId, $processedMstPosIds)) {
                    continue; 
                }
                $processedMstPosIds[] = $mstPosId;

                // Section Head -> Approval Level 1
                $secHeadPosId = $getApproverPosId($lj->section_head_name);
                if ($secHeadPosId) {
                    MstPositionApproval::firstOrCreate([
                        'position_id' => $mstPosId,
                        'approval_level' => 1,
                    ], [
                        'approver_position_id' => $secHeadPosId,
                    ]);
                }

                // Department Head -> Approval Level 2
                $deptHeadPosId = $getApproverPosId($lj->department_head_name);
                if ($deptHeadPosId) {
                    MstPositionApproval::firstOrCreate([
                        'position_id' => $mstPosId,
                        'approval_level' => 2,
                    ], [
                        'approver_position_id' => $deptHeadPosId,
                    ]);
                }
            }
            $this->command->info('Migrasi Approval Selesai.');

            // 5. Update Foreign Keys in Competency Tables
            $this->command->info('Memperbarui Foreign Keys di Tabel Kompetensi...');
            $tablesToUpdate = [
                'mst_tc',
                'mst_soft_skills',
                'mst_additionals',
                'poin_kategoris',
                'trs_penilaian_tcs',
                'trs_penilaian_soft_skills',
                'trs_penilaian_additionals',
                'tc_people_developments'
            ];

            foreach ($tablesToUpdate as $tableName) {
                if (!Schema::hasTable($tableName)) continue;
                
                $records = DB::table($tableName)->get();
                $updatedCount = 0;
                
                foreach ($records as $record) {
                    // Update field id_job_position
                    if (isset($record->id_job_position) && isset($jobPositionMap[$record->id_job_position])) {
                        DB::table($tableName)
                            ->where('id', $record->id)
                            ->update(['id_job_position' => $jobPositionMap[$record->id_job_position]]);
                        $updatedCount++;
                    }
                }
                $this->command->info("Tabel {$tableName}: {$updatedCount} baris diperbarui.");
            }

            DB::commit();
            $this->command->info('Seluruh migrasi berhasil!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}

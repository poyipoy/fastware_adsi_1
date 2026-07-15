<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\MstDepartment;
use App\Models\MstSection;
use App\Models\TcJobPosition;

class MigrateLegacyDeptSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai migrasi data Departemen dan Section dari tc_job_positions...');

        // 1. Migrasi Departemen
        $departments = TcJobPosition::whereNotNull('department')
            ->where('department', '!=', '')
            ->select('department')
            ->distinct()
            ->get();

        $deptCount = 0;
        foreach ($departments as $dept) {
            $departmentName = trim($dept->department);
            
            // Gunakan firstOrCreate untuk menghindari duplikat jika seeder dijalankan berulang
            MstDepartment::firstOrCreate(
                ['name' => $departmentName],
                ['is_active' => true]
            );
            $deptCount++;
        }
        $this->command->info("Berhasil memigrasikan {$deptCount} departemen.");

        // 2. Migrasi Section
        $sections = TcJobPosition::whereNotNull('department')
            ->where('department', '!=', '')
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->select('department', 'section')
            ->distinct()
            ->get();

        $sectionCount = 0;
        foreach ($sections as $sec) {
            $departmentName = trim($sec->department);
            $sectionName = trim($sec->section);

            // Cari ID departemen yang sudah di-insert
            $department = MstDepartment::where('name', $departmentName)->first();
            
            if ($department) {
                MstSection::firstOrCreate(
                    [
                        'department_id' => $department->id,
                        'name' => $sectionName
                    ],
                    ['is_active' => true]
                );
                $sectionCount++;
            } else {
                $this->command->warn("Departemen '{$departmentName}' tidak ditemukan untuk section '{$sectionName}'.");
            }
        }
        
        $this->command->info("Berhasil memigrasikan {$sectionCount} section.");
        $this->command->info('Migrasi data selesai!');
    }
}

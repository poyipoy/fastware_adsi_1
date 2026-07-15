<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\MstJobPosition;
use App\Models\UserJobPosition;
use Illuminate\Support\Facades\Log;

class KaryawanHRSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Memulai Seeding Data Karyawan & Job Position dari JSON...');

        // 1. Update nama user yang sudah ada agar persis dengan JSON
        User::where('name', 'RICHARDUS')->update(['name' => 'RICHARDUS CHRISTIAN']);
        $this->command->info('Updated user RICHARDUS -> RICHARDUS CHRISTIAN');

        User::where('name', 'RUSLAN M.ALI')->update(['name' => 'RUSLAN']);
        $this->command->info('Updated user RUSLAN M.ALI -> RUSLAN');

        // 2. Baca file JSON
        $jsonPath = base_path('karyawan_job_position_seed (1).json');
        if (!file_exists($jsonPath)) {
            $this->command->error("File JSON tidak ditemukan di: {$jsonPath}");
            return;
        }

        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $karyawanList = $jsonData['karyawan'];

        // 3. Pastikan semua Job Position ada di MstJobPosition
        $uniqueJobPositions = collect($karyawanList)->pluck('job_position')->unique();
        
        foreach ($uniqueJobPositions as $jpString) {
            $splitPositions = array_map('trim', explode(';', $jpString));

            foreach ($splitPositions as $jpName) {
                if (empty($jpName)) continue;

                $dbName = $jpName;
                // Mapping per instruksi user
                if ($jpName === 'Logistic & Warehouse  Sec Head' || $jpName === 'Logistic & Warehouses Dept Head') {
                    // Fix double spaces or plural typos if necessary, but actually the user only asked for one specific fix before.
                    if ($jpName === 'Logistic & Warehouse  Sec Head') {
                        $dbName = 'Logistic & Warehouse Sec Head';
                    }
                }

                // Cari atau buat baru
                $exists = MstJobPosition::where('position_name', $dbName)->first();
                if (!$exists) {
                    MstJobPosition::create([
                        'position_name' => $dbName,
                        'is_active' => true,
                    ]);
                    $this->command->info("Created missing Job Position: {$dbName}");
                }
            }
        }

        // 4. Pastikan semua User ada di tabel Users (sebagai pengaman tambahan, meskipun user bilang semua sudah ada)
        // Kita hanya mengupdate nama di atas, asumsi semua nama lain sudah pas.

        // 5. Clean up mapping lama (TRUNCATE user_job_positions)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        UserJobPosition::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->command->info('Cleaned up user_job_positions table.');

        // 6. Insert pemetaan baru
        $successCount = 0;
        $errorCount = 0;

        foreach ($karyawanList as $k) {
            $jsonName = trim($k['nama_karyawan']);
            $jsonJob = trim($k['job_position']);

            // Handle multiple positions if separated by semicolon
            $splitJobs = array_map('trim', explode(';', $jsonJob));

            $user = User::where('name', $jsonName)->first();
            if (!$user) {
                $this->command->error("User tidak ditemukan: {$jsonName}");
                $errorCount++;
                continue;
            }

            foreach ($splitJobs as $individualJob) {
                if (empty($individualJob)) continue;

                $dbJobName = $individualJob;
                if ($individualJob === 'Logistic & Warehouse  Sec Head') {
                    $dbJobName = 'Logistic & Warehouse Sec Head';
                }

                $job = MstJobPosition::where('position_name', $dbJobName)->first();

                if ($job) {
                    UserJobPosition::firstOrCreate([
                        'user_id' => $user->id,
                        'mst_job_position_id' => $job->id,
                    ], [
                        'is_active' => true,
                    ]);
                    $successCount++;
                } else {
                    $errorCount++;
                    $this->command->error("Job Position tidak ditemukan: {$dbJobName}");
                }
            }
        }

        $this->command->info("Seeding selesai!");
        $this->command->info("Total di JSON: " . count($karyawanList));
        $this->command->info("Berhasil di-insert: {$successCount}");
        $this->command->info("Gagal/Error: {$errorCount}");

        if ($successCount === 94) {
            $this->command->info("PERFECT MATCH! 94 baris berhasil disinkronisasi.");
        }
    }
}

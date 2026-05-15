<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserJobAccess;
use Illuminate\Database\Seeder;

class UserJobAccessSeeder extends Seeder
{
    /**
     * Seed the tc_user_job_accesses table dari data hardcoded yang lama.
     * Tabel ini menggantikan semua mapping hardcoded user→job_position
     * di TcController, PenilaianTCController, dan TcJobController.
     */
    public function run(): void
    {
        // ================================================================
        // USER-BASED ACCESS (untuk indexTrs, tcShow, createTC, createPenilaian)
        // ================================================================
        $userMappings = [
            'ABDUR RAHMAN AL FAAIZ' => [
                'Admin Cutting Sheet (ACS)',
                'Delivery Staff',
                'Feeder Operator',
                'Feeder Staff',
                'Logistic Foreman',
                'PPC Staff',
                'Warehouse Staff',
            ],
            'MUGI PRAMONO' => [
                'HT Admin',
                'CT MC Foreman',
                'QC Foreman',
                'Cutting Leader',
                'HT Leader',
                'CT MC Operator',
                'Operator MC',
                'HT Operator',
                'Maintenance Operator',
                'Prod HT & QC Sec Head',
            ],
            'RAGIL ISHA RAHMANTO' => [
                'MC Leader',
                'MC Custom Leader',
                'MC Custom Staff',
                'MC Custom Sec Head',
                'Bubut Operator',
                'Machining Operator',
                'MC Custom Operator',
                'Operator MC',
            ],
            'HARDI SAPUTRA' => [
                'Admin Cutting Sheet (ACS)',
                'Delivery Staff',
                'Feeder Operator',
                'Feeder Staff',
                'Logistic Foreman',
                'PPC Staff',
                'Warehouse Staff',
            ],

            'ARY RODJO PRASETYO' => [
                'HT Admin',
                'Cutting Leader',
                'Cutting Operator',
                'CT MC Foreman',
                'MC Leader',
                'MC Custom Leader',
                'QC Foreman',
                'HT Leader',
                'MC Custom Staff',
                'MC Custom Sec Head',
                'Bubut Operator',
                'CT MC Operator',
                'Operator MC',
                'HT Operator',
                'Maintenance Operator',
                'Machining Operator',
                'MC Custom Operator',
                'Prod HT & QC Sec Head',
            ],
            // Nama di DB bisa ADHI PRASETYO atau ADHI PRASETIYO
            'ADHI PRASETYO' => [
                'Accounting Staff',
                'Accounting Sec Head',
                'Finance Sec Head',
                'Finance Staff',
                'Finance Support',
                'Invoicing Staff',
            ],
            'RICHARDUS' => [
                'Accounting Staff',
                'Accounting Sec Head',
                'Finance Sec Head',
                'Finance Staff',
                'Finance Support',
                'Invoicing Staff',
            ],
            'ILHAM CHOLID' => [
                'Sales Admin',
                'Sales Engineer Region 1',
                'Sales Engineer Region 2',
                'Sales Office Head Region 1',
                'Sales Office Head Region 2',
            ],
            'JUN JOHAMIN PD' => [
                'Sales Engineer Region 3',
                'Sales Engineer Region 4',
                'Sales Office Head Region 3&4',
            ],
            'MARTINUS CAHYO RAHASTO' => [
                'Accounting Staff',
                'Accounting Sec Head',
                'Finance Sec Head',
                'Finance Staff',
                'Finance Support',
                'HRGA Staff',
                'HR & Legal Staff',
                'IT Staff',
                'Inventory Staff',
                'Invoicing Staff',
                'Procurement Staff',
                'Dept Head PDCA Proc Inv IT',
            ],
            'JESSICA PAUNE' => [
                'HR, GA, Legal, PDCA, Procurement & IT Se. Head',
                'IT Developer',
                'IT Staff',
                'Inventory Staff',
                'Procurement Staff',
                'Dept Head PDCA Proc Inv IT',
                'Purchasing Import Staff',
            ],
            'YULMAI RIDO WINANDA' => [
                'Sales Admin',
                'Sales Engineer Region 1',
                'Sales Engineer Region 2',
                'Sales Office Head Region 1',
                'Sales Office Head Region 2',
            ],
            'ANDIK TOTOK SISWOYO' => [
                'Sales Engineer Region 3',
                'Sales Engineer Region 4',
                'Sales Office Head Region 3&4',
            ],
            // SITI MARIA ULFA - role_id 1/15 sudah bypass filter,
            // tapi tetap disimpan sebagai dokumentasi
            'SITI MARIA ULFA' => [
                'Admin Cutting Sheet (ACS)',
                'HT Admin',
                'Accounting Staff',
                'Cutting Leader',
                'Delivery Staff',
                'Feeder Operator',
                'Feeder Staff',
                'Accounting Sec Head',
                'Finance Sec Head',
                'CT MC Foreman',
                'MC Custom Leader',
                'QC Foreman',
                'HRGA Staff',
                'HR & Legal Staff',
                'HR, GA, Legal, PDCA, Procurement & IT Se. Head',
                'IT Developer',
                'IT Staff',
                'Inventory Staff',
                'Invoicing Staff',
                'HT Leader',
                'MC Leader',
                'Logistic Foreman',
                'MC Custom Staff',
                'MC Custom Sec Head',
                'Bubut Operator',
                'CT MC Operator',
                'Operator MC',
                'HT Operator',
                'Maintenance Operator',
                'Machining Operator',
                'MC Custom Operator',
                'Procurement Staff',
                'PPC Staff',
                'Prod HT & QC Sec Head',
                'Sales Office Head Region 1',
                'Sales Office Head Region 2',
                'Sales Office Head Region 3&4',
                'Sales Admin',
                'Sales Engineer Region 1',
                'Sales Engineer Region 2',
                'Sales Engineer Region 3',
                'Sales Engineer Region 4',
                'IT Support',
                'Sales Staff',
                'Finance Support',
            ],
        ];

        // Nama alternatif jika nama utama tidak ditemukan di tabel users
        $alternateNames = [
            'ARY RODJO PRASETYO' => ['ARYA RODJO PRASETYO'],
            'ADHI PRASETYO' => ['ADHI PRASETIYO'],
        ];

        foreach ($userMappings as $userName => $positions) {
            // Cari user berdasarkan nama
            $user = User::where('name', $userName)->first();

            // Jika tidak ditemukan, coba nama alternatif
            if (!$user && isset($alternateNames[$userName])) {
                foreach ($alternateNames[$userName] as $altName) {
                    $user = User::where('name', $altName)->first();
                    if ($user) break;
                }
            }

            if (!$user) {
                $this->command->warn("User '{$userName}' tidak ditemukan, skip.");
                continue;
            }

            foreach ($positions as $jobPosition) {
                UserJobAccess::firstOrCreate([
                    'user_id' => $user->id,
                    'job_position' => $jobPosition,
                ]);
            }

            $this->command->info("Berhasil seed akses untuk: {$userName} ({$user->id}) - " . count($positions) . " posisi");
        }

        // ================================================================
        // ROLE-BASED ACCESS (untuk indexTrs2 - Ka. Dept)
        // ================================================================
        $roleMappings = [
            7 => [ // Ka. Sie Logistik / Logistic
                'Admin Cutting Sheet (ACS)',
                'Delivery Staff',
                'Feeder Operator',
                'Logistic Foreman',
                'PPC Staff',
            ],
            5 => [ // Ka. Dept Produksi
                'HT Admin',
                'CT MC Foreman',
                'MC Leader',
                'MC Custom Leader',
                'QC Foreman',
                'Cutting Leader',
                'HT Leader',
                'MC Custom Staff',
                'MC Custom Sec Head',
                'Bubut Operator',
                'CT MC Operator',
                'HT Operator',
                'Maintenance Operator',
                'Machining Operator',
                'MC Custom Operator',
                'Prod HT & QC Sec Head',
            ],
            11 => [ // Ka. Dept Finance & HR
                'Accounting Sec Head',
                'Finance Sec Head',
                'Finance Staff',
                'HRGA Staff',
                'HR & Legal Staff',
                'Invoicing Staff',
            ],
            2 => [ // Ka. Dept Sales
                'Sales Office Head Region 1',
                'Sales Office Head Region 2',
                'Sales Office Head Region 3&4',
                'Sales Admin',
                'Sales Engineer Region 1',
                'Sales Engineer Region 2',
                'Sales Engineer Region 3',
                'Sales Engineer Region 4',
            ],
            14 => [ // Ka. Dept Inventory & IT
                'IT Staff',
                'Inventory Staff',
                'Procurement Staff',
                'Dept Head PDCA Proc Inv IT',
            ],
            3 => [ // Ka. Dept Inventory & IT
                'IT Staff',
                'Inventory Staff',
                'Procurement Staff',
                'Dept Head PDCA Proc Inv IT',
            ],
        ];

        foreach ($roleMappings as $roleId => $positions) {
            foreach ($positions as $jobPosition) {
                UserJobAccess::firstOrCreate([
                    'role_id' => $roleId,
                    'job_position' => $jobPosition,
                ]);
            }

            $this->command->info("Berhasil seed akses untuk role_id: {$roleId} - " . count($positions) . " posisi");
        }
    }
}

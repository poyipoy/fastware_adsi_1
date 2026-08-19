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
use Carbon\Carbon;

class DummyCompetencyTraining2025_2026Seeder extends Seeder
{
    /**
     * Run the database seeds for Competency & Training Development (2025 & 2026).
     * 
     * PERHATIAN (CONSTRAINT):
     * - Tidak mengubah master data Job Position (mst_job_positions)
     * - Tidak mengubah master data Approval (mst_position_approvals)
     * - Tidak mengubah mapping karyawan (user_job_positions, users, roles)
     * 
     * ATURAN BISNIS (STATUS APPROVAL & SALES DIV HEAD):
     * - Status 1: Draf
     * - Status 2: Menunggu Konfirmasi Dept. Head
     * - Status 3: Menunggu Konfirmasi Div. Head -> HANYA untuk posisi di bawah Departemen Sales!
     * - Status 4: Telah Disetujui (Final)
     * - Tahun 2025: 100% berstatus 4 (Telah Disetujui / Final)
     * 
     * CEGAH DUPLIKASI (CHART OVER-SCALE PREVENTION):
     * - Menggunakan updateOrCreate agar tidak terjadi duplikasi nilai aktual bila seeder dijalankan berulang.
     */
    public function run()
    {
        $faker = Faker::create('id_ID');
        $years = [2025, 2026];

        $this->command->info("=== START: Generating Dummy Competency & Training (2025 - 2026) ===");

        // 1. Ambil semua Job Position standar
        $jobPositions = MstJobPosition::with('department')->get();
        $this->command->info("Found " . $jobPositions->count() . " Job Positions.");

        DB::beginTransaction();
        try {
            // STEP A: Pastikan setiap Job Position memiliki item Pengajuan Competency (TC, SK, AD)
            $newTcCount = 0;
            $newSkCount = 0;
            $newAdCount = 0;

            foreach ($jobPositions as $job) {
                // Technical Competency (TC)
                $existingTc = MstTc::where('id_job_position', $job->id)->count();
                if ($existingTc == 0) {
                    $tcItems = [
                        ['keterangan' => 'Penguasaan Prosedur Kerja & Instruksi Kerja (SOP/IK)', 'deskripsi' => 'Mampu menjalankan seluruh tahapan kerja sesuai prosedur operasional standar.'],
                        ['keterangan' => 'Keterampilan Teknis & Operasional Spesifik Jabatan', 'deskripsi' => 'Mampu mengoperasikan peralatan/sistem yang diperlukan dengan efektif.'],
                        ['keterangan' => 'Analisis & Penyelesaian Masalah Teknis (Troubleshooting)', 'deskripsi' => 'Mampu mendeteksi kendala teknis dan mengambil langkah perbaikan yang tepat.'],
                    ];
                    foreach ($tcItems as $item) {
                        MstTc::create([
                            'id_job_position' => $job->id,
                            'id_poin_kategori' => 3, // Standar level 3
                            'keterangan_tc' => $item['keterangan'],
                            'deskripsi_tc' => $item['deskripsi'],
                            'nilai' => 3,
                        ]);
                        $newTcCount++;
                    }
                }

                // Soft Skill (SK)
                $existingSk = MstSoftSkill::where('id_job_position', $job->id)->count();
                if ($existingSk == 0) {
                    $skItems = [
                        ['keterangan' => 'Communication Skills & Kerjasama Tim', 'deskripsi' => 'Berkomunikasi jelas dan bekerja sama secara proaktif dengan tim.'],
                        ['keterangan' => 'Leadership & Manajemen Tugas', 'deskripsi' => 'Mengatur tanggung jawab serta memberikan bimbingan rekan kerja.'],
                        ['keterangan' => 'Problem Solving & Pengambilan Keputusan', 'deskripsi' => 'Menganalisis situasi secara logis dan menentukan solusi efektif.'],
                    ];
                    foreach ($skItems as $item) {
                        MstSoftSkill::create([
                            'id_job_position' => $job->id,
                            'id_poin_kategori' => 3,
                            'keterangan_sk' => $item['keterangan'],
                            'deskripsi_sk' => $item['deskripsi'],
                            'nilai' => 3,
                        ]);
                        $newSkCount++;
                    }
                }

                // Additional Competencies (AD)
                $existingAd = MstAdditionals::where('id_job_position', $job->id)->count();
                if ($existingAd == 0) {
                    $adItems = [
                        ['keterangan' => 'LK3 Awareness & Keselamatan Kerja', 'deskripsi' => 'Mematuhi standar K3 serta mengenali potensi bahaya kerja.'],
                        ['keterangan' => 'Penerapan 5R di Area Kerja', 'deskripsi' => 'Menjaga kerapihan, kebersihan, dan ketertiban area kerja.'],
                        ['keterangan' => 'Emergency Preparedness & First Aid', 'deskripsi' => 'Siap bertindak cepat dalam situasi darurat.'],
                    ];
                    foreach ($adItems as $item) {
                        MstAdditionals::create([
                            'id_job_position' => $job->id,
                            'id_poin_kategori' => 2,
                            'keterangan_ad' => $item['keterangan'],
                            'deskripsi_ad' => $item['deskripsi'],
                            'nilai' => 2,
                        ]);
                        $newAdCount++;
                    }
                }
            }

            $this->command->info("Seeded Competency Items - TC: +{$newTcCount}, SK: +{$newSkCount}, AD: +{$newAdCount}");

            // STEP B: Generate Penilaian & Training untuk Karyawan (2025 & 2026)
            $users = User::with('userJobPositions.jobPosition.department')->get();
            $targetUsers = [];
            $assessorName = 'System Admin (Seeder)';

            // Filter karyawan yang punya job position
            foreach ($users as $user) {
                if ($user->userJobPositions->isNotEmpty()) {
                    $targetUsers[] = $user;
                }
            }

            $this->command->info("Found " . count($targetUsers) . " employees with job position mappings.");

            $trsCount = 0;
            $detailCount = 0;
            $trainingCount = 0;

            foreach ($targetUsers as $user) {
                // Ambil job position utama karyawan
                $primaryJob = null;
                foreach ($user->userJobPositions as $ujp) {
                    if ($ujp->jobPosition) {
                        $primaryJob = $ujp->jobPosition;
                        break;
                    }
                }
                if (!$primaryJob) continue;

                // Cek apakah job position ini berada di bawah Departemen Sales
                $isSalesDept = false;
                if ($primaryJob->department && (stripos($primaryJob->department->name, 'sales') !== false || stripos($primaryJob->department->name, 'marketing') !== false)) {
                    $isSalesDept = true;
                } elseif (stripos($primaryJob->position_name, 'sales') !== false || stripos($primaryJob->position_name, 'soh') !== false) {
                    $isSalesDept = true;
                }

                $tcs = MstTc::where('id_job_position', $primaryJob->id)->get();
                $sks = MstSoftSkill::where('id_job_position', $primaryJob->id)->get();
                $ads = MstAdditionals::where('id_job_position', $primaryJob->id)->get();

                if ($tcs->isEmpty() && $sks->isEmpty() && $ads->isEmpty()) continue;

                foreach ($years as $year) {
                    // Status 2025: 100% berstatus 4 (Telah Disetujui / Final)
                    // Status 2026:
                    // - Jika Departemen Sales: Bisa status 1, 2 (Dept Head), 3 (Div Head), atau 4 (Final).
                    // - Jika Non-Sales: HANYA bisa status 1, 2 (Dept Head), atau 4 (Final). TIDAK PERNAH 3!
                    if ($year == 2025) {
                        $status = 4;
                    } else {
                        if ($isSalesDept) {
                            $status = $faker->randomElement([1, 2, 3, 4]); // Sales boleh 3 (Div Head)
                        } else {
                            $status = $faker->randomElement([1, 2, 4, 4]); // Non-Sales TIDAK PERNAH 3
                        }
                    }

                    $isLocked = ($year == 2025);

                    // --- 1. SEED PENILAIAN TC (Gunakan updateOrCreate agar tidak duplikat) ---
                    foreach ($tcs as $tc) {
                        $target = $tc->nilai ?? 3;
                        $actual = $faker->numberBetween(max(1, $target - 1), 4);

                        TrsPenilaianTc::updateOrCreate(
                            [
                                'id_user' => $user->id,
                                'id_job_position' => $primaryJob->id,
                                'tahun_penilaian' => $year,
                                'id_tc' => $tc->id,
                            ],
                            [
                                'id_sk' => null,
                                'id_ad' => null,
                                'nilai_tc' => $actual,
                                'nilai_sk' => null,
                                'nilai_ad' => null,
                                'status' => $status,
                                'is_locked' => $isLocked,
                                'modified_at' => $user->id,
                                'modified_updated' => $assessorName,
                            ]
                        );
                        $trsCount++;
                    }

                    // --- 2. SEED PENILAIAN SK (Gunakan updateOrCreate agar tidak duplikat) ---
                    foreach ($sks as $sk) {
                        $target = $sk->nilai ?? 3;
                        $actual = $faker->numberBetween(max(1, $target - 1), 4);

                        TrsPenilaianTc::updateOrCreate(
                            [
                                'id_user' => $user->id,
                                'id_job_position' => $primaryJob->id,
                                'tahun_penilaian' => $year,
                                'id_sk' => $sk->id,
                            ],
                            [
                                'id_tc' => null,
                                'id_ad' => null,
                                'nilai_tc' => null,
                                'nilai_sk' => $actual,
                                'nilai_ad' => null,
                                'status' => $status,
                                'is_locked' => $isLocked,
                                'modified_at' => $user->id,
                                'modified_updated' => $assessorName,
                            ]
                        );
                        $trsCount++;
                    }

                    // --- 3. SEED PENILAIAN AD (Gunakan updateOrCreate agar tidak duplikat) ---
                    foreach ($ads as $ad) {
                        $target = $ad->nilai ?? 2;
                        $actual = $faker->numberBetween(1, 4);

                        TrsPenilaianTc::updateOrCreate(
                            [
                                'id_user' => $user->id,
                                'id_job_position' => $primaryJob->id,
                                'tahun_penilaian' => $year,
                                'id_ad' => $ad->id,
                            ],
                            [
                                'id_tc' => null,
                                'id_sk' => null,
                                'nilai_tc' => null,
                                'nilai_sk' => null,
                                'nilai_ad' => $actual,
                                'status' => $status,
                                'is_locked' => $isLocked,
                                'modified_at' => $user->id,
                                'modified_updated' => $assessorName,
                            ]
                        );
                        $trsCount++;
                    }

                    // --- 4. DETAIL PENILAIAN LOG ---
                    DetailTcPenilaian::updateOrCreate(
                        [
                            'id_job_position' => $primaryJob->id,
                            'name' => $user->name,
                            'keterangan_detail' => 'Penilaian Kompetensi Tahunan ' . $year,
                        ],
                        [
                            'nilai_sebelum' => json_encode([]),
                            'catatan' => 'Evaluasi kompetensi reguler tahun ' . $year,
                            'modified_at' => $assessorName,
                        ]
                    );
                    $detailCount++;

                    // --- 5. SEED TRAINING DEVELOPMENT / PEOPLE DEVELOPMENT ---
                    $numTrainings = $faker->numberBetween(1, 2);
                    for ($t = 0; $t < $numTrainings; $t++) {
                        $selectedTc = $tcs->isNotEmpty() ? $tcs->random() : null;
                        $compText = $selectedTc ? $selectedTc->keterangan_tc : 'Professional Development Training';
                        $kategori = $selectedTc ? 'Technical' : 'Non Technical';

                        // Status Training: 2025 = Done/Approved, 2026 = Variasi
                        $status1 = ($year == 2025) ? 3 : $faker->randomElement([1, 2, 3]);
                        $status2 = ($year == 2025) ? 'Done' : $faker->randomElement(['Mencari Vendor', 'Proses Pendaftaran', 'On Progress', 'Done', 'Pending']);
                        
                        $budget = $faker->randomElement([750000, 1500000, 2500000, 3500000]);
                        $vendor = $faker->company;
                        $dateMonth = $faker->numberBetween(1, 12);
                        $dateDay = $faker->numberBetween(1, 28);
                        $dueDate = Carbon::createFromDate($year, $dateMonth, $dateDay)->format('Y-m-d');

                        TcPeopleDevelopment::updateOrCreate(
                            [
                                'id_user' => $user->id,
                                'id_job_position' => $primaryJob->id,
                                'tahun_usulan' => $year,
                                'competency' => $compText,
                            ],
                            [
                                'id_role' => $user->role_id ?? 1,
                                'section_id' => $primaryJob->section_id ?? 1,
                                'id_tc' => $selectedTc ? $selectedTc->id : null,
                                'id_sk' => null,
                                'id_ad' => null,
                                'id_trs' => null,
                                'program_training' => 'Pelatihan & Sertifikasi: ' . $compText,
                                'program_training_plan' => 'Pelatihan & Sertifikasi: ' . $compText,
                                'kategori_competency' => $kategori,
                                'due_date' => $dueDate,
                                'due_date_plan' => $dueDate,
                                'lembaga' => $vendor,
                                'lembaga_plan' => $vendor,
                                'keterangan_tujuan' => 'Peningkatan kapasitas dan keahlian untuk ' . $compText,
                                'keterangan_plan' => 'Peningkatan kapasitas dan keahlian untuk ' . $compText,
                                'biaya' => $budget,
                                'biaya_plan' => $budget,
                                'status_1' => $status1,
                                'status_2' => $status2,
                                'modified_at' => (string) $user->id,
                                'tahun_aktual' => $year,
                            ]
                        );
                        $trainingCount++;
                    }
                }
            }

            DB::commit();
            $this->command->info("=== SEEDING COMPLETED SUCCESSFULLY ===");
            $this->command->info("TrsPenilaianTc generated/updated: {$trsCount}");
            $this->command->info("DetailTcPenilaian generated/updated: {$detailCount}");
            $this->command->info("TcPeopleDevelopment generated/updated: {$trainingCount}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("ERROR during seeding: " . $e->getMessage());
        }
    }
}

<?php

namespace App\Services\HR;

use Illuminate\Support\Facades\DB;

/**
 * Service untuk logika bisnis modul Training Development (People Development).
 *
 * Memindahkan logika berat keluar dari PdController agar controller
 * tetap ramping dan mudah diuji.
 */
class TrainingDevelopmentService
{
    /**
     * Mendapatkan daftar penilaian kompetensi yang sudah disetujui (status=3)
     * untuk daftar job position yang diizinkan.
     *
     * @param array<string> $allowedJobs Daftar nama job position yang dapat diakses user
     * @return \Illuminate\Support\Collection
     */
    public function getApprovedCompetencyPenilaians(array $allowedJobs): \Illuminate\Support\Collection
    {
        if (empty($allowedJobs)) {
            return collect();
        }

        $technicalPenilaians = DB::table('trs_penilaian_tcs as p')
            ->join('mst_tcs as tc', 'p.id_tc', '=', 'tc.id')
            ->select(
                DB::raw("'technical' as category"),
                'tc.keterangan_tc as keterangan',
                'tc.nilai as nilai_standard',
                'p.nilai_tc as nilai_aktual',
                'p.id_user',
                'p.id_job_position',
                'p.id_tc',
                DB::raw('NULL as id_sk'),
                DB::raw('NULL as id_ad')
            )
            ->where('p.status', 4)
            ->whereIn('p.id_job_position', $allowedJobs)
            ->whereNotNull('p.id_tc')
            ->whereRaw('p.nilai_tc < tc.nilai')
            ->get();

        $nonTechnicalPenilaians = DB::table('trs_penilaian_tcs as p')
            ->join('mst_soft_skills as sk', 'p.id_sk', '=', 'sk.id')
            ->select(
                DB::raw("'softskill' as category"),
                'sk.keterangan_sk as keterangan',
                'sk.nilai as nilai_standard',
                'p.nilai_sk as nilai_aktual',
                'p.id_user',
                'p.id_job_position',
                DB::raw('NULL as id_tc'),
                'p.id_sk',
                DB::raw('NULL as id_ad')
            )
            ->where('p.status', 4)
            ->whereIn('p.id_job_position', $allowedJobs)
            ->whereNotNull('p.id_sk')
            ->whereRaw('p.nilai_sk < sk.nilai')
            ->get();

        $additionalPenilaians = DB::table('trs_penilaian_tcs as p')
            ->join('mst_additionals as ad', 'p.id_ad', '=', 'ad.id')
            ->select(
                DB::raw("'additional' as category"),
                'ad.keterangan_ad as keterangan',
                'ad.nilai as nilai_standard',
                'p.nilai_ad as nilai_aktual',
                'p.id_user',
                'p.id_job_position',
                DB::raw('NULL as id_tc'),
                DB::raw('NULL as id_sk'),
                'p.id_ad'
            )
            ->where('p.status', 4)
            ->whereIn('p.id_job_position', $allowedJobs)
            ->whereNotNull('p.id_ad')
            ->whereRaw('p.nilai_ad < ad.nilai')
            ->get();

        return $technicalPenilaians
            ->merge($nonTechnicalPenilaians)
            ->merge($additionalPenilaians)
            ->map(function ($penilaian) {
                $penilaian->competency_option = $this->formatCompetencyOption(
                    $penilaian->keterangan,
                    $penilaian->nilai_standard,
                    $penilaian->nilai_aktual
                );

                return $penilaian;
            })
            ->values();
    }

    /**
     * Validasi bahwa setiap baris data competency yang disubmit user
     * sesuai dengan data penilaian yang ada di database.
     *
     * @param \Illuminate\Support\Collection $penilaians Hasil getApprovedCompetencyPenilaians
     * @param array<string, mixed>           $data       Data dari request
     * @param string                         $prefix     Prefix field name (kosong atau 'new_')
     * @return array<string>                             List pesan error (kosong = valid)
     */
    public function validateCompetencyRows(\Illuminate\Support\Collection $penilaians, array $data, string $prefix = ''): array
    {
        $lookup = $penilaians->mapWithKeys(function ($penilaian) {
            return [
                $this->competencyLookupKey(
                    $penilaian->id_user,
                    $penilaian->id_job_position,
                    $penilaian->category,
                    $penilaian->competency_option
                ) => true,
            ];
        });

        $users       = $data[$prefix . 'id_user'] ?? [];
        $jobs        = $data[$prefix . 'id_job_position'] ?? [];
        $categories  = $data[$prefix . 'kategori_competency'] ?? [];
        $competencies = $data[$prefix . 'competency'] ?? [];
        $rowCount    = max(count($users), count($jobs), count($categories), count($competencies));
        $errors      = [];

        for ($index = 0; $index < $rowCount; $index++) {
            $userId     = $users[$index] ?? null;
            $jobPosition = trim((string) ($jobs[$index] ?? ''));
            $category   = $this->normalizeCompetencyCategory($categories[$index] ?? '');
            $competency = trim((string) ($competencies[$index] ?? ''));

            if (!$userId || $jobPosition === '' || $category === '' || $competency === '') {
                $errors[] = 'Baris ' . ($index + 1) . ': data employee/job position/competency belum lengkap.';
                continue;
            }

            if (!in_array($category, ['technical', 'softskill', 'additional'], true)) {
                $errors[] = 'Baris ' . ($index + 1) . ': kategori competency harus berasal dari data kompetensi karyawan.';
                continue;
            }

            $key = $this->competencyLookupKey($userId, $jobPosition, $category, $competency);
            if (!$lookup->has($key)) {
                $errors[] = 'Baris ' . ($index + 1) . ': competency tidak sesuai dengan data kompetensi karyawan/job position terpilih.';
            }
        }

        return $errors;
    }

    /**
     * Membuat kunci unik untuk lookup validasi competency.
     *
     * @param mixed  $userId
     * @param string $jobPosition
     * @param string $category
     * @param string $competency
     * @return string
     */
    public function competencyLookupKey($userId, string $jobPosition, string $category, string $competency): string
    {
        return implode('|', [
            (int) $userId,
            mb_strtoupper(trim($jobPosition)),
            $this->normalizeCompetencyCategory($category),
            mb_strtoupper(trim($competency)),
        ]);
    }

    /**
     * Memformat opsi competency untuk tampilan di select dropdown.
     *
     * @param string|null $keterangan
     * @param mixed       $nilaiStandard
     * @param mixed       $nilaiAktual
     * @return string
     */
    public function formatCompetencyOption($keterangan, $nilaiStandard, $nilaiAktual): string
    {
        return trim((string) $keterangan) . ' - std: ' . $nilaiStandard . ' - aktual: ' . $nilaiAktual;
    }

    /**
     * Normalisasi kategori competency ke nilai standar.
     *
     * @param string|null $category
     * @return string
     */
    public function normalizeCompetencyCategory(?string $category): string
    {
        $category = strtolower(trim((string) $category));

        return match ($category) {
            'technical', 'technical competency' => 'technical',
            'nontechnical', 'non technical', 'non-technical', 'softskill', 'soft skill', 'soft-skill' => 'softskill',
            'additional' => 'additional',
            default => $category,
        };
    }
}

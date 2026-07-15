<?php

namespace App\Services\Competency;

use Illuminate\Support\Facades\DB;

/**
 * Service reusable untuk logika pembandingan nilai aktual vs standar kompetensi.
 *
 * Dipakai bersama oleh:
 * - Modul 1.2: Tabel Strength pada dsDetailCompetency
 * - Modul 2.3: Badge Nama Karyawan (mentor kandidat) pada Area Development Dashboard TCPD
 */
class CompetencyAssessmentService
{
    /**
     * Ambil daftar kompetensi STRENGTH untuk seorang karyawan.
     * Strength = nilai aktual (total_nilai) >= nilai standar (mst nilai).
     *
     * @param int $userId
     * @return array  Array of ['type', 'id', 'name', 'standard', 'actual']
     */
    public function getStrengthCompetencies(int $userId): array
    {
        $result = [];

        // --- TC ---
        $tcStrength = DB::table('trs_penilaian_tcs as tpt')
            ->join('mst_tcs as tc', 'tpt.id_tc', '=', 'tc.id')
            ->select(
                DB::raw("'tc' as type"),
                'tc.id',
                'tc.keterangan_tc as name',
                DB::raw('MAX(tc.nilai) as standard'),
                DB::raw('AVG(tpt.nilai_tc) as actual')
            )
            ->where('tpt.id_user', $userId)
            ->whereNotNull('tpt.id_tc')
            ->groupBy('tpt.id_tc', 'tc.id', 'tc.keterangan_tc')
            ->havingRaw('AVG(tpt.nilai_tc) >= MAX(tc.nilai)')
            ->get()->toArray();

        // --- SK ---
        $skStrength = DB::table('trs_penilaian_tcs as tpt')
            ->join('mst_soft_skills as sk', 'tpt.id_sk', '=', 'sk.id')
            ->select(
                DB::raw("'sk' as type"),
                'sk.id',
                'sk.keterangan_sk as name',
                DB::raw('MAX(sk.nilai) as standard'),
                DB::raw('AVG(tpt.nilai_sk) as actual')
            )
            ->where('tpt.id_user', $userId)
            ->whereNotNull('tpt.id_sk')
            ->groupBy('tpt.id_sk', 'sk.id', 'sk.keterangan_sk')
            ->havingRaw('AVG(tpt.nilai_sk) >= MAX(sk.nilai)')
            ->get()->toArray();

        // --- AD ---
        $adStrength = DB::table('trs_penilaian_tcs as tpt')
            ->join('mst_additionals as ad', 'tpt.id_ad', '=', 'ad.id')
            ->select(
                DB::raw("'ad' as type"),
                'ad.id',
                'ad.keterangan_ad as name',
                DB::raw('MAX(ad.nilai) as standard'),
                DB::raw('AVG(tpt.nilai_ad) as actual')
            )
            ->where('tpt.id_user', $userId)
            ->whereNotNull('tpt.id_ad')
            ->groupBy('tpt.id_ad', 'ad.id', 'ad.keterangan_ad')
            ->havingRaw('AVG(tpt.nilai_ad) >= MAX(ad.nilai)')
            ->get()->toArray();

        foreach ([$tcStrength, $skStrength, $adStrength] as $group) {
            foreach ($group as $row) {
                $result[] = [
                    'type'     => $row->type,
                    'id'       => $row->id,
                    'name'     => $row->name,
                    'standard' => (float) $row->standard,
                    'actual'   => (float) $row->actual,
                ];
            }
        }

        return $result;
    }

    /**
     * Ambil daftar kompetensi AREA DEVELOPMENT untuk seorang karyawan.
     * Area Development = nilai aktual < nilai standar.
     *
     * @param int $userId
     * @return array  Array of ['type', 'id', 'name', 'standard', 'actual']
     */
    public function getAreaDevelopmentCompetencies(int $userId): array
    {
        $result = [];

        // --- TC ---
        $tcArea = DB::table('trs_penilaian_tcs as tpt')
            ->join('mst_tcs as tc', 'tpt.id_tc', '=', 'tc.id')
            ->select(
                DB::raw("'tc' as type"),
                'tc.id',
                'tc.keterangan_tc as name',
                DB::raw('MAX(tc.nilai) as standard'),
                DB::raw('AVG(tpt.nilai_tc) as actual')
            )
            ->where('tpt.id_user', $userId)
            ->whereNotNull('tpt.id_tc')
            ->groupBy('tpt.id_tc', 'tc.id', 'tc.keterangan_tc')
            ->havingRaw('AVG(tpt.nilai_tc) < MAX(tc.nilai)')
            ->get()->toArray();

        // --- SK ---
        $skArea = DB::table('trs_penilaian_tcs as tpt')
            ->join('mst_soft_skills as sk', 'tpt.id_sk', '=', 'sk.id')
            ->select(
                DB::raw("'sk' as type"),
                'sk.id',
                'sk.keterangan_sk as name',
                DB::raw('MAX(sk.nilai) as standard'),
                DB::raw('AVG(tpt.nilai_sk) as actual')
            )
            ->where('tpt.id_user', $userId)
            ->whereNotNull('tpt.id_sk')
            ->groupBy('tpt.id_sk', 'sk.id', 'sk.keterangan_sk')
            ->havingRaw('AVG(tpt.nilai_sk) < MAX(sk.nilai)')
            ->get()->toArray();

        // --- AD ---
        $adArea = DB::table('trs_penilaian_tcs as tpt')
            ->join('mst_additionals as ad', 'tpt.id_ad', '=', 'ad.id')
            ->select(
                DB::raw("'ad' as type"),
                'ad.id',
                'ad.keterangan_ad as name',
                DB::raw('MAX(ad.nilai) as standard'),
                DB::raw('AVG(tpt.nilai_ad) as actual')
            )
            ->where('tpt.id_user', $userId)
            ->whereNotNull('tpt.id_ad')
            ->groupBy('tpt.id_ad', 'ad.id', 'ad.keterangan_ad')
            ->havingRaw('AVG(tpt.nilai_ad) < MAX(ad.nilai)')
            ->get()->toArray();

        foreach ([$tcArea, $skArea, $adArea] as $group) {
            foreach ($group as $row) {
                $result[] = [
                    'type'     => $row->type,
                    'id'       => $row->id,
                    'name'     => $row->name,
                    'standard' => (float) $row->standard,
                    'actual'   => (float) $row->actual,
                ];
            }
        }

        return $result;
    }

    /**
     * Ambil daftar karyawan yang memenuhi standar untuk suatu kompetensi tertentu.
     * Dipakai untuk badge mentor pada Area Development di Dashboard TCPD (Modul 2.3).
     *
     * @param string $type         'tc' | 'sk' | 'ad'
     * @param int    $competencyId id dari mst_tcs / mst_soft_skills / mst_additionals
     * @param int    $excludeUserId Karyawan yang sedang ditampilkan (di-exclude dari hasil)
     * @return array  Array of ['id', 'name']
     */
    public function getEmployeesMeetingStandard(string $type, int $competencyId, int $excludeUserId = 0): array
    {
        switch ($type) {
            case 'tc':
            case 'technical':
                $menteeComp = DB::table('mst_tcs')->where('id', $competencyId)->first();
                if (!$menteeComp) return [];
                $compName = $menteeComp->keterangan_tc;
                $menteeStandard = (float) $menteeComp->nilai;
                $allIds = DB::table('mst_tcs')->where('keterangan_tc', $compName)->pluck('id');

                $rows = DB::table('trs_penilaian_tcs as tpt')
                    ->join('users as u', 'tpt.id_user', '=', 'u.id')
                    ->join('mst_tcs as tc', 'tpt.id_tc', '=', 'tc.id')
                    ->leftJoin('user_job_positions as ujp', 'u.id', '=', 'ujp.user_id')
                    ->leftJoin('mst_job_positions as mjp', 'ujp.mst_job_position_id', '=', 'mjp.id')
                    ->select('u.id', 'u.name',
                        DB::raw('MAX(mjp.position_name) as job_position'),
                        DB::raw('AVG(tpt.nilai_tc) as actual'),
                        DB::raw('MAX(tc.nilai) as standard'))
                    ->whereIn('tpt.id_tc', $allIds)
                    ->where('tpt.id_user', '!=', $excludeUserId)
                    ->whereNotNull('tpt.id_tc')
                    ->groupBy('u.id', 'u.name')
                    ->havingRaw('AVG(tpt.nilai_tc) >= MAX(tc.nilai) AND AVG(tpt.nilai_tc) >= ?', [$menteeStandard])
                    ->orderByDesc('actual')
                    ->orderBy('u.name')
                    ->get()->toArray();
                break;

            case 'sk':
            case 'soft_skill':
                $menteeComp = DB::table('mst_soft_skills')->where('id', $competencyId)->first();
                if (!$menteeComp) return [];
                $compName = $menteeComp->keterangan_sk;
                $menteeStandard = (float) $menteeComp->nilai;
                $allIds = DB::table('mst_soft_skills')->where('keterangan_sk', $compName)->pluck('id');

                $rows = DB::table('trs_penilaian_tcs as tpt')
                    ->join('users as u', 'tpt.id_user', '=', 'u.id')
                    ->join('mst_soft_skills as sk', 'tpt.id_sk', '=', 'sk.id')
                    ->leftJoin('user_job_positions as ujp', 'u.id', '=', 'ujp.user_id')
                    ->leftJoin('mst_job_positions as mjp', 'ujp.mst_job_position_id', '=', 'mjp.id')
                    ->select('u.id', 'u.name',
                        DB::raw('MAX(mjp.position_name) as job_position'),
                        DB::raw('AVG(tpt.nilai_sk) as actual'),
                        DB::raw('MAX(sk.nilai) as standard'))
                    ->whereIn('tpt.id_sk', $allIds)
                    ->where('tpt.id_user', '!=', $excludeUserId)
                    ->whereNotNull('tpt.id_sk')
                    ->groupBy('u.id', 'u.name')
                    ->havingRaw('AVG(tpt.nilai_sk) >= MAX(sk.nilai) AND AVG(tpt.nilai_sk) >= ?', [$menteeStandard])
                    ->orderByDesc('actual')
                    ->orderBy('u.name')
                    ->get()->toArray();
                break;

            case 'ad':
            case 'additional':
                $menteeComp = DB::table('mst_additionals')->where('id', $competencyId)->first();
                if (!$menteeComp) return [];
                $compName = $menteeComp->keterangan_ad;
                $menteeStandard = (float) $menteeComp->nilai;
                $allIds = DB::table('mst_additionals')->where('keterangan_ad', $compName)->pluck('id');

                $rows = DB::table('trs_penilaian_tcs as tpt')
                    ->join('users as u', 'tpt.id_user', '=', 'u.id')
                    ->join('mst_additionals as ad', 'tpt.id_ad', '=', 'ad.id')
                    ->leftJoin('user_job_positions as ujp', 'u.id', '=', 'ujp.user_id')
                    ->leftJoin('mst_job_positions as mjp', 'ujp.mst_job_position_id', '=', 'mjp.id')
                    ->select('u.id', 'u.name',
                        DB::raw('MAX(mjp.position_name) as job_position'),
                        DB::raw('AVG(tpt.nilai_ad) as actual'),
                        DB::raw('MAX(ad.nilai) as standard'))
                    ->whereIn('tpt.id_ad', $allIds)
                    ->where('tpt.id_user', '!=', $excludeUserId)
                    ->whereNotNull('tpt.id_ad')
                    ->groupBy('u.id', 'u.name')
                    ->havingRaw('AVG(tpt.nilai_ad) >= MAX(ad.nilai) AND AVG(tpt.nilai_ad) >= ?', [$menteeStandard])
                    ->orderByDesc('actual')
                    ->orderBy('u.name')
                    ->get()->toArray();
                break;

            default:
                return [];
        }

        return array_map(fn($row) => [
            'id' => (int) $row->id,
            'name' => $row->name,
            'job_position' => $row->job_position,
            'actual' => is_numeric($row->actual) ? round((float) $row->actual, 2) : $row->actual,
            'standard' => is_numeric($row->standard) ? round((float) $row->standard, 2) : $row->standard,
        ], $rows);
    }


    /**
     * Hitung matriks persentase untuk Key Positions.
     * Mengembalikan % memenuhi standar vs % defisit dari seluruh asesmen kompetensi.
     *
     * @return array ['meets_standard_pct' => float, 'deficit_pct' => float,
     *                'meets_count' => int, 'deficit_count' => int, 'total' => int,
     *                'positions' => array]
     */
    public function getKeyPositionStats(): array
    {
        // Ambil semua user yang menjabat key positions
        $keyPositionUserIds = DB::table('user_job_positions as ujp')
            ->join('mst_job_positions as mjp', 'ujp.mst_job_position_id', '=', 'mjp.id')
            ->where('mjp.is_key_position', true)
            ->where('ujp.is_active', true)
            ->pluck('ujp.user_id')
            ->unique()
            ->values()
            ->toArray();

        if (empty($keyPositionUserIds)) {
            return [
                'meets_standard_pct' => 0,
                'deficit_pct'        => 0,
                'meets_count'        => 0,
                'deficit_count'      => 0,
                'total'              => 0,
                'positions'          => [],
            ];
        }

        $meetsCount   = 0;
        $deficitCount = 0;
        $positions    = [];

        foreach ($keyPositionUserIds as $userId) {
            $strengthCount  = count($this->getStrengthCompetencies($userId));
            $deficitCountUser = count($this->getAreaDevelopmentCompetencies($userId));

            $meetsCount   += $strengthCount;
            $deficitCount += $deficitCountUser;

            // Ambil nama user untuk detail
            $userName = DB::table('users')->where('id', $userId)->value('name') ?? "User #$userId";
            $positions[] = [
                'user_id'        => $userId,
                'name'           => $userName,
                'meets_count'    => $strengthCount,
                'deficit_count'  => $deficitCountUser,
            ];
        }

        $total = $meetsCount + $deficitCount;

        return [
            'meets_standard_pct' => $total > 0 ? round(($meetsCount / $total) * 100, 1) : 0,
            'deficit_pct'        => $total > 0 ? round(($deficitCount / $total) * 100, 1) : 0,
            'meets_count'        => $meetsCount,
            'deficit_count'      => $deficitCount,
            'total'              => $total,
            'positions'          => $positions,
        ];
    }
}

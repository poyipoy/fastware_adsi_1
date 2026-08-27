<?php

namespace App\Http\Controllers;

use App\Models\MstJobPosition;
use App\Models\TrsPenilaianTc;
use App\Models\TcPeopleDevelopment;
use App\Models\PoinKategori;
use App\Models\User;
use App\Models\DetailTcPenilaian;
use App\Services\HR\HRRoleAccessService;
use App\Services\HR\JobPositionAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

// Import the DB facade

class PenilaianTCController extends Controller
{
    public function __construct(
        private JobPositionAccessService $jobPositionAccess,
        private HRRoleAccessService $roleAccess
    ) {
    }

    private function normalizeScoreValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || strtolower($trimmed) === 'null') {
                return null;
            }
            $value = $trimmed;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Gabungkan posisi dari user_id + role_id di tc_user_job_accesses.
     */
    private function getAccessiblePositions(User $user): array
    {
        return $this->jobPositionAccess
            ->getAccessibleJobPositionNames($user)
            ->all();
    }

    private function abortUnlessCompetencyLevel(string $level): void
    {
        abort_unless(
            $this->roleAccess->canAccessCompetencyLevel(auth()->user(), $level),
            403,
            'Anda tidak memiliki akses untuk level penilaian ini.'
        );
    }

    private function abortUnlessJobPositionAccessible(string $jobPosition): void
    {
        $user = auth()->user();
        $hasAccess = $this->jobPositionAccess->canAccessJobPosition($user, $jobPosition);
        
        Log::info("Access Check:", [
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : null,
            'input_job_position' => $jobPosition,
            'has_access' => $hasAccess ? 'yes' : 'no'
        ]);

        abort_unless(
            $hasAccess,
            403,
            'Anda tidak memiliki akses untuk job position ini.'
        );
    }

    private function forbiddenJson(string $message = 'Anda tidak memiliki akses untuk aksi ini.')
    {
        return response()->json(['success' => false, 'message' => $message], 403);
    }



    /**
     * Halaman Penilaian — satu route untuk Ka. Sie, Ka. Dept, dan HR.
     * Gunakan ?level=kasie|kadept|hr untuk bedakan konteks.
     */
    public function indexTrs(Request $request)
    {
        $level = $request->query('level', 'kasie');
        $user = auth()->user();
        $level = in_array($level, ['kasie', 'kadept', 'divhead', 'hr'], true) ? $level : 'kasie';

        $this->abortUnlessCompetencyLevel($level);

        // [3] Auto-lock data tahun lama
        TrsPenilaianTc::lockPreviousYears();

        // [4] Status 0 = Draft, ditampilkan untuk kasie
        $statusFilters = match ($level) {
            'kasie' => [0, 1, 2, 3, 4],
            'kadept' => [2, 3, 4],
            'divhead' => [3, 4],
            'hr' => [0, 1, 2, 3, 4],
        };

        // [3] Filter tahun — default ke tahun terbaru yang memiliki data penilaian di database
        $availableYears = TrsPenilaianTc::getAvailableYears();
        if (empty($availableYears)) {
            $availableYears = [now()->year];
        }
        $selectedYear = (int) $request->query('year', $availableYears[0]);
        $selectedStatus = $request->query('status');

        $query = TrsPenilaianTc::with(['jobPosition', 'jobPosition.department', 'user'])
            ->whereIn('status', $statusFilters)
            ->forYear($selectedYear);

        if ($selectedStatus !== null && $selectedStatus !== '') {
            $query->where('status', (int) $selectedStatus);
        }

        $penilaianData = $query->orderByDesc('status')->get();

        $approvalScope = $this->jobPositionAccess->getUserApprovalScope($user);
        $hasFullAccess = $this->jobPositionAccess->hasFullAccess($user);

        // Filter data berdasarkan scope Department/Section approver
        if (!$hasFullAccess) {
            $penilaianData = $penilaianData->filter(function ($item) use ($level, $approvalScope) {
                $jobPos = $item->jobPosition;
                if (!$jobPos) return false;

                if ($level === 'divhead') {
                    return in_array($jobPos->department_id, $approvalScope['div_dept_ids']);
                }
                if ($level === 'kadept') {
                    return in_array($jobPos->department_id, $approvalScope['dept_ids']);
                }

                // Level Kasie / Section Head:
                if ($jobPos->section_id) {
                    return in_array($jobPos->section_id, $approvalScope['section_ids']);
                }

                // Dynamic fallback for shared positions (section_id is null)
                $userSectionName = $item->user?->section;
                if ($userSectionName) {
                    $resolvedSectionId = \App\Models\MstSection::where('name', $userSectionName)->value('id');
                    if ($resolvedSectionId) {
                        return in_array($resolvedSectionId, $approvalScope['section_ids']);
                    }
                }

                return false;
            });
        }

        // Group by job position for the index list
        $penilaianData = $penilaianData->unique(fn($item) => trim((string) $item->id_job_position))
            ->values();

        // Khusus Div Head: Hanya tampilkan data yang memiliki konfigurasi Div Head (approval_level = 3)
        // Status 3 (Div Head) bersifat opsional dan hanya berlaku jika dikonfigurasi pada mapping approval
        if ($level === 'divhead') {
            $penilaianData = $penilaianData->filter(function ($item) {
                $hasDivHead = \App\Models\MstPositionApproval::where('position_id', $item->id_job_position)
                    ->where('approval_level', 3)
                    ->whereNotNull('approver_position_id')
                    ->exists();

                return $hasDivHead;
            })->values();
        }

        // Tandai flag aksi (submit/approve/edit) per item — berdasarkan section/dept scope
        $userPosIds = \App\Models\UserJobPosition::where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('mst_job_position_id')
            ->toArray();

        foreach ($penilaianData as $item) {
            $jobPos = $item->jobPosition;

            if ($hasFullAccess) {
                $item->can_submit_draft = true;
                $item->can_approve_kasie = true;
                $item->can_edit_kasie = true;
            } else {
                // Periksa apakah posisi ini punya Sub Section Head (approval level 0)
                $level0Approver = \App\Models\MstPositionApproval::where('position_id', $item->id_job_position)
                    ->where('approval_level', 0)
                    ->first();
                $level1Approver = \App\Models\MstPositionApproval::where('position_id', $item->id_job_position)
                    ->where('approval_level', 1)
                    ->first();

                $hasSubSecHead = $level0Approver && $level0Approver->approver_position_id;

                // Cek apakah user adalah approver level 0/1 berdasarkan Section scope
                $isKasieInSection = false;
                if ($jobPos) {
                    if ($jobPos->section_id) {
                        $isKasieInSection = in_array($jobPos->section_id, $approvalScope['section_ids']);
                    } else {
                        // Shared position (null section): Check if the assessment owner's resolved section matches
                        $ownerSectionName = $item->user?->section;
                        if ($ownerSectionName) {
                            $resolvedSecId = \App\Models\MstSection::where('name', $ownerSectionName)->value('id');
                            $isKasieInSection = $resolvedSecId && in_array($resolvedSecId, $approvalScope['section_ids']);
                        }
                    }
                }

                if ($hasSubSecHead) {
                    // Untuk posisi dengan Sub-Section Head:
                    // - Submit draft: user harus punya posisi yang di-mapping sebagai level 0 di posisi ini
                    // - Approve kasie: user harus punya posisi yang di-mapping sebagai level 1 di posisi ini
                    $item->can_submit_draft  = $level0Approver ? in_array($level0Approver->approver_position_id, $userPosIds) : false;
                    $item->can_approve_kasie = $level1Approver ? in_array($level1Approver->approver_position_id, $userPosIds) : false;
                } else {
                    // Untuk posisi tanpa Sub-Section Head:
                    // - Submit draft dilakukan oleh Section Head (level 1)
                    $item->can_submit_draft  = $isKasieInSection;
                    $item->can_approve_kasie = $isKasieInSection && ($item->status == 1);
                }

                if (in_array($item->status, [0, 5])) {
                    $item->can_edit_kasie = $item->can_submit_draft;
                } elseif ($item->status == 1) {
                    $item->can_edit_kasie = $item->can_approve_kasie;
                } else {
                    $item->can_edit_kasie = $isKasieInSection;
                }
            }
        }

        $positions = MstJobPosition::all();
        $employees = User::all();

        $viewName = match ($level) {
            'kadept' => 'tc_penilaian.penilaian_index_dept',
            'divhead' => 'tc_penilaian.penilaian_index_div',
            default => 'tc_penilaian.penilaian_index',
        };

        return view($viewName, compact('penilaianData', 'positions', 'employees', 'level', 'selectedYear', 'availableYears', 'selectedStatus'));
    }

    /**
     * Backward compatibility — redirect ke /penilaian?level=kadept
     */
    public function indexTrs2()
    {
        return redirect()->route('penilaian.index', ['level' => 'kadept']);
    }

    /**
     * Backward compatibility — redirect ke /penilaian?level=hr
     */
    public function indexTrs3()
    {
        return redirect()->route('penilaian.index', ['level' => 'hr']);
    }

    /**
     * Backward compatibility — redirect ke /penilaian?level=divhead
     */
    public function indexTrs4()
    {
        return redirect()->route('penilaian.index', ['level' => 'divhead']);
    }

    public function createPenilaian()
    {
        $this->abortUnlessCompetencyLevel('kasie');

        $id_user = DB::table('users')->pluck('id')->first();
        $id_tc = DB::table('mst_tcs')->pluck('id')->first();
        $id_sk = DB::table('mst_soft_skills')->pluck('id')->first();
        $id_ad = DB::table('mst_additionals')->pluck('id')->first();

        // Ambil data employee dan posisi untuk form
        $users = User::all();

        $jobPositions = $this->jobPositionAccess
            ->getAccessibleJobPositionOptions(auth()->user());

        $trsPenilaian = TrsPenilaianTc::all();
        $idJobPosition = optional($trsPenilaian->first())->id_job_position;

        $dataTc1 = PoinKategori::find(1);
        $dataTc2 = PoinKategori::find(2);
        $dataTc3 = PoinKategori::find(3);

        return view('tc_penilaian.sc_penilaian', compact('users', 'id_tc', 'id_sk', 'id_ad', 'jobPositions', 'trsPenilaian', 'idJobPosition', 'dataTc1', 'dataTc2', 'dataTc3'));
    }

    public function dsCompetency(
        \App\Services\Dashboard\CompetencyDashboardService $service
    ) {
        $data = $service->getDashboardData();

        return view('dashboard.dsCompetency', $data);
    }

    public function dsDetailCompetency()
    {
        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga


        return view('dashboard.dsDetailCompetency', compact('dataTc1', 'dataTc2', 'dataTc3'));
    }

    public function getJobPositionData(Request $request)
    {
        $this->abortUnlessCompetencyLevel('kasie');

        $jobPosition = $request->input('id'); // Ambil parameter id dari request

        if (!$this->jobPositionAccess->canAccessJobPosition(auth()->user(), $jobPosition)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk job position ini.',
            ], 403);
        }

        // Log nilai jobPosition yang diterima
        Log::info('Received jobPosition:', ['jobPosition' => $jobPosition]);

        // Query pertama untuk data TC
        $tcResults = DB::select('
            SELECT u.id AS id_user, mjp.id AS id_job_position, mjp.position_name AS job_position, 
                dept.name AS department, sec.name AS section, 
                \'\' AS department_head_name, \'\' AS section_head_name, u.name, 
                tcs.id AS id_tc, NULL AS id_sk, NULL AS id_ad, 
                tcs.keterangan_tc AS keterangan,
                tcs.id_poin_kategori, 
                COALESCE(tcs.nilai, \'N/A\') AS nilai, 
                \'tc\' AS type,
                CASE WHEN EXISTS (
                    SELECT 1 FROM trs_penilaian_tcs trs 
                    WHERE trs.id_user = u.id 
                    AND trs.id_job_position = mjp.id
                ) THEN 1 ELSE 0 END AS has_penilaian
            FROM user_job_positions ujp
            JOIN users u ON ujp.user_id = u.id
            JOIN mst_job_positions mjp ON ujp.mst_job_position_id = mjp.id
            LEFT JOIN mst_departments dept ON mjp.department_id = dept.id
            LEFT JOIN mst_sections sec ON mjp.section_id = sec.id
            LEFT JOIN mst_tcs tcs ON tcs.id_job_position = mjp.id
            WHERE mjp.id = ?', [$jobPosition]);

        // Log hasil dari query TC
        Log::info('TC Results:', ['tcResults' => $tcResults]);

        // Query kedua untuk data SK
        $skResults = DB::select('
            SELECT u.id AS id_user, mjp.id AS id_job_position, mjp.position_name AS job_position, 
                dept.name AS department, sec.name AS section, 
                \'\' AS department_head_name, \'\' AS section_head_name, u.name, 
                NULL AS id_tc, sk.id AS id_sk, NULL AS id_ad, 
                sk.keterangan_sk AS keterangan, 
                sk.id_poin_kategori,
                COALESCE(sk.nilai, \'N/A\') AS nilai, 
                \'sk\' AS type,
                CASE WHEN EXISTS (
                    SELECT 1 FROM trs_penilaian_tcs trs 
                    WHERE trs.id_user = u.id 
                    AND trs.id_job_position = mjp.id
                ) THEN 1 ELSE 0 END AS has_penilaian
            FROM user_job_positions ujp
            JOIN users u ON ujp.user_id = u.id
            JOIN mst_job_positions mjp ON ujp.mst_job_position_id = mjp.id
            LEFT JOIN mst_departments dept ON mjp.department_id = dept.id
            LEFT JOIN mst_sections sec ON mjp.section_id = sec.id
            LEFT JOIN mst_soft_skills sk ON sk.id_job_position = mjp.id
            WHERE mjp.id = ?', [$jobPosition]);

        // Log hasil dari query SK
        Log::info('SK Results:', ['skResults' => $skResults]);

        // Query ketiga untuk data AD
        $adResults = DB::select('
            SELECT u.id AS id_user, mjp.id AS id_job_position, mjp.position_name AS job_position, 
                dept.name AS department, sec.name AS section, 
                \'\' AS department_head_name, \'\' AS section_head_name, u.name, 
                NULL AS id_tc, NULL AS id_sk, ad.id AS id_ad, 
                ad.keterangan_ad AS keterangan, 
                ad.id_poin_kategori,
                COALESCE(ad.nilai, \'N/A\') AS nilai, 
                \'ad\' AS type,
                CASE WHEN EXISTS (
                    SELECT 1 FROM trs_penilaian_tcs trs 
                    WHERE trs.id_user = u.id 
                    AND trs.id_job_position = mjp.id
                ) THEN 1 ELSE 0 END AS has_penilaian
            FROM user_job_positions ujp
            JOIN users u ON ujp.user_id = u.id
            JOIN mst_job_positions mjp ON ujp.mst_job_position_id = mjp.id
            LEFT JOIN mst_departments dept ON mjp.department_id = dept.id
            LEFT JOIN mst_sections sec ON mjp.section_id = sec.id
            LEFT JOIN mst_additionals ad ON ad.id_job_position = mjp.id
            WHERE mjp.id = ?', [$jobPosition]);

        // Log hasil dari query AD
        Log::info('AD Results:', ['adResults' => $adResults]);

        // Gabungkan hasil dari ketiga query
        $results = array_merge($tcResults, $skResults, $adResults);

        $hasFullAccess = $this->jobPositionAccess->hasFullAccess(auth()->user());
        $approvalScope = $this->jobPositionAccess->getUserApprovalScope(auth()->user());

        if (!$hasFullAccess) {
            $allowedSectionIds = $approvalScope['section_ids'];
            $filterFn = function ($item) use ($allowedSectionIds, $jobPosition) {
                $jobPos = \App\Models\MstJobPosition::find($jobPosition);
                if ($jobPos && $jobPos->section_id) {
                    return true;
                }

                $employee = \App\Models\User::find($item->id_user);
                if ($employee && $employee->section) {
                    $resolvedSecId = \App\Models\MstSection::where('name', $employee->section)->value('id');
                    return $resolvedSecId && in_array($resolvedSecId, $allowedSectionIds);
                }

                return false;
            };

            $results = array_values(array_filter($results, $filterFn));
        }

        // Log hasil gabungan
        Log::info('Final Results:', ['results' => $results]);

        // Kembalikan hasil sebagai JSON
        return response()->json($results);
    }

    public function getJobPositionDataEdit(Request $request)
    {
        $jobPosition = $request->input('id'); // Ambil parameter id dari request

        $results = DB::select('
        (
            SELECT ujp.id AS user_job_position_id, u.id AS id_user, mjp.position_name AS job_position, dept.name AS department, sec.name AS section, \'\' AS department_head_name, \'\' AS section_head_name, u.name, 
                tcs.id AS id_tc, NULL AS id_sk, NULL AS id_ad, 
                tcs.keterangan_tc AS keterangan, 
                COALESCE(trs.nilai_tc, 0) AS nilai_tc,  
                NULL AS nilai_sk,  
                NULL AS nilai_ad,  
                "tc" AS type
            FROM user_job_positions ujp
            JOIN users u ON ujp.user_id = u.id
            JOIN mst_job_positions mjp ON ujp.mst_job_position_id = mjp.id
            LEFT JOIN mst_departments dept ON mjp.department_id = dept.id
            LEFT JOIN mst_sections sec ON mjp.section_id = sec.id
            LEFT JOIN mst_tcs tcs ON mjp.id = tcs.id_job_position
            LEFT JOIN trs_penilaian_tcs trs ON tcs.id = trs.id_tc AND trs.id_job_position = mjp.id AND trs.id_user = u.id
            WHERE mjp.id = ?
        )
        UNION ALL
        (
            SELECT ujp.id AS user_job_position_id, u.id AS id_user, mjp.position_name AS job_position, dept.name AS department, sec.name AS section, \'\' AS department_head_name, \'\' AS section_head_name, u.name, 
                NULL AS id_tc, sk.id AS id_sk, NULL AS id_ad, 
                sk.keterangan_sk AS keterangan, 
                NULL AS nilai_tc,  
                COALESCE(trs.nilai_sk, 0) AS nilai_sk,  
                NULL AS nilai_ad,  
                "sk" AS type
            FROM user_job_positions ujp
            JOIN users u ON ujp.user_id = u.id
            JOIN mst_job_positions mjp ON ujp.mst_job_position_id = mjp.id
            LEFT JOIN mst_departments dept ON mjp.department_id = dept.id
            LEFT JOIN mst_sections sec ON mjp.section_id = sec.id
            LEFT JOIN mst_soft_skills sk ON mjp.id = sk.id_job_position
            LEFT JOIN trs_penilaian_tcs trs ON sk.id = trs.id_sk AND trs.id_job_position = mjp.id AND trs.id_user = u.id
            WHERE mjp.id = ?
        )
        UNION ALL
        (
            SELECT ujp.id AS user_job_position_id, u.id AS id_user, mjp.position_name AS job_position, dept.name AS department, sec.name AS section, \'\' AS department_head_name, \'\' AS section_head_name, u.name, 
                NULL AS id_tc, NULL AS id_sk, ad.id AS id_ad, 
                ad.keterangan_ad AS keterangan, 
                NULL AS nilai_tc,  
                NULL AS nilai_sk,  
                COALESCE(trs.nilai_ad, 0) AS nilai_ad,  
                "ad" AS type
            FROM user_job_positions ujp
            JOIN users u ON ujp.user_id = u.id
            JOIN mst_job_positions mjp ON ujp.mst_job_position_id = mjp.id
            LEFT JOIN mst_departments dept ON mjp.department_id = dept.id
            LEFT JOIN mst_sections sec ON mjp.section_id = sec.id
            LEFT JOIN mst_additionals ad ON mjp.id = ad.id_job_position
            LEFT JOIN trs_penilaian_tcs trs ON ad.id = trs.id_ad AND trs.id_job_position = mjp.id AND trs.id_user = u.id
            WHERE mjp.id = ?
        )
        ', [$jobPosition, $jobPosition, $jobPosition]);

        $hasFullAccess = $this->jobPositionAccess->hasFullAccess(auth()->user());
        $approvalScope = $this->jobPositionAccess->getUserApprovalScope(auth()->user());

        if (!$hasFullAccess) {
            $allowedSectionIds = $approvalScope['section_ids'];
            $filterFn = function ($item) use ($allowedSectionIds, $jobPosition) {
                $jobPos = \App\Models\MstJobPosition::find($jobPosition);
                if ($jobPos && $jobPos->section_id) {
                    return true;
                }

                $employee = \App\Models\User::find($item->id_user);
                if ($employee && $employee->section) {
                    $resolvedSecId = \App\Models\MstSection::where('name', $employee->section)->value('id');
                    return $resolvedSecId && in_array($resolvedSecId, $allowedSectionIds);
                }

                return false;
            };

            $results = array_values(array_filter($results, $filterFn));
        }

        return response()->json($results);
    }

    public function getNilaiDataEdit(Request $request)
    {
        // Ambil nilai id_job_position dari input request
        $jobPosition = $request->input('id');

        // Query untuk mengambil data berdasarkan id_job_position
        $query = DB::table('trs_penilaian_tcs')
            ->select('id', 'id_tc', 'id_sk', 'id_ad', 'nilai_tc', 'nilai_sk', 'nilai_ad')
            ->where('id_job_position', $jobPosition);

        $hasFullAccess = $this->jobPositionAccess->hasFullAccess(auth()->user());
        if (!$hasFullAccess) {
            $scope = $this->jobPositionAccess->getUserApprovalScope(auth()->user());
            $allowedSectionNames = \App\Models\MstSection::whereIn('id', $scope['section_ids'])->pluck('name')->toArray();
            
            $query->whereIn('id_user', function($q) use ($allowedSectionNames) {
                $q->select('id')->from('users')->whereIn('section', $allowedSectionNames);
            });
        }

        $results = $query->get();

        return response()->json($results);
    }

    public function getJobPointKategori(Request $request)
    {
        $jobPositionId = $request->input('id'); // Ambil job_position ID dari request
        $jobPosition = \App\Models\MstJobPosition::find($jobPositionId)?->position_name ?? '';

        // Query untuk mengambil data TC
        $tcResults = DB::select('
            SELECT jp.id, pk.id AS id_poin_kategori, pk.id_tc, pk.standar_poin AS standar_nilai, pk.tujuan,
                pk.deskripsi AS deskripsi, "tc" AS type
            FROM mst_job_positions jp
            LEFT JOIN tc_poin_kategoris pk ON jp.id = pk.id_job_position
            WHERE jp.position_name = ? AND pk.id_tc IS NOT NULL
        ', [$jobPosition]);

        // Query untuk mengambil data SK
        $skResults = DB::select('
            SELECT jp.id, pk.id AS id_poin_kategori, pk.id_sk, pk.standar_poin AS standar_nilai, pk.tujuan,
                pk.deskripsi AS deskripsi, "sk" AS type
            FROM mst_job_positions jp
            LEFT JOIN tc_poin_kategoris pk ON jp.id = pk.id_job_position
            WHERE jp.position_name = ? AND pk.id_sk IS NOT NULL
        ', [$jobPosition]);

        // Query untuk mengambil data AD
        $adResults = DB::select('
            SELECT jp.id, pk.id AS id_poin_kategori, pk.id_ad, pk.standar_poin AS standar_nilai, pk.tujuan,
                pk.deskripsi AS deskripsi, "ad" AS type
            FROM mst_job_positions jp
            LEFT JOIN tc_poin_kategoris pk ON jp.id = pk.id_job_position
            WHERE jp.position_name = ? AND pk.id_ad IS NOT NULL
        ', [$jobPosition]);

        // Mengembalikan data dalam format JSON
        return response()->json([
            'tc' => $tcResults,
            'sk' => $skResults,
            'ad' => $adResults,
        ]);
    }

    public function savePenilaian(Request $request)
    {
        try {
            $this->abortUnlessCompetencyLevel('kasie');

            Log::info('Request data:', ['request_data' => $request->all()]);

            // Mengonversi semua ID menjadi integer
            $userIds = array_map('intval', $request->input('id_user', []));
            $nilaiTc = $request->input('nilai_tc', []);
            $nilaiSk = $request->input('nilai_sk', []);
            $nilaiAd = $request->input('nilai_ad', []);
            $idTc = $request->input('id_tc', []);
            $idSk = $request->input('id_sk', []);
            $idAd = $request->input('id_ad', []);
            $idJobPosition = $request->input('posisi');

            if (!$idJobPosition) {
                return response()->json([
                    'error' => 'Job position tidak ditemukan.',
                ], 404);
            }

            if (!$this->jobPositionAccess->canAccessJobPosition(auth()->user(), $idJobPosition)) {
                return response()->json([
                    'error' => 'Anda tidak memiliki akses untuk job position ini.',
                ], 403);
            }

            if (empty($userIds)) {
                return response()->json([
                    'error' => 'Tidak ada data karyawan yang dipilih atau dikirim untuk dinilai.',
                ], 400);
            }

            // Reset status record lama yang sudah disetujui (status >= 2) kembali ke 1
            // karena ada penambahan karyawan baru pada job position yang sama
            $existingStatuses = TrsPenilaianTc::where('id_job_position', $idJobPosition)
                ->whereIn('id_user', $userIds)
                ->pluck('status')
                ->toArray();

            $hasNewUsers = collect($userIds)->filter(function ($userId) use ($idJobPosition) {
                return !TrsPenilaianTc::where('id_job_position', $idJobPosition)
                    ->where('id_user', $userId)
                    ->exists();
            })->isNotEmpty();

            if ($hasNewUsers) {
                // Ada karyawan baru → reset semua record lama pada job position ini ke status 1
                TrsPenilaianTc::where('id_job_position', $idJobPosition)
                    ->where('status', '>', 1)
                    ->update([
                        'status' => 1,
                        'modified_at' => auth()->user()->id,
                        'modified_updated' => auth()->user()->name,
                    ]);

                Log::info("Status reset to 1 for job_position {$idJobPosition} due to new employee added.");
            }

            foreach ($userIds as $userId) {
                if (!User::find($userId)) {
                    Log::warning("User ID $userId not found, skipping.");
                    continue;
                }

                Log::info("Processing User ID: $userId");

                // Hitung jumlah index berdasarkan jumlah terbanyak dari tc, sk, ad
                $maxCount = max(
                    count($nilaiTc[$userId] ?? []),
                    count($nilaiSk[$userId] ?? []),
                    count($nilaiAd[$userId] ?? [])
                );

                for ($index = 0; $index < $maxCount; $index++) {
                    $nilaiTcValue = isset($nilaiTc[$userId][$index]) ? (int) $nilaiTc[$userId][$index] : null;
                    $nilaiSkValue = isset($nilaiSk[$userId][$index]) ? (int) $nilaiSk[$userId][$index] : null;
                    $nilaiAdValue = isset($nilaiAd[$userId][$index]) ? (int) $nilaiAd[$userId][$index] : null;

                    // Ambil id_tc, id_sk, id_ad
                    $idTcValue = isset($idTc[$userId][$index]) ? (int) $idTc[$userId][$index] : null;
                    $idSkValue = isset($idSk[$userId][$index]) ? (int) $idSk[$userId][$index] : null;
                    $idAdValue = isset($idAd[$userId][$index]) ? (int) $idAd[$userId][$index] : null;

                    // Gunakan updateOrCreate agar record yang sudah ada tidak duplikat,
                    // record baru (competency baru) tetap dibuat
                    $matchKeys = [
                        'id_user' => $userId,
                        'id_job_position' => $idJobPosition,
                        'id_tc' => $idTcValue,
                        'id_sk' => $idSkValue,
                        'id_ad' => $idAdValue,
                    ];

                    // Hanya update nilai yang tidak null agar tidak menimpa data yang sudah ada
                    $updateData = [
                        'status' => 0, // [4] Draft status — belum dikirim ke approver
                        'tahun_penilaian' => now()->year, // [3] Set tahun penilaian otomatis
                        'modified_at' => auth()->user()->id,
                        'modified_updated' => auth()->user()->name,
                    ];
                    if ($nilaiTcValue !== null)
                        $updateData['nilai_tc'] = $nilaiTcValue;
                    if ($nilaiSkValue !== null)
                        $updateData['nilai_sk'] = $nilaiSkValue;
                    if ($nilaiAdValue !== null)
                        $updateData['nilai_ad'] = $nilaiAdValue;

                    Log::info('Data to save/update:', ['match' => $matchKeys, 'values' => $updateData]);

                    $result = TrsPenilaianTc::updateOrCreate($matchKeys, $updateData);

                    Log::info('Data berhasil disimpan/diupdate untuk user ID: ' . $userId, ['saved_data' => $result->toArray()]);
                }
            }

            Log::info('Data penilaian berhasil disimpan.');
            return response()->json(['success' => 'Data penilaian berhasil disimpan.'], 200);
        } catch (\Exception $e) {
            Log::error('Error while saving penilaian:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Terjadi kesalahan saat menyimpan data.'], 500);
        }
    }

    public function editTrs($id_job_position)
    {
        $this->abortUnlessCompetencyLevel('kasie');
        $this->abortUnlessJobPositionAccessible($id_job_position);

        // Ambil satu data penilaian berdasarkan id_job_position
        $penilaian = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user', 'jobPosition'])
            ->where('id_job_position', $id_job_position)
            ->orderByDesc('status')
            ->first(); // Mengambil satu record dengan status tertinggi

        abort_if(!$penilaian || !in_array((int) $penilaian->status, [0, 1, 2, 3, 4]), 403, 'Penilaian tidak dapat diedit pada status ini.');

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

        // Ambil semua data dari DetailTcPenilaian yang terkait dengan id_job_position
        // Ambil data detail penilaian terkait id_job_position
        $detailPenilaian = DetailTcPenilaian::where('id_job_position', $id_job_position)
            ->orderBy('created_at', 'desc') // Urutkan berdasarkan waktu terbaru
            ->get();

        return view('tc_penilaian.edit_penilaian', compact('penilaian', 'dataTc1', 'dataTc2', 'dataTc3', 'detailPenilaian'));
    }

    public function editTrs2($id_job_position)
    {
        $this->abortUnlessCompetencyLevel('kadept');
        $this->abortUnlessJobPositionAccessible($id_job_position);

        // Ambil satu data penilaian berdasarkan id_job_position
        $penilaian = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user', 'jobPosition'])
            ->where('id_job_position', $id_job_position)
            ->orderByDesc('status')
            ->first(); // Mengambil satu record dengan status tertinggi

        abort_if(!$penilaian || !in_array((int) $penilaian->status, [1, 2, 3, 4]), 403, 'Penilaian tidak dapat dikonfirmasi pada status ini.');

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

        $detailPenilaian = DetailTcPenilaian::where('id_job_position', $id_job_position)
            ->orderBy('created_at', 'desc') // Urutkan berdasarkan waktu terbaru
            ->get();

        $updateRoute = route('updateDept', $id_job_position);
        $backRoute = route('penilaian.index2');

        return view('tc_penilaian.dept_penilaian', compact('penilaian', 'dataTc1', 'dataTc2', 'dataTc3', 'detailPenilaian', 'updateRoute', 'backRoute'));
    }

    public function editTrs3($id_job_position)
    {
        $this->abortUnlessCompetencyLevel('divhead');
        $this->abortUnlessJobPositionAccessible($id_job_position);

        // Ambil satu data penilaian berdasarkan id_job_position
        $penilaian = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user', 'jobPosition'])
            ->where('id_job_position', $id_job_position)
            ->orderByDesc('status')
            ->first(); // Mengambil satu record dengan status tertinggi

        abort_if(!$penilaian || !in_array((int) $penilaian->status, [3, 4]), 403, 'Penilaian tidak dapat dikonfirmasi pada status ini.');

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

        $detailPenilaian = DetailTcPenilaian::where('id_job_position', $id_job_position)
            ->orderBy('created_at', 'desc') // Urutkan berdasarkan waktu terbaru
            ->get();

        $updateRoute = route('updateDiv', $id_job_position);
        $backRoute = route('penilaian.index4');

        return view('tc_penilaian.dept_penilaian', compact('penilaian', 'dataTc1', 'dataTc2', 'dataTc3', 'detailPenilaian', 'updateRoute', 'backRoute'));
    }

    public function viewTrs($id_job_position)
    {
        $this->abortUnlessJobPositionAccessible($id_job_position);

        // Ambil satu data penilaian berdasarkan id_job_position
        $penilaian = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user', 'jobPosition'])
            ->where('id_job_position', $id_job_position)
            ->orderByDesc('status')
            ->first(); // Mengambil satu record dengan status tertinggi

        abort_if(!$penilaian, 404, 'Data penilaian belum tersedia untuk Job Position ini.');

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

        $detailPenilaian = DetailTcPenilaian::where('id_job_position', $id_job_position)
            ->orderBy('created_at', 'desc') // Urutkan berdasarkan waktu terbaru
            ->get();

        return view('tc_penilaian.view_penilaian', compact('penilaian', 'dataTc1', 'dataTc2', 'dataTc3', 'detailPenilaian'));
    }

    public function previewTrs($id_job_position)
    {
        // Ambil satu data penilaian berdasarkan id_job_position
        $penilaian = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user', 'jobPosition'])
            ->where('id_job_position', $id_job_position)
            ->orderByDesc('status')
            ->first(); // Mengambil satu record dengan status tertinggi

        abort_if(!$penilaian, 404, 'Data penilaian belum tersedia untuk Job Position ini.');

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

        $detailPenilaian = DetailTcPenilaian::where('id_job_position', $id_job_position)
            ->orderBy('created_at', 'desc') // Urutkan berdasarkan waktu terbaru
            ->get();


        return view('tc_penilaian.privew_penilaian', compact('penilaian', 'dataTc1', 'dataTc2', 'dataTc3', 'detailPenilaian'));
    }

    public function getDataTrs(Request $request)
    {
        // Ambil semua data penilaian berdasarkan id_job_position
        $penilaians = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user'])
            ->where('id_job_position', $request->id_job_position)
            ->get(); // Mengambil semua record yang cocok

        return response()->json($penilaians);
    }

    public function updateTrs(Request $request, $id_job_position)
    {
        // Decode HTML entities pada $id_job_position untuk menghindari perubahan karakter
        $decoded_job_position = html_entity_decode($id_job_position);
        $this->abortUnlessCompetencyLevel('kasie');
        $this->abortUnlessJobPositionAccessible($decoded_job_position);

        if (!TrsPenilaianTc::where('id_job_position', $decoded_job_position)->whereIn('status', [0, 1, 2, 3, 4])->exists()) {
            return response()->json(['success' => false, 'message' => 'Penilaian tidak dapat diedit pada status ini.'], 403);
        }

        // Ambil data JSON yang dikirim dari AJAX
        $data = $request->json()->all();

        // Ambil records yang dikirim dari frontend (sudah di-group per record ID)
        $records = $data['records'] ?? [];

        // Log data yang diterima untuk pengecekan
        Log::info('Received records:', ['records' => $records]);

        // Array untuk mengumpulkan perubahan keterangan_detail per name
        $changesByName = [];

        foreach ($records as $record) {
            $recordId = $record['id'] ?? null;
            if (!$recordId)
                continue;

            // Cari penilaian berdasarkan record ID dan pastikan job position sesuai
            $penilaian = TrsPenilaianTc::where('id', $recordId)
                ->where('id_job_position', $decoded_job_position)
                ->first();

            if (!$penilaian)
                continue;

            $hasChanged = false;
            $userName = $record['name'] ?? 'Unknown';
            $currentKeteranganDetail = [];
            $beforeValues = []; // [3] History BEFORE

            $nilaiTcValue = $this->normalizeScoreValue($record['nilai_tc'] ?? null);
            $nilaiSkValue = $this->normalizeScoreValue($record['nilai_sk'] ?? null);
            $nilaiAdValue = $this->normalizeScoreValue($record['nilai_ad'] ?? null);

            // Proses nilai_tc
            if ($nilaiTcValue !== null && (int) $penilaian->nilai_tc !== $nilaiTcValue) {
                $beforeValues['nilai_tc'] = $penilaian->nilai_tc; // [3] Simpan BEFORE
                $penilaian->nilai_tc = $nilaiTcValue;
                $hasChanged = true;
                $keteranganTc = $record['keterangan_tc'] ?? '-';
                $currentKeteranganDetail[] = "Technical Competency: {$keteranganTc} = {$beforeValues['nilai_tc']} → {$nilaiTcValue}";
            }

            // Proses nilai_sk
            if ($nilaiSkValue !== null && (int) $penilaian->nilai_sk !== $nilaiSkValue) {
                $beforeValues['nilai_sk'] = $penilaian->nilai_sk; // [3] Simpan BEFORE
                $penilaian->nilai_sk = $nilaiSkValue;
                $hasChanged = true;
                $keteranganSk = $record['keterangan_sk'] ?? '-';
                $currentKeteranganDetail[] = "Non-Competency (Soft Skills): {$keteranganSk} = {$beforeValues['nilai_sk']} → {$nilaiSkValue}";
            }

            // Proses nilai_ad
            if ($nilaiAdValue !== null && (int) $penilaian->nilai_ad !== $nilaiAdValue) {
                $beforeValues['nilai_ad'] = $penilaian->nilai_ad; // [3] Simpan BEFORE
                $penilaian->nilai_ad = $nilaiAdValue;
                $hasChanged = true;
                $keteranganAd = $record['keterangan_ad'] ?? '-';
                $currentKeteranganDetail[] = "Additional: {$keteranganAd} = {$beforeValues['nilai_ad']} → {$nilaiAdValue}";
            }

            // Simpan perubahan penilaian jika ada
            if ($hasChanged) {
                $penilaian->save();

                // Gabungkan perubahan berdasarkan nama
                if (!isset($changesByName[$userName])) {
                    $changesByName[$userName] = [];
                }
                $changesByName[$userName] = array_merge($changesByName[$userName], $currentKeteranganDetail);

                // [3] Simpan before values untuk history
                if (!isset($beforeValuesByName[$userName])) {
                    $beforeValuesByName[$userName] = [];
                }
                $beforeValuesByName[$userName] = array_merge($beforeValuesByName[$userName], $beforeValues);
            }
        }

        // Handle new_records (competency baru yang belum ada record-nya)
        $newRecords = $data['new_records'] ?? [];
        foreach ($newRecords as $newRecord) {
            $userId = $newRecord['id_user'] ?? null;
            $userName = $newRecord['name'] ?? 'Unknown';
            if (!$userId)
                continue;

            $nilaiTcValue = $this->normalizeScoreValue($newRecord['nilai_tc'] ?? null);
            $nilaiSkValue = $this->normalizeScoreValue($newRecord['nilai_sk'] ?? null);
            $nilaiAdValue = $this->normalizeScoreValue($newRecord['nilai_ad'] ?? null);

            // Skip jika semua nilai null
            if ($nilaiTcValue === null && $nilaiSkValue === null && $nilaiAdValue === null)
                continue;

            $result = TrsPenilaianTc::create([
                'id_user' => $userId,
                'id_job_position' => $decoded_job_position,
                'id_tc' => $newRecord['id_tc'] ?? null,
                'id_sk' => $newRecord['id_sk'] ?? null,
                'id_ad' => $newRecord['id_ad'] ?? null,
                'nilai_tc' => $nilaiTcValue,
                'nilai_sk' => $nilaiSkValue,
                'nilai_ad' => $nilaiAdValue,
                'status' => 1,
                'modified_at' => auth()->user()->id,
                'modified_updated' => auth()->user()->name,
            ]);

            Log::info('New record created:', ['data' => $result->toArray()]);

            // Track perubahan untuk DetailTcPenilaian
            $currentKeteranganDetail = [];
            if ($nilaiTcValue !== null) {
                $currentKeteranganDetail[] = "Technical Competency: " . ($newRecord['keterangan_tc'] ?? '-') . " = {$nilaiTcValue}";
            }
            if ($nilaiSkValue !== null) {
                $currentKeteranganDetail[] = "Non-Competency (Soft Skills): " . ($newRecord['keterangan_sk'] ?? '-') . " = {$nilaiSkValue}";
            }
            if ($nilaiAdValue !== null) {
                $currentKeteranganDetail[] = "Additional: " . ($newRecord['keterangan_ad'] ?? '-') . " = {$nilaiAdValue}";
            }

            if (!empty($currentKeteranganDetail)) {
                if (!isset($changesByName[$userName])) {
                    $changesByName[$userName] = [];
                }
                $changesByName[$userName] = array_merge($changesByName[$userName], $currentKeteranganDetail);
            }
        }

        // [3] Simpan ke DetailTcPenilaian dengan BEFORE-AFTER history
        $beforeValuesByName = $beforeValuesByName ?? [];
        foreach ($changesByName as $userName => $keteranganDetails) {
            DetailTcPenilaian::create([
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails), // AFTER (dengan format BEFORE → AFTER)
                'nilai_sebelum' => json_encode($beforeValuesByName[$userName] ?? []), // [3] JSON snapshot BEFORE
                'catatan' => $data['alasan_perubahan'] ?? null,
                'modified_at' => auth()->user()->name,
            ]);

            Log::info('DetailTcPenilaian created for:', [
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails),
                'nilai_sebelum' => $beforeValuesByName[$userName] ?? [],
                'catatan' => $data['alasan_perubahan'] ?? null
            ]);
        }

        if (!empty($changesByName) || !empty($newRecords)) {
            TrsPenilaianTc::where('id_job_position', $decoded_job_position)
                ->whereIn('status', [1, 2, 3, 4])
                ->update(['status' => 0]);
        }

        // Kembalikan respon sukses
        return response()->json(['success' => true, 'message' => 'Nilai berhasil diupdate']);
    }


    public function updateTrs2(Request $request, $id_job_position)
    {
        // Decode HTML entities pada $id_job_position untuk menghindari perubahan karakter
        $decoded_job_position = html_entity_decode($id_job_position);
        $this->abortUnlessCompetencyLevel('kadept');
        $this->abortUnlessJobPositionAccessible($decoded_job_position);

        if (!TrsPenilaianTc::where('id_job_position', $decoded_job_position)->whereIn('status', [1, 2, 3, 4])->exists()) {
            return response()->json(['success' => false, 'message' => 'Penilaian tidak dapat dikonfirmasi pada status ini.'], 403);
        }

        // Ambil data JSON yang dikirim dari AJAX
        $data = $request->json()->all();

        // Ambil records yang dikirim dari frontend (sudah di-group per record ID)
        $records = $data['records'] ?? [];

        // Log data yang diterima untuk pengecekan
        Log::info('Received records:', ['records' => $records]);

        // Array untuk mengumpulkan perubahan keterangan_detail per nama
        $changesByName = [];

        foreach ($records as $record) {
            $recordId = $record['id'] ?? null;
            if (!$recordId)
                continue;

            // Cari penilaian berdasarkan record ID dan pastikan job position sesuai
            $penilaian = TrsPenilaianTc::where('id', $recordId)
                ->where('id_job_position', $decoded_job_position)
                ->first();

            if (!$penilaian)
                continue;

            $hasChanged = false;
            $userName = $record['name'] ?? 'Unknown';
            $currentKeteranganDetail = [];
            $beforeValues = []; // [3] History BEFORE

            $nilaiTcValue = $this->normalizeScoreValue($record['nilai_tc'] ?? null);
            $nilaiSkValue = $this->normalizeScoreValue($record['nilai_sk'] ?? null);
            $nilaiAdValue = $this->normalizeScoreValue($record['nilai_ad'] ?? null);

            // Proses nilai_tc
            if ($nilaiTcValue !== null && (int) $penilaian->nilai_tc !== $nilaiTcValue) {
                $beforeValues['nilai_tc'] = $penilaian->nilai_tc; // [3] Simpan BEFORE
                $penilaian->nilai_tc = $nilaiTcValue;
                $hasChanged = true;
                $keteranganTc = $record['keterangan_tc'] ?? '-';
                $currentKeteranganDetail[] = "Technical Competency: {$keteranganTc} = {$beforeValues['nilai_tc']} → {$nilaiTcValue}";
            }

            // Proses nilai_sk
            if ($nilaiSkValue !== null && (int) $penilaian->nilai_sk !== $nilaiSkValue) {
                $beforeValues['nilai_sk'] = $penilaian->nilai_sk; // [3] Simpan BEFORE
                $penilaian->nilai_sk = $nilaiSkValue;
                $hasChanged = true;
                $keteranganSk = $record['keterangan_sk'] ?? '-';
                $currentKeteranganDetail[] = "Non-Competency (Soft Skills): {$keteranganSk} = {$beforeValues['nilai_sk']} → {$nilaiSkValue}";
            }

            // Proses nilai_ad
            if ($nilaiAdValue !== null && (int) $penilaian->nilai_ad !== $nilaiAdValue) {
                $beforeValues['nilai_ad'] = $penilaian->nilai_ad; // [3] Simpan BEFORE
                $penilaian->nilai_ad = $nilaiAdValue;
                $hasChanged = true;
                $keteranganAd = $record['keterangan_ad'] ?? '-';
                $currentKeteranganDetail[] = "Additional: {$keteranganAd} = {$beforeValues['nilai_ad']} → {$nilaiAdValue}";
            }

            // Simpan perubahan penilaian jika ada
            if ($hasChanged) {
                $penilaian->save();

                // Gabungkan perubahan berdasarkan nama
                if (!isset($changesByName[$userName])) {
                    $changesByName[$userName] = [];
                }
                $changesByName[$userName] = array_merge($changesByName[$userName], $currentKeteranganDetail);

                // [3] Simpan before values untuk history
                if (!isset($beforeValuesByName[$userName])) {
                    $beforeValuesByName[$userName] = [];
                }
                $beforeValuesByName[$userName] = array_merge($beforeValuesByName[$userName], $beforeValues);
            }
        }

        // Handle new_records (competency baru yang belum ada record-nya)
        $newRecords = $data['new_records'] ?? [];
        foreach ($newRecords as $newRecord) {
            $userId = $newRecord['id_user'] ?? null;
            $userName = $newRecord['name'] ?? 'Unknown';
            if (!$userId)
                continue;

            $nilaiTcValue = $this->normalizeScoreValue($newRecord['nilai_tc'] ?? null);
            $nilaiSkValue = $this->normalizeScoreValue($newRecord['nilai_sk'] ?? null);
            $nilaiAdValue = $this->normalizeScoreValue($newRecord['nilai_ad'] ?? null);

            if ($nilaiTcValue === null && $nilaiSkValue === null && $nilaiAdValue === null)
                continue;

            $result = TrsPenilaianTc::create([
                'id_user' => $userId,
                'id_job_position' => $decoded_job_position,
                'id_tc' => $newRecord['id_tc'] ?? null,
                'id_sk' => $newRecord['id_sk'] ?? null,
                'id_ad' => $newRecord['id_ad'] ?? null,
                'nilai_tc' => $nilaiTcValue,
                'nilai_sk' => $nilaiSkValue,
                'nilai_ad' => $nilaiAdValue,
                'status' => 2,
                'modified_at' => auth()->user()->id,
                'modified_updated' => auth()->user()->name,
            ]);

            Log::info('New record created:', ['data' => $result->toArray()]);

            $currentKeteranganDetail = [];
            if ($nilaiTcValue !== null) {
                $currentKeteranganDetail[] = "Technical Competency: " . ($newRecord['keterangan_tc'] ?? '-') . " = {$nilaiTcValue}";
            }
            if ($nilaiSkValue !== null) {
                $currentKeteranganDetail[] = "Non-Competency (Soft Skills): " . ($newRecord['keterangan_sk'] ?? '-') . " = {$nilaiSkValue}";
            }
            if ($nilaiAdValue !== null) {
                $currentKeteranganDetail[] = "Additional: " . ($newRecord['keterangan_ad'] ?? '-') . " = {$nilaiAdValue}";
            }

            if (!empty($currentKeteranganDetail)) {
                if (!isset($changesByName[$userName])) {
                    $changesByName[$userName] = [];
                }
                $changesByName[$userName] = array_merge($changesByName[$userName], $currentKeteranganDetail);
            }
        }

        // Simpan ke DetailTcPenilaian dengan menggabungkan keterangan_detail per nama
        $beforeValuesByName = $beforeValuesByName ?? [];
        foreach ($changesByName as $userName => $keteranganDetails) {
            DetailTcPenilaian::create([
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails), // Gabungkan detail dengan pemisah
                'nilai_sebelum' => json_encode($beforeValuesByName[$userName] ?? []), // [3] JSON snapshot BEFORE
                'catatan' => $data['alasan_perubahan'] ?? null, // Alasan perubahan
                'modified_at' => auth()->user()->name,
            ]);

            Log::info('DetailTcPenilaian created for:', [
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails),
                'nilai_sebelum' => $beforeValuesByName[$userName] ?? [],
                'catatan' => $data['alasan_perubahan'] ?? null
            ]);
        }

        if (!empty($changesByName) || !empty($newRecords)) {
            TrsPenilaianTc::where('id_job_position', $decoded_job_position)
                ->whereIn('status', [1, 3, 4])
                ->update(['status' => 2]);
        }

        // Kembalikan respon sukses
        return response()->json(['success' => true, 'message' => 'Nilai berhasil diupdate']);
    }

    public function updateTrs3(Request $request, $id_job_position)
    {
        // Decode HTML entities pada $id_job_position untuk menghindari perubahan karakter
        $decoded_job_position = html_entity_decode($id_job_position);
        $this->abortUnlessCompetencyLevel('divhead');
        $this->abortUnlessJobPositionAccessible($decoded_job_position);

        if (!TrsPenilaianTc::where('id_job_position', $decoded_job_position)->whereIn('status', [3, 4])->exists()) {
            return response()->json(['success' => false, 'message' => 'Penilaian tidak dapat dikonfirmasi pada status ini.'], 403);
        }

        // Ambil data JSON yang dikirim dari AJAX
        $data = $request->json()->all();

        // Ambil records yang dikirim dari frontend (sudah di-group per record ID)
        $records = $data['records'] ?? [];

        // Log data yang diterima untuk pengecekan
        Log::info('Received records:', ['records' => $records]);

        // Array untuk mengumpulkan perubahan keterangan_detail per nama
        $changesByName = [];

        foreach ($records as $record) {
            $recordId = $record['id'] ?? null;
            if (!$recordId)
                continue;

            // Cari penilaian berdasarkan record ID dan pastikan job position sesuai
            $penilaian = TrsPenilaianTc::where('id', $recordId)
                ->where('id_job_position', $decoded_job_position)
                ->first();

            if (!$penilaian)
                continue;

            $hasChanged = false;
            $userName = $record['name'] ?? 'Unknown';
            $currentKeteranganDetail = [];
            $beforeValues = []; // [3] History BEFORE

            $nilaiTcValue = $this->normalizeScoreValue($record['nilai_tc'] ?? null);
            $nilaiSkValue = $this->normalizeScoreValue($record['nilai_sk'] ?? null);
            $nilaiAdValue = $this->normalizeScoreValue($record['nilai_ad'] ?? null);

            // Proses nilai_tc
            if ($nilaiTcValue !== null && (int) $penilaian->nilai_tc !== $nilaiTcValue) {
                $beforeValues['nilai_tc'] = $penilaian->nilai_tc; // [3] Simpan BEFORE
                $penilaian->nilai_tc = $nilaiTcValue;
                $hasChanged = true;
                $keteranganTc = $record['keterangan_tc'] ?? '-';
                $currentKeteranganDetail[] = "Technical Competency: {$keteranganTc} = {$beforeValues['nilai_tc']} → {$nilaiTcValue}";
            }

            // Proses nilai_sk
            if ($nilaiSkValue !== null && (int) $penilaian->nilai_sk !== $nilaiSkValue) {
                $beforeValues['nilai_sk'] = $penilaian->nilai_sk; // [3] Simpan BEFORE
                $penilaian->nilai_sk = $nilaiSkValue;
                $hasChanged = true;
                $keteranganSk = $record['keterangan_sk'] ?? '-';
                $currentKeteranganDetail[] = "Non-Competency (Soft Skills): {$keteranganSk} = {$beforeValues['nilai_sk']} → {$nilaiSkValue}";
            }

            // Proses nilai_ad
            if ($nilaiAdValue !== null && (int) $penilaian->nilai_ad !== $nilaiAdValue) {
                $beforeValues['nilai_ad'] = $penilaian->nilai_ad; // [3] Simpan BEFORE
                $penilaian->nilai_ad = $nilaiAdValue;
                $hasChanged = true;
                $keteranganAd = $record['keterangan_ad'] ?? '-';
                $currentKeteranganDetail[] = "Additional: {$keteranganAd} = {$beforeValues['nilai_ad']} → {$nilaiAdValue}";
            }

            // Simpan perubahan penilaian jika ada
            if ($hasChanged) {
                $penilaian->save();

                // Gabungkan perubahan berdasarkan nama
                if (!isset($changesByName[$userName])) {
                    $changesByName[$userName] = [];
                }
                $changesByName[$userName] = array_merge($changesByName[$userName], $currentKeteranganDetail);

                // [3] Simpan before values untuk history
                if (!isset($beforeValuesByName[$userName])) {
                    $beforeValuesByName[$userName] = [];
                }
                $beforeValuesByName[$userName] = array_merge($beforeValuesByName[$userName], $beforeValues);
            }
        }

        // Handle new_records (competency baru yang belum ada record-nya)
        $newRecords = $data['new_records'] ?? [];

        // Cek secara dinamis apakah posisi ini memiliki konfigurasi Div Head
        // Digunakan untuk menentukan status record baru dan downgrade status setelah edit
        $hasDivHead = \App\Models\MstPositionApproval::where('position_id', $decoded_job_position)
            ->where('approval_level', 3)
            ->whereNotNull('approver_position_id')
            ->exists();
        $newRecordStatus = $hasDivHead ? 3 : 4;

        foreach ($newRecords as $newRecord) {
            $userId = $newRecord['id_user'] ?? null;
            $userName = $newRecord['name'] ?? 'Unknown';
            if (!$userId)
                continue;

            $nilaiTcValue = $this->normalizeScoreValue($newRecord['nilai_tc'] ?? null);
            $nilaiSkValue = $this->normalizeScoreValue($newRecord['nilai_sk'] ?? null);
            $nilaiAdValue = $this->normalizeScoreValue($newRecord['nilai_ad'] ?? null);

            if ($nilaiTcValue === null && $nilaiSkValue === null && $nilaiAdValue === null)
                continue;

            $result = TrsPenilaianTc::create([
                'id_user' => $userId,
                'id_job_position' => $decoded_job_position,
                'id_tc' => $newRecord['id_tc'] ?? null,
                'id_sk' => $newRecord['id_sk'] ?? null,
                'id_ad' => $newRecord['id_ad'] ?? null,
                'nilai_tc' => $nilaiTcValue,
                'nilai_sk' => $nilaiSkValue,
                'nilai_ad' => $nilaiAdValue,
                'status' => $newRecordStatus,
                'modified_at' => auth()->user()->id,
                'modified_updated' => auth()->user()->name,
            ]);

            Log::info('New record created:', ['data' => $result->toArray()]);

            $currentKeteranganDetail = [];
            if ($nilaiTcValue !== null) {
                $currentKeteranganDetail[] = "Technical Competency: " . ($newRecord['keterangan_tc'] ?? '-') . " = {$nilaiTcValue}";
            }
            if ($nilaiSkValue !== null) {
                $currentKeteranganDetail[] = "Non-Competency (Soft Skills): " . ($newRecord['keterangan_sk'] ?? '-') . " = {$nilaiSkValue}";
            }
            if ($nilaiAdValue !== null) {
                $currentKeteranganDetail[] = "Additional: " . ($newRecord['keterangan_ad'] ?? '-') . " = {$nilaiAdValue}";
            }

            if (!empty($currentKeteranganDetail)) {
                if (!isset($changesByName[$userName])) {
                    $changesByName[$userName] = [];
                }
                $changesByName[$userName] = array_merge($changesByName[$userName], $currentKeteranganDetail);
            }
        }

        // Simpan ke DetailTcPenilaian dengan menggabungkan keterangan_detail per nama
        $beforeValuesByName = $beforeValuesByName ?? [];
        foreach ($changesByName as $userName => $keteranganDetails) {
            DetailTcPenilaian::create([
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails), // Gabungkan detail dengan pemisah
                'nilai_sebelum' => json_encode($beforeValuesByName[$userName] ?? []), // [3] JSON snapshot BEFORE
                'catatan' => $data['alasan_perubahan'] ?? null, // Alasan perubahan
                'modified_at' => auth()->user()->name,
            ]);

            Log::info('DetailTcPenilaian created for:', [
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails),
                'nilai_sebelum' => $beforeValuesByName[$userName] ?? [],
                'catatan' => $data['alasan_perubahan'] ?? null
            ]);
        }

        if (!empty($changesByName) || !empty($newRecords)) {
            TrsPenilaianTc::where('id_job_position', $decoded_job_position)
                ->where('status', 4)
                ->update(['status' => $newRecordStatus]);
        }

        // Kembalikan respon sukses
        return response()->json(['success' => true, 'message' => 'Nilai berhasil diupdate']);
    }

    public function updateCatatan(Request $request, $id)
    {
        // Authorization: hanya user dengan hak akses penilaian (minimal Ka. Sie) yang boleh mengubah catatan
        $user = auth()->user();
        $hasAccessKaSie   = $this->roleAccess->canAccessCompetencyLevel($user, 'kasie');
        $hasAccessKaDept  = $this->roleAccess->canAccessCompetencyLevel($user, 'kadept');
        $hasAccessDivHead = $this->roleAccess->canAccessCompetencyLevel($user, 'divhead');
        $hasAccessHR      = $this->roleAccess->canAccessCompetencyLevel($user, 'hr');

        if (!$hasAccessKaSie && !$hasAccessKaDept && !$hasAccessDivHead && !$hasAccessHR) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mengubah catatan penilaian.');
        }

        // Validasi input
        $request->validate([
            'catatan' => 'nullable|string|max:255'
        ]);

        // Temukan catatan berdasarkan ID
        $detail = DetailTcPenilaian::find($id);

        // Periksa apakah catatan ditemukan
        if (!$detail) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        // Perbarui catatan
        $detail->catatan = $request->input('catatan');
        $detail->modified_at = auth()->user()->name; // Set 'modified_by' sebagai pengguna yang mengedit
        $detail->save();

        // Log pembaruan catatan
        Log::info('Catatan updated:', [
            'id' => $id,
            'catatan' => $detail->catatan,
            'modified_at' => $detail->modified_at,
        ]);

        // Redirect kembali dengan pesan sukses
        return redirect()->back()->with('success', 'Catatan berhasil diperbarui.');
    }

    public function kirimSC(Request $request, $id_job_position)
    {
        if (!$this->roleAccess->canAccessCompetencyLevel(auth()->user(), 'kasie')) {
            return $this->forbiddenJson('Anda tidak memiliki akses untuk mengirim penilaian Ka. Sie.');
        }

        $jobPos = \App\Models\MstJobPosition::find($id_job_position);
        if (!$jobPos) {
            return $this->forbiddenJson('Job position tidak ditemukan.');
        }

        $hasFullAccess = $this->jobPositionAccess->hasFullAccess(auth()->user());

        if (!$hasFullAccess) {
            $scope = $this->jobPositionAccess->getUserApprovalScope(auth()->user());

            if ($jobPos->section_id) {
                if (!in_array($jobPos->section_id, $scope['section_ids'])) {
                    return $this->forbiddenJson('Anda tidak memiliki akses ke section dari job position ini.');
                }
            } else {
                // Shared position (null section): check if Section Head has access to the sections of the employees in this assessment
                $hasEmployeesInSection = TrsPenilaianTc::where('id_job_position', $id_job_position)
                    ->where('status', 1)
                    ->whereHas('user', function($q) use ($scope) {
                        $q->whereIn('section', \App\Models\MstSection::whereIn('id', $scope['section_ids'])->pluck('name'));
                    })
                    ->exists();
                if (!$hasEmployeesInSection) {
                    return $this->forbiddenJson('Anda tidak memiliki akses ke karyawan dari job position ini.');
                }
            }

            // Validasi khusus: hanya user yang di-mapping sebagai Level 1 (Section Head) yang boleh approve
            $userPosIds = \App\Models\UserJobPosition::where('user_id', auth()->id())
                ->where('is_active', true)
                ->pluck('mst_job_position_id')
                ->toArray();
            
            $approver1Ids = \App\Models\MstPositionApproval::where('position_id', $id_job_position)
                ->where('approval_level', 1)
                ->pluck('approver_position_id')
                ->toArray();

            if (!empty($approver1Ids)) {
                $isMatched = false;
                foreach ($userPosIds as $upId) {
                    if (in_array($upId, $approver1Ids)) {
                        $isMatched = true;
                        break;
                    }
                }
                
                // Fallback for shared positions: if no match, allow if user is a KaSie and has the section in scope
                if (!$isMatched && !$jobPos->section_id) {
                    $isMatched = $this->roleAccess->isKaSie(auth()->user());
                }

                if (!$isMatched) {
                    return $this->forbiddenJson('Anda bukan Section Head yang ditugaskan untuk menyetujui penilaian ini.');
                }
            } else {
                // Fallback: verify that the user is a KaSie
                if (!$this->roleAccess->isKaSie(auth()->user())) {
                    return $this->forbiddenJson('Anda bukan Section Head yang ditugaskan untuk menyetujui penilaian ini.');
                }
            }
        }

        // Temukan semua entri dengan id_job_position yang sesuai dan status 1
        $query = TrsPenilaianTc::where('id_job_position', $id_job_position)
            ->where('status', 1);

        if (!$hasFullAccess) {
            $scope = $this->jobPositionAccess->getUserApprovalScope(auth()->user());
            $allowedSectionNames = \App\Models\MstSection::whereIn('id', $scope['section_ids'])->pluck('name')->toArray();
            
            $query->whereHas('user', function($q) use ($allowedSectionNames) {
                $q->whereIn('section', $allowedSectionNames);
            });
        }

        $penilaians = $query->get();

        if ($penilaians->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data not found.'], 404);
        }

        $query->update([
            'status' => 2,
            'modified_at' => auth()->user()->id,
            'modified_updated' => auth()->user()->name,
        ]);

        return response()->json(['success' => true, 'message' => 'Data Competency Telah Dikirim.']);
    }

    public function kirimDept(Request $request, $id_job_position)
    {
        if (!$this->roleAccess->canAccessCompetencyLevel(auth()->user(), 'kadept')) {
            return $this->forbiddenJson('Anda tidak memiliki akses untuk approval Ka. Dept.');
        }

        $jobPos = \App\Models\MstJobPosition::with('department')->find($id_job_position);
        if (!$jobPos) {
            return $this->forbiddenJson('Job position tidak ditemukan.');
        }

        $hasFullAccess = $this->jobPositionAccess->hasFullAccess(auth()->user());

        if (!$hasFullAccess) {
            $scope = $this->jobPositionAccess->getUserApprovalScope(auth()->user());
            if (!in_array($jobPos->department_id, $scope['dept_ids'])) {
                return $this->forbiddenJson('Anda tidak memiliki akses ke departemen dari job position ini.');
            }
        }

        // Temukan semua entri dengan id_job_position yang sesuai
        $penilaians = TrsPenilaianTc::where('id_job_position', $id_job_position)->get();

        if ($penilaians->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data not found.'], 404);
        }

        // Cek apakah posisi memiliki konfigurasi Div Head (approval_level = 3)
        // Status 3 bersifat opsional: hanya diteruskan ke Div Head jika ada mapping-nya
        $hasDivHead = \App\Models\MstPositionApproval::where('position_id', $id_job_position)
            ->where('approval_level', 3)
            ->whereNotNull('approver_position_id')
            ->exists();

        $nextStatus = $hasDivHead ? 3 : 4;
        $message = ($nextStatus === 3) ? 'Data Competency diteruskan ke Div. Head.' : 'Data Competency Telah Disetujui (Final).';

        TrsPenilaianTc::where('id_job_position', $id_job_position)
            ->where('status', 2)
            ->update([
                'status' => $nextStatus,
                'modified_updated' => auth()->user()->name,
            ]);

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function kirimDiv(Request $request, $id_job_position)
    {
        if (!$this->roleAccess->canAccessCompetencyLevel(auth()->user(), 'divhead')) {
            return $this->forbiddenJson('Anda tidak memiliki akses untuk approval Div Head.');
        }

        $jobPos = \App\Models\MstJobPosition::find($id_job_position);
        if (!$jobPos) {
            return $this->forbiddenJson('Job position tidak ditemukan.');
        }

        $hasFullAccess = $this->jobPositionAccess->hasFullAccess(auth()->user());

        if (!$hasFullAccess) {
            $scope = $this->jobPositionAccess->getUserApprovalScope(auth()->user());
            if (!in_array($jobPos->department_id, $scope['div_dept_ids'])) {
                return $this->forbiddenJson('Anda tidak memiliki akses ke departemen ini sebagai Div Head.');
            }
        }

        $penilaians = TrsPenilaianTc::where('id_job_position', $id_job_position)->get();

        if ($penilaians->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data not found.'], 404);
        }

        TrsPenilaianTc::where('id_job_position', $id_job_position)
            ->where('status', 3)
            ->update([
                'status' => 4,
                'modified_updated' => auth()->user()->name,
            ]);

        return response()->json(['success' => true, 'message' => 'Data Competency Telah Disetujui (Final).']);
    }
    //chartRadar
    public function getCompetencyData(Request $request)
    {
        $selectedJobPosition = $request->input('job_position');

        $competencyData = DB::table('trs_penilaian_tcs as tpt')
            ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
            ->leftJoin('mst_job_positions as mjp', 'tpt.id_job_position', '=', 'mjp.id')
            ->leftJoin('mst_tcs as tc', 'tpt.id_tc', '=', 'tc.id')
            ->leftJoin('mst_soft_skills as sk', 'tpt.id_sk', '=', 'sk.id')
            ->leftJoin('mst_additionals as ad', 'tpt.id_ad', '=', 'ad.id')
            ->select(
                'tpt.id_job_position',
                'mjp.position_name as job_position_name',
                'u.name',
                'tpt.id_user',
                DB::raw('GROUP_CONCAT(DISTINCT tpt.id_tc ORDER BY tpt.id_tc ASC) AS id_tcs'),
                DB::raw('GROUP_CONCAT(DISTINCT tc.keterangan_tc ORDER BY tpt.id_tc ASC) AS keterangan_tcs'),
                DB::raw('GROUP_CONCAT(DISTINCT tpt.id_sk ORDER BY tpt.id_sk ASC) AS id_sks'),
                DB::raw('GROUP_CONCAT(DISTINCT sk.keterangan_sk ORDER BY tpt.id_sk ASC) AS keterangan_sks'),
                DB::raw('GROUP_CONCAT(DISTINCT tpt.id_ad ORDER BY tpt.id_ad ASC) AS id_ads'),
                DB::raw('GROUP_CONCAT(DISTINCT ad.keterangan_ad ORDER BY tpt.id_ad ASC) AS keterangan_ads'),
                DB::raw('SUM(tpt.nilai_tc) AS total_nilai_tc'),
                DB::raw('SUM(tpt.nilai_sk) AS total_nilai_sk'),
                DB::raw('SUM(tpt.nilai_ad) AS total_nilai_ad'),
                DB::raw('SUM(tc.nilai) AS standar_nilai_tc'),
                DB::raw('SUM(sk.nilai) AS standar_nilai_sk'),
                DB::raw('SUM(ad.nilai) AS standar_nilai_ad')
            )
            ->where('tpt.id_job_position', $selectedJobPosition)
            ->groupBy('tpt.id_user', 'tpt.id_job_position', 'mjp.position_name', 'u.name')
            ->get();

        return response()->json($competencyData);
    }

    public function getCompetencyFilter(Request $request)
    {
        $jobPosition = $request->input('job_position');
        $dataType = $request->input('data_type');  // Ambil data_type dari request

        if ($dataType === 'total_nilai_tc') {
            // Query untuk data yang berhubungan dengan TC
            $data = DB::table('trs_penilaian_tcs as tpt')
                ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
                ->leftJoin('mst_tcs as tc', 'tpt.id_tc', '=', 'tc.id')
                ->select(
                    'tpt.id_job_position',
                    'u.name',
                    'tpt.id_user',
                    'tpt.id_tc',
                    'tc.keterangan_tc',
                    DB::raw('MAX(tc.nilai) as tc_nilai'), // Menggunakan fungsi agregasi MAX
                    DB::raw('SUM(tpt.nilai_tc) as total_nilai_tc')
                )
                ->where('tpt.id_job_position', $jobPosition)
                ->whereNotNull('tpt.id_tc')
                ->groupBy(
                    'tpt.id_user',
                    'tpt.id_job_position',
                    'u.name',
                    'tpt.id_tc',
                    'tc.keterangan_tc'
                )
                ->get();
        } elseif ($dataType === 'total_nilai_sk') {
            // Query untuk data yang berhubungan dengan SK
            $data = DB::table('trs_penilaian_tcs as tpt')
                ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
                ->leftJoin('mst_soft_skills as sk', 'tpt.id_sk', '=', 'sk.id')
                ->select(
                    'tpt.id_job_position',
                    'u.name',
                    'tpt.id_user',
                    'tpt.id_sk',
                    'sk.keterangan_sk',
                    DB::raw('MAX(sk.nilai) as sk_nilai'), // Menggunakan fungsi agregasi MAX
                    DB::raw('SUM(tpt.nilai_sk) as total_nilai_sk')
                )
                ->where('tpt.id_job_position', $jobPosition)
                ->whereNotNull('tpt.id_sk')
                ->groupBy(
                    'tpt.id_user',
                    'tpt.id_job_position',
                    'u.name',
                    'tpt.id_sk',
                    'sk.keterangan_sk'
                )
                ->get();
        } elseif ($dataType === 'total_nilai_ad') {
            // Query untuk data yang berhubungan dengan AD
            $data = DB::table('trs_penilaian_tcs as tpt')
                ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
                ->leftJoin('mst_additionals as ad', 'tpt.id_ad', '=', 'ad.id')
                ->select(
                    'tpt.id_job_position',
                    'u.name',
                    'tpt.id_user',
                    'tpt.id_ad',
                    'ad.keterangan_ad',
                    DB::raw('MAX(ad.nilai) as ad_nilai'), // Menggunakan fungsi agregasi MAX
                    DB::raw('SUM(tpt.nilai_ad) as total_nilai_ad')
                )
                ->where('tpt.id_job_position', $jobPosition)
                ->whereNotNull('tpt.id_ad')
                ->groupBy(
                    'tpt.id_user',
                    'tpt.id_job_position',
                    'u.name',
                    'tpt.id_ad',
                    'ad.keterangan_ad'
                )
                ->get();
        } else {
            // Jika data_type tidak sesuai, kembalikan respons kosong atau pesan kesalahan
            return response()->json([], 400);  // Kembalikan kode status 400 untuk permintaan tidak valid
        }

        // Mengembalikan data sebagai JSON
        return response()->json($data);
    }

    public function getDetailCompetency(Request $request)
    {
        $id_user = $request->query('id_user');

        // Query untuk data yang berhubungan dengan TC
        $tcData = DB::table('trs_penilaian_tcs as tpt')
            ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
            ->leftJoin('mst_job_positions as mjp', 'tpt.id_job_position', '=', 'mjp.id')
            ->leftJoin('mst_tcs as tc', 'tpt.id_tc', '=', 'tc.id')
            ->select(
                'tpt.id_job_position',
                'mjp.position_name as job_position_name',
                'u.name',
                'tpt.id_user',
                'tpt.id_tc',
                'tc.keterangan_tc',
                DB::raw('MAX(tc.nilai) as tc_nilai'), // Menggunakan fungsi agregasi MAX
                DB::raw('SUM(tpt.nilai_tc) as total_nilai_tc')
            )
            ->where('tpt.id_user', $id_user)
            ->whereNotNull('tpt.id_tc')
            ->groupBy(
                'tpt.id_user',
                'tpt.id_job_position',
                'mjp.position_name',
                'u.name',
                'tpt.id_tc',
                'tc.keterangan_tc'
            )
            ->get();

        // Query untuk data yang berhubungan dengan SK
        $skData = DB::table('trs_penilaian_tcs as tpt')
            ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
            ->leftJoin('mst_job_positions as mjp', 'tpt.id_job_position', '=', 'mjp.id')
            ->leftJoin('mst_soft_skills as sk', 'tpt.id_sk', '=', 'sk.id')
            ->select(
                'tpt.id_job_position',
                'mjp.position_name as job_position_name',
                'u.name',
                'tpt.id_user',
                'tpt.id_sk',
                'sk.keterangan_sk',
                DB::raw('MAX(sk.nilai) as sk_nilai'), // Menggunakan fungsi agregasi MAX
                DB::raw('SUM(tpt.nilai_sk) as total_nilai_sk')
            )
            ->where('tpt.id_user', $id_user)
            ->whereNotNull('tpt.id_sk')
            ->groupBy(
                'tpt.id_user',
                'tpt.id_job_position',
                'mjp.position_name',
                'u.name',
                'tpt.id_sk',
                'sk.keterangan_sk'
            )
            ->get();

        // Query untuk data yang berhubungan dengan AD
        $adData = DB::table('trs_penilaian_tcs as tpt')
            ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
            ->leftJoin('mst_job_positions as mjp', 'tpt.id_job_position', '=', 'mjp.id')
            ->leftJoin('mst_additionals as ad', 'tpt.id_ad', '=', 'ad.id')
            ->select(
                'tpt.id_job_position',
                'mjp.position_name as job_position_name',
                'u.name',
                'tpt.id_user',
                'tpt.id_ad',
                'ad.keterangan_ad',
                DB::raw('MAX(ad.nilai) as ad_nilai'), // Menggunakan fungsi agregasi MAX
                DB::raw('SUM(tpt.nilai_ad) as total_nilai_ad')
            )
            ->where('tpt.id_user', $id_user)
            ->whereNotNull('tpt.id_ad')
            ->groupBy(
                'tpt.id_user',
                'tpt.id_job_position',
                'mjp.position_name',
                'u.name',
                'tpt.id_ad',
                'ad.keterangan_ad'
            )
            ->get();

        // Query untuk TcPeopleDevelopment
        $dataTcPeopleDevelopment = TcPeopleDevelopment::where('id_user', $id_user)
            ->where('status_2', 'Done') // Add condition for status_2 to be 'Done'
            ->with('user') // Ensure the user relationship is loaded
            ->get();

        // Menggunakan model Eloquent untuk mengambil data penilaian
        $penilaians = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user'])
            ->where('id_user', $id_user)
            ->get(); // Mengambil semua record yang cocok

        // Gabungkan hasil query menjadi satu array
        $data = [
            'tc_data' => $tcData,
            'sk_data' => $skData,
            'ad_data' => $adData,
            'penilaians' => $penilaians,
            'dataTcPeopleDevelopment' => $dataTcPeopleDevelopment, // Tambahkan hasil penilaian ke dalam array data
        ];

        // Mengembalikan data sebagai JSON
        return response()->json($data);
    }



    /**
     * [4] Submit Draft — ubah status dari 0 (Draft) ke 1 (Menunggu Konfirmasi).
     */
    public function submitDraft(Request $request, $id_job_position)
    {
        if (!$this->roleAccess->canAccessCompetencyLevel(auth()->user(), 'kasie')) {
            return $this->forbiddenJson('Anda tidak memiliki akses untuk mengirim penilaian.');
        }

        $jobPos = \App\Models\MstJobPosition::find($id_job_position);
        if (!$jobPos) {
            return $this->forbiddenJson('Job position tidak ditemukan.');
        }

        $hasFullAccess = $this->jobPositionAccess->hasFullAccess(auth()->user());

        if (!$hasFullAccess) {
            $scope = $this->jobPositionAccess->getUserApprovalScope(auth()->user());
            
            if ($jobPos->section_id) {
                if (!in_array($jobPos->section_id, $scope['section_ids'])) {
                    return $this->forbiddenJson('Anda tidak memiliki akses ke section dari job position ini.');
                }
            } else {
                // Shared position (null section): check if Sub Section Head has access to the sections of the employees in this assessment
                $hasEmployeesInSection = TrsPenilaianTc::where('id_job_position', $id_job_position)
                    ->where('status', 0)
                    ->whereHas('user', function($q) use ($scope) {
                        $q->whereIn('section', \App\Models\MstSection::whereIn('id', $scope['section_ids'])->pluck('name'));
                    })
                    ->exists();
                if (!$hasEmployeesInSection) {
                    return $this->forbiddenJson('Anda tidak memiliki akses ke karyawan dari job position ini.');
                }
            }
        }

        $hasSubSecHead = \App\Models\MstPositionApproval::where('position_id', $id_job_position)
            ->where('approval_level', 0)
            ->whereNotNull('approver_position_id')
            ->exists();

        if (!$hasFullAccess && $hasSubSecHead) {
            // Jika ada Sub Sec Head, hanya user yang di-mapping sebagai level 0 yang boleh submit pertama
            $userPosIds = \App\Models\UserJobPosition::where('user_id', auth()->id())
                ->where('is_active', true)
                ->pluck('mst_job_position_id')
                ->toArray();
            $approver0 = \App\Models\MstPositionApproval::where('position_id', $id_job_position)
                ->where('approval_level', 0)
                ->first();
            if (!$approver0 || !in_array($approver0->approver_position_id, $userPosIds)) {
                return $this->forbiddenJson('Anda bukan Sub Section Head yang ditugaskan untuk submit draft ini.');
            }
        }

        // Jika ada Sub Sec Head, submit pertama ke Kasie (status=1), jika tidak langsung ke Kadept (status=2)
        $nextStatus = $hasSubSecHead ? 1 : 2;

        $query = TrsPenilaianTc::where('id_job_position', $id_job_position)
            ->where('status', 0);

        if (!$hasFullAccess) {
            $scope = $this->jobPositionAccess->getUserApprovalScope(auth()->user());
            $allowedSectionNames = \App\Models\MstSection::whereIn('id', $scope['section_ids'])->pluck('name')->toArray();
            
            $query->whereHas('user', function($q) use ($allowedSectionNames) {
                $q->whereIn('section', $allowedSectionNames);
            });
        }

        $updated = $query->update([
            'status' => $nextStatus,
            'modified_at' => auth()->user()->id,
            'modified_updated' => auth()->user()->name,
        ]);

        if ($updated === 0) {
            return response()->json(['success' => false, 'message' => 'Tidak ada data Draft untuk dikirim.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Draft berhasil dikirim untuk review.']);
    }

    /**
     * [3] Koreksi post-approval oleh Section Head atau Dept Head.
     * Section Head edit → status kembali ke 1 (Menunggu Konfirmasi)
     * Dept Head edit → status tetap 3, tercatat atas nama Dept Head
     */
    public function correctPenilaian(Request $request, $id_job_position)
    {
        $decoded_job_position = html_entity_decode($id_job_position);
        $user = auth()->user();

        $isSectionHead = $this->roleAccess->isKaSie($user);
        $isDeptHead = $this->roleAccess->isKaDept($user);

        if (!$isSectionHead && !$isDeptHead) {
            return $this->forbiddenJson('Hanya Section Head atau Dept Head yang dapat melakukan koreksi.');
        }

        $data = $request->json()->all();
        $records = $data['records'] ?? [];
        $changesByName = [];
        $beforeValuesByName = [];
        $correctedByRole = $isSectionHead ? 'section_head' : 'dept_head';

        foreach ($records as $record) {
            $recordId = $record['id'] ?? null;
            if (!$recordId) continue;

            $penilaian = TrsPenilaianTc::where('id', $recordId)
                ->where('id_job_position', $decoded_job_position)
                ->first();

            if (!$penilaian) continue;

            $hasChanged = false;
            $userName = $record['name'] ?? 'Unknown';
            $currentKeteranganDetail = [];
            $beforeValues = [];

            $nilaiTcValue = $this->normalizeScoreValue($record['nilai_tc'] ?? null);
            $nilaiSkValue = $this->normalizeScoreValue($record['nilai_sk'] ?? null);
            $nilaiAdValue = $this->normalizeScoreValue($record['nilai_ad'] ?? null);

            if ($nilaiTcValue !== null && (int) $penilaian->nilai_tc !== $nilaiTcValue) {
                $beforeValues['nilai_tc'] = $penilaian->nilai_tc;
                $penilaian->nilai_tc = $nilaiTcValue;
                $hasChanged = true;
                $keteranganTc = $record['keterangan_tc'] ?? '-';
                $currentKeteranganDetail[] = "[KOREKSI] TC: {$keteranganTc} = {$beforeValues['nilai_tc']} → {$nilaiTcValue}";
            }

            if ($nilaiSkValue !== null && (int) $penilaian->nilai_sk !== $nilaiSkValue) {
                $beforeValues['nilai_sk'] = $penilaian->nilai_sk;
                $penilaian->nilai_sk = $nilaiSkValue;
                $hasChanged = true;
                $keteranganSk = $record['keterangan_sk'] ?? '-';
                $currentKeteranganDetail[] = "[KOREKSI] SK: {$keteranganSk} = {$beforeValues['nilai_sk']} → {$nilaiSkValue}";
            }

            if ($nilaiAdValue !== null && (int) $penilaian->nilai_ad !== $nilaiAdValue) {
                $beforeValues['nilai_ad'] = $penilaian->nilai_ad;
                $penilaian->nilai_ad = $nilaiAdValue;
                $hasChanged = true;
                $keteranganAd = $record['keterangan_ad'] ?? '-';
                $currentKeteranganDetail[] = "[KOREKSI] AD: {$keteranganAd} = {$beforeValues['nilai_ad']} → {$nilaiAdValue}";
            }

            if ($hasChanged) {
                // [3] Section Head edit → status kembali ke 1 (Menunggu Konfirmasi)
                if ($isSectionHead && !$isDeptHead) {
                    $penilaian->status = 1;
                }
                // [3] Dept Head edit → status tetap, tercatat atas nama Dept Head
                $penilaian->modified_updated = $user->name;
                $penilaian->save();

                if (!isset($changesByName[$userName])) {
                    $changesByName[$userName] = [];
                }
                $changesByName[$userName] = array_merge($changesByName[$userName], $currentKeteranganDetail);

                if (!isset($beforeValuesByName[$userName])) {
                    $beforeValuesByName[$userName] = [];
                }
                $beforeValuesByName[$userName] = array_merge($beforeValuesByName[$userName], $beforeValues);
            }
        }

        // Jika Section Head koreksi, reset SEMUA record pada job position ini ke status 1
        if ($isSectionHead && !$isDeptHead && !empty($changesByName)) {
            TrsPenilaianTc::where('id_job_position', $decoded_job_position)
                ->where('status', '>', 1)
                ->update([
                    'status' => 1,
                    'modified_at' => $user->id,
                    'modified_updated' => $user->name,
                ]);
        }

        // Simpan detail perubahan
        foreach ($changesByName as $userName => $keteranganDetails) {
            DetailTcPenilaian::create([
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails),
                'nilai_sebelum' => json_encode($beforeValuesByName[$userName] ?? []),
                'catatan' => $data['alasan_perubahan'] ?? null,
                'modified_at' => $user->name,
                'corrected_by_role' => $correctedByRole,
            ]);
        }

        $actionMessage = $isSectionHead && !$isDeptHead
            ? 'Koreksi berhasil. Status dikembalikan ke Menunggu Konfirmasi.'
            : 'Koreksi berhasil dicatat atas nama Dept Head.';

        return response()->json(['success' => true, 'message' => $actionMessage]);
    }

    /**
     * [3] View history penilaian per tahun (termasuk yang sudah terkunci).
     */
    public function yearlyHistory($id_job_position, $year)
    {
        $this->abortUnlessJobPositionAccessible($id_job_position);

        $penilaian = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user', 'jobPosition'])
            ->where('id_job_position', $id_job_position)
            ->forYear((int) $year)
            ->orderByDesc('status')
            ->first();

        abort_if(!$penilaian, 404, 'Data penilaian belum tersedia untuk Job Position ini.');

        $dataTc1 = PoinKategori::find(1);
        $dataTc2 = PoinKategori::find(2);
        $dataTc3 = PoinKategori::find(3);

        $detailPenilaian = DetailTcPenilaian::where('id_job_position', $id_job_position)
            ->orderBy('created_at', 'desc')
            ->get();

        $availableYears = TrsPenilaianTc::where('id_job_position', $id_job_position)
            ->select('tahun_penilaian')->distinct()->orderByDesc('tahun_penilaian')
            ->pluck('tahun_penilaian')->toArray();

        $isLocked = $penilaian ? $penilaian->is_locked : true;

        return view('tc_penilaian.view_penilaian', compact(
            'penilaian', 'dataTc1', 'dataTc2', 'dataTc3',
            'detailPenilaian', 'year', 'availableYears', 'isLocked'
        ));
    }

    /**
     * [4] Halaman monitoring admin/HR — status pengajuan per orang/section.
     */
    public function monitoringIndex(Request $request)
    {
        // Hanya admin/HR
        abort_unless($this->roleAccess->hasFullAccess(auth()->user()), 403);

        $availableYears = TrsPenilaianTc::getAvailableYears();
        if (empty($availableYears)) {
            $availableYears = [now()->year];
        }
        $selectedYear = (int) $request->query('year', $availableYears[0]);
        $filterSection = $request->query('section');
        $filterStatus = $request->query('status');

        $query = TrsPenilaianTc::forYear($selectedYear)
            ->select('id_job_position', 'id_user', 'status', 'tahun_penilaian', 'is_locked', 'modified_updated', 'updated_at')
            ->with('user');

        if ($filterStatus !== null && $filterStatus !== '') {
            $query->where('status', (int) $filterStatus);
        }

        $allRecords = $query->orderByDesc('updated_at')->get();

        // Group by id_job_position + id_user untuk unique entries
        $monitoringData = $allRecords->unique(fn($item) => $item->id_job_position . '-' . $item->id_user)->values();

        // Filter by section jika dipilih
        if ($filterSection) {
            $jobPositionsInSection = MstJobPosition::whereHas('section', function($q) use ($filterSection) {
                $q->where('name', $filterSection);
            })->pluck('id')->unique()->toArray();
            $monitoringData = $monitoringData->filter(
                fn($item) => in_array(trim($item->id_job_position), $jobPositionsInSection)
            )->values();
        }

        // Ambil daftar sections untuk filter dropdown
        $sections = \App\Models\MstSection::pluck('name');

        $summary = [
            'total' => $monitoringData->count(),
            'draft' => $monitoringData->where('status', 0)->count(),
            'menunggu' => $monitoringData->where('status', 1)->count(),
            'dept_review' => $monitoringData->where('status', 2)->count(),
            'approved' => $monitoringData->where('status', 3)->count(),
        ];

        return view('tc_penilaian.monitoring', compact(
            'monitoringData', 'sections', 'selectedYear', 'availableYears',
            'filterSection', 'filterStatus', 'summary'
        ));
    }

    /**
     * [3] Get summary data with average per kategori.
     */
    public function getSummaryWithAverage(Request $request)
    {
        $jobPosition = $request->input('job_position');
        $year = (int) $request->input('year', now()->year);

        $records = TrsPenilaianTc::where('id_job_position', $jobPosition)
            ->forYear($year)
            ->with(['tc', 'sk', 'ad', 'user'])
            ->get();

        // Hitung average per user
        $userGroups = $records->groupBy('id_user');
        $averages = [];

        foreach ($userGroups as $userId => $userRecords) {
            $tcValues = $userRecords->pluck('nilai_tc')->filter(fn($v) => $v !== null);
            $skValues = $userRecords->pluck('nilai_sk')->filter(fn($v) => $v !== null);
            $adValues = $userRecords->pluck('nilai_ad')->filter(fn($v) => $v !== null);

            $userName = optional($userRecords->first()->user)->name ?? 'Unknown';

            $avgTc = $tcValues->count() > 0 ? round($tcValues->avg(), 2) : null;
            $avgSk = $skValues->count() > 0 ? round($skValues->avg(), 2) : null;
            $avgAd = $adValues->count() > 0 ? round($adValues->avg(), 2) : null;

            $allValues = collect([$avgTc, $avgSk, $avgAd])->filter(fn($v) => $v !== null);
            $avgTotal = $allValues->count() > 0 ? round($allValues->avg(), 2) : null;

            $averages[] = [
                'id_user' => $userId,
                'name' => $userName,
                'avg_tc' => $avgTc,
                'avg_sk' => $avgSk,
                'avg_ad' => $avgAd,
                'avg_total' => $avgTotal,
            ];
        }

        return response()->json([
            'records' => $records,
            'averages' => $averages,
        ]);
    }
}

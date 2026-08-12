<?php

namespace App\Http\Controllers;

use App\Enums\TrainingStatus;
use App\Http\Requests\UpdateTrainingEvaluationRequest;
use App\Http\Requests\UpdateTrainingFollowUpRequest;
use App\Models\TcPeopleDevelopment;
use App\Models\TrsPenilaianTc;
use App\Models\PoinKategori;
use App\Models\User;
use App\Models\Role;
use App\Models\BtnStatus;
use App\Models\DetailTcPenilaian;
use App\Models\MstPdActiveYear;
use App\Services\HR\HRRoleAccessService;
use App\Services\HR\JobPositionAccessService;
use App\Services\HR\TrainingDevelopmentService;
use App\Services\HR\TrainingEvaluationService;
use App\Services\HR\TrainingHistoryQueryService;
use App\Services\HR\TrainingHistoryPresentationService;
use App\Services\HR\TrainingParticipantService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache; // Import the Cache facade
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;


class PdController extends Controller
{
    public function __construct(
        private JobPositionAccessService $jobPositionAccess,
        private HRRoleAccessService $roleAccess,
        private TrainingDevelopmentService $trainingService,
        private TrainingEvaluationService $trainingEvaluation,
        private TrainingParticipantService $participantService,
        private TrainingHistoryQueryService $trainingHistory,
        private TrainingHistoryPresentationService $trainingHistoryPresentation,
    )
    {
    }

    /**
     * Ambil daftar sections dari tc_job_positions secara dinamis, difilter per departemen.
     */
    /**
     * Ambil daftar sections (string nama) yang dapat diakses user.
     * @deprecated Gunakan getSectionIdsForUser() atau getSectionObjectsForUser().
     */
    private function getSectionsForUser(): array
    {
        return $this->jobPositionAccess->getAccessibleSections(auth()->user(), false);
    }

    /**
     * Ambil daftar MstSection object (dengan id + name) untuk dropdown di form.
     */
    private function getSectionObjectsForUser()
    {
        return $this->jobPositionAccess->getAccessibleSectionObjects(auth()->user(), false);
    }

    /**
     * Ambil array section_id (integer) yang dapat diakses user, untuk filter query DB.
     */
    private function getSectionIdsForUser(): array
    {
        return $this->jobPositionAccess->getAccessibleSectionIds(auth()->user(), false);
    }

    private function getJobPositionsForUser()
    {
        return $this->jobPositionAccess->getAccessibleJobPositionOptions(auth()->user(), false);
    }

    private function rejectInvalidJobPositions(Request $request, array $jobPositions)
    {
        $invalidJobPositions = $this->jobPositionAccess
            ->getInvalidJobPositions(auth()->user(), $jobPositions);

        if (empty($invalidJobPositions)) {
            return null;
        }

        $message = 'Anda tidak memiliki akses untuk job position: ' . implode(', ', $invalidJobPositions);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['error' => $message], 403);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }

    private function abortUnlessCanManageTraining(): void
    {
        abort_unless(
            $this->roleAccess->canAccessTrainingDevelopment(auth()->user()),
            403,
            'Anda tidak memiliki akses untuk pengajuan training.'
        );
    }

    private function abortUnlessCanApproveTraining(): void
    {
        abort_unless(
            $this->roleAccess->canApproveTrainingDevelopment(auth()->user()),
            403,
            'Anda tidak memiliki akses untuk persetujuan training.'
        );
    }

    /**
     * Ambil daftar penilaian kompetensi yang sudah disetujui untuk user saat ini.
     * Mendelegasikan logika ke TrainingDevelopmentService.
     */
    private function getApprovedCompetencyPenilaians()
    {
        $allowedJobs = $this->jobPositionAccess
            ->getAccessibleJobPositions(auth()->user(), false)
            ->pluck('id')
            ->all();

        return $this->trainingService->getApprovedCompetencyPenilaians($allowedJobs);
    }

    /**
     * Validasi baris competency dari request dan kembalikan response error jika gagal.
     * Mendelegasikan validasi ke TrainingDevelopmentService.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|null
     */
    private function rejectInvalidCompetencyRows(Request $request, array $data, string $prefix = '')
    {
        $penilaians = $this->getApprovedCompetencyPenilaians();
        $errors     = $this->trainingService->validateCompetencyRows($penilaians, $data, $prefix);

        if (empty($errors)) {
            return null;
        }

        $message = implode(' ', $errors);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['error' => $message], 422);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }

    /**
     * Buat kunci lookup competency untuk validasi (delegate ke service).
     */
    private function competencyLookupKey($userId, string $jobPosition, string $category, string $competency): string
    {
        return $this->trainingService->competencyLookupKey($userId, $jobPosition, $category, $competency);
    }

    /**
     * Format teks opsi competency untuk dropdown (delegate ke service).
     */
    private function formatCompetencyOption($keterangan, $nilaiStandard, $nilaiAktual): string
    {
        return $this->trainingService->formatCompetencyOption($keterangan, $nilaiStandard, $nilaiAktual);
    }

    /**
     * Normalisasi kategori competency ke nilai standar (delegate ke service).
     */
    private function normalizeCompetencyCategory(?string $category): string
    {
        return $this->trainingService->normalizeCompetencyCategory($category);
    }

    public function indexPD()
    {
        $this->abortUnlessCanManageTraining();

        // Ambil ID peran pengguna yang login
        $loggedInUserRoleId = auth()->user()->role_id;

        // Ambil nama pengguna yang login
        $loggedInUser = auth()->user()->name;

        // Subquery untuk mendapatkan id maksimum untuk setiap modified_at dan tahun_aktual
        $subQuery = TcPeopleDevelopment::select('tahun_aktual', 'modified_at', DB::raw('MAX(id) as max_id'))
            ->groupBy('tahun_aktual', 'modified_at');

        // Main query untuk mendapatkan data terbaru berdasarkan id maksimum dari subquery
        $query = TcPeopleDevelopment::with([
            'user:id,npk,name', 'participants:id,npk,name', 'section.department', 'jobPosition.department',
        ])->joinSub($subQuery, 'sub', function ($join) {
            $join->on('mst_pd_pengajuans.id', '=', 'sub.max_id');
        });

        // Tambahkan filter untuk `modified_at` jika bukan HR/Admin full access
        // Dan kita hapus hardcoding untuk list di indexPD agar semua job position yg dimanage bisa muncul
        if (!$this->roleAccess->hasFullAccess(auth()->user())) {
            // Jika dia Kasie atau Dept Head biasa, hanya lihat punya miliknya sendiri
            $query->where('mst_pd_pengajuans.modified_at', $loggedInUser);
        }

        // Ambil data tanpa limit jika role_id adalah 1, 14, atau 15, atau limit 1 untuk lainnya
        $data = $query->get();

        // Ambil tahun_aktual dari data pertama (jika ada)
        $tahun_aktual = $data->first()->tahun_aktual ?? now()->year;

        // Query untuk KPI:
        // - Admin/full-access: ambil SEMUA data (bukan hanya miliknya sendiri)
        // - User biasa: hanya data dengan modified_at = nama user yang login
        if ($this->roleAccess->hasFullAccess(auth()->user())) {
            $data2 = TcPeopleDevelopment::get();
        } else {
            $data2 = TcPeopleDevelopment::where('modified_at', $loggedInUser)->get();
        }

        // Hitung metrik/KPI untuk ditampilkan di atas tabel
        $kpiTotalProgram = $data2->pluck('program_training')->filter()->unique()->count();
        $kpiTotalTraining = $data2->count();
        $kpiTotalBiaya = $data2->sum(function ($item) {
            $val = preg_replace('/[^0-9.-]/', '', $item->biaya);
            return is_numeric($val) ? (float) $val : 0;
        });
        $kpiTotalKaryawan = $data2->pluck('id_user')->filter()->unique()->count();
        $kpiStatusDraft = $data->where('status_1', 1)->count();
        $kpiStatusProses = $data->where('status_1', 2)->count();
        $kpiStatusSelesai = $data->where('status_1', 3)->count();

        // Kpi for status_2 (progress)
        $kpiMencariVendor = $data2->where('status_2', \App\Enums\TrainingStatus::MENCARI_VENDOR)->count();
        $kpiProsesPendaftaran = $data2->where('status_2', \App\Enums\TrainingStatus::PROSES_PENDAFTARAN)->count();
        $kpiOnProgress = $data2->where('status_2', \App\Enums\TrainingStatus::ON_PROGRESS)->count();
        $kpiDone = $data2->where('status_2', \App\Enums\TrainingStatus::DONE)->count();
        $kpiPending = $data2->where('status_2', \App\Enums\TrainingStatus::PENDING)->count();
        $kpiDitolak = $data2->where('status_2', \App\Enums\TrainingStatus::DITOLAK)->count();

        // Tampilkan pengingat jika kegiatan Done tetapi evaluasinya belum lengkap.
        $hasDoneStatus = $data2->contains(function ($item) {
            return $item->status_2 === TrainingStatus::DONE
                && ! $item->evaluation_completed;
        });

        $activeYear = \App\Models\MstPdActiveYear::getActiveYear();

        // Mengirim data ke view
        $buttonStatus = Storage::exists('button_status.txt') ? (int) Storage::get('button_status.txt') : 0;
        return view('people_development.dept_develop_index', compact(
            'data', 'tahun_aktual', 'hasDoneStatus', 'buttonStatus',
            'kpiTotalProgram', 'kpiTotalTraining', 'kpiTotalBiaya', 'kpiTotalKaryawan',
            'kpiStatusDraft', 'kpiStatusProses', 'kpiStatusSelesai',
            'kpiMencariVendor', 'kpiProsesPendaftaran', 'kpiOnProgress',
            'kpiDone', 'kpiPending', 'kpiDitolak', 'activeYear'
        ));
    }

    public function indexPD2(Request $request)
    {
        $this->abortUnlessCanApproveTraining();

        // Mendapatkan tahun saat ini
        $currentYear = Carbon::now()->year;

        // Rentang tahun tersedia: 5 tahun ke belakang s.d. 2 tahun ke depan
        $availableYears = [];
        for ($i = -5; $i <= 2; $i++) {
            $availableYears[] = (string)($currentYear + $i);
        }

        // Filter tahun dari request (jika ada), default tampilkan semua
        $selectedYear = $request->query('year', ''); // '' = semua tahun

        // Tentukan tahun yang dipakai di query
        $filterYears = $selectedYear ? [$selectedYear] : $availableYears;

        // Subquery untuk mendapatkan id maksimum per tahun_aktual
        $subQuery = TcPeopleDevelopment::select('tahun_aktual', DB::raw('MAX(id) as max_id'))
            ->whereIn('status_1', [2, 3])
            ->whereIn('tahun_aktual', $filterYears)
            ->groupBy('tahun_aktual');

        // Mengambil data dari TcPeopleDevelopment beserta relasi Role
        $data = TcPeopleDevelopment::with([
                'role', 'user:id,npk,name', 'participants:id,npk,name', 'section.department', 'jobPosition.department',
            ])
            ->joinSub($subQuery, 'sub', function ($join) {
                $join->on('mst_pd_pengajuans.id', '=', 'sub.max_id');
            })
            ->orderBy('mst_pd_pengajuans.tahun_aktual', 'desc')
            ->get();

        // Ambil semua data yang statusnya dikirim (2) atau disetujui (3) pada rentang tahun tersebut
        $allHrgaData = TcPeopleDevelopment::whereIn('status_1', [2, 3])
            ->whereIn('tahun_aktual', $filterYears)
            ->get();

        // Hitung metrik HRGA
        $kpiTotalBiayaUsulan = $allHrgaData->sum(function ($item) {
            $val = preg_replace('/[^0-9.-]/', '', $item->biaya);
            return is_numeric($val) ? (float) $val : 0;
        });
        $kpiTotalBiayaPlan = $allHrgaData->sum(function ($item) {
            $val = preg_replace('/[^0-9.-]/', '', $item->biaya_plan);
            return is_numeric($val) ? (float) $val : 0;
        });
        $kpiTotalKaryawan = $allHrgaData->pluck('id_user')->filter()->unique()->count();
        $kpiTotalTraining = $allHrgaData->count();
        $kpiMencariVendor = $allHrgaData->where('status_2', TrainingStatus::MENCARI_VENDOR)->count();
        $kpiProsesPendaftaran = $allHrgaData->where('status_2', TrainingStatus::PROSES_PENDAFTARAN)->count();
        $kpiOnProgress = $allHrgaData->where('status_2', TrainingStatus::ON_PROGRESS)->count();
        $kpiDone = $allHrgaData->where('status_2', TrainingStatus::DONE)->count();
        $kpiPending = $allHrgaData->where('status_2', TrainingStatus::PENDING)->count();
        $kpiDitolak = $allHrgaData->where('status_2', TrainingStatus::DITOLAK)->count();

        $activeYear = \App\Models\MstPdActiveYear::getActiveYear();

        // Mengirim data ke view
        return view('people_development.hrga_develop_index', compact(
            'data',
            'kpiTotalBiayaUsulan', 'kpiTotalBiayaPlan', 'kpiTotalKaryawan', 'kpiTotalTraining',
            'kpiMencariVendor', 'kpiProsesPendaftaran', 'kpiOnProgress',
            'kpiDone', 'kpiPending', 'kpiDitolak',
            'availableYears', 'selectedYear', 'activeYear'
        ));
    }

    public function historiDevelop(Request $request)
    {
        $actor = $request->user();
        $historyFilters = $this->trainingHistoryFilters($request);
        $historyPayload = $this->trainingHistoryPresentation->payload(
            $actor,
            $this->trainingHistory->flattened($actor, $historyFilters),
        );

        $scope = $this->jobPositionAccess->getUserApprovalScope($actor);
        if ($this->roleAccess->hasFullAccess($actor)) {
            $departmentIds = \App\Models\MstDepartment::query()
                ->active()
                ->pluck('id')
                ->all();
        } else {
            $sectionDepartmentIds = \App\Models\MstSection::query()
                ->whereIn('id', array_values(array_unique($scope['section_ids'])))
                ->pluck('department_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();
            $departmentIds = array_values(array_unique(array_merge(
                $scope['dept_ids'],
                $scope['div_dept_ids'],
                $sectionDepartmentIds,
            )));
        }

        $departments = \App\Models\MstDepartment::query()
            ->whereIn('id', $departmentIds)
            ->orderBy('name')
            ->get(['id', 'name']);
        $availableYears = range((int) now()->year, 2015);

        return view('people_development.histori_develop', compact(
            'historyPayload',
            'historyFilters',
            'departments',
            'availableYears',
        ));
    }

    public function viewPD($modified_at, $tahun_aktual)
    {
        $this->abortUnlessCanManageTraining();

        // Mengambil nama pengguna yang sedang login
        $userName = auth()->user()->name;

        // Mengambil ID pengguna
        $userId = auth()->user()->name;

        // Get the role of the authenticated user
        $roleId = auth()->user()->role_id;

        // Ambil data berdasarkan modified_at dan id_user sesuai dengan nama pengguna
        $query = TcPeopleDevelopment::where('tahun_aktual', $tahun_aktual)
            ->where('modified_at', $modified_at)
            ->with(['role', 'user:id,npk,name', 'participants:id,npk,name', 'jobPosition.department', 'section.department']);

        if (!$this->roleAccess->hasFullAccess(auth()->user())) {
            // Jika role bukan 1, 14, atau 15, filter memastikan hanya bisa melihat miliknya sendiri
            $query->where('modified_at', $userId);
        }

        $data = $query->get();

        $sections = $this->getSectionObjectsForUser();

        // Mengambil job positions beserta relasinya
        $jobPositions = $this->getJobPositionsForUser();

        $penilaians = $this->getApprovedCompetencyPenilaians();

        // Mengambil data berdasarkan modified_at dan id_user sesuai dengan nama pengguna untuk filtering
        $filteredData = TcPeopleDevelopment::where('modified_at', $modified_at)
            ->where('tahun_aktual', $tahun_aktual)
            ->when(!$this->roleAccess->hasFullAccess(auth()->user()), function ($query) use ($userId) {
                $query->where('modified_at', $userId);
            })
            ->get();

        // Menghitung jumlah total data yang sudah difilter
        $totalRecords = $filteredData->count();

        // Menghitung jumlah data berdasarkan status sesuai dengan filtered data
        $countStatusBlue = $filteredData->where('status_2', TrainingStatus::MENCARI_VENDOR)->count();
        $countStatusOrange = $filteredData->where('status_2', TrainingStatus::PROSES_PENDAFTARAN)->count();
        $countStatusYellow = $filteredData->where('status_2', TrainingStatus::ON_PROGRESS)->count();
        $countStatusGreen = $filteredData->where('status_2', TrainingStatus::DONE)->count();
        $countStatusGray = $filteredData->where('status_2', TrainingStatus::PENDING)->count();
        $countStatusRed = $filteredData->where('status_2', TrainingStatus::DITOLAK)->count();

        // Menghitung persentase masing-masing status
        $percentageStatusBlue = $totalRecords > 0 ? ($countStatusBlue / $totalRecords) * 100 : 0;
        $percentageStatusOrange = $totalRecords > 0 ? ($countStatusOrange / $totalRecords) * 100 : 0;
        $percentageStatusYellow = $totalRecords > 0 ? ($countStatusYellow / $totalRecords) * 100 : 0;
        $percentageStatusGreen = $totalRecords > 0 ? ($countStatusGreen / $totalRecords) * 100 : 0;
        $percentageStatusGray = $totalRecords > 0 ? ($countStatusGray / $totalRecords) * 100 : 0;
        $percentageStatusRed = $totalRecords > 0 ? ($countStatusRed / $totalRecords) * 100 : 0;

        // Kirim data ke view
        return view('people_development.view_develop', compact(
            'data',
            'sections',
            'jobPositions',
            'penilaians',
            'totalRecords',
            'countStatusBlue',
            'countStatusOrange',
            'countStatusYellow',
            'countStatusGreen',
            'countStatusGray',
            'countStatusRed',
            'percentageStatusBlue',
            'percentageStatusOrange',
            'percentageStatusYellow',
            'percentageStatusGreen',
            'percentageStatusGray',
            'percentageStatusRed'
        ));
    }

    public function viewPD2($tahun_aktual)
    {
        $this->abortUnlessCanApproveTraining();

        // Ambil data yang tidak memiliki tahun_usulan
        $dataTanpaTahunUsulan = TcPeopleDevelopment::with(['role', 'user:id,npk,name', 'participants:id,npk,name', 'jobPosition.department', 'section.department'])
            ->where('tahun_aktual', $tahun_aktual)
            ->whereNull('tahun_usulan')
            ->get();

        // Ambil data yang memiliki tahun_usulan
        $dataDenganTahunUsulan = TcPeopleDevelopment::with(['role', 'user:id,npk,name', 'participants:id,npk,name', 'jobPosition.department', 'section.department'])
            ->where('tahun_aktual', $tahun_aktual)
            ->whereNotNull('tahun_usulan')
            ->get();

        // Gabungkan kedua data
        $data = $dataTanpaTahunUsulan->merge($dataDenganTahunUsulan);

        // Menghitung jumlah total data yang sudah difilter
        $totalRecords = $data->count();

        // Menghitung jumlah data berdasarkan status
        $countStatusBlue = $data->where('status_2', \App\Enums\TrainingStatus::MENCARI_VENDOR ?? 'Mencari Vendor')->count();
        $countStatusOrange = $data->where('status_2', \App\Enums\TrainingStatus::PROSES_PENDAFTARAN ?? 'Proses Pendaftaran')->count();
        $countStatusYellow = $data->where('status_2', \App\Enums\TrainingStatus::ON_PROGRESS ?? 'On Progress')->count();
        $countStatusGreen = $data->where('status_2', \App\Enums\TrainingStatus::DONE ?? 'Done')->count();
        $countStatusGray = $data->where('status_2', \App\Enums\TrainingStatus::PENDING ?? 'Pending')->count();
        $countStatusRed = $data->where('status_2', \App\Enums\TrainingStatus::DITOLAK ?? 'Ditolak')->count();

        // Menghitung persentase masing-masing status
        $percentageStatusBlue = $totalRecords > 0 ? ($countStatusBlue / $totalRecords) * 100 : 0;
        $percentageStatusOrange = $totalRecords > 0 ? ($countStatusOrange / $totalRecords) * 100 : 0;
        $percentageStatusYellow = $totalRecords > 0 ? ($countStatusYellow / $totalRecords) * 100 : 0;
        $percentageStatusGreen = $totalRecords > 0 ? ($countStatusGreen / $totalRecords) * 100 : 0;
        $percentageStatusGray = $totalRecords > 0 ? ($countStatusGray / $totalRecords) * 100 : 0;
        $percentageStatusRed = $totalRecords > 0 ? ($countStatusRed / $totalRecords) * 100 : 0;

        // Kirim data ke view
        return view('people_development.view_develop_hrga', compact(
            'data',
            'totalRecords',
            'countStatusBlue',
            'countStatusOrange',
            'countStatusYellow',
            'countStatusGreen',
            'countStatusGray',
            'countStatusRed',
            'percentageStatusBlue',
            'percentageStatusOrange',
            'percentageStatusYellow',
            'percentageStatusGreen',
            'percentageStatusGray',
            'percentageStatusRed'
        ));
    }

    public function exportPD2($tahun_aktual)
    {
        return app(TrainingExportController::class)->followUp(request(), (int) $tahun_aktual);
    }

    public function createPD()
    {
        $this->abortUnlessCanManageTraining();

        $sections = $this->getSectionObjectsForUser();

        // Fetch job positions with relationships to users
        $jobPositions = $this->getJobPositionsForUser();

        $penilaians = $this->getApprovedCompetencyPenilaians();

        // Pass sections and other data to the view
        return view('people_development.create_develop', compact('sections', 'jobPositions', 'penilaians'));
    }

    public function savePdPengajuan(Request $request)
    {
        $this->abortUnlessCanManageTraining();

        $data = $request->all();
        $userName = Auth::user()->name; // Mengambil nama user yang sedang login


        // Cek apakah kunci 'id_user' ada dalam $data
        if (!isset($data['id_user']) || !is_array($data['id_user'])) {
            return redirect()->back()->with('error', 'Data id_user tidak ditemukan atau tidak valid.');
        }

        if ($response = $this->rejectInvalidJobPositions($request, $data['id_job_position'] ?? [])) {
            return $response;
        }

        if ($response = $this->rejectInvalidCompetencyRows($request, $data)) {
            return $response;
        }

        // Loop melalui setiap row dan simpan data
        foreach ($data['id_user'] as $key => $value) {

            $tahunAktual = date('Y') + 1; // Mendapatkan tahun depan
            TcPeopleDevelopment::create([
                'section_id'          => $data['section_id'][$key] ?? null,
                'id_job_position'     => $data['id_job_position'][$key] ?? null,
                'id_user'             => $data['id_user'][$key] ?? null,
                'program_training'    => $data['program_training'][$key] ?? null,
                'kategori_competency' => $data['kategori_competency'][$key] ?? null,
                'competency'          => $data['competency'][$key] ?? null,
                'due_date'            => $data['due_date'][$key] ?? null,
                'biaya'               => $data['biaya'][$key] ?? null,
                'lembaga'             => $data['lembaga'][$key] ?? null,
                'keterangan_tujuan'   => $data['keterangan_tujuan'][$key] ?? null,
                'objective_learning'  => $data['objective_learning'][$key] ?? null, // Modul 4.4
                'modified_at'         => $userName,
                'status_1'            => 1,
                'tahun_aktual'        => $tahunAktual,
            ]);
        }

        return redirect()->route('indexPD')->with('success', 'Data berhasil diperbarui');
    }

    public function savePdPengajuanDept(Request $request)
    {
        return $this->savePdPengajuan($request);
    }

    public function editPdPengajuan($modified_at, $tahun_aktual)
    {
        $this->abortUnlessCanManageTraining();

        $sections = $this->getSectionObjectsForUser();

        // Fetch job positions along with their relations
        $jobPositions = $this->getJobPositionsForUser();

        $penilaians = $this->getApprovedCompetencyPenilaians();

        // Fetch TcPeopleDevelopment data based on the modified_at timestamp
        $data = TcPeopleDevelopment::where('modified_at', $modified_at)
            ->where('tahun_aktual', $tahun_aktual)
            ->with(['user:id,npk,name', 'participants:id,npk,name', 'section.department', 'jobPosition.department'])
            ->when(!$this->roleAccess->hasFullAccess(auth()->user()), function ($query) {
                $query->where('modified_at', auth()->user()->name);
            })
            ->get();

        // Pass data to the view, including sections, job positions, and evaluations
        return view('people_development.edit_develop', compact('data', 'sections', 'jobPositions', 'penilaians'));
    }

    public function editPdPengajuanHRGA($tahun_aktual)
    {
        $this->abortUnlessCanApproveTraining();

        $sections = $this->getSectionObjectsForUser();

        // Mengambil job positions beserta relasinya
        $jobPositions = $this->getJobPositionsForUser();

        $penilaians = $this->getApprovedCompetencyPenilaians();

        // Mengambil semua data TcPeopleDevelopment tanpa filter modified_at
        $data = TcPeopleDevelopment::with([
            'user:id,npk,name', 'participants:id,npk,name', 'section.department', 'jobPosition.department',
        ])->where('tahun_aktual', $tahun_aktual)->get();

        // Menghitung jumlah total data
        $totalRecords = $data->count();

        // Menghitung jumlah data berdasarkan status
        $countStatusBlue = $data->where('status_2', TrainingStatus::MENCARI_VENDOR)->count();
        $countStatusOrange = $data->where('status_2', TrainingStatus::PROSES_PENDAFTARAN)->count();
        $countStatusYellow = $data->where('status_2', TrainingStatus::ON_PROGRESS)->count();
        $countStatusGreen = $data->where('status_2', TrainingStatus::DONE)->count();
        $countStatusGray = $data->where('status_2', TrainingStatus::PENDING)->count();
        $countStatusRed = $data->where('status_2', TrainingStatus::DITOLAK)->count();

        // Menghitung persentase masing-masing status
        $percentageStatusBlue = $totalRecords > 0 ? ($countStatusBlue / $totalRecords) * 100 : 0;
        $percentageStatusOrange = $totalRecords > 0 ? ($countStatusOrange / $totalRecords) * 100 : 0;
        $percentageStatusYellow = $totalRecords > 0 ? ($countStatusYellow / $totalRecords) * 100 : 0;
        $percentageStatusGreen = $totalRecords > 0 ? ($countStatusGreen / $totalRecords) * 100 : 0;
        $percentageStatusGray = $totalRecords > 0 ? ($countStatusGray / $totalRecords) * 100 : 0;
        $percentageStatusRed = $totalRecords > 0 ? ($countStatusRed / $totalRecords) * 100 : 0;

        // Mengirimkan data ke view, menyertakan sections, job positions, dan penilaians
        $activeYear = MstPdActiveYear::getActiveYear();

        $masterCompetencies = [
            'technical'  => \App\Models\MstTc::whereNotNull('keterangan_tc')->distinct()->pluck('keterangan_tc'),
            'softskill'  => \App\Models\MstSoftSkill::whereNotNull('keterangan_sk')->distinct()->pluck('keterangan_sk'),
            'additional' => \App\Models\MstAdditionals::whereNotNull('keterangan_ad')->distinct()->pluck('keterangan_ad'),
            'Others'     => ['Others']
        ];

        return view('people_development.edit_develop_hrga', compact(
            'data',
            'sections',
            'jobPositions',
            'penilaians',
            'tahun_aktual',
            'activeYear',
            'masterCompetencies',
            'totalRecords',
            'countStatusBlue',
            'countStatusOrange',
            'countStatusYellow',
            'countStatusGreen',
            'countStatusGray',
            'countStatusRed',
            'percentageStatusBlue',
            'percentageStatusOrange',
            'percentageStatusYellow',
            'percentageStatusGreen',
            'percentageStatusGray',
            'percentageStatusRed'
        ));
    }

    /**
     * Modul 4.2 — Set Tahun Aktif Pengajuan Training.
     * Hanya HR / Administrator yang berhak mengubah.
     */
    public function setActiveYear(Request $request)
    {
        abort_unless(
            $this->roleAccess->hasFullAccess(auth()->user()),
            403,
            'Hanya HR / Administrator yang dapat mengatur tahun aktif.'
        );

        $request->validate(['year' => 'required|integer|digits:4|min:2020|max:2050']);

        MstPdActiveYear::setActiveYear((int) $request->year, auth()->id());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tahun aktif berhasil diubah ke ' . $request->year,
                'year'    => $request->year,
            ]);
        }

        return back()->with('success', 'Tahun aktif pengajuan training berhasil diubah ke ' . $request->year . '.');
    }

    /**
     * API: Ambil tahun aktif pengajuan training saat ini.
     */
    public function getActiveYear()
    {
        return response()->json(['year' => MstPdActiveYear::getActiveYear()]);
    }

    public function editEvaluasi($id)
    {
        $data = TcPeopleDevelopment::with([
            'user:id,npk,name',
            'participants:id,npk,name',
            'section:id,name',
        ])->findOrFail($id);
        $this->trainingEvaluation->assertCanEdit(auth()->user(), $data);

        $participants = $this->trainingEvaluation->participants($data);
        $isSharing = (bool) $data->is_sharing_knowledge;
        $canEditEvaluation = $this->trainingEvaluation->canEdit(auth()->user(), $data);
        $evaluationPayload = $this->trainingEvaluation->payload($data);

        return view('people_development.form_evaluasi', compact(
            'data',
            'participants',
            'isSharing',
            'canEditEvaluation',
            'evaluationPayload',
        ));
    }

    public function update(Request $request)
    {
        $this->abortUnlessCanManageTraining();

        $data = $request->all();

        // Ambil nama pengguna yang sedang login
        $userName = auth()->user()->name;

        if ($response = $this->rejectInvalidJobPositions($request, $data['id_job_position'] ?? [])) {
            return $response;
        }

        if ($response = $this->rejectInvalidCompetencyRows($request, $data)) {
            return $response;
        }

        // Ambil tahun depan
        $currentYear = date('Y');
        $nextYear = $currentYear + 1;

        // Hapus data yang dihapus oleh user (ada di original_id tapi tidak ada di submitted id)
        $originalIds = $request->input('original_id', []);
        $submittedIds = array_filter($data['id'] ?? []);
        $deletedIds = array_diff($originalIds, $submittedIds);
        
        if (!empty($deletedIds)) {
            TcPeopleDevelopment::whereIn('id', $deletedIds)->delete();
        }

        // Ambil jumlah baris data yang dikirimkan
        $rowCount = count($data['id_user'] ?? []);

        // Loop melalui setiap baris data
        for ($index = 0; $index < $rowCount; $index++) {
            $id = $data['id'][$index] ?? null;

            TcPeopleDevelopment::updateOrCreate(
                ['id' => $id],
                [
                    'section_id'          => $data['section_id'][$index] ?? null,
                    'id_job_position'     => $data['id_job_position'][$index] ?? null,
                    'id_user'             => $data['id_user'][$index] ?? null,
                    'program_training'    => $data['program_training'][$index] ?? null,
                    'kategori_competency' => $data['kategori_competency'][$index] ?? null,
                    'competency'          => $data['competency'][$index] ?? null,
                    'due_date'            => $data['due_date'][$index] ?? null,
                    'biaya'               => $data['biaya'][$index] ?? null,
                    'lembaga'             => $data['lembaga'][$index] ?? null,
                    'keterangan_tujuan'   => $data['keterangan_tujuan'][$index] ?? null,
                    'objective_learning'  => $data['objective_learning'][$index] ?? null,
                    'modified_at'         => $userName,
                    'tahun_aktual'        => $nextYear,
                    'status_1'            => 1,
                ]
            );

            // Log untuk memeriksa data yang diperbarui atau ditambahkan
            Log::info('Data diperbarui atau ditambahkan untuk ID: ' . ($id ?? 'baru'), [
                'data' => [
                    'section' => $data['section'][$index] ?? null,
                    'id_job_position' => $data['id_job_position'][$index] ?? null,
                    'id_user' => $data['id_user'][$index] ?? null,
                    'program_training' => $data['program_training'][$index] ?? null,
                    'kategori_competency' => $data['kategori_competency'][$index] ?? null,
                    'competency' => $data['competency'][$index] ?? null,
                    'due_date' => $data['due_date'][$index] ?? null,
                    'biaya' => $data['biaya'][$index] ?? null,
                    'lembaga' => $data['lembaga'][$index] ?? null,
                    'keterangan_tujuan' => $data['keterangan_tujuan'][$index] ?? null,
                    'objective_learning' => $data['objective_learning'][$index] ?? null,
                    'modified_at' => $userName,
                    'tahun_aktual' => $nextYear,
                    'status_1' => 1,
                ]
            ]);
        }

        // Redirect ke halaman index dengan pesan sukses
        return redirect()->route('indexPD')->with('success', 'Data berhasil diperbarui');
    }

    public function updateData(UpdateTrainingFollowUpRequest $request)
    {
        $this->abortUnlessCanApproveTraining();

        $rows = $this->participantService->prepareRows($request->validated('rows'), $request->user());
        $staged = [];
        $finalFiles = [];

        try {
            foreach ($rows as $row) {
                $rowKey = (string) $row['id'];
                if (! $request->hasFile('file.'.$rowKey)) {
                    continue;
                }

                $upload = $request->file('file.'.$rowKey);
                $staged[$rowKey] = [
                    'path' => $upload->storeAs(
                        'tmp/training-follow-up',
                        Str::uuid().'.'.$upload->getClientOriginalExtension(),
                    ),
                    'original' => $upload->getClientOriginalName(),
                ];
            }

            DB::transaction(function () use ($request, $rows, $staged, &$finalFiles): void {
                foreach ($rows as $item) {
                    $rowKey = (string) $item['id'];
                    $isNew = str_starts_with($rowKey, 'new_');
                    $training = $isNew
                        ? new TcPeopleDevelopment()
                        : TcPeopleDevelopment::query()->lockForUpdate()->findOrFail((int) $rowKey);

                    if ($isNew) {
                        $training->status_1 = 3;
                        $training->tahun_aktual = (int) ($request->input('tahun_aktual') ?: date('Y') + 1);
                        $training->tahun_usulan = $training->tahun_aktual - 1;
                        $training->modified_at = $this->resolveTrainingOwnerName($item);
                    }

                    $training->fill([
                        'section_id' => $item['section_id'] ?? null,
                        'id_job_position' => $item['id_job_position'] ?? null,
                        'id_user' => $item['id_user'],
                        'program_training' => $this->nullableValue($item, 'program_training'),
                        'kategori_competency' => $this->nullableValue($item, 'kategori_competency'),
                        'competency' => $this->nullableValue($item, 'competency'),
                        'due_date' => $this->nullableValue($item, 'due_date'),
                        'biaya' => $item['biaya'] ?? 0,
                        'lembaga' => $this->nullableValue($item, 'lembaga'),
                        'keterangan_tujuan' => $this->nullableValue($item, 'keterangan_tujuan'),
                        'program_training_plan' => $this->nullableValue($item, 'program_training_plan'),
                        'due_date_plan' => $this->nullableValue($item, 'due_date_plan'),
                        'biaya_plan' => $item['biaya_plan'] ?? 0,
                        'lembaga_plan' => $this->nullableValue($item, 'lembaga_plan'),
                        'keterangan_plan' => $this->nullableValue($item, 'keterangan_plan'),
                        'status_2' => $this->nullableValue($item, 'status_2'),
                        'objective_learning_aktual' => $this->nullableValue($item, 'objective_learning_aktual'),
                        'objective_learning' => $this->nullableValue($item, 'objective_learning'),
                        'is_sharing_knowledge' => $item['is_sharing_knowledge'],
                    ]);

                    if ($request->input('action') === 'approve') {
                        $training->status_1 = 3;
                    }

                    if (isset($staged[$rowKey])) {
                        File::ensureDirectoryExists(public_path('uploads'));
                        $extension = pathinfo($staged[$rowKey]['original'], PATHINFO_EXTENSION);
                        $finalName = now()->format('YmdHis').'_'.Str::uuid().'.'.$extension;
                        $finalPath = public_path('uploads'.DIRECTORY_SEPARATOR.$finalName);
                        File::move(storage_path('app'.DIRECTORY_SEPARATOR.$staged[$rowKey]['path']), $finalPath);
                        $finalFiles[] = $finalPath;
                        $training->file = $finalName;
                        $training->file_name = $staged[$rowKey]['original'];
                    }

                    $training->save();
                    $this->participantService->sync($training, $item);
                }
            });

            return response()->json(['message' => 'Data dan participant berhasil diperbarui.']);
        } catch (\Throwable $exception) {
            foreach ($finalFiles as $path) {
                File::delete($path);
            }

            Log::error('Update Data Error', ['exception' => $exception]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat memperbarui data: '.$exception->getMessage(),
            ], 500);
        } finally {
            foreach ($staged as $file) {
                File::delete(storage_path('app'.DIRECTORY_SEPARATOR.$file['path']));
            }
        }
    }

    private function resolveTrainingOwnerName(array $item): string
    {
        $jobPosition = \App\Models\MstJobPosition::find($item['id_job_position'] ?? null);
        $approverPosition = $jobPosition?->getApproverPosition(2);

        return $approverPosition?->activeUsers()->first()?->name ?? auth()->user()->name;
    }

    private function nullableValue(array $item, string $key): mixed
    {
        return isset($item[$key]) && $item[$key] !== '' ? $item[$key] : null;
    }

    private function updateDataLegacy(Request $request)
    {
        try {
            // Decode JSON string menjadi array
            $data = json_decode($request->input('data'), true);
            $action = $request->input('action'); // Ambil action dari request

            if (!is_array($data)) {
                throw new \Exception('Invalid data format');
            }

            foreach ($data as $item) {
                // Validasi data
                if (!isset($item['id'])) {
                    continue;
                }

                $isNew = str_starts_with($item['id'], 'new_');

                if ($isNew) {
                    // Check if it's completely empty (user didn't fill anything)
                    if (empty($item['id_user']) && empty($item['program_training']) && empty($item['program_training_plan'])) {
                        continue;
                    }

                    // Create new additional row
                    $tcPeopleDevelopment = new TcPeopleDevelopment();
                    $tcPeopleDevelopment->status_1 = 3; // Approved (finalized by HRGA directly)
                    
                    $tahunAktual = $request->input('tahun_aktual', date('Y') + 1);
                    $tcPeopleDevelopment->tahun_aktual = $tahunAktual;
                    $tcPeopleDevelopment->tahun_usulan = $tahunAktual - 1;

                    // Set proposal fields
                    $tcPeopleDevelopment->section_id = $item['section_id'] ?? null;
                    $tcPeopleDevelopment->id_job_position = $item['id_job_position'] ?? null;
                    $tcPeopleDevelopment->id_user = $item['id_user'] ?? null;
                    $tcPeopleDevelopment->program_training = $item['program_training'] ?? null;
                    $tcPeopleDevelopment->kategori_competency = $item['kategori_competency'] ?? null;
                    $tcPeopleDevelopment->competency = $item['competency'] ?? null;
                    $tcPeopleDevelopment->due_date = !empty($item['due_date']) ? $item['due_date'] : null;
                    $tcPeopleDevelopment->biaya = !empty($item['biaya']) ? $item['biaya'] : 0;
                    $tcPeopleDevelopment->lembaga = !empty($item['lembaga']) ? $item['lembaga'] : null;
                    $tcPeopleDevelopment->keterangan_tujuan = !empty($item['keterangan_tujuan']) ? $item['keterangan_tujuan'] : null;

                    // Set actual plan fields
                    $tcPeopleDevelopment->program_training_plan = !empty($item['program_training_plan']) ? $item['program_training_plan'] : null;
                    $tcPeopleDevelopment->due_date_plan = !empty($item['due_date_plan']) ? $item['due_date_plan'] : null;
                    $tcPeopleDevelopment->biaya_plan = !empty($item['biaya_plan']) ? $item['biaya_plan'] : 0;
                    $tcPeopleDevelopment->lembaga_plan = !empty($item['lembaga_plan']) ? $item['lembaga_plan'] : null;
                    $tcPeopleDevelopment->keterangan_plan = !empty($item['keterangan_plan']) ? $item['keterangan_plan'] : null;
                    $tcPeopleDevelopment->status_2 = !empty($item['status_2']) ? $item['status_2'] : null;

                    // Modul 4.1 — Tindak Lanjut Pasca Training
                    $tcPeopleDevelopment->objective_learning_aktual = !empty($item['objective_learning_aktual']) ? $item['objective_learning_aktual'] : null;
                    $tcPeopleDevelopment->objective_learning = !empty($item['objective_learning']) ? $item['objective_learning'] : null;
                    
                    // Kategori Usulan
                    $tcPeopleDevelopment->is_sharing_knowledge = filter_var($item['is_sharing_knowledge'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    // Set modified_at based on job position or user
                    $userName = auth()->user()->name;
                    $jobPos = \App\Models\MstJobPosition::find($item['id_job_position']);
                    if ($jobPos) {
                        $approverPos = $jobPos->getApproverPosition(2);
                        $deptHeadName = $approverPos
                            ? $approverPos->activeUsers()->first()?->name
                            : null;
                        $tcPeopleDevelopment->modified_at = $deptHeadName ?: $userName;
                    } else {
                        $tcPeopleDevelopment->modified_at = $userName;
                    }

                    $tcPeopleDevelopment->save();

                    // Handle file upload for the new row
                    if ($request->hasFile('file.' . $item['id'])) {
                        $file = $request->file('file.' . $item['id']);
                        $fileName = time() . '_' . $file->getClientOriginalName();
                        $file->move(public_path('uploads'), $fileName);
                        $tcPeopleDevelopment->file = $fileName;
                        $tcPeopleDevelopment->file_name = $file->getClientOriginalName();
                        $tcPeopleDevelopment->save();
                    }
                } else {
                    $existingItem = TcPeopleDevelopment::find($item['id']);

                    if ($existingItem) {
                        // Proses update — semua field nullable boleh di-clear ke null.
                        // Khusus biaya & biaya_plan (kolom numerik) default ke 0, bukan null.
                        $updateData = [
                            'due_date'             => !empty($item['due_date']) ? $item['due_date'] : null,
                            'biaya'                => !empty($item['biaya']) ? $item['biaya'] : 0,
                            'lembaga'              => isset($item['lembaga']) && $item['lembaga'] !== '' ? $item['lembaga'] : null,
                            'keterangan_tujuan'    => isset($item['keterangan_tujuan']) && $item['keterangan_tujuan'] !== '' ? $item['keterangan_tujuan'] : null,
                            'program_training_plan'=> isset($item['program_training_plan']) && $item['program_training_plan'] !== '' ? $item['program_training_plan'] : null,
                            'due_date_plan'        => !empty($item['due_date_plan']) ? $item['due_date_plan'] : null,
                            'biaya_plan'           => !empty($item['biaya_plan']) ? $item['biaya_plan'] : 0,
                            'lembaga_plan'         => isset($item['lembaga_plan']) && $item['lembaga_plan'] !== '' ? $item['lembaga_plan'] : null,
                            'keterangan_plan'      => isset($item['keterangan_plan']) && $item['keterangan_plan'] !== '' ? $item['keterangan_plan'] : null,
                            'status_2'             => !empty($item['status_2']) ? $item['status_2'] : null,
                            // Modul 4.1 — Tindak Lanjut Pasca Training
                            'objective_learning_aktual' => isset($item['objective_learning_aktual']) && $item['objective_learning_aktual'] !== '' ? $item['objective_learning_aktual'] : null,
                            'objective_learning'   => isset($item['objective_learning']) && $item['objective_learning'] !== '' ? $item['objective_learning'] : null,
                        ];

                        if ($action === 'approve') {
                            $updateData['status_1'] = 3;
                        }

                        // Langsung update — tidak pakai array_filter agar nilai null bisa meng-clear data lama.
                        $existingItem->update($updateData);

                        // Handle file upload
                        if ($request->hasFile('file.' . $item['id'])) {
                            $file = $request->file('file.' . $item['id']);
                            $fileName = time() . '_' . $file->getClientOriginalName();
                            $file->move(public_path('uploads'), $fileName);
                            // Save to existing columns: `file` and `file_name`
                            $existingItem->file = $fileName;
                            $existingItem->file_name = $file->getClientOriginalName();
                            $existingItem->save();
                        }
                    }
                }
            }

            return response()->json(['message' => 'Data berhasil diperbarui']);
        } catch (\Exception $e) {
            \Log::error('Update Data Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage()], 500);
        }
    }
    public function updatePdPlan2(Request $request)
    {
        $this->abortUnlessCanApproveTraining();

        $data = $request->all();
        $userName = Auth::user()->name;
        $currentYear = date('Y');

        // Mengolah data baru
        if (isset($data['new_section_id'])) {
            if ($response = $this->rejectInvalidJobPositions($request, $data['new_id_job_position'] ?? [])) {
                return $response;
            }

            if ($response = $this->rejectInvalidCompetencyRows($request, $data, 'new_')) {
                return $response;
            }

            foreach ($data['new_section_id'] as $index => $sectionId) {
                $tcPeopleDevelopment = new TcPeopleDevelopment();
                $tcPeopleDevelopment->status_1 = 2;
                $tcPeopleDevelopment->tahun_aktual = $currentYear + 1;
                $tcPeopleDevelopment->tahun_usulan = $currentYear;
                $tcPeopleDevelopment->section_id = $sectionId;
                $tcPeopleDevelopment->id_job_position = $data['new_id_job_position'][$index];
                $tcPeopleDevelopment->id_user = $data['new_id_user'][$index];
                $tcPeopleDevelopment->kategori_competency = $data['new_kategori_competency'][$index];
                $tcPeopleDevelopment->competency = $data['new_competency'][$index];

                // Set data lainnya, with null handling
                $tcPeopleDevelopment->program_training = $data['new_program_training'][$index] ?? null;
                $tcPeopleDevelopment->due_date = $data['new_due_date'][$index] ?? null;
                $tcPeopleDevelopment->biaya = $data['new_biaya'][$index] ?? null;
                $tcPeopleDevelopment->lembaga = $data['new_lembaga'][$index] ?? null;
                $tcPeopleDevelopment->keterangan_tujuan = $data['new_keterangan_tujuan'][$index] ?? null;
                $tcPeopleDevelopment->objective_learning = $data['new_objective_learning'][$index] ?? null;

                // Mengambil department_head_name secara dinamis dari MstJobPosition
                $jobPos = \App\Models\MstJobPosition::find($data['new_id_job_position'][$index]);
                if ($jobPos) {
                    // Ambil dept head name dari user yang menjabat posisi approval level 2
                    $approverPos = $jobPos->getApproverPosition(2);
                    $deptHeadName = $approverPos
                        ? $approverPos->activeUsers()->first()?->name
                        : null;
                    $tcPeopleDevelopment->modified_at = $deptHeadName ?: $userName;
                } else {
                    $tcPeopleDevelopment->modified_at = $userName;
                }
                $tcPeopleDevelopment->save();
            }
        }

        return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui']);
    }

    public function updateEvaluasi(UpdateTrainingEvaluationRequest $request, $id)
    {
        $evaluasi = TcPeopleDevelopment::with([
            'user:id,npk,name',
            'participants:id,npk,name',
        ])->findOrFail($id);
        $this->trainingEvaluation->assertCanEdit($request->user(), $evaluasi);
        $validated = $request->validated();

        if ($evaluasi->is_sharing_knowledge) {
            $validated['metode_evaluasi'] = 'Sharing Knowledge';
        }

        $evaluationData = array_merge($validated, [
            'dievaluasi' => Auth::user()->name,
            'tgl_pengajuan' => now(),
        ]);

        if (! $evaluasi->is_sharing_knowledge) {
            $evaluationData['diketahui'] = $evaluasi->user->name
                ?? $evaluasi->id_user
                ?? Auth::user()->name;
        }

        $evaluasi->update($evaluationData);

        return redirect()->route('viewPD', [
            'modified_at' => $evaluasi->modified_at,
            'tahun_aktual' => $evaluasi->tahun_aktual,
        ])->with('success', 'Evaluasi berhasil diperbarui.');
    }
    public function updateBtn(Request $request)
    {
        if (!$this->roleAccess->canManageTrainingConfig(auth()->user())) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $status = $request->input('enabled') ? 1 : 0;

        // Simpan status ke file (persistent, tidak terpengaruh cache:clear)
        Storage::put('button_status.txt', $status);

        Log::info('Button status updated', ['status' => $status, 'file_content' => Storage::get('button_status.txt')]);

        return response()->json(['status' => 'success', 'button_status' => $status]);
    }

    public function downloadPDF($id)
    {
        // Find the record based on ID
        $data = TcPeopleDevelopment::find($id);

        // Check if the record exists
        if (!$data) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => 'Data not found'], 404);
            }
            return redirect()->back()->withErrors('Data not found');
        }

        // Authorization: hanya pemilik pengajuan (modified_at == nama user) atau user full-access (HRGA/Admin) yang boleh download
        $isOwner     = $data->modified_at === auth()->user()->name;
        $hasFullAccess = $this->roleAccess->hasFullAccess(auth()->user());

        if (!$isOwner && !$hasFullAccess) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => 'Anda tidak memiliki akses untuk mengunduh dokumen ini.'], 403);
            }
            return redirect()->back()->withErrors('Anda tidak memiliki akses untuk mengunduh dokumen ini.');
        }

        $candidates = [];

        // If file field contains a full path, try it
        if (!empty($data->file) && (Str::startsWith($data->file, '/') || preg_match('/^[A-Za-z]:\\\\/', $data->file))) {
            $candidates[] = $data->file;
        }

        // Common public upload locations
        if (!empty($data->file)) {
            $candidates[] = public_path('uploads/' . $data->file);
            $candidates[] = public_path($data->file);
            $candidates[] = public_path('assets/people_development/' . $data->file);
            $candidates[] = storage_path('app/public/uploads/' . $data->file);
            $candidates[] = storage_path('app/' . $data->file);
        }

        // If original file name available, try to find matching file in uploads
        if (!empty($data->file_name)) {
            $pattern = public_path('uploads/*' . basename($data->file_name) . '*');
            foreach (glob($pattern) as $match) {
                $candidates[] = $match;
            }
            // also check assets folder
            $pattern2 = public_path('assets/people_development/*' . basename($data->file_name) . '*');
            foreach (glob($pattern2) as $match) {
                $candidates[] = $match;
            }
        }

        // Normalize and find first existing file
        $found = null;
        foreach ($candidates as $p) {
            if (empty($p)) continue;
            // prevent directory traversal
            $p = str_replace(['\\', '\\'], DIRECTORY_SEPARATOR, $p);
            if (file_exists($p) && is_file($p)) {
                $found = $p;
                break;
            }
        }

        Log::info('downloadPDF debug', [
            'id' => $id,
            'data_file' => $data->file,
            'file_name' => $data->file_name,
            'candidates' => $candidates,
            'found' => $found,
        ]);

        if (!$found) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => 'File not found'], 404);
            }
            return redirect()->back()->withErrors('File not found');
        }

        $downloadName = $data->file_name ?: basename($found);
        $mime = mime_content_type($found) ?: 'application/octet-stream';
        return response()->download($found, $downloadName, ['Content-Type' => $mime]);
    }

    public function sendPD($modified_at, $tahun_aktual)
    {
        $this->abortUnlessCanManageTraining();

        $query = TcPeopleDevelopment::where('modified_at', $modified_at)
            ->where('tahun_aktual', $tahun_aktual);

        if (!$this->roleAccess->hasFullAccess(auth()->user())) {
            $query->where('modified_at', auth()->user()->name);
        }

        // Mengupdate status draft menjadi menunggu HRGA tanpa menghilangkan data dari list.
        $query->where('status_1', 1)
            ->update(['status_1' => 2]);

        // Redirect atau kembali ke halaman yang diinginkan setelah update
        return redirect()->route('indexPD')->with('success', 'Status telah diubah menjadi Menunggu Persetujuan HRGA.');
    }

    public function sendPD2($tahun_aktual)
    {
        $this->abortUnlessCanApproveTraining();

        // Mengupdate status pending HRGA menjadi disetujui tanpa mengubah draft lain di tahun yang sama.
        TcPeopleDevelopment::where('tahun_aktual', $tahun_aktual)
            ->where('status_1', 2)
            ->update(['status_1' => 3]);

        // Redirect atau kembali ke halaman yang diinginkan setelah update
        return redirect()->route('indexPD2')->with('success', 'Status telah diubah menjadi Disetujui HRGA.');
    }

    public function getFilteredData(Request $request)
    {
        $actor = $request->user();
        $filters = $this->trainingHistoryFilters($request);

        return response()->json(
            $this->trainingHistoryPresentation->payload(
                $actor,
                $this->trainingHistory->flattened($actor, $filters),
            ),
        );
    }

    private function trainingHistoryFilters(Request $request): array
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'integer', 'min:1'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:'.((int) now()->year + 1)],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        return [
            'department_id' => isset($validated['department_id'])
                ? (int) $validated['department_id']
                : null,
            'year' => isset($validated['year']) ? (int) $validated['year'] : null,
            'search' => trim((string) ($validated['search'] ?? '')),
        ];
    }

    private function getFilteredDataLegacy(Request $request)
    {
        $sectionIds = $this->getSectionIdsForUser();
        $yearEnd = $request->input('year', now()->year);
        $roleFilter = $request->input('role_id'); // department selector from UI

        // Base query: only completed (Done) records and eager-load user
        $query = TcPeopleDevelopment::where('status_2', TrainingStatus::DONE)
            ->with('user', 'section');

        // Build department → section_id mapping using MstSection + MstDepartment
        if ($roleFilter) {
            $roleFilter = (int) $roleFilter;

            // Map from UI role_id to department name keywords
            $deptRoleMap = [
                11 => ['Finance', 'Accounting', 'HRGA', 'HR', 'PDCA', 'Procurement', 'Inventory'],
                2  => ['Sales'],
                5  => ['Production', 'PPC', 'Technical Support', 'Machining'],
                7  => ['Logistic', 'Delivery', 'Feeder', 'Cutting Sheet', 'Warehouse'],
            ];

            $keywords = $deptRoleMap[$roleFilter] ?? null;

            if ($keywords) {
                // Find matching section IDs by section name containing keywords
                $selectedSectionIds = \App\Models\MstSection::all()
                    ->filter(function ($sec) use ($keywords) {
                        foreach ($keywords as $kw) {
                            if (\Illuminate\Support\Str::contains($sec->name, $kw)) return true;
                        }
                        return false;
                    })
                    ->pluck('id')
                    ->toArray();

                // Intersect with user-allowed section IDs
                $effectiveSectionIds = array_values(array_intersect($sectionIds, $selectedSectionIds));

                if (empty($effectiveSectionIds) && $this->jobPositionAccess->hasFullAccess(auth()->user())) {
                    $effectiveSectionIds = $selectedSectionIds;
                }

                if (empty($effectiveSectionIds)) {
                    return response()->json([]);
                }

                $query->whereIn('section_id', $effectiveSectionIds);
            } else {
                return response()->json([]);
            }
        } else {
            $query->whereIn('section_id', $sectionIds);
        }

        // Apply year range filter from 2000 to the selected year
        if ($yearEnd) {
            $query->whereBetween('created_at', ['2000-01-01', $yearEnd . '-12-31']);
        }

        $dataTcPeopleDevelopment = $query->get();

        return response()->json($dataTcPeopleDevelopment);
    }


}

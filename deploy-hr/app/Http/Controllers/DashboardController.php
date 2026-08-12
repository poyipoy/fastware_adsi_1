<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use App\Models\MstPoPengajuan;
use App\Models\TrsPoPengajuan;
use App\Models\InquirySales;
use App\Models\MstDboCrp;
// use App\Models\TcJobPosition; // DISABLED
use App\Models\MstTc;
use App\Models\MstSoftSkill;
use App\Models\MstAdditionals;
use App\Models\TrsPenilaianTc;
use App\Models\User;
use Carbon\Carbon;
use App\Services\Dashboard\FpbDashboardService;
use App\Services\Dashboard\LeadTimeDashboardService;
use App\Services\Dashboard\InquiryDashboardService;
use App\Services\Dashboard\CrpDashboardService;
use App\Services\Dashboard\TcpdDashboardService;
use App\Services\HR\TcpdDashboardAccessService;
use App\Services\HR\TcpdSensitiveAccessService;
use App\Services\Competency\CompetencyAssessmentService;
use App\Enums\HRMenuAccessGroup;
use App\Exports\TcpdCompanyExport;
use App\Exports\TcpdCompetencyExport;
use App\Exports\TcpdEmployeesExport;
use App\Exports\TcpdFullWorkbookExport;
use App\Exports\TcpdTopJobsExport;
use App\Exports\TcpdCriticalFocusExport;
use Maatwebsite\Excel\Facades\Excel;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DashboardController extends Controller
{
    public function __construct(
        private FpbDashboardService $fpbDashboardService,
        private LeadTimeDashboardService $leadTimeDashboardService,
        private InquiryDashboardService $inquiryDashboardService,
        private CrpDashboardService $crpDashboardService,
        private TcpdDashboardService $tcpdDashboardService,
        private TcpdDashboardAccessService $tcpdDashboardAccess,
        private TcpdSensitiveAccessService $tcpdSensitiveAccess,
    ) {
    }

    public function dashboardFPB()
    {
        return view('dashboard.dashboardFPB', $this->fpbDashboardService->getFilterData());
    }

    // --- ENDPOINT API BARU YANG CEPAT DAN TERPISAH ---

    public function getFpbData(Request $request)
    {
        try {
            $data = $this->fpbDashboardService->getChartData(
                $request->input('start_date', now()->subYear()->startOfYear()->format('Y-m-d')),
                $request->input('end_date', now()->format('Y-m-d')),
                $request->input('kategori_po')
            );

            return response()->json(['success' => true, 'data' => $data]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            Log::error('Error in getFpbData: ' . $exception->getMessage() . ' line ' . $exception->getLine());
            return response()->json(['success' => false, 'message' => 'Gagal memuat data FPB.'], 500);
        }
    }

    public function getLeadTimeData(Request $request)
    {
        try {
            $data = $this->leadTimeDashboardService->getChartData(
                $request->input('start_date', now()->subYear()->startOfYear()->format('Y-m-d')),
                $request->input('end_date', now()->format('Y-m-d'))
            );

            return response()->json(['success' => true, 'data' => $data]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            Log::error('Error in getLeadTimeData: ' . $exception->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memuat data Lead Time.'], 500);
        }
    }

    public function getInquiryData(Request $request)
    {
        try {
            $payload = $this->inquiryDashboardService->getChartData(
                $request->input('start_date', now()->subYear()->startOfYear()->format('Y-m-d')),
                $request->input('end_date', now()->format('Y-m-d'))
            );

            return response()->json(['success' => true, 'data' => $payload]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            Log::error('Error in getInquiryData: ' . $exception->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memuat data Inquiry.'], 500);
        }
    }
    
    public function getCrpData(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $payload = $this->crpDashboardService->getChartData($user);
            return response()->json(['success' => true, 'data' => $payload]);
        } catch (\Throwable $exception) {
            Log::error('Error in getCrpData: ' . $exception->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memuat data CRP.'], 500);
        }
    }

    public function dashboardTCPD(Request $request)
    {
        $payload = $this->tcpdDashboardService->buildDashboardData($request, Auth::user());

        // Modul 2.1 — Key Position stats
        $sensitiveScope = $this->tcpdSensitiveAccess->scope($request->user());
        $payload['canViewTcpdSensitive'] = $sensitiveScope['can_view'];
        $payload['canClearTcpdCache'] = Gate::allows('clearTcpdDashboardCache');

        return view('dashboard.dashboardTCPD', $payload);
    }

    public function getTcpdSensitiveData(Request $request)
    {
        $scope = $this->tcpdSensitiveAccess->scope(
            $request->user(),
            $request->input('department_id'),
            true,
        );

        return response()->json([
            'success' => true,
            'data' => $this->tcpdDashboardService->getSensitivePayload($request, $scope),
        ]);
    }

    public function getTcpdCompetencyData(Request $request)
    {
        try {
            $payload = $this->tcpdDashboardService->getCompetencyPayload($request, $request->user());
            return response()->json([
                'success' => true,
                'data' => $payload,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Job position not found.',
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Error in getTcpdCompetencyData: ' . $exception->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data kompetensi.',
            ], 500);
        }
    }

    public function getTcpdCompanyData(Request $request)
    {
        try {
            $payload = $this->tcpdDashboardService->getCompanyPayload($request, Auth::user());
            return response()->json([
                'success' => true,
                'data' => $payload,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Error in getTcpdCompanyData: ' . $exception->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data TCPD company.',
            ], 500);
        }
    }

    /**
     * Clear the 60-minute TCPD calculation cache so the next request recomputes fresh data.
     */
    public function clearTcpdCache()
    {
        $this->authorize('clearTcpdDashboardCache');

        try {
            $this->tcpdDashboardService->clearTcpdCache();
            return response()->json(['success' => true]);
        } catch (\Throwable $exception) {
            Log::error('Error clearing TCPD cache: ' . $exception->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $exception->getMessage()
            ], 500);
        }
    }

    /**
     * Build metadata array for Excel export Title Blocks.
     */
    protected function buildExportMeta(Request $request): array
    {
        $accessScope = $this->tcpdDashboardAccess->scope($request->user(), true);
        $requestedJobPositionId = $request->input('job_position_id');

        if ($requestedJobPositionId !== null
            && $requestedJobPositionId !== ''
            && ! in_array((int) $requestedJobPositionId, $accessScope['job_position_ids'], true)) {
            abort(403, 'Job position berada di luar scope Dashboard TCPD Anda.');
        }

        $requestedDepartment = trim((string) $request->input('department', ''));
        if ($requestedDepartment !== '' && mb_strtolower($requestedDepartment) !== 'all') {
            $allowedDepartments = \App\Models\MstDepartment::query()
                ->whereIn('id', $accessScope['department_ids'])
                ->pluck('name')
                ->map(fn ($name) => mb_strtolower(trim((string) $name)))
                ->all();

            if (! in_array(mb_strtolower($requestedDepartment), $allowedDepartments, true)) {
                abort(403, 'Department berada di luar scope Dashboard TCPD Anda.');
            }
        }

        $yearFrom = $request->input('company_year_from', $request->input('year_from', Carbon::now()->year));
        $yearTo   = $request->input('company_year_to', $request->input('year_to', $yearFrom));
        $dept     = $request->input('department', 'All');
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        $jobPosId = $request->input('job_position_id');
        $jobPosName = 'Semua Job Position';
        if ($jobPosId) {
            $jobPos = \App\Models\MstJobPosition::find($jobPosId);
            if ($jobPos) {
                $jobPosName = $jobPos->position_name;
            }
        }

        return [
            'period'       => "{$yearFrom} - {$yearTo}",
            'department'   => $dept ?: 'All',
            'job_position' => $jobPosName,
            'date_range'   => ($dateFrom && $dateTo) ? "{$dateFrom} s/d {$dateTo}" : 'Semua Periode',
            'export_date'  => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Export all TCPD data as a 3-sheet Excel workbook.
     * Respects all active filters passed via query parameters.
     */
    public function exportTcpdAll(Request $request)
    {
        try {
            $meta = $this->buildExportMeta($request);
            $companyRows = $this->buildExportCompanyRows($request);
            $competencyRows = $this->buildExportCompetencyRows($request);
            [$topJobs, $criticalFocus] = $this->buildExportEmployeesData($request);

            $yearFrom = $request->input('company_year_from', Carbon::now()->year);
            $yearTo   = $request->input('company_year_to', $yearFrom);
            $fileName = 'TCPD_Export_' . $yearFrom . '-' . $yearTo . '_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download(
                new TcpdFullWorkbookExport($companyRows, $competencyRows, $topJobs, $criticalFocus, $meta),
                $fileName
            );
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $e) {
            Log::error('TCPD Export All Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengekspor data.'], 500);
        }
    }

    /**
     * Export Company & Department Overview sheet only.
     */
    public function exportTcpdCompanyData(Request $request)
    {
        try {
            $meta = $this->buildExportMeta($request);
            $rows = $this->buildExportCompanyRows($request);
            $yearFrom = $request->input('year_from', $request->input('company_year_from', Carbon::now()->year));
            $yearTo   = $request->input('year_to', $request->input('company_year_to', $yearFrom));
            $fileName = 'TCPD_Company_' . $yearFrom . '-' . $yearTo . '_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download(new TcpdCompanyExport($rows, $meta), $fileName);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $e) {
            Log::error('TCPD Company Export Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengekspor data company.'], 500);
        }
    }

    /**
     * Export Area Development (competency rows per job position) sheet only.
     */
    public function exportTcpdCompetencyData(Request $request)
    {
        try {
            $meta = $this->buildExportMeta($request);
            $rows = $this->buildExportCompetencyRows($request);
            $fileName = 'TCPD_AreaDevelopment_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download(new TcpdCompetencyExport($rows, $meta), $fileName);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $e) {
            Log::error('TCPD Competency Export Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengekspor data area development.'], 500);
        }
    }

    /**
     * Export Top Jobs & Critical Focus employees sheet only.
     */
    public function exportTcpdEmployeesData(Request $request)
    {
        try {
            $meta = $this->buildExportMeta($request);
            [$topJobs, $criticalFocus] = $this->buildExportEmployeesData($request);
            $fileName = 'TCPD_Employees_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download(new TcpdEmployeesExport($topJobs, $criticalFocus, $meta), $fileName);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $e) {
            Log::error('TCPD Employees Export Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengekspor data karyawan.'], 500);
        }
    }

    /**
     * Export Top Jobs sheet only.
     */
    public function exportTcpdTopJobs(Request $request)
    {
        try {
            $meta = $this->buildExportMeta($request);
            [$topJobs, $criticalFocus] = $this->buildExportEmployeesData($request);
            $fileName = 'TCPD_TopJobs_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download(new TcpdTopJobsExport($topJobs, $meta), $fileName);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $e) {
            Log::error('TCPD Top Jobs Export Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengekspor data Top Jobs.'], 500);
        }
    }

    /**
     * Export Critical Focus Area sheet only.
     */
    public function exportTcpdCriticalFocus(Request $request)
    {
        try {
            $meta = $this->buildExportMeta($request);
            [$topJobs, $criticalFocus] = $this->buildExportEmployeesData($request);
            $fileName = 'TCPD_CriticalFocus_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download(new TcpdCriticalFocusExport($criticalFocus, $meta), $fileName);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $e) {
            Log::error('TCPD Critical Focus Export Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengekspor data Critical Focus.'], 500);
        }
    }

    /**
     * Build flat row array for Company & Department export.
     */
    protected function buildExportCompanyRows(Request $request): array
    {
        $companyPayload = $this->tcpdDashboardService->getCompanyPayload($request, Auth::user());
        $chartRows = $companyPayload['company_chart_rows'] ?? [];
        $years = $companyPayload['company_years'] ?? [];

        $exportRows = [];
        foreach ($chartRows as $row) {
            $label = $row['label'] ?? '';
            $isCompany = $row['is_company'] ?? false;
            $values = $row['values'] ?? [];

            if (empty($values)) {
                $exportRows[] = [
                    'department'  => $isCompany ? 'Company' : $label,
                    'job_position' => $isCompany ? '' : '',
                    'average'     => $row['percentage'] ?? null,
                    'year'        => 'All',
                    'percentage'  => $row['percentage'] ?? null,
                ];
            } else {
                foreach ($values as $value) {
                    $exportRows[] = [
                        'department'   => $isCompany ? 'Company' : $label,
                        'job_position' => '',
                        'average'      => $row['percentage'] ?? null,
                        'year'         => $value['label'] ?? ($value['year'] ?? 'All'),
                        'percentage'   => $value['percentage'] ?? null,
                    ];
                }
            }
        }

        return $exportRows;
    }

    /**
     * Build flat row array for Area Development (competency) export.
     */
    protected function buildExportCompetencyRows(Request $request): array
    {
        $jobPositionId = $request->input('job_position_id');
        if (!$jobPositionId) {
            return [];
        }

        try {
            $payload = $this->tcpdDashboardService->getCompetencyPayload($request, $request->user());
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $e) {
            return [];
        }

        $jobPosition = $payload['job_position'] ?? '';
        $competencies = $payload['competencies'] ?? [];

        $exportRows = [];
        foreach ($competencies as $comp) {
            foreach ($comp['employees'] ?? [] as $emp) {
                $exportRows[] = [
                    'department'   => '',
                    'section'      => '',
                    'job_position' => $jobPosition,
                    'npk'          => $emp['npk'] ?? '-',
                    'user'         => $emp['name'] ?? '',
                    'competency'   => $comp['name'] ?? '',
                    'actual'       => $emp['actual'] ?? null,
                    'standard'     => $comp['standard'] ?? null,
                    'average'      => $comp['average'] ?? null,
                ];
            }
        }

        return $exportRows;
    }

    /**
     * Build top_jobs and critical_focus arrays for employees export.
     * @return array{0: array, 1: array}
     */
    protected function buildExportEmployeesData(Request $request): array
    {
        try {
            $payload = $this->tcpdDashboardService->getCompanyPayload($request, Auth::user());
            $insights = $payload['insights'] ?? [];
            $topJobs = $insights['top_jobs'] ?? [];
            $criticalFocus = $insights['critical_focus'] ?? [];
        } catch (\Throwable $e) {
            $topJobs = [];
            $criticalFocus = [];
        }

        return [$topJobs, $criticalFocus];
    }

    protected function resolveDateRange(?string $from, ?string $to): array
    {
        $start = null;
        $end = null;

        try {
            if ($from) {
                $start = Carbon::parse($from)->startOfDay();
            }
        } catch (\Throwable $exception) {
            $start = null;
        }

        try {
            if ($to) {
                $end = Carbon::parse($to)->endOfDay();
            }
        } catch (\Throwable $exception) {
            $end = null;
        }

        if ($start && $end && $start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    protected function resolveYearRange(?string $from, ?string $to): array
    {
        $start = null;
        $end = null;

        try {
            if ($from && is_numeric($from)) {
                $start = Carbon::create((int) $from, 1, 1, 0, 0, 0)->startOfDay();
            }
        } catch (\Throwable $exception) {
            $start = null;
        }

        try {
            if ($to && is_numeric($to)) {
                $end = Carbon::create((int) $to, 12, 31, 23, 59, 59)->endOfDay();
            }
        } catch (\Throwable $exception) {
            $end = null;
        }

        if ($start && $end && $start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    protected function extractYearSegments(?Carbon $startDate, ?Carbon $endDate): array
    {
        if (!$startDate && !$endDate) {
            return [];
        }

        $startYear = $startDate ? (int) $startDate->copy()->startOfYear()->year : null;
        $endYear = $endDate ? (int) $endDate->copy()->startOfYear()->year : null;

        if ($startYear === null && $endYear !== null) {
            return [$endYear];
        }

        if ($startYear !== null && $endYear === null) {
            return [$startYear];
        }

        if ($startYear === null || $endYear === null) {
            return [];
        }

        if ($startYear > $endYear) {
            [$startYear, $endYear] = [$endYear, $startYear];
        }

        return range($startYear, $endYear);
    }

    protected function applyDateConstraint($query, ?Carbon $startDate, ?Carbon $endDate): void
    {
        if (!$startDate && !$endDate) {
            return;
        }

        $table = (new TrsPenilaianTc())->getTable();
        if (Schema::hasColumn($table, 'tahun_penilaian')) {
            // Check if the range represents a whole calendar year (Jan 1 to Dec 31)
            $isWholeYear = false;
            if ($startDate && $endDate) {
                $isWholeYear = $startDate->month === 1 && $startDate->day === 1 && $endDate->month === 12 && $endDate->day === 31;
            }

            if ($isWholeYear) {
                $startYear = $startDate->year;
                $endYear = $endDate->year;
                $query->whereBetween('tahun_penilaian', [$startYear, $endYear]);
                return;
            }
        }

        static $dateColumns = null;

        if ($dateColumns === null) {
            $dateColumns = collect(['created_at', 'modified_updated'])
                ->filter(fn ($column) => Schema::hasColumn($table, $column))
                ->values()
                ->all();
        }

        if (empty($dateColumns)) {
            return;
        }

        $query->where(function ($outer) use ($dateColumns, $startDate, $endDate) {
            foreach ($dateColumns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $outer->{$method}(function ($inner) use ($column, $startDate, $endDate) {
                    if ($startDate && $endDate) {
                        $inner->whereBetween($column, [$startDate, $endDate]);
                    } elseif ($startDate) {
                        $inner->where($column, '>=', $startDate);
                    } else {
                        $inner->where($column, '<=', $endDate);
                    }
                });
            }
        });
    }

    protected function availableCompanyYears(): array
    {
        $table = (new TrsPenilaianTc())->getTable();
        $years = collect();

        if (Schema::hasColumn($table, 'tahun_penilaian')) {
            $years = $years->merge(
                TrsPenilaianTc::selectRaw('DISTINCT tahun_penilaian as year')
                    ->whereNotNull('tahun_penilaian')
                    ->pluck('year')
            );
        } else {
            if (Schema::hasColumn($table, 'created_at')) {
                $years = $years->merge(
                    TrsPenilaianTc::selectRaw('DISTINCT YEAR(created_at) as year')
                        ->whereNotNull('created_at')
                        ->pluck('year')
                );
            }

            if (Schema::hasColumn($table, 'modified_updated')) {
                $years = $years->merge(
                    TrsPenilaianTc::selectRaw('DISTINCT YEAR(modified_updated) as year')
                        ->whereNotNull('modified_updated')
                        ->pluck('year')
                );
            }
        }


        $cleanYears = $years
            ->filter(fn ($year) => $year !== null)
            ->map(fn ($year) => (int) $year)
            ->filter(fn ($year) => $year > 0)
            ->unique()
            ->sort()
            ->values();

        if ($cleanYears->isEmpty()) {
            $cleanYears = collect([Carbon::now()->year]);
        }

        return $cleanYears->all();
    }

    
    protected function buildCompanyOverview(array $departmentSummaries, array $years): array
    {
        $years = array_map('intval', $years);
        sort($years);

        $rows = [];
        $departmentAverages = [];
        $companyYearTotals = [];

        foreach ($departmentSummaries as $department) {
            $departmentName = $department['department'] ?? 'Department';
            $entries = $department['entries'] ?? [];
            $totalEntry = collect($entries)->first(fn ($entry) => ($entry['is_total'] ?? false) === true);

            if (!$totalEntry) {
                $rows[] = [
                    'label' => $departmentName,
                    'percentage' => null,
                    'has_data' => false,
                    'values' => [],
                    'is_company' => false,
                ];
                continue;
            }

            $percentage = isset($totalEntry['percentage']) && is_numeric($totalEntry['percentage'])
                ? round((float) $totalEntry['percentage'], 2)
                : null;

            if ($percentage !== null) {
                $departmentAverages[] = $percentage;
            }

            $values = [];
            foreach ($totalEntry['values'] ?? [] as $value) {
                $key = isset($value['key']) ? (string) $value['key'] : null;
                if (!$key) {
                    continue;
                }

                $valuePercentage = isset($value['percentage']) && is_numeric($value['percentage'])
                    ? round((float) $value['percentage'], 2)
                    : null;

                $values[] = [
                    'key' => $key,
                    'label' => $value['label'] ?? $key,
                    'year' => $value['year'] ?? null,
                    'percentage' => $valuePercentage,
                    'has_data' => $valuePercentage !== null,
                ];

                if ($valuePercentage !== null && $key !== 'all') {
                    $companyYearTotals[$key][] = $valuePercentage;
                }
            }

            if (empty($values)) {
                $values[] = [
                    'key' => 'all',
                    'label' => 'All',
                    'year' => null,
                    'percentage' => $percentage,
                    'has_data' => $percentage !== null,
                ];
            }

            $rows[] = [
                'label' => $departmentName,
                'percentage' => $percentage,
                'has_data' => $percentage !== null,
                'values' => $values,
                'is_company' => false,
            ];
        }

        $companyAverage = !empty($departmentAverages)
            ? round(array_sum($departmentAverages) / count($departmentAverages), 2)
            : null;

        $companyValues = [];
        if (!empty($years)) {
            foreach ($years as $year) {
                $key = (string) $year;
                $yearBucket = $companyYearTotals[$key] ?? [];
                $yearAverage = !empty($yearBucket)
                    ? round(array_sum($yearBucket) / count($yearBucket), 2)
                    : null;
                $companyValues[] = [
                    'key' => $key,
                    'label' => (string) $year,
                    'year' => $year,
                    'percentage' => $yearAverage,
                    'has_data' => $yearAverage !== null,
                ];
            }
        }

        if (empty($companyValues)) {
            $companyValues[] = [
                'key' => 'all',
                'label' => 'All',
                'year' => null,
                'percentage' => $companyAverage,
                'has_data' => $companyAverage !== null,
            ];
        }

        array_unshift($rows, [
            'label' => 'Company',
            'percentage' => $companyAverage,
            'has_data' => $companyAverage !== null,
            'values' => $companyValues,
            'is_company' => true,
        ]);

        $departmentCount = count(array_filter($rows, fn ($row) => !$row['is_company'] && $row['has_data']));

        return [
            'chartRows' => $rows,
            'average' => $companyAverage ?? 0.0,
            'hasData' => $companyAverage !== null,
            'departmentCount' => $departmentCount,
            'years' => $years,
            'mode' => !empty($years) ? 'yearly' : 'aggregate',
        ];
    }

    protected function buildTcpdSnapshot(
        string $jobPositionName,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?array $allowedJobPositionIds = null,
    ): array {
        $jobPositionIds = \App\Models\MstJobPosition::query()
            ->where('position_name', $jobPositionName)
            ->where('is_active', true)
            ->when($allowedJobPositionIds !== null, fn ($query) => $query->whereIn('id', $allowedJobPositionIds))
            ->pluck('id');

        if ($jobPositionIds->isEmpty()) {
            return [
                'qty' => 0, 'competencies' => [], 'userSummaries' => [],
                'totalPercentage' => 0.0, 'hasTotalPercentage' => false,
            ];
        }

        $userIds = \Illuminate\Support\Facades\DB::table('user_job_positions')
            ->join('mst_job_positions', 'user_job_positions.mst_job_position_id', '=', 'mst_job_positions.id')
            ->join('users', 'users.id', '=', 'user_job_positions.user_id')
            ->whereIn('mst_job_positions.id', $jobPositionIds)
            ->where('mst_job_positions.is_active', true)
            ->where('user_job_positions.is_active', true)
            ->where('users.is_active', 0)
            ->where(fn ($query) => $query->whereNull('user_job_positions.effective_from')->orWhereDate('user_job_positions.effective_from', '<=', today()))
            ->where(fn ($query) => $query->whereNull('user_job_positions.effective_until')->orWhereDate('user_job_positions.effective_until', '>=', today()))
            ->distinct()
            ->pluck('user_job_positions.user_id')
            ->values();

        $userCount = $userIds->count();
        $userLookup = $userIds->isEmpty()
            ? collect()
            : User::whereIn('id', $userIds)->get(['id', 'npk', 'name'])->keyBy('id');

        $getCompetencies = function (string $model, string $nameCol, string $valueCol, string $type) use ($jobPositionIds) {
            return app($model)->whereIn('id_job_position', $jobPositionIds)
                ->select('id', DB::raw("$nameCol as name"), DB::raw("$valueCol as standard"))
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => $row->name,
                    'standard' => $row->standard !== null ? (float) $row->standard : null,
                    'type' => $type,
                ])
                ->values();
        };

        $technical = $getCompetencies(MstTc::class, 'keterangan_tc', 'nilai', 'technical');
        $softSkills = $getCompetencies(MstSoftSkill::class, 'keterangan_sk', 'nilai', 'soft_skill');
        $additionals = $getCompetencies(MstAdditionals::class, 'keterangan_ad', 'nilai', 'additional');

        $competencies = collect()->concat($technical)->concat($softSkills)->concat($additionals)
            ->filter(fn ($row) => isset($row['name']) && trim((string) $row['name']) !== '')
            ->values();

        if ($competencies->isEmpty() || $userIds->isEmpty()) {
            return [
                'qty' => $userCount, 'competencies' => [], 'userSummaries' => [],
                'totalPercentage' => 0.0, 'hasTotalPercentage' => false,
            ];
        }

        $sumStandards = fn ($collection) => $collection->reduce(fn ($sum, $row) => $sum + (is_numeric($row['standard']) ? (float) $row['standard'] : 0), 0.0);
        $technicalStandardsSum = $sumStandards($technical);
        $softSkillStandardsSum = $sumStandards($softSkills);
        $additionalStandardsSum = $sumStandards($additionals);

        $userTotals = [];
        foreach ($userIds as $userId) {
            $userTotals[(int) $userId] = ['technical' => 0.0, 'soft_skill' => 0.0, 'additional' => 0.0];
        }

        $scoreRowsQuery = TrsPenilaianTc::whereIn('id_user', $userIds)
            ->where(function ($query) use ($jobPositionName, $jobPositionIds) {
                $query->where('id_job_position', $jobPositionName)
                    ->orWhereIn('id_job_position', $jobPositionIds->all());
            })
            ->select('id_user', 'id_tc', 'id_sk', 'id_ad', 'nilai_tc', 'nilai_sk', 'nilai_ad');
        $this->applyDateConstraint($scoreRowsQuery, $startDate, $endDate);
        $scoreRows = $scoreRowsQuery->get();

        $technicalSet = $technical->pluck('id')->flip();
        $softSkillSet = $softSkills->pluck('id')->flip();
        $additionalSet = $additionals->pluck('id')->flip();

        foreach ($scoreRows as $scoreRow) {
            $userId = (int) $scoreRow->id_user;
            if (!isset($userTotals[$userId])) continue;

            if ($scoreRow->id_tc !== null && isset($technicalSet[$scoreRow->id_tc]) && is_numeric($scoreRow->nilai_tc)) {
                $userTotals[$userId]['technical'] += (float) $scoreRow->nilai_tc;
            }
            if ($scoreRow->id_sk !== null && isset($softSkillSet[$scoreRow->id_sk]) && is_numeric($scoreRow->nilai_sk)) {
                $userTotals[$userId]['soft_skill'] += (float) $scoreRow->nilai_sk;
            }
            if ($scoreRow->id_ad !== null && isset($additionalSet[$scoreRow->id_ad]) && is_numeric($scoreRow->nilai_ad)) {
                $userTotals[$userId]['additional'] += (float) $scoreRow->nilai_ad;
            }
        }

        $userSummaries = $userIds->map(function ($userId) use ($userTotals, $userLookup, $technicalStandardsSum, $softSkillStandardsSum, $additionalStandardsSum) {
            $totals = $userTotals[(int) $userId] ?? ['technical' => 0.0, 'soft_skill' => 0.0, 'additional' => 0.0];
            
            // Cap at 100%: nilai aktual tidak boleh melebihi standar
            $calcPercentage = fn ($total, $standardSum) => $standardSum > 0 ? min(100.0, round(($total / $standardSum) * 100, 2)) : null;

            return [
                'id' => (int) $userId,
                'npk' => \App\Services\HR\EmployeeIdentityFormatter::npk(optional($userLookup->get($userId))->npk),
                'name' => optional($userLookup->get($userId))->name ?? ('User ' . $userId),
                'tc_percentage' => $calcPercentage($totals['technical'], $technicalStandardsSum),
                'sk_percentage' => $calcPercentage($totals['soft_skill'], $softSkillStandardsSum),
                'ad_percentage' => $calcPercentage($totals['additional'], $additionalStandardsSum),
            ];
        })->values()->all();

        $userSummariesCollection = collect($userSummaries);
        if ($userSummariesCollection->isNotEmpty()) {
            $avg_tc = $userSummariesCollection->pluck('tc_percentage')->filter(fn($v) => $v !== null)->avg();
            $avg_sk = $userSummariesCollection->pluck('sk_percentage')->filter(fn($v) => $v !== null)->avg();
            $avg_ad = $userSummariesCollection->pluck('ad_percentage')->filter(fn($v) => $v !== null)->avg();

            array_unshift($userSummaries, [
                'id' => 'average',
                'npk' => '-',
                'name' => $jobPositionName,
                'tc_percentage' => $avg_tc !== null ? round($avg_tc, 2) : null,
                'sk_percentage' => $avg_sk !== null ? round($avg_sk, 2) : null,
                'ad_percentage' => $avg_ad !== null ? round($avg_ad, 2) : null,
            ]);
        }

        // Hitung totalPercentage hanya dari baris user nyata (bukan baris 'average')
        $allPercentages = [];
        foreach ($userSummaries as $summary) {
            if (($summary['id'] ?? null) === 'average') continue;
            if ($summary['tc_percentage'] !== null) $allPercentages[] = $summary['tc_percentage'];
            if ($summary['sk_percentage'] !== null) $allPercentages[] = $summary['sk_percentage'];
            if ($summary['ad_percentage'] !== null) $allPercentages[] = $summary['ad_percentage'];
        }
        
        $totalPercentage = !empty($allPercentages) ? min(100.0, round(array_sum($allPercentages) / count($allPercentages), 2)) : 0.0;

        $technicalStandards = $technical->mapWithKeys(fn ($r) => [$r['id'] => $r['standard']])->all();
        $softSkillStandards = $softSkills->mapWithKeys(fn ($r) => [$r['id'] => $r['standard']])->all();
        $additionalStandards = $additionals->mapWithKeys(fn ($r) => [$r['id'] => $r['standard']])->all();

        $belowStandardUsers = ['technical' => [], 'soft_skill' => [], 'additional' => []];
        $userScoresPerCompetency = [];
        $userScoresMapPerCompetency = ['technical' => [], 'soft_skill' => [], 'additional' => []];

        foreach ($scoreRows as $scoreRow) {
            $userId = (int) $scoreRow->id_user;
            if ($scoreRow->id_tc !== null && isset($technicalSet[$scoreRow->id_tc]) && is_numeric($scoreRow->nilai_tc)) {
                $value = (float) $scoreRow->nilai_tc;
                $compId = (int) $scoreRow->id_tc;
                $standard = $technicalStandards[$compId] ?? null;
                if ($standard !== null && $value < $standard) {
                    $belowStandardUsers['technical'][$compId][$userId] = true;
                }
                $userScoresPerCompetency['technical'][$compId][] = $value;
                $userScoresMapPerCompetency['technical'][$compId][$userId][] = $value;
            }
            if ($scoreRow->id_sk !== null && isset($softSkillSet[$scoreRow->id_sk]) && is_numeric($scoreRow->nilai_sk)) {
                $value = (float) $scoreRow->nilai_sk;
                $compId = (int) $scoreRow->id_sk;
                $standard = $softSkillStandards[$compId] ?? null;
                if ($standard !== null && $value < $standard) {
                    $belowStandardUsers['soft_skill'][$compId][$userId] = true;
                }
                $userScoresPerCompetency['soft_skill'][$compId][] = $value;
                $userScoresMapPerCompetency['soft_skill'][$compId][$userId][] = $value;
            }
            if ($scoreRow->id_ad !== null && isset($additionalSet[$scoreRow->id_ad]) && is_numeric($scoreRow->nilai_ad)) {
                $value = (float) $scoreRow->nilai_ad;
                $compId = (int) $scoreRow->id_ad;
                $standard = $additionalStandards[$compId] ?? null;
                if ($standard !== null && $value < $standard) {
                    $belowStandardUsers['additional'][$compId][$userId] = true;
                }
                $userScoresPerCompetency['additional'][$compId][] = $value;
                $userScoresMapPerCompetency['additional'][$compId][$userId][] = $value;
            }
        }

        $rows = $competencies->map(function (array $row) use ($userScoresPerCompetency, $belowStandardUsers, $userScoresMapPerCompetency, $userLookup, $jobPositionName) {
            $scores = $userScoresPerCompetency[$row['type']][$row['id']] ?? [];
            $average = !empty($scores) ? round(array_sum($scores) / count($scores), 2) : null;

            // Build employees list: users below standard for this competency
            $belowUserIds = array_keys($belowStandardUsers[$row['type']][$row['id']] ?? []);
            $employees = [];
            foreach ($belowUserIds as $uid) {
                $uid = (int) $uid;
                $userScores = $userScoresMapPerCompetency[$row['type']][$row['id']][$uid] ?? [];
                $actualVal = !empty($userScores) ? round(array_sum($userScores) / count($userScores), 2) : null;
                $employees[] = [
                    'id'           => $uid,
                    'npk'          => \App\Services\HR\EmployeeIdentityFormatter::npk(optional($userLookup->get($uid))->npk),
                    'name'         => optional($userLookup->get($uid))->name ?? ('User ' . $uid),
                    'job_position' => $jobPositionName,
                    'actual'       => $actualVal,
                    'standard'     => $row['standard'],
                ];
            }
            usort($employees, fn($a, $b) => strcmp($a['name'], $b['name']));

            return [
                'id'        => $row['id'],
                'name'      => $row['name'],
                'type'      => $row['type'],
                'standard'  => $row['standard'],
                'average'   => $average,
                'qty'       => count($belowUserIds),
                'employees' => $employees,
            ];
        })->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();


        return [
            'qty' => $userCount,
            'competencies' => $rows,
            'userSummaries' => $userSummaries,
            'totalPercentage' => $totalPercentage,
            'hasTotalPercentage' => true,
        ];
    }

    protected function extractSnapshotPercentage(?array $snapshot): ?float
    {
        if (!$snapshot) {
            return null;
        }

        $hasTotal = $snapshot['hasTotalPercentage'] ?? false;
        if (!$hasTotal) {
            return null;
        }

        $value = $snapshot['totalPercentage'] ?? null;
        if (!is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    protected function buildTcpdJobData(
        array $jobNames,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?array $allowedJobPositionIds = null,
    ): array {
        $availableJobPositions = \App\Models\MstJobPosition::query()
            ->where('is_active', true)
            ->when($allowedJobPositionIds !== null, fn ($query) => $query->whereIn('id', $allowedJobPositionIds))
            ->distinct()
            ->pluck('position_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $availableLookup = array_flip($availableJobPositions);

        $normalized = collect($jobNames)
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '' && array_key_exists($name, $availableLookup))
            ->unique()
            ->values()
            ->all();

        $years = array_map('intval', $this->extractYearSegments($startDate, $endDate));
        $aggregateSnapshots = [];
        $yearSnapshots = [];

        foreach ($normalized as $jobName) {
            $aggregateSnapshots[$jobName] = $this->buildTcpdSnapshot(
                $jobName,
                $startDate,
                $endDate,
                $allowedJobPositionIds,
            );

            if (!empty($years)) {
                foreach ($years as $year) {
                    $yearStart = Carbon::create($year, 1, 1, 0, 0, 0)->startOfDay();
                    $yearEnd = Carbon::create($year, 12, 31, 23, 59, 59)->endOfDay();
                    $yearSnapshots[$jobName][$year] = $this->buildTcpdSnapshot(
                        $jobName,
                        $yearStart,
                        $yearEnd,
                        $allowedJobPositionIds,
                    );
                }
            }
        }

        return [
            'years' => $years,
            'aggregate' => $aggregateSnapshots,
            'per_year' => $yearSnapshots,
        ];
    }

    
    protected function buildDepartmentSummaries(array $departmentDefinitions, array $jobSnapshotData): array
    {
        $years = array_map('intval', $jobSnapshotData['years'] ?? []);
        sort($years);

        $aggregateSnapshots = $jobSnapshotData['aggregate'] ?? [];
        $perYearSnapshots = $jobSnapshotData['per_year'] ?? [];

        $summaries = [];

        foreach ($departmentDefinitions as $departmentName => $jobNames) {
            $jobNames = collect($jobNames ?? [])
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->filter(fn ($name) => array_key_exists($name, $aggregateSnapshots))
                ->unique()
                ->values()
                ->all();

            if (empty($jobNames)) {
                $summaries[] = [
                    'department' => $departmentName,
                    'entries' => [],
                    'overall' => null,
                    'has_total_data' => false,
                    'years' => $years,
                ];
                continue;
            }

            $entries = [];
            $overallContributions = [];
            $departmentYearTotals = [];

            foreach ($jobNames as $jobName) {
                $snapshot = $aggregateSnapshots[$jobName] ?? null;
                $percentage = $this->extractSnapshotPercentage($snapshot);
                $hasData = $percentage !== null;

                $values = [];
                if (!empty($years)) {
                    foreach ($years as $year) {
                        $yearSnapshot = $perYearSnapshots[$jobName][$year] ?? null;
                        $yearPercentage = $this->extractSnapshotPercentage($yearSnapshot);
                        $values[] = [
                            'key' => (string) $year,
                            'label' => (string) $year,
                            'year' => $year,
                            'percentage' => $yearPercentage,
                            'has_data' => $yearPercentage !== null,
                        ];
                        if ($yearPercentage !== null) {
                            $departmentYearTotals[$year][] = $yearPercentage;
                        }
                    }
                }

                if (empty($values)) {
                    $values[] = [
                        'key' => 'all',
                        'label' => 'All',
                        'year' => null,
                        'percentage' => $percentage,
                        'has_data' => $hasData,
                    ];
                }

                if ($hasData) {
                    $overallContributions[] = $percentage;
                }

                $entries[] = [
                    'label' => $jobName,
                    'percentage' => $percentage,
                    'has_data' => $hasData,
                    'values' => $values,
                ];
            }

            if (empty($entries)) {
                $summaries[] = [
                    'department' => $departmentName,
                    'entries' => [],
                    'overall' => null,
                    'has_total_data' => false,
                    'years' => $years,
                ];
                continue;
            }

            $overall = !empty($overallContributions)
                ? round(array_sum($overallContributions) / count($overallContributions), 2)
                : null;

            $totalValues = [];
            if (!empty($years)) {
                foreach ($years as $year) {
                    $yearBucket = $departmentYearTotals[$year] ?? [];
                    $yearAverage = !empty($yearBucket)
                        ? round(array_sum($yearBucket) / count($yearBucket), 2)
                        : null;
                    $totalValues[] = [
                        'key' => (string) $year,
                        'label' => (string) $year,
                        'year' => $year,
                        'percentage' => $yearAverage,
                        'has_data' => $yearAverage !== null,
                    ];
                }
            }

            if (empty($totalValues)) {
                $totalValues[] = [
                    'key' => 'all',
                    'label' => 'All',
                    'year' => null,
                    'percentage' => $overall,
                    'has_data' => $overall !== null,
                ];
            }

            array_unshift($entries, [
                'label' => 'Total',
                'percentage' => $overall,
                'is_total' => true,
                'has_data' => $overall !== null,
                'values' => $totalValues,
            ]);

            $summaries[] = [
                'department' => $departmentName,
                'entries' => $entries,
                'overall' => $overall,
                'has_total_data' => $overall !== null,
                'years' => $years,
            ];
        }

        return $summaries;
    }

    protected function departmentDefinitions(): array
    {
        return [
            'Logistik' => [
                'Logistic Foreman',
                'Admin Cutting Sheet (ACS)',
                'Delivery Staff',
                'Feeder Operator',
                'PPC Staff',
                'Warehouse Staff',
                'Feeder Staff',
            ],
            'Sales' => [
                'Sales Admin',
                'Sales Office Head Region 1',
                'Sales Engineer Region 1',
                'Sales Office Head Region 2',
                'Sales Engineer Region 2',
                'Sales Office Head Region 3&4',
                'Sales Engineer Region 3',
                'Sales Engineer Region 4',
                'Sales Staff',
            ],
            'Procurement' => [
                'Dept Head PDCA Proc Inv IT',
                'Procurement Staff',
                'Inventory Staff',
                'IT Staff',
                'HALOO',
                'IT Support',
            ],
            'Finance, AR, HRGA' => [
                'HRGA Staff',
                'HR & Legal Staff',
                'Accounting Sec Head',
                'Finance Staff',
                'Finance Sec Head',
                'Invoicing Staff',
                'Accounting Staff',
                'Finance Support',
            ],
            'Produksi' => [
                'Prod HT & QC Sec Head',
                'CT MC Foreman',
                'QC Foreman',
                'AKU LUCU',
                'HT Leader',
                'CT MC Operator',
                'HT Admin',
                'Maintenance Operator',
                'HT Operator',
                'Cutting Leader',
                'MC Custom Sec Head',
                'MC Leader',
                'Bubut Operator',
                'MC Custom Operator',
                'MC Custom Staff',
                'Machining Operator',
                'MC Custom Leader',
                'Operator MC',
            ],
        ];
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Models\MstPoPengajuan;
use App\Models\TrsPoPengajuan;
use App\Models\InquirySales;
use App\Models\MstDboCrp;
use App\Models\TcJobPosition;
use App\Models\MstTc;
use App\Models\MstSoftSkill;
use App\Models\MstAdditionals;
use App\Models\TrsPenilaianTc;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Menampilkan view utama dashboard dengan data ringan untuk filter.
     */
    public function dashboardFPB()
    {
        $kategoriList = MstPoPengajuan::distinct()->pluck('kategori_po');
        $allCategories = [
            'IT', 'Subcont', 'Consumable', 'Repair Maintenance', 'Utility',
            'HRGA', 'Material Cost', 'Indirect Material', 'Others',
        ];

        return view('dashboard.dashboardFPB', [
            'kategoriList' => $kategoriList,
            'allCategories' => $allCategories,
        ]);
    }

    // --- ENDPOINT API BARU YANG CEPAT DAN TERPISAH ---

    /**
     * Endpoint #1: Data untuk semua chart di Slide 1 (FPB).
     */
    public function getFpbData(Request $request)
    {
        try {
            $startDateInput = $request->input('start_date', now()->subYear()->startOfYear()->format('Y-m-d'));
            $endDateInput = $request->input('end_date', now()->format('Y-m-d'));
            $kategoriPo = $request->input('kategori_po');

            $startDate = Carbon::parse($startDateInput)->startOfDay();
            $endDate = Carbon::parse($endDateInput)->endOfDay();

            if ($startDate->gt($endDate)) {
                return response()->json(['success' => false, 'message' => 'Rentang tanggal tidak valid.'], 422);
            }

            $cacheKey = sprintf(
                'dashboard:fpb:%s:%s:%s',
                $startDate->format('YmdHis'),
                $endDate->format('YmdHis'),
                $kategoriPo ?: 'all'
            );

            $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($startDate, $endDate, $kategoriPo) {
                $table = (new MstPoPengajuan())->getTable();

                $baseQuery = fn () => MstPoPengajuan::query()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->when($kategoriPo, fn ($query) => $query->where('kategori_po', $kategoriPo));

                $fpbCreatedMonthly = $baseQuery()
                    ->selectRaw('MONTH(created_at) as month, COUNT(id) as total')
                    ->groupBy('month')
                    ->pluck('total', 'month')
                    ->all();

                $fpbFinishedMonthly = TrsPoPengajuan::query()
                    ->where('status', 9)
                    ->whereBetween('updated_at', [$startDate, $endDate])
                    ->whereIn('id_fpb', function ($query) use ($startDate, $endDate, $kategoriPo, $table) {
                        $query->select('id')
                            ->from($table)
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->when($kategoriPo, fn ($subQuery) => $subQuery->where('kategori_po', $kategoriPo));
                    })
                    ->selectRaw('MONTH(updated_at) as month, COUNT(DISTINCT id_fpb) as total')
                    ->groupBy('month')
                    ->pluck('total', 'month')
                    ->all();

                $monthlyData = ['open' => [], 'finish' => []];
                for ($month = 1; $month <= 12; $month++) {
                    $monthlyData['open'][] = (int) ($fpbCreatedMonthly[$month] ?? 0);
                    $monthlyData['finish'][] = (int) ($fpbFinishedMonthly[$month] ?? 0);
                }

                $totalOpen = $baseQuery()->count();

                $totalFinish = TrsPoPengajuan::query()
                    ->where('status', 9)
                    ->whereIn('id_fpb', function ($query) use ($startDate, $endDate, $kategoriPo, $table) {
                        $query->select('id')
                            ->from($table)
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->when($kategoriPo, fn ($subQuery) => $subQuery->where('kategori_po', $kategoriPo));
                    })
                    ->distinct('id_fpb')
                    ->count('id_fpb');

                $categoryBreakdown = $baseQuery()
                    ->selectRaw('kategori_po, COUNT(*) as total')
                    ->groupBy('kategori_po')
                    ->pluck('total', 'kategori_po')
                    ->toArray();

                return [
                    'monthlyData' => $monthlyData,
                    'totalFPB' => $totalOpen,
                    'pieStatus' => [
                        'open' => $totalOpen,
                        'finish' => (int) $totalFinish,
                    ],
                    'pieCategory' => $categoryBreakdown,
                ];
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            Log::error('Error in getFpbData: ' . $e->getMessage() . ' line ' . $e->getLine());
            return response()->json(['success' => false, 'message' => 'Gagal memuat data FPB.'], 500);
        }
    }

    /**
     * Endpoint #2: Data untuk chart Lead Time (Slide 2).
     */
    public function getLeadTimeData(Request $request)
    {
        try {
            $startDateInput = $request->input('start_date', now()->subYear()->startOfYear()->format('Y-m-d'));
            $endDateInput = $request->input('end_date', now()->format('Y-m-d'));

            $startDate = Carbon::parse($startDateInput)->startOfDay();
            $endDate = Carbon::parse($endDateInput)->endOfDay();

            if ($startDate->gt($endDate)) {
                return response()->json(['success' => false, 'message' => 'Rentang tanggal tidak valid.'], 422);
            }

            $cacheKey = sprintf(
                'dashboard:leadtime:%s:%s',
                $startDate->format('YmdHis'),
                $endDate->format('YmdHis')
            );

            $leadTimeData = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($startDate, $endDate) {
                $table = (new MstPoPengajuan())->getTable();

                $confirmSub = TrsPoPengajuan::query()
                    ->select('id_fpb', DB::raw('MIN(updated_at) as confirmed_at'))
                    ->where('status', 6)
                    ->groupBy('id_fpb');

                $finishSub = TrsPoPengajuan::query()
                    ->select('id_fpb', DB::raw('MIN(updated_at) as finished_at'))
                    ->where('status', 9)
                    ->groupBy('id_fpb');

                $categoryRows = MstPoPengajuan::query()
                    ->select(
                        "{$table}.kategori_po",
                        DB::raw("AVG(CASE WHEN confirm.confirmed_at IS NOT NULL THEN DATEDIFF(confirm.confirmed_at, {$table}.created_at) END) as avg_first"),
                        DB::raw("AVG(CASE WHEN confirm.confirmed_at IS NOT NULL AND finish.finished_at IS NOT NULL THEN DATEDIFF(finish.finished_at, confirm.confirmed_at) END) as avg_second")
                    )
                    ->leftJoinSub($confirmSub, 'confirm', function ($join) use ($table) {
                        $join->on('confirm.id_fpb', '=', "{$table}.id");
                    })
                    ->leftJoinSub($finishSub, 'finish', function ($join) use ($table) {
                        $join->on('finish.id_fpb', '=', "{$table}.id");
                    })
                    ->whereBetween("{$table}.created_at", [$startDate, $endDate])
                    ->groupBy("{$table}.kategori_po")
                    ->get()
                    ->keyBy('kategori_po');

                $overall = MstPoPengajuan::query()
                    ->select(
                        DB::raw("AVG(CASE WHEN confirm.confirmed_at IS NOT NULL THEN DATEDIFF(confirm.confirmed_at, {$table}.created_at) END) as avg_first"),
                        DB::raw("AVG(CASE WHEN confirm.confirmed_at IS NOT NULL AND finish.finished_at IS NOT NULL THEN DATEDIFF(finish.finished_at, confirm.confirmed_at) END) as avg_second")
                    )
                    ->leftJoinSub($confirmSub, 'confirm', function ($join) use ($table) {
                        $join->on('confirm.id_fpb', '=', "{$table}.id");
                    })
                    ->leftJoinSub($finishSub, 'finish', function ($join) use ($table) {
                        $join->on('finish.id_fpb', '=', "{$table}.id");
                    })
                    ->whereBetween("{$table}.created_at", [$startDate, $endDate])
                    ->first();

                $categories = ['Total', 'IT', 'Spareparts', 'Consumable', 'GA', 'Subcont'];
                $result = [];

                foreach ($categories as $category) {
                    if ($category === 'Total') {
                        $result[$category] = [
                            'average_lead_days_first' => $overall ? (int) round($overall->avg_first ?? 0) : 0,
                            'average_lead_days_second' => $overall ? (int) round($overall->avg_second ?? 0) : 0,
                        ];
                        continue;
                    }

                    $row = $categoryRows->get($category);
                    $result[$category] = [
                        'average_lead_days_first' => $row ? (int) round($row->avg_first ?? 0) : 0,
                        'average_lead_days_second' => $row ? (int) round($row->avg_second ?? 0) : 0,
                    ];
                }

                return $result;
            });

            return response()->json(['success' => true, 'data' => ['leadTimeData' => $leadTimeData]]);
        } catch (\Throwable $e) {
            Log::error('Error in getLeadTimeData: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memuat data Lead Time.'], 500);
        }
    }

    /**
     * Endpoint #3: Data untuk chart Inquiry (Slide 2).
     */
    public function getInquiryData(Request $request)
    {
        try {
            $startDateInput = $request->input('start_date', now()->subYear()->startOfYear()->format('Y-m-d'));
            $endDateInput = $request->input('end_date', now()->format('Y-m-d'));

            $startDate = Carbon::parse($startDateInput)->startOfDay();
            $endDate = Carbon::parse($endDateInput)->endOfDay();

            if ($startDate->gt($endDate)) {
                return response()->json(['success' => false, 'message' => 'Rentang tanggal tidak valid.'], 422);
            }

            $cacheKey = sprintf(
                'dashboard:inquiry:%s:%s',
                $startDate->format('YmdHis'),
                $endDate->format('YmdHis')
            );

            $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($startDate, $endDate) {
                $baseQuery = fn () => InquirySales::query()
                    ->whereBetween('created_at', [$startDate, $endDate]);

                $openCounts = $baseQuery()
                    ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                    ->groupBy('month')
                    ->pluck('total', 'month')
                    ->all();

                $finishCounts = $baseQuery()
                    ->where('status', 6)
                    ->selectRaw('MONTH(updated_at) as month, COUNT(*) as total')
                    ->groupBy('month')
                    ->pluck('total', 'month')
                    ->all();

                $onProgressCounts = $baseQuery()
                    ->whereIn('status', [5, 7, 8, 9])
                    ->selectRaw('MONTH(updated_at) as month, COUNT(*) as total')
                    ->groupBy('month')
                    ->pluck('total', 'month')
                    ->all();

                $monthlyData = [
                    'open' => [],
                    'onprogress' => [],
                    'finish' => [],
                ];

                for ($month = 1; $month <= 12; $month++) {
                    $monthlyData['open'][] = (int) ($openCounts[$month] ?? 0);
                    $monthlyData['onprogress'][] = (int) ($onProgressCounts[$month] ?? 0);
                    $monthlyData['finish'][] = (int) ($finishCounts[$month] ?? 0);
                }

                $totalInquiries = $baseQuery()->count();

                return [
                    'monthlyData1' => $monthlyData,
                    'totalinquiry' => $totalInquiries,
                ];
            });

            return response()->json(['success' => true, 'data' => $payload]);
        } catch (\Throwable $e) {
            Log::error('Error in getInquiryData: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memuat data Inquiry.'], 500);
        }
    }
    
    /**
     * Endpoint #4: Data untuk chart CRP (Slide 3 & 4).
     */
    public function getCrpData(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $allCategories = ['IT', 'Subcont', 'Consumable', 'Repair Maintenance', 'Utility', 'HRGA', 'Material Cost', 'Indirect Material', 'Others'];
            $categories = array_merge(['Total'], $allCategories);

            $cacheKey = sprintf('dashboard:crp:%s', $user->id);

            $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user, $categories, $allCategories) {
                $aggregated = MstDboCrp::query()
                    ->where('partner_user', $user->id)
                    ->select(
                        'nm_category',
                        'plan_actual',
                        DB::raw('SUM(month_1) as month_1'),
                        DB::raw('SUM(month_2) as month_2'),
                        DB::raw('SUM(month_3) as month_3'),
                        DB::raw('SUM(month_4) as month_4'),
                        DB::raw('SUM(month_5) as month_5'),
                        DB::raw('SUM(month_6) as month_6'),
                        DB::raw('SUM(month_7) as month_7'),
                        DB::raw('SUM(month_8) as month_8'),
                        DB::raw('SUM(month_9) as month_9'),
                        DB::raw('SUM(month_10) as month_10'),
                        DB::raw('SUM(month_11) as month_11'),
                        DB::raw('SUM(month_12) as month_12'),
                        DB::raw('SUM(grand_tot) as grand_total')
                    )
                    ->groupBy('nm_category', 'plan_actual')
                    ->get();

                $monthlyActuals = [];
                $monthlyPlans = [];
                $grandTotalComparison = [];

                foreach ($categories as $category) {
                    $monthlyActuals[$category] = array_fill(0, 12, 0);
                    $monthlyPlans[$category] = array_fill(0, 12, 0);
                    $grandTotalComparison[$category] = ['Plan' => 0, 'Actual' => 0];
                }

                foreach ($aggregated as $row) {
                    $category = $row->nm_category;
                    if (!in_array($category, $allCategories, true)) {
                        continue;
                    }

                    for ($month = 1; $month <= 12; $month++) {
                        $value = (float) ($row->{'month_' . $month} ?? 0);
                        if ($row->plan_actual === 'Plan') {
                            $monthlyPlans[$category][$month - 1] += $value;
                            $monthlyPlans['Total'][$month - 1] += $value;
                        } else {
                            $monthlyActuals[$category][$month - 1] += $value;
                            $monthlyActuals['Total'][$month - 1] += $value;
                        }
                    }

                    if ($row->plan_actual === 'Plan') {
                        $grandTotalComparison[$category]['Plan'] += (float) $row->grand_total;
                        $grandTotalComparison['Total']['Plan'] += (float) $row->grand_total;
                    } else {
                        $grandTotalComparison[$category]['Actual'] += (float) $row->grand_total;
                        $grandTotalComparison['Total']['Actual'] += (float) $row->grand_total;
                    }
                }

                $allMonthlyData = [];
                foreach ($categories as $category) {
                    $cumulativePlan = 0;
                    $cumulativeActual = 0;
                    $monthlyPlanCumulative = [];
                    $monthlyActualCumulative = [];
                    for ($month = 0; $month < 12; $month++) {
                        $cumulativePlan += $monthlyPlans[$category][$month];
                        $cumulativeActual += $monthlyActuals[$category][$month];
                        $monthlyPlanCumulative[] = $cumulativePlan;
                        $monthlyActualCumulative[] = $cumulativeActual;
                    }
                    $allMonthlyData[$category] = [
                        'plan' => $monthlyPlanCumulative,
                        'actual' => $monthlyActualCumulative,
                    ];
                }

                return [
                    'bulanList' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                    'monthlyActuals' => $monthlyActuals,
                    'monthlyPlans' => $monthlyPlans,
                    'grandTotalComparison' => $grandTotalComparison,
                    'allMonthlyData' => $allMonthlyData,
                ];
            });

            return response()->json(['success' => true, 'data' => $payload]);
        } catch (\Throwable $e) {
            Log::error('Error in getCrpData: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memuat data CRP.'], 500);
        }
    }

    public function dashboardTCPD(Request $request)
    {
        $yearOptions = $this->availableCompanyYears();

        $companyYearFromInput = $request->input('company_year_from');
        $companyYearToInput = $request->input('company_year_to');
        [$companyStartDate, $companyEndDate] = $this->resolveYearRange($companyYearFromInput, $companyYearToInput);

        if (!$companyStartDate && !$companyEndDate) {
            $defaultYear = !empty($yearOptions) ? (int) end($yearOptions) : Carbon::now()->year;
            if (!empty($yearOptions)) {
                reset($yearOptions);
            }
            $companyStartDate = Carbon::create($defaultYear, 1, 1, 0, 0, 0)->startOfDay();
            $companyEndDate = Carbon::create($defaultYear, 12, 31, 23, 59, 59)->endOfDay();
            $companyYearFromInput = $defaultYear;
            $companyYearToInput = $defaultYear;
        }

        $jobDateFromInput = $request->input('job_date_from');
        $jobDateToInput = $request->input('job_date_to');

        if (empty($jobDateFromInput) && empty($jobDateToInput)) {
            $now = Carbon::now();
            $jobDateFromInput = $now->copy()->startOfYear()->format('Y-m-d');
            $jobDateToInput = $now->copy()->endOfYear()->format('Y-m-d');
        }

        [$jobStartDate, $jobEndDate] = $this->resolveDateRange($jobDateFromInput, $jobDateToInput);

        $jobPositions = TcJobPosition::query()
            ->select(DB::raw('MIN(id) as id'), 'job_position')
            ->groupBy('job_position')
            ->orderBy('job_position')
            ->get();

        $departmentDefinitions = $this->departmentDefinitions();
        $allJobNames = collect($departmentDefinitions)
            ->flatten()
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $jobSnapshotData = $this->buildTcpdJobData($allJobNames, $companyStartDate, $companyEndDate);
        $departmentSummaries = $this->buildDepartmentSummaries($departmentDefinitions, $jobSnapshotData);
        $companyOverview = $this->buildCompanyOverview($departmentSummaries, $jobSnapshotData['years'] ?? []);

        if ($jobPositions->isEmpty()) {
            return view('dashboard.dashboardTCPD', [
                'jobPositions' => $jobPositions,
                'selectedJobPositionId' => null,
                'selectedJobPositionName' => null,
                'competencyRows' => [],
                'userCountByJobPosition' => 0,
                'userSummaries' => [],
                'totalPercentage' => 0.0,
                'departmentSummaries' => $departmentSummaries,
                'companyOverview' => $companyOverview,
                'yearOptions' => $yearOptions,
                'companyYearFrom' => $companyYearFromInput,
                'companyYearTo' => $companyYearToInput,
                'jobDateFrom' => $jobDateFromInput,
                'jobDateTo' => $jobDateToInput,
            ]);
        }

        $selectedJobPositionId = (int) $request->input('job_position_id', $jobPositions->first()->id);
        $selectedJobPosition = $jobPositions->firstWhere('id', $selectedJobPositionId) ?? $jobPositions->first();
        $selectedJobPositionName = $selectedJobPosition->job_position;

        $snapshot = $this->buildTcpdSnapshot($selectedJobPositionName, $jobStartDate, $jobEndDate);

        return view('dashboard.dashboardTCPD', [
            'jobPositions' => $jobPositions,
            'selectedJobPositionId' => $selectedJobPositionId,
            'selectedJobPositionName' => $selectedJobPositionName,
            'competencyRows' => $snapshot['competencies'],
            'userCountByJobPosition' => $snapshot['qty'],
            'userSummaries' => $snapshot['userSummaries'],
            'totalPercentage' => $snapshot['totalPercentage'],
            'departmentSummaries' => $departmentSummaries,
            'companyOverview' => $companyOverview,
            'yearOptions' => $yearOptions,
            'companyYearFrom' => $companyYearFromInput,
            'companyYearTo' => $companyYearToInput,
            'jobDateFrom' => $jobDateFromInput,
            'jobDateTo' => $jobDateToInput,
        ]);
    }

    public function getTcpdCompetencyData(Request $request)
    {
        $jobPositionId = (int) $request->input('job_position_id');
        $jobPosition = TcJobPosition::select('job_position')->find($jobPositionId);

        if (!$jobPosition) {
            return response()->json([
                'success' => false,
                'message' => 'Job position not found.',
            ], 404);
        }

        [$startDate, $endDate] = $this->resolveDateRange(
            $request->input('date_from'),
            $request->input('date_to')
        );

        $snapshot = $this->buildTcpdSnapshot($jobPosition->job_position, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => [
                'job_position' => $jobPosition->job_position,
                'qty' => $snapshot['qty'],
                'competencies' => $snapshot['competencies'],
                'user_summaries' => $snapshot['userSummaries'],
                'total_percentage' => $snapshot['totalPercentage'],
            ],
        ]);
    }

    public function getTcpdCompanyData(Request $request)
    {
        $yearOptions = $this->availableCompanyYears();

        [$startDate, $endDate] = $this->resolveYearRange(
            $request->input('year_from'),
            $request->input('year_to')
        );

        if (!$startDate && !$endDate) {
            $defaultYear = !empty($yearOptions) ? (int) end($yearOptions) : Carbon::now()->year;
            $startDate = Carbon::create($defaultYear, 1, 1, 0, 0, 0)->startOfDay();
            $endDate = Carbon::create($defaultYear, 12, 31, 23, 59, 59)->endOfDay();
        }

        $departmentDefinitions = $this->departmentDefinitions();
        $allJobNames = collect($departmentDefinitions)
            ->flatten()
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $jobSnapshotData = $this->buildTcpdJobData($allJobNames, $startDate, $endDate);
        $departmentSummaries = $this->buildDepartmentSummaries($departmentDefinitions, $jobSnapshotData);
        $companyOverview = $this->buildCompanyOverview($departmentSummaries, $jobSnapshotData['years'] ?? []);

        return response()->json([
            'success' => true,
            'data' => [
                'company_chart_rows' => $companyOverview['chartRows'],
                'company_average' => $companyOverview['average'],
                'company_years' => $companyOverview['years'],
                'company_chart_mode' => $companyOverview['mode'],
                'company_has_data' => $companyOverview['hasData'],
                'company_department_count' => $companyOverview['departmentCount'],
                'department_summaries' => $departmentSummaries,
            ],
        ]);
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

        static $dateColumns = null;

        if ($dateColumns === null) {
            $table = (new TrsPenilaianTc())->getTable();
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
            'chartRows' => array_map(function (array $row) {
                return [
                    'label' => $row['label'],
                    'percentage' => $row['percentage'],
                    'has_data' => $row['has_data'],
                    'values' => $row['values'],
                    'is_company' => $row['is_company'],
                ];
            }, $rows),
            'average' => $companyAverage ?? 0.0,
            'hasData' => $companyAverage !== null,
            'departmentCount' => $departmentCount,
            'years' => $years,
            'mode' => !empty($years) ? 'yearly' : 'aggregate',
        ];
    }

    protected function buildTcpdSnapshot(string $jobPositionName, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $jobPositionIds = TcJobPosition::where('job_position', $jobPositionName)->pluck('id');

        if ($jobPositionIds->isEmpty()) {
            return [
                'qty' => 0, 'competencies' => [], 'userSummaries' => [],
                'totalPercentage' => 0.0, 'hasTotalPercentage' => false,
            ];
        }

        $userIds = TcJobPosition::where('job_position', $jobPositionName)
            ->whereNotNull('id_user')->distinct()->pluck('id_user')->values();

        $userCount = $userIds->count();
        $userLookup = $userIds->isEmpty()
            ? collect()
            : User::whereIn('id', $userIds)->get(['id', 'name'])->keyBy('id');

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
            
            $calcPercentage = fn ($total, $standardSum) => $standardSum > 0 ? round(($total / $standardSum) * 100, 2) : null;

            return [
                'id' => (int) $userId,
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
                'name' => $jobPositionName,
                'tc_percentage' => $avg_tc !== null ? round($avg_tc, 2) : null,
                'sk_percentage' => $avg_sk !== null ? round($avg_sk, 2) : null,
                'ad_percentage' => $avg_ad !== null ? round($avg_ad, 2) : null,
            ]);
        }

        $allPercentages = [];
        foreach ($userSummaries as $summary) {
            if ($summary['tc_percentage'] !== null) $allPercentages[] = $summary['tc_percentage'];
            if ($summary['sk_percentage'] !== null) $allPercentages[] = $summary['sk_percentage'];
            if ($summary['ad_percentage'] !== null) $allPercentages[] = $summary['ad_percentage'];
        }
        
        $totalPercentage = !empty($allPercentages) ? round(array_sum($allPercentages) / count($allPercentages), 2) : 0.0;

        $technicalStandards = $technical->mapWithKeys(fn ($r) => [$r['id'] => $r['standard']])->all();
        $softSkillStandards = $softSkills->mapWithKeys(fn ($r) => [$r['id'] => $r['standard']])->all();
        $additionalStandards = $additionals->mapWithKeys(fn ($r) => [$r['id'] => $r['standard']])->all();

        $belowStandardUsers = ['technical' => [], 'soft_skill' => [], 'additional' => []];
        $userScoresPerCompetency = [];

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
            }
            if ($scoreRow->id_sk !== null && isset($softSkillSet[$scoreRow->id_sk]) && is_numeric($scoreRow->nilai_sk)) {
                $value = (float) $scoreRow->nilai_sk;
                $compId = (int) $scoreRow->id_sk;
                $standard = $softSkillStandards[$compId] ?? null;
                if ($standard !== null && $value < $standard) {
                    $belowStandardUsers['soft_skill'][$compId][$userId] = true;
                }
                $userScoresPerCompetency['soft_skill'][$compId][] = $value;
            }
            if ($scoreRow->id_ad !== null && isset($additionalSet[$scoreRow->id_ad]) && is_numeric($scoreRow->nilai_ad)) {
                $value = (float) $scoreRow->nilai_ad;
                $compId = (int) $scoreRow->id_ad;
                $standard = $additionalStandards[$compId] ?? null;
                if ($standard !== null && $value < $standard) {
                    $belowStandardUsers['additional'][$compId][$userId] = true;
                }
                $userScoresPerCompetency['additional'][$compId][] = $value;
            }
        }

        $rows = $competencies->map(function (array $row) use ($userScoresPerCompetency, $belowStandardUsers) {
            $scores = $userScoresPerCompetency[$row['type']][$row['id']] ?? [];
            $average = !empty($scores) ? round(array_sum($scores) / count($scores), 2) : null;
            
            return [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => $row['type'],
                'standard' => $row['standard'],
                'average' => $average,
                'qty' => count($belowStandardUsers[$row['type']][$row['id']] ?? []),
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

    protected function buildTcpdJobData(array $jobNames, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $availableJobPositions = TcJobPosition::query()
            ->where('status', 1)
            ->distinct()
            ->pluck('job_position')
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
            $aggregateSnapshots[$jobName] = $this->buildTcpdSnapshot($jobName, $startDate, $endDate);

            if (!empty($years)) {
                foreach ($years as $year) {
                    $yearStart = Carbon::create($year, 1, 1, 0, 0, 0)->startOfDay();
                    $yearEnd = Carbon::create($year, 12, 31, 23, 59, 59)->endOfDay();
                    $yearSnapshots[$jobName][$year] = $this->buildTcpdSnapshot($jobName, $yearStart, $yearEnd);
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
                'Logistic Admin',
                'Admin Cutting Sheet (ACS)',
                'Delivery Staff',
                'Feeder',
                'Logistic Foreman',
                'PPIC Staff',
            ],
            'Sales' => [
                'Sales Admin',
                'SOH Region 1',
                'Sales Engineer Reg 1',
                'SOH Region 2',
                'Sales Engineer Reg 2',
                'SOH Region 3',
                'Sales Engineer Reg 3',
                'SOH Region 4',
                'Sales Engineer Reg 4',
            ],
            'Procurement' => [
                'PDCA, Inventory, Procurement & IT Sec. Head',
                'PDCA & Procurement Non Material Staff',
                'Procurement Material Staff',
                'Procurement Administration',
                'Inventory Staff',
                'IT Staff',
            ],
            'Finance, AR, HRGA' => [
                'HR & GA Section Head',
                'HR & Legal Staff',
                'HRGA & CSR Staff',
                'Finance & Accounting Sec. Head',
                'Finance Admin',
                'Finance & Treasury Sec. Head',
                'Invoicing Staff',
                'AR Staff',
                'Accounting Staff & Kasir',
            ],
            'Produksi' => [
                'Produksi HT Sec. Head',
                'Foreman CT',
                'Foreman QC',
                'Leader HT',
                'Operator CT',
                'Admin HT & PPC',
                'Operator MTN',
                'Operator HT',
                'Leader Cutting',
                'Machining Custom Sec. Head',
                'Leader MC',
                'Operator Bubut',
                'Operator Mc. Custom',
                'MC Custom Staff',
                'Operator Machining',
                'Foreman Machining Custom',
                'Foreman MC',
            ],
        ];
    }
}

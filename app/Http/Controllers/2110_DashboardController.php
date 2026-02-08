<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\MstPoPengajuan;
use App\Models\TrsPoPengajuan;
use App\Models\InquirySales;
use App\Models\MstDboCrp;
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
}

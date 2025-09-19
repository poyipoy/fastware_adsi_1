<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
            $startDate = $request->input('start_date', now()->subYear()->startOfYear()->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));
            $kategoriPo = $request->input('kategori_po');

            $baseQuery = MstPoPengajuan::whereBetween('created_at', [$startDate, $endDate]);
            if ($kategoriPo) {
                $baseQuery->where('kategori_po', $kategoriPo);
            }
            
            $queryForTotals = clone $baseQuery;
            $filteredIds = $queryForTotals->pluck('id');

            // Data untuk Column Chart (FPB Dibuat vs Selesai per bulan)
            $fpbCreatedMonthly = $baseQuery->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(id) as count'))
                ->groupBy('month')->pluck('count', 'month')->all();
                
            $fpbFinishedMonthly = TrsPoPengajuan::select(DB::raw('MONTH(updated_at) as month'), DB::raw('COUNT(DISTINCT id_fpb) as count'))
                ->whereIn('id_fpb', $filteredIds)
                ->where('status', 9) // Asumsi status 9 = Selesai
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->groupBy('month')->pluck('count', 'month')->all();

            $monthlyData = ['open' => [], 'finish' => []];
            for ($m = 1; $m <= 12; $m++) {
                $monthlyData['open'][] = $fpbCreatedMonthly[$m] ?? 0;
                $monthlyData['finish'][] = $fpbFinishedMonthly[$m] ?? 0;
            }

            // Data untuk Pie Chart #1 (Total FPB Dibuat vs Selesai)
            $totalOpen = $queryForTotals->count();
            $totalFinish = TrsPoPengajuan::whereIn('id_fpb', $filteredIds)->where('status', 9)->distinct('id_fpb')->count();
            
            // Data untuk Pie Chart #2 (Breakdown Kategori FPB)
            $categoryBreakdown = $queryForTotals->select('kategori_po', DB::raw('count(*) as total'))
                ->groupBy('kategori_po')->pluck('total', 'kategori_po');

            return response()->json(['success' => true, 'data' => [
                'monthlyData' => $monthlyData,
                'totalFPB' => $totalOpen,
                'pieStatus' => [ 'open' => $totalOpen, 'finish' => $totalFinish ],
                'pieCategory' => $categoryBreakdown,
            ]]);

        } catch (\Exception $e) {
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
            $startDate = $request->input('start_date', now()->subYear()->startOfYear()->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));
            
            $pengajuans = MstPoPengajuan::with('trsPoPengajuans')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $categories = ['Total', 'IT', 'Spareparts', 'Consumable', 'GA', 'Subcont'];
            $leadTimeData = [];
            $groupedByCategory = $pengajuans->groupBy('kategori_po');

            foreach ($categories as $category) {
                $collection = ($category === 'Total') ? $pengajuans : $groupedByCategory->get($category, collect());
                $leadDaysFirstJob = []; $leadDaysSecondJob = [];

                foreach ($collection as $fpb) {
                    $trsSorted = $fpb->trsPoPengajuans->sortBy('updated_at');
                    $firstJobFinish = $trsSorted->where('status', 6)->first(); // Asumsi status 6 = Confirm
                    $secondJobFinish = $trsSorted->where('status', 9)->first(); // Asumsi status 9 = Finish
                    if ($firstJobFinish) {
                        $leadDaysFirstJob[] = Carbon::parse($fpb->created_at)->diffInDays($firstJobFinish->updated_at);
                    }
                    if ($firstJobFinish && $secondJobFinish) {
                        $leadDaysSecondJob[] = Carbon::parse($firstJobFinish->updated_at)->diffInDays($secondJobFinish->updated_at);
                    }
                }
                $leadTimeData[$category] = [
                    'average_lead_days_first' => empty($leadDaysFirstJob) ? 0 : round(array_sum($leadDaysFirstJob) / count($leadDaysFirstJob)),
                    'average_lead_days_second' => empty($leadDaysSecondJob) ? 0 : round(array_sum($leadDaysSecondJob) / count($leadDaysSecondJob)),
                ];
            }
            return response()->json(['success' => true, 'data' => ['leadTimeData' => $leadTimeData]]);
        } catch (\Exception $e) {
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
            $startDate = $request->input('start_date', now()->subYear()->startOfYear()->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            $inquiries = InquirySales::whereBetween('created_at', [$startDate, $endDate])->get();
            $monthlyData = ['open' => array_fill(0, 12, 0), 'onprogress' => array_fill(0, 12, 0), 'finish' => array_fill(0, 12, 0)];
            
            foreach ($inquiries as $inquiry) {
                $createdMonth = Carbon::parse($inquiry->created_at)->month - 1;
                $updatedMonth = Carbon::parse($inquiry->updated_at)->month - 1;
                
                $monthlyData['open'][$createdMonth]++;
                if ($inquiry->status == 6) { // Asumsi status 6 = Finish
                    $monthlyData['finish'][$updatedMonth]++;
                } elseif (in_array($inquiry->status, [5, 7, 8, 9])) { // Asumsi status on progress
                    $monthlyData['onprogress'][$updatedMonth]++;
                }
            }

            return response()->json(['success' => true, 'data' => [
                'monthlyData1' => $monthlyData,
                'totalinquiry' => $inquiries->count(),
            ]]);
        } catch (\Exception $e) {
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
            
            $crpData = MstDboCrp::where('partner_user', $user->id)->get();
            $actuals = $crpData->where('plan_actual', 'Actual');
            $plans = $crpData->where('plan_actual', 'Plan');

            $monthlyActuals = []; $monthlyPlans = []; $grandTotalComparison = [];
            foreach ($categories as $cat) {
                $monthlyActuals[$cat] = array_fill(0, 12, 0);
                $monthlyPlans[$cat] = array_fill(0, 12, 0);
                $grandTotalComparison[$cat] = ['Plan' => 0, 'Actual' => 0];
            }

            foreach ($actuals as $item) {
                $cat = $item->nm_category;
                if (!in_array($cat, $allCategories)) continue;
                for ($i = 1; $i <= 12; $i++) {
                    $val = $item->{"month_$i"} ?? 0;
                    $monthlyActuals[$cat][$i - 1] += $val;
                    $monthlyActuals['Total'][$i - 1] += $val;
                }
                $grandTotalComparison[$cat]['Actual'] += $item->grand_tot ?? 0;
                $grandTotalComparison['Total']['Actual'] += $item->grand_tot ?? 0;
            }

            foreach ($plans as $item) {
                $cat = $item->nm_category;
                if (!in_array($cat, $allCategories)) continue;
                for ($i = 1; $i <= 12; $i++) {
                    $val = $item->{"month_$i"} ?? 0;
                    $monthlyPlans[$cat][$i - 1] += $val;
                    $monthlyPlans['Total'][$i - 1] += $val;
                }
                $grandTotalComparison[$cat]['Plan'] += $item->grand_tot ?? 0;
                $grandTotalComparison['Total']['Plan'] += $item->grand_tot ?? 0;
            }

            $allMonthlyData = [];
            foreach ($categories as $cat) {
                $cumulativePlan = 0; $cumulativeActual = 0;
                $monthlyPlanCumulative = []; $monthlyActualCumulative = [];
                for ($month = 0; $month < 12; $month++) {
                    $cumulativePlan += $monthlyPlans[$cat][$month];
                    $cumulativeActual += $monthlyActuals[$cat][$month];
                    $monthlyPlanCumulative[] = $cumulativePlan;
                    $monthlyActualCumulative[] = $cumulativeActual;
                }
                $allMonthlyData[$cat] = ['plan' => $monthlyPlanCumulative, 'actual' => $monthlyActualCumulative];
            }

            return response()->json(['success' => true, 'data' => [
                'bulanList' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                'monthlyActuals' => $monthlyActuals,
                'monthlyPlans' => $monthlyPlans,
                'grandTotalComparison' => $grandTotalComparison,
                'allMonthlyData' => $allMonthlyData,
            ]]);
        } catch (\Exception $e) {
            Log::error('Error in getCrpData: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memuat data CRP.'], 500);
        }
    }
}
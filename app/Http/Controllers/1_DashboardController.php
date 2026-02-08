<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\MstPoPengajuan;
use App\Models\TrsPoPengajuan;
use App\Models\InquirySales;
use App\Models\MstDboCrp;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // Di controller, dalam fungsi dashboardFPB Anda:
    public function dashboardFPB(Request $request)
    {
        // Ambil data ringan untuk filter
        $kategoriList = MstPoPengajuan::distinct()->pluck('kategori_po'); // Untuk slide 1
        $allCategories = [
            'IT', 'Subcont', 'Consumable', 'Repair Maintenance', 'Utility',
            'HRGA', 'Material Cost', 'Indirect Material', 'Others',
        ]; // Untuk slide 3 & 4

        // Jika Anda ingin nilai default untuk selectedCategory di slide 4
        $selectedCategory = 'Total'; // Atau ambil dari request jika ada filter awal

        return view('dashboard.dashboardFPB', [
            'kategoriList' => $kategoriList,
            'allCategories' => $allCategories,
            'selectedCategory' => $selectedCategory,
            // JANGAN kirim data berat seperti monthlyData, leadTimeData, dll.
        ]);
    }

    // Fungsi untuk menampilkan dashboard CRP (view saja)
    public function dashboardcrp()
    {
        $user = Auth::user();
        $userName = $user->name;

        // Data ringan untuk view
        $allCategories = [
            'IT', 'Subcont', 'Consumable', 'Repair Maintenance', 'Utility',
            'HRGA', 'Material Cost', 'Indirect Material', 'Others',
        ];

        return view('dashboard.dashboardCRP', [ // Pastikan nama view benar
            'userName' => $userName,
            'allCategories' => $allCategories,
            // Data berat seperti actuals, plans, dll tidak perlu dikirim
        ]);
    }

    // --- Endpoint AJAX untuk Slide 1 dan 2 (FPB, Lead Time, Inquiry) ---
    public function getSlide1And2Data(Request $request)
    {
        try {
            // Ambil parameter filter dari request
            $startDateFpb = $request->input('start_date_fpb');
            $endDateFpb = $request->input('end_date_fpb');
            $kategoriPo = $request->input('kategori_po', '');
            $startDateLeadtime = $request->input('start_date_leadtime');
            $endDateLeadtime = $request->input('end_date_leadtime');
            $startDateInquiry = $request->input('start_date_inquiry');
            $endDateInquiry = $request->input('end_date_inquiry');

            // --- Logika Pemrosesan Data FPB (Slide 1) ---
            $startDate1 = $startDateFpb ?: now()->subYear()->startOfYear()->format('Y-m-d'); // Default tahun lalu
            $endDate1 = $endDateFpb ?: now()->format('Y-m-d'); // Default hari ini
            $kategori = $kategoriPo;

            
            $queryMst = MstPoPengajuan::query();
            if ($kategori) {
                $queryMst->where('kategori_po', $kategori);
            }
            $noFpbList = $queryMst->pluck('no_fpb');

            $queryTrs = TrsPoPengajuan::whereIn('id_fpb', function ($query) use ($noFpbList) {
                $query->select('id')->from('mst_po_pengajuans')->whereIn('no_fpb', $noFpbList);
            });
            if ($startDate1 && $endDate1) {
                $queryTrs->whereBetween('created_at', [$startDate1, $endDate1]);
            }

            $trsPoPengajuan = $queryTrs->get()->groupBy('id_fpb');

            // Monthly Data & Counts
            $fpbOpenUnique = 0;
            $fpbFinishUnique = 0;
            $processedFPB = [];
            $monthlyData = [
                'open' => array_fill(0, 12, 0),
                'finish' => array_fill(0, 12, 0)
            ];
            $currentYear = Carbon::now()->year;
            $currentMonth = Carbon::now()->month - 1;

            foreach ($trsPoPengajuan as $id_fpb => $trsEntries) {
                $fpbEntry = $trsEntries->first();
                // Pastikan $fpbEntry ada
                if (!$fpbEntry) continue;

                $no_fpb = MstPoPengajuan::where('id', $id_fpb)->value('no_fpb');

                if ($no_fpb && !isset($processedFPB[$no_fpb])) {
                    $processedFPB[$no_fpb] = true;
                    $lastRecord = $trsEntries->sortByDesc('updated_at')->first();
                    $createdDate = Carbon::parse($fpbEntry->created_at);
                    $createdYear = $createdDate->year;
                    $createdMonth = $createdDate->month - 1;

                    if ($lastRecord && $lastRecord->status == 9) {
                        $finishDate = Carbon::parse($lastRecord->updated_at);
                        $finishYear = $finishDate->year;
                        $finishMonth = $finishDate->month - 1;

                        if ($finishYear > $createdYear) {
                            for ($year = $createdYear; $year <= $finishYear; $year++) {
                                $startMonth = ($year == $createdYear) ? $createdMonth : 0;
                                $endMonth = ($year == $finishYear) ? $finishMonth : 11;
                                for ($m = $startMonth; $m <= $endMonth; $m++) {
                                    $monthlyData['open'][$m]++;
                                }
                            }
                        } else {
                            // Batasi loop hanya dalam rentang filter
                            $loopStartMonth = ($createdYear == Carbon::parse($startDate1)->year) ? Carbon::parse($startDate1)->month - 1 : $createdMonth;
                            $loopEndMonth = ($finishYear == Carbon::parse($endDate1)->year) ? Carbon::parse($endDate1)->month - 1 : $finishMonth;
                            for ($m = max($loopStartMonth, $createdMonth); $m <= min($loopEndMonth, $finishMonth); $m++) {
                                $monthlyData['open'][$m]++;
                            }
                        }
                        // Hanya tambah finish jika bulan finish dalam rentang filter
                        if ($finishYear >= Carbon::parse($startDate1)->year && $finishYear <= Carbon::parse($endDate1)->year) {
                             if ($finishMonth >= 0 && $finishMonth <= 11) { // Validasi index bulan
                                 $monthlyData['finish'][$finishMonth]++;
                             }
                        }
                        $fpbFinishUnique++;
                    } else {
                        // Jika belum selesai, tetap dihitung sampai bulan berjalan atau akhir filter
                        $loopEndYear = Carbon::parse($endDate1)->year;
                        $loopEndMonth = Carbon::parse($endDate1)->month - 1;
                        for ($year = $createdYear; $year <= min($loopEndYear, $currentYear); $year++) {
                            $startMonth = ($year == $createdYear) ? $createdMonth : 0;
                            $endMonth = ($year == $loopEndYear) ? $loopEndMonth : (($year == $currentYear) ? $currentMonth : 11);

                            for ($m = max($startMonth, 0); $m <= min($endMonth, 11); $m++) {
                                $monthlyData['open'][$m]++;
                            }
                        }
                        $fpbOpenUnique++;
                    }
                }
            }
            $totalFPB = $fpbOpenUnique + $fpbFinishUnique;

            // --- Logika Pemrosesan Data Lead Time (Slide 2) ---
            $query2 = MstPoPengajuan::query();
            if ($startDateLeadtime && $endDateLeadtime) {
                $query2->whereBetween('created_at', [$startDateLeadtime, $endDateLeadtime]);
            } else {
                 // Jika tidak ada filter, gunakan default atau rentang besar
                 $query2->whereBetween('created_at', [
                     now()->subYear()->startOfYear()->format('Y-m-d'),
                     now()->format('Y-m-d')
                 ]);
            }
            $mstPoPengajuans2 = $query2->get();
            $uniqueFPB2 = $mstPoPengajuans2->unique('no_fpb');

            $categories = ['IT', 'Spareparts', 'Consumable', 'GA', 'Subcont'];
            $leadTimeData = [];
            $totalSubmitConfirm = 0;
            $totalConfirmFinish = 0;

            foreach ($categories as $category) {
                $filteredMst = $uniqueFPB2->where('kategori_po', $category);
                $categoryTotal = $filteredMst->count();

                $categoryLeadDaysFirstJob = [];
                $categoryLeadDaysSecondJob = [];
                $categorySubmitConfirm = 0;
                $categoryConfirmFinish = 0;

                foreach ($filteredMst as $fpb) {
                    $fpbStartDate = Carbon::parse($fpb->created_at);

                    $trsPoFirstJob = TrsPoPengajuan::where('id_fpb', $fpb->id)
                        ->whereBetween('status', [2, 6])
                        ->orderBy('updated_at', 'desc')
                        ->first();

                    $trsPoSecondJob = TrsPoPengajuan::where('id_fpb', $fpb->id)
                        ->whereBetween('status', [7, 9])
                        ->orderBy('updated_at', 'desc')
                        ->first();

                    if ($trsPoFirstJob && $trsPoFirstJob->status >= 6) {
                        $leadDaysFirstJob = $trsPoFirstJob->updated_at->diffInDays($fpbStartDate);
                        $categoryLeadDaysFirstJob[] = $leadDaysFirstJob;
                    }

                    if ($trsPoSecondJob && $trsPoSecondJob->status >= 6) {
                        $leadDaysSecondJob = $trsPoSecondJob->updated_at->diffInDays($trsPoFirstJob ? $trsPoFirstJob->updated_at : $fpbStartDate);
                        $categoryLeadDaysSecondJob[] = $leadDaysSecondJob;
                    }

                    if ($trsPoSecondJob && in_array($trsPoSecondJob->status, [10, 9])) {
                        $categoryConfirmFinish++;
                    } elseif ($trsPoFirstJob && in_array($trsPoFirstJob->status, [6, 7, 8])) {
                        $categorySubmitConfirm++;
                    }
                }

                $averageLeadDaysFirstJob = count($categoryLeadDaysFirstJob) > 0 ? round(array_sum($categoryLeadDaysFirstJob) / count($categoryLeadDaysFirstJob)) : 0;
                $averageLeadDaysSecondJob = count($categoryLeadDaysSecondJob) > 0 ? round(array_sum($categoryLeadDaysSecondJob) / count($categoryLeadDaysSecondJob)) : 0;

                $totalSubmitConfirm += $categorySubmitConfirm;
                $totalConfirmFinish += $categoryConfirmFinish;

                $leadTimeData[$category] = [
                    'average_lead_days_first' => $averageLeadDaysFirstJob,
                    'average_lead_days_second' => $averageLeadDaysSecondJob,
                    'total' => $categoryTotal,
                    'percentage' => 0, // Bisa dihitung jika perlu
                    'submit_confirm' => $categorySubmitConfirm,
                    'confirm_finish' => $categoryConfirmFinish
                ];
            }

            // Total Lead Time
            $leadTimeData['Total'] = [
                'average_lead_days_first' => count($categories) > 0 ? round(array_sum(array_column($leadTimeData, 'average_lead_days_first')) / count($categories)) : 0,
                'average_lead_days_second' => count($categories) > 0 ? round(array_sum(array_column($leadTimeData, 'average_lead_days_second')) / count($categories)) : 0,
                'total' => $totalFPB, // Gunakan total FPB atau hitung total lead time jika beda
                'percentage' => 100,
                'submit_confirm' => $totalSubmitConfirm,
                'confirm_finish' => $totalConfirmFinish
            ];

            // --- Logika Pemrosesan Data Inquiry (Slide 2) ---
            $startDateInq = $startDateInquiry ?: now()->subYear()->startOfYear()->format('Y-m-d');
            $endDateInq = $endDateInquiry ?: now()->format('Y-m-d');

            $query = InquirySales::query();
            if ($startDateInq && $endDateInq) {
                $query->whereBetween('created_at', [$startDateInq, $endDateInq]);
            }

            $inquirysales = $query->get();
            $uniqueinquiry = $inquirysales->unique('id');

            // Inisialisasi monthly data inquiry (12 bulan statis)
            $monthlyData1 = [
                'open' => array_fill(0, 12, 0),
                'onprogress' => array_fill(0, 12, 0),
                'finish' => array_fill(0, 12, 0)
            ];

            $startMonthIndexInq = Carbon::parse($startDateInq)->month - 1;
            $endMonthIndexInq = Carbon::parse($endDateInq)->month - 1;
            $startYearInq = Carbon::parse($startDateInq)->year;
            $endYearInq = Carbon::parse($endDateInq)->year;

            $processedinquiry = [];

            foreach ($uniqueinquiry as $inquiry) {
                 $createdDateInq = Carbon::parse($inquiry->created_at);
                 $createdMonth1 = $createdDateInq->month - 1;
                 $createdYear1 = $createdDateInq->year;

                 // Hanya proses jika inquiry dibuat dalam rentang filter
                 if ($createdYear1 < $startYearInq || ($createdYear1 == $startYearInq && $createdMonth1 < $startMonthIndexInq) ||
                     $createdYear1 > $endYearInq || ($createdYear1 == $endYearInq && $createdMonth1 > $endMonthIndexInq)) {
                     continue;
                 }

                 $lastRecord1 = InquirySales::where('id', $inquiry->id)
                     ->orderBy('updated_at', 'desc')
                     ->first();

                 if ($lastRecord1 && !isset($processedinquiry[$inquiry->id])) {
                     $processedinquiry[$inquiry->id] = true;

                     if ($lastRecord1->status == 6) {
                         $finishDateInq = Carbon::parse($lastRecord1->updated_at);
                         $finishMonth1 = $finishDateInq->month - 1;
                         $finishYear1 = $finishDateInq->year;

                         // Hanya tambah open jika dalam rentang
                         $loopEndMonth = ($finishYear1 == $endYearInq) ? min($finishMonth1, $endMonthIndexInq) : $endMonthIndexInq;
                         for ($m = max($createdMonth1, $startMonthIndexInq); $m <= $loopEndMonth; $m++) {
                             if ($m >= 0 && $m <= 11) $monthlyData1['open'][$m]++;
                         }
                         // Hanya tambah finish jika dalam rentang
                         if ($finishYear1 >= $startYearInq && $finishYear1 <= $endYearInq &&
                             $finishMonth1 >= $startMonthIndexInq && $finishMonth1 <= $endMonthIndexInq) {
                              if ($finishMonth1 >= 0 && $finishMonth1 <= 11) $monthlyData1['finish'][$finishMonth1]++;
                         }
                     } elseif (in_array($lastRecord1->status, [5, 8, 9, 7])) {
                         for ($m = max($createdMonth1, $startMonthIndexInq); $m <= $endMonthIndexInq; $m++) {
                             if ($m >= 0 && $m <= 11) $monthlyData1['open'][$m]++;
                         }
                         if ($endMonthIndexInq >= 0 && $endMonthIndexInq <= 11) $monthlyData1['onprogress'][$endMonthIndexInq]++;
                     } else {
                         for ($m = max($createdMonth1, $startMonthIndexInq); $m <= $endMonthIndexInq; $m++) {
                             if ($m >= 0 && $m <= 11) $monthlyData1['open'][$m]++;
                         }
                     }
                 }
            }

            // Hitung total inquiry
            $inquiryOpenUnique = 0;
            $inquiryOnprogressUnique = 0;
            $inquiryFinishUnique = 0;
            // Reset untuk perhitungan unik berdasarkan status akhir
            $processedinquiryStatus = [];

            foreach ($uniqueinquiry as $inquiry) {
                $lastRecord1 = InquirySales::where('id', $inquiry->id)
                    ->orderBy('updated_at', 'desc')
                    ->first();

                if ($lastRecord1 && !isset($processedinquiryStatus[$inquiry->id])) {
                    if ($lastRecord1->status == 6) {
                        $inquiryFinishUnique++;
                        $processedinquiryStatus[$inquiry->id] = 'finish';
                    } elseif (in_array($lastRecord1->status, [5, 8, 9, 7])) {
                        $inquiryOnprogressUnique++;
                        $processedinquiryStatus[$inquiry->id] = 'onprogress';
                    } elseif (in_array($lastRecord1->status, [1, 2, 3, 4])) {
                        $inquiryOpenUnique++;
                        $processedinquiryStatus[$inquiry->id] = 'open';
                    }
                }
            }
            $totalinquiry = $inquiryOpenUnique + $inquiryOnprogressUnique + $inquiryFinishUnique;


            // Kembalikan data dalam format JSON
            return response()->json([
                'success' => true,
                'data' => [
                    // Slide 1 Data
                    'monthlyData' => $monthlyData,
                    'fpbCategoryBreakdown' => [], // Jika Anda memerlukan breakdown ini, proses di sini
                    'inquiryStatusBreakdown' => [
                        'open' => $inquiryOpenUnique,
                        'onprogress' => $inquiryOnprogressUnique,
                        'finish' => $inquiryFinishUnique,
                    ],
                    'totalFPB' => $totalFPB,
                    'startDate1' => $startDate1,
                    'endDate1' => $endDate1,
                    // Slide 2 Data
                    'leadTimeData' => $leadTimeData,
                    'monthlyData1' => $monthlyData1,
                    'totalinquiry' => $totalinquiry,
                    'startDateInq' => $startDateInq, // Kirim kembali untuk helper JS
                    'endDateInq' => $endDateInq,     // Kirim kembali untuk helper JS
                    // Tambahkan data lain yang diperlukan untuk slide 1 dan 2
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getSlide1And2Data: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memuat data.'], 500);
        }
    }

    // --- Endpoint AJAX untuk Slide 3 dan 4 (CRP) ---
    public function getSlide3And4Data(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                 return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            // Daftar kategori
            $allCategories = [
                'IT', 'Subcont', 'Consumable', 'Repair Maintenance', 'Utility',
                'HRGA', 'Material Cost', 'Indirect Material', 'Others',
            ];
            $categories = array_merge(['Total'], $allCategories);

            // Ambil data berdasarkan user
            $mstDboCrps = MstDboCrp::where('partner_user', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $actuals = MstDboCrp::where('partner_user', $user->id)
                ->where('plan_actual', 'Actual')
                ->orderBy('created_at', 'desc')
                ->get();

            $plans = MstDboCrp::where('partner_user', $user->id)
                ->where('plan_actual', 'Plan')
                ->orderBy('created_at', 'desc')
                ->get();

            $mstIds = $mstDboCrps->pluck('id')->toArray();
            // Tidak perlu mengambil semua TrsDboCrp jika tidak digunakan di chart
            // $trsDboCrps = TrsDboCrp::whereIn('mst_id', $mstIds)->orderBy('created_at', 'desc')->get();

            // Inisialisasi array
            $monthlyActuals = [];
            $monthlyPlans = [];
            $monthlyPlanCumulative = [];
            $monthlyActualCumulative = [];
            $grandTotalComparison = [];

            foreach ($categories as $cat) {
                $monthlyActuals[$cat] = array_fill(0, 12, 0);
                $monthlyPlans[$cat] = array_fill(0, 12, 0);
                $monthlyPlanCumulative[$cat] = array_fill(0, 12, 0);
                $monthlyActualCumulative[$cat] = array_fill(0, 12, 0);
                $grandTotalComparison[$cat] = ['Plan' => 0, 'Actual' => 0];
            }

            // Proses data actual
            foreach ($actuals as $item) {
                $cat = $item->nm_category;
                if (!in_array($cat, $allCategories)) continue; // Abaikan kategori tidak dikenal
                for ($i = 1; $i <= 12; $i++) {
                    $val = $item->{"month_$i"} ?? 0;
                    $monthlyActuals[$cat][$i - 1] += $val;
                    $monthlyActuals['Total'][$i - 1] += $val;
                }
                $grandTotalComparison[$cat]['Actual'] += $item->grand_tot ?? 0;
                $grandTotalComparison['Total']['Actual'] += $item->grand_tot ?? 0;
            }

            // Proses data plan
            foreach ($plans as $item) {
                $cat = $item->nm_category;
                if (!in_array($cat, $allCategories)) continue; // Abaikan kategori tidak dikenal
                for ($i = 1; $i <= 12; $i++) {
                    $val = $item->{"month_$i"} ?? 0;
                    $monthlyPlans[$cat][$i - 1] += $val;
                    $monthlyPlans['Total'][$i - 1] += $val;
                }
                $grandTotalComparison[$cat]['Plan'] += $item->grand_tot ?? 0;
                $grandTotalComparison['Total']['Plan'] += $item->grand_tot ?? 0;
            }

            // Hitung data kumulatif
            foreach ($categories as $cat) {
                $cumulativePlan = 0;
                $cumulativeActual = 0;
                for ($month = 0; $month < 12; $month++) {
                    $cumulativePlan += $monthlyPlans[$cat][$month];
                    $cumulativeActual += $monthlyActuals[$cat][$month];
                    $monthlyPlanCumulative[$cat][$month] = $cumulativePlan;
                    $monthlyActualCumulative[$cat][$month] = $cumulativeActual;
                }
            }

            // Siapkan data untuk semua kategori (untuk chart dinamis)
            $allMonthlyData = [];
            foreach ($categories as $cat) {
                $allMonthlyData[$cat] = [
                    'plan' => $monthlyPlanCumulative[$cat],
                    'actual' => $monthlyActualCumulative[$cat],
                ];
            }

            // Daftar bulan
            $bulanList = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];

            // Kembalikan data dalam format JSON
            return response()->json([
                'success' => true,
                'data' => [
                    // Slide 3 & 4 Data
                    'bulanList' => $bulanList,
                    'monthlyActuals' => $monthlyActuals,
                    'monthlyPlans' => $monthlyPlans,
                    'grandTotalComparison' => $grandTotalComparison,
                    'allMonthlyData' => $allMonthlyData,
                    // Tambahkan data lain yang diperlukan untuk slide 3 dan 4
                ]
            ]);

        } catch (\Exception $e) {
             Log::error('Error in getSlide3And4Data: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memuat data.'], 500);
        }
    }
}

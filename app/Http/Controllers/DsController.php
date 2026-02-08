<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\MaintenanceDashboardService;
use App\Services\Dashboard\HandlingDashboardService;
use Illuminate\Http\Request;

class DsController extends Controller
{
    public function __construct(
        private MaintenanceDashboardService $maintenanceDashboardService,
        private HandlingDashboardService $handlingDashboardService,
    ) {
        $this->middleware('auth');
    }

    // Buat Dashboard dan Chart
    public function dashboardMaintenance(Request $request)
    {
        $data = $this->maintenanceDashboardService->getDashboardData($request);

        return view('dashboard.dashboardMaintenance', $data);
    }

    public function dashboardHandling(Request $request)
    {
        // Mengambil semua data FormFPP yang memiliki nomor mesin valid, diurutkan berdasarkan updated_at terbaru
        $formperbaikans = FormFPP::whereIn('mesin', function ($query) {
            $query->select('no_mesin')
                ->from('mesin');
        })
            ->orderBy('updated_at', 'desc')
            ->get();

        // Mengambil semua data Mesin yang diurutkan berdasarkan updated_at terbaru
        $mesins = Mesin::orderBy('updated_at', 'desc')->get();

        // Menghitung jumlah form FPP berdasarkan status
        $openCount = $formperbaikans->where('status', 0)->count();
        $onProgressCount = $formperbaikans->where('status', 1)->count();
        $finishCount = $formperbaikans->where('status', 2)->count();
        $closedCount = $formperbaikans->where('status', 3)->count();

        $chartCutting = FormFPP::join('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
            ->select(
                DB::raw('COUNT(CASE WHEN form_f_p_p_s.status_2 = 0 THEN 1 END) as total_status_2_0'),
                DB::raw('COUNT(CASE WHEN form_f_p_p_s.status = 3 THEN 1 END) as total_status_3'),
                DB::raw('MONTH(form_f_p_p_s.created_at) as month')
            )
            ->where('form_f_p_p_s.section', 'cutting') // Tambahkan kondisi untuk memeriksa nilai 'section'
            ->groupBy('month')
            // ->groupBy('form_f_p_p_s.mesin') // Grouping berdasarkan nomor mesin
            ->get();

        $chartMachiningCustom = FormFPP::join('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
            ->select(
                DB::raw('COUNT(CASE WHEN form_f_p_p_s.status_2 = 0 THEN 1 END) as total_status_2_0'),
                DB::raw('COUNT(CASE WHEN form_f_p_p_s.status = 3 THEN 1 END) as total_status_3'),
                DB::raw('MONTH(form_f_p_p_s.created_at) as month')
            )
            ->where('form_f_p_p_s.section', 'machining custom') // Tambahkan kondisi untuk memeriksa nilai 'section'
            ->groupBy('month')
            // ->groupBy('form_f_p_p_s.mesin') // Grouping berdasarkan nomor mesin
            ->get();

        $chartMachining = FormFPP::join('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
            ->select(
                DB::raw('COUNT(CASE WHEN form_f_p_p_s.status_2 = 0 THEN 1 END) as total_status_2_0'),
                DB::raw('COUNT(CASE WHEN form_f_p_p_s.status = 3 THEN 1 END) as total_status_3'),
                DB::raw('MONTH(form_f_p_p_s.created_at) as month')
            )
            ->where('form_f_p_p_s.section', 'machining') // Tambahkan kondisi untuk memeriksa nilai 'section'
            ->groupBy('month')
            // ->groupBy('form_f_p_p_s.mesin') // Grouping berdasarkan nomor mesin
            ->get();

        $chartHeatTreatment = FormFPP::join('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
            ->select(
                DB::raw('COUNT(CASE WHEN form_f_p_p_s.status_2 = 0 THEN 1 END) as total_status_2_0'),
                DB::raw('COUNT(CASE WHEN form_f_p_p_s.status = 3 THEN 1 END) as total_status_3'),
                DB::raw('MONTH(form_f_p_p_s.created_at) as month')
            )
            ->where('form_f_p_p_s.section', 'heat treatment') // Tambahkan kondisi untuk memeriksa nilai 'section'
            ->groupBy('month')
            // ->groupBy('form_f_p_p_s.mesin') // Grouping berdasarkan nomor mesin
            ->get();

        $summaryData = FormFPP::join('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
            ->select(
                DB::raw('MONTH(form_f_p_p_s.created_at) as month'),
                'form_f_p_p_s.section',
                DB::raw('SUM(CASE WHEN form_f_p_p_s.status_2 = 0 THEN 1 ELSE 0 END) as total_status_2_0'), // Total status "open"
                DB::raw('SUM(CASE WHEN form_f_p_p_s.status = 3 THEN 1 ELSE 0 END) as total_status_3') // Total status "closed"
            )
            ->whereIn('form_f_p_p_s.section', ['cutting', 'machining', 'heat treatment', 'machining custom'])
            // ->groupBy('month', 'form_f_p_p_s.section', 'form_f_p_p_s.mesin')
            ->groupBy('month', 'form_f_p_p_s.section')
            ->get();

        $years2 = []; // Tambahkan tahun 2024 secara manual
        sort($years2);
        // Mendapatkan semua section yang tersedia dari tabel Mesin
        $sections = Mesin::where('status', 0)->select('section')->distinct()->pluck('section');

        $section = $request->input('section', 'All');
        $startDate = Carbon::parse($request->input('start_mesin'));
        $endDate = Carbon::parse($request->input('end_mesin'));

        $section_alat = $request->input('section_alat', 'All');
        $startDate_alat = Carbon::parse($request->input('start_alat'));
        $endDate_alat = Carbon::parse($request->input('end_alat'));

        $selectedYear = $request->input('year', date('Y'));
        $selectedSection = $request->input('section', 'All');
        $startMonth = Carbon::parse($request->input('start_month2'));
        $endMonth = Carbon::parse($request->input('end_month2'));

        $summaryData2 = FormFPP::join('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
            ->selectRaw('YEAR(form_f_p_p_s.created_at) as year,
            MONTH(form_f_p_p_s.created_at) as month, SUM(TIMESTAMPDIFF(SECOND, form_f_p_p_s.created_at, form_f_p_p_s.updated_at) / 60) as total_hour')
            ->whereYear('form_f_p_p_s.created_at', $selectedYear)
            ->where('form_f_p_p_s.status', 3)
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // $periodeWaktuPengerjaan = FormFPP::join('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
        //     ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, form_f_p_p_s.created_at, form_f_p_p_s.updated_at) / 60) as total_minute')
        //     ->whereYear('form_f_p_p_s.created_at', $selectedYear)
        //     ->whereBetween('form_f_p_p_s.created_at', [$startMonth, $endMonth])
        //     ->where('form_f_p_p_s.status', 3)
        //     ->first();

        // $periodeWaktuAlat = FormFPP::leftJoin('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
        //     ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, form_f_p_p_s.created_at, form_f_p_p_s.updated_at) / 60) as total_minute')
        //     ->whereYear('form_f_p_p_s.created_at', $selectedYear)
        //     ->where('form_f_p_p_s.status', 3)
        //     ->whereNull('mesin.no_mesin') // Hanya data yang tidak terkait dengan mesin
        //     ->first();

        // Buat array lengkap dari label bulan
        $fullMonthLabels = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        // Inisialisasi array kosong untuk data
        $data2 = array_fill(0, 12, 0);

        return view(
            'dashboard.dashboardHandling',
            compact(
                'formperbaikans',
                'openCount',
                'onProgressCount',
                'finishCount',
                'closedCount',
                'chartCutting',
                'summaryData',
                'summaryData2',
                // 'periodeWaktuAlat',
                'chartCutting',
                'chartMachining',
                'chartMachiningCustom',
                'chartHeatTreatment',
                'data2',
                // 'periodeWaktuPengerjaan',
                'sections',
                'years2',
            )
        );
    }

    public function dshandling(Request $request)
    {
        $data = $this->handlingDashboardService->getDashboardData($request);

        return view('dashboard.dsHandling', $data);
    }
}

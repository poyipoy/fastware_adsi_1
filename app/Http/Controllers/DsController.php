<?php

namespace App\Http\Controllers;

use App\Models\FormFPP;
use App\Models\Handling;
use App\Models\Mesin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Buat Dashboard dan Chart
    public function dashboardMaintenance(Request $request)
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

        $periodeWaktuPengerjaan = FormFPP::join('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, form_f_p_p_s.created_at, form_f_p_p_s.updated_at) / 60) as total_minute')
            ->whereYear('form_f_p_p_s.created_at', $selectedYear)
            ->whereBetween('form_f_p_p_s.created_at', [$startMonth, $endMonth])
            ->where('form_f_p_p_s.status', 3)
            ->first();

        $periodeWaktuAlat = FormFPP::leftJoin('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, form_f_p_p_s.created_at, form_f_p_p_s.updated_at) / 60) as total_minute')
            ->whereBetween('form_f_p_p_s.created_at', [$startMonth, $endMonth])
            ->where('form_f_p_p_s.status', 3)
            ->whereNull('mesin.no_mesin') // Hanya data yang tidak terkait dengan mesin
            ->first();

        // Buat array lengkap dari label bulan
        $fullMonthLabels = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        // Inisialisasi array kosong untuk data
        $data2 = array_fill(0, 12, 0);

        return view(
            'dashboard.dashboardMaintenance',
            compact(
                'formperbaikans',
                'openCount',
                'onProgressCount',
                'finishCount',
                'closedCount',
                'chartCutting',
                'summaryData',
                'summaryData2',
                'periodeWaktuAlat',
                'chartCutting',
                'chartMachining',
                'chartMachiningCustom',
                'chartHeatTreatment',
                'data2',
                'periodeWaktuPengerjaan',
                'sections',
                'years2',
            )
        );
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

    public function dsHandling(Request $request)
    {
        // Mendapatkan data dari database berdasarkan tahun ini pada kolom created_at dan status 3
        $claimData = Handling::whereYear('created_at', date('Y'))
            ->where('type_1', 'Claim')
            ->where('status', 3) // Menambahkan kondisi untuk status bernilai 3
            ->get(['created_at'])
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('m');
            })
            ->map(function ($item) {
                return count($item);
            })
            ->toArray();

        $complainData = Handling::whereYear('created_at', date('Y'))
            ->where('type_1', 'Complain')
            ->where('status', 3) // Menambahkan kondisi untuk status bernilai 3
            ->get(['created_at'])
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('m');
            })
            ->map(function ($item) {
                return count($item);
            })
            ->toArray();

        $countPeriode = Handling::select(
            DB::raw('COUNT(CASE WHEN status_2 = 0 THEN 1 END) as total_status_2_0'),
            DB::raw('COUNT(CASE WHEN status = 3 THEN 1 END) as total_status_3'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('YEAR(created_at) as years') // Menggunakan YEAR() untuk mengambil tahun
        )
            ->groupBy('years', 'month') // Mengelompokkan berdasarkan years (tahun)
            ->get();

        // Mengambil data dari database
        $tipematerialDS = Handling::join('type_materials', 'handlings.type_id', '=', 'type_materials.id')
            ->select('type_materials.id', 'type_materials.type_name', \DB::raw('COUNT(*) as total_type_materials'))
            ->groupBy('type_materials.id', 'type_materials.type_name')
            ->get();

        // Memformat data agar sesuai dengan format yang dibutuhkan oleh Highcharts
        $formattedData = [];
        foreach ($tipematerialDS as $item) {
            $formattedData[] = [
                'name' => $item->type_name,
                'y' => $item->total_type_materials,
            ];
        }

        // Query untuk menghitung jumlah setiap jenis proses
        $processes = DB::table('handlings')
            ->select(
                DB::raw('SUM(CASE WHEN handlings.process_type = "Heat Treatment" THEN 1 ELSE 0 END) AS total_heat_treatment'),
                DB::raw('SUM(CASE WHEN handlings.process_type = "Cutting" THEN 1 ELSE 0 END) AS total_cutting'),
                DB::raw('SUM(CASE WHEN handlings.process_type = "Machining" THEN 1 ELSE 0 END) AS total_machining')
            )
            ->get();

        $pieProses = [
            [
                'name' => 'Heat Treatment',
                'y' => intval($processes[0]->total_heat_treatment),
            ],
            [
                'name' => 'Cutting',
                'y' => intval($processes[0]->total_cutting),
            ],
            [
                'name' => 'Machining',
                'y' => intval($processes[0]->total_machining),
            ],
        ];

        // Inisialisasi array kosong untuk data
        $data2 = array_fill(0, 12, 0);

        return view(
            'dashboard.dsHandling',
            compact(
                'complainData',
                'countPeriode',
                'data2',
                'formattedData',
                'pieProses'
            )
        );
    }
}

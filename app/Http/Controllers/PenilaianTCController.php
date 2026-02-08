<?php

namespace App\Http\Controllers;

use App\Models\TcJobPosition;
use App\Models\TrsPenilaianTc;
use App\Models\TcPeopleDevelopment;
use App\Models\PoinKategori;
use App\Models\User;
use App\Models\DetailTcPenilaian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

// Import the DB facade

class PenilaianTCController extends Controller
{
    public function indexTrs()
    {
        // Mengambil semua data penilaian
        $allPenilaianData = TrsPenilaianTc::all();

        // Mengambil data unik berdasarkan id_job_position
        $penilaianData = $allPenilaianData->unique('id_job_position');

        // Ambil nama dan role_id user yang sedang login
        $userName = auth()->user()->name;
        $roleId = auth()->user()->role_id;

        // Cek apakah role_id adalah 1, 14, atau 15
        if (!in_array($roleId, [1, 15])) {
            // Tentukan data yang ditampilkan berdasarkan nama user
            if ($userName == 'ABDUR RAHMAN AL FAAIZ') {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Admin Cutting Sheet (ACS)',
                        'Delivery Staff',
                        'Feeder',
                        'Logistic Foreman',
                        'Logistic Admin', 
                        'PPIC Staff'
                    ]);
                });
            } elseif ($userName == 'MUGI PRAMONO') {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Produksi HT Sec. Head',
                        'Admin HT & PPC',
                        'Foreman CT',
                        'Foreman QC',
                        'Leader Cutting',
                        'Leader HT',
                        'Operator CT',
                        'Operator HT',
                        'Operator MTN',
                        'Foreman QC',
                    ]);
                });
            } elseif ($userName == 'RAGIL ISHA RAHMANTO') {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Machining Custom Sec. Head',
                        'Leader MC',
                        'Operator Mc. Custom',
                        'MC Custom Staff',
                        'Operator Machining',
                        'Operator Bubut',
                        'Foreman Machining Custom',
                        'Foreman MC',

                    ]);
                });
            } elseif ($userName == 'ADMINSTRATOR' || $userName == 'SITI MARIA ULFA') {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Feeder',
                        'Admin Cutting Sheet (ACS)',
                        'Logistic Admin',
                        'Delivery Staff',
                        'Logistic Foreman',
                        'Finance & Accounting Sec. Head',
                        'HR & Legal Staff',
                        'Finance & Treasury Sec. Head',
                        'HRGA & CSR Staff',
                        'Accounting Staff & Kasir',
                        'Invoicing Staff',
                        'SOH Region 1',
                        'Sales Admin',
                        'Machining Custom Sec. Head',
                        'Produksi HT Sec. Head',
                        'Foreman CT & MC',
                        'Foreman QC',
                        'PPIC Staff',
                        'Leader MC',
                        'Leader HT',
                        'Operator CT',
                        'Operator Bubut',
                        'Operator Mc. Custom',
                        'MC Custom Staff',
                        'Operator Machining',
                        'Admin HT & PPC',
                        'Operator MTN',
                        'Operator HT',
                        'Procurement Material Staff',
                        'Sales Engineer Reg 3',
                        'Sales Engineer Reg 4',
                        'Foreman Machining Custom',
                        'Sales Engineer Reg 1',
                        'SOH Region 2',
                        'AR Staff',
                        'IT Staff',
                        'Sales Engineer Reg 2',
                        'SOH Region 3',
                        'SOH Region 4',
                        'HR, GA, Legal, PDCA, Procurement & IT Se. Head',
                        'HR & GA Section Head',
                        'Leader Cutting',
                        'PDCA & Procurement Non Material Staff',
                        'Procurement Administration',
                        'Inventory Section Head',
                    ]);
                });
            } elseif ($userName == 'ADHI PRASETIYO' ||  $userName == 'RICHARDUS') {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Finance & Accounting Sec. Head',
                        'Finance & Treasury Sec. Head',
                        'AR Staff',
                        'Invoicing Staff',
                        'Finance Admin'
                    ]);
                });
            } elseif ($userName == 'ILHAM CHOLID') {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'SOH Region 1',
                        'Sales Engineer Reg 1',
                        'SOH Region 2',
                        'Sales Engineer Reg 2',
                        'Sales Admin',
                    ]);
                });
            } elseif ($userName == 'JUN JOHAMIN PD') {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'SOH Region 3',
                        'Sales Engineer Reg 3',
                        'SOH Region 4',
                        'Sales Engineer Reg 4'
                    ]);
                });
            } elseif ($userName == 'HARDI SAPUTRA') {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Admin Cutting Sheet (ACS)',
                        'Delivery Staff',
                        'Feeder',
                        'Logistic Foreman',
                        'Logistic Admin', 
                        'PPIC Staff'
                    ]);
                });
            } elseif ($userName == 'ARY RODJO PRASETYO') {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Machining Custom Sec. Head',
                        'Leader MC',
                        'Operator Mc. Custom',
                        'MC Custom Staff',
                        'Operator Machining',
                        'Operator Bubut',
                        'Foreman Machining Custom',
                        'Foreman MC',
                        'Produksi HT Sec. Head',
                        'Admin HT & PPC',
                        'Foreman CT',
                        'Foreman QC',
                        'Leader Cutting',
                        'Leader HT',
                        'Operator CT',
                        'Operator HT',
                        'Operator MTN',
                        'Foreman QC',
                    ]);
                });
            } elseif ($userName == 'MARTINUS CAHYO RAHASTO') {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Finance & Accounting Sec. Head',
                        'Finance & Treasury Sec. Head',
                        'AR Staff',
                        'Invoicing Staff',
                        'Finance Admin',
                        'HR & GA Section Head',
                        'HRGA & CSR Staff',
                        'HR & Legal Staff',
                    ]);
                });
            } elseif ($userName == 'JESSICA PAUNE') {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Inventory Staff',
                        'IT Staff',
                        'PDCA & Procurement Non Material Staff',
                        'PDCA, Inventory, Procurement & IT Sec. Head',
                        'Procurement Administration',
                        'Procurement Material Staff',
                    ]);
                });
            }elseif ($userName == 'YULMAI RIDO WINANDA') {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'SOH Region 1',
                        'Sales Engineer Reg 1',
                        'SOH Region 2',
                        'Sales Engineer Reg 2',
                        'Sales Admin',
                    ]);
                });
            } elseif ($userName == 'ANDIK TOTOK SISWOYO') {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'SOH Region 3',
                        'Sales Engineer Reg 3',
                        'SOH Region 4',
                        'Sales Engineer Reg 4'
                    ]);
                });
            }
        }

        // Ambil semua data posisi dan karyawan
        $positions = TcJobPosition::all();
        $employees = User::all();

        // Menampilkan halaman penilaian dan mengirimkan data yang telah diambil ke view
        return view('tc_penilaian.penilaian_index', compact('penilaianData', 'positions', 'employees'));
    }

    public function indexTrs2()
    {
        // Mengambil semua data penilaian
        $allPenilaianData = TrsPenilaianTc::all();

        // Mengambil data unik berdasarkan id_job_position
        $penilaianData = $allPenilaianData->unique('id_job_position');

        // Ambil nama dan role_id user yang sedang login
        $userName = auth()->user()->name;
        $roleId = auth()->user()->role_id;

        // Cek apakah role_id adalah 1, 14, atau 15
        if (!in_array($roleId, [1, 15])) {
            // Tentukan data yang ditampilkan berdasarkan nama user
            if ($roleId == 7) {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Admin Cutting Sheet (ACS)',
                        'Delivery Staff',
                        'Feeder',
                        'Logistic Foreman',
                        'Logistic Admin', 
                        'PPIC Staff'
                    ]);
                });
            } elseif ($roleId == 5) {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Produksi HT Sec. Head',
                        'Admin HT & PPC',
                        'Foreman CT',
                        'Foreman QC',
                        'Leader Cutting',
                        'Leader HT',
                        'Operator CT',
                        'Operator HT',
                        'Operator MTN',
                        'Foreman QC',
                        'Machining Custom Sec. Head',
                        'Leader MC',
                        'Operator Mc. Custom',
                        'MC Custom Staff',
                        'Operator Machining',
                        'Operator Bubut',
                        'Foreman Machining Custom',
                        'Foreman MC',

                    ]);
                });
            } elseif ($roleId == 11) {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Finance & Accounting Sec. Head',
                        'Finance & Treasury Sec. Head',
                        'AR Staff',
                        'Invoicing Staff',
                        'Finance Admin',
                        'HR & GA Section Head',
                        'HRGA & CSR Staff',
                        'HR & Legal Staff',

                    ]);
                });
            } elseif ($roleId == 2) {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Sales Engineer Reg 1',
                        'Sales Engineer Reg 2',
                        'Sales Admin',
                        'SOH Region 1',
                        'SOH Region 2',
                        'Sales Engineer Reg 3',
                        'Sales Engineer Reg 4'
                    ]);
                });
            } elseif ($roleId == 14) {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Inventory Staff',
                        'IT Staff',
                        'PDCA & Procurement Non Material Staff',
                        'PDCA, Inventory, Procurement & IT Sec. Head',
                        'Procurement Administration',
                        'Procurement Material Staff',
                    ]);
                });
            }
            elseif ($roleId == 15) {
                $penilaianData = $penilaianData->filter(function ($item) {
                    return in_array($item->id_job_position, [
                        'Feeder',
                        'Admin Cutting Sheet (ACS)',
                        'Logistic Admin',
                        'Delivery Staff',
                        'Logistic Foreman',
                        'Finance & Accounting Sec. Head',
                        'HR & Legal Staff',
                        'Finance & Treasury Sec. Head',
                        'HRGA & CSR Staff',
                        'Accounting Staff & Kasir',
                        'Invoicing Staff',
                        'SOH Region 1',
                        'Sales Admin',
                        'Machining Custom Sec. Head',
                        'Produksi HT Sec. Head',
                        'Foreman CT & MC',
                        'Foreman QC',
                        'PPIC Staff',
                        'Leader MC',
                        'Leader HT',
                        'Operator CT',
                        'Operator Bubut',
                        'Operator Mc. Custom',
                        'MC Custom Staff',
                        'Operator Machining',
                        'Admin HT & PPC',
                        'Operator MTN',
                        'Operator HT',
                        'Procurement Material Staff',
                        'Sales Engineer Reg 3',
                        'Sales Engineer Reg 4',
                        'Foreman Machining Custom',
                        'Sales Engineer Reg 1',
                        'SOH Region 2',
                        'AR Staff',
                        'IT Staff',
                        'Sales Engineer Reg 2',
                        'SOH Region 3',
                        'SOH Region 4',
                        'HR, GA, Legal, PDCA, Procurement & IT Se. Head',
                        'HR & GA Section Head',
                        'Leader Cutting',
                        'PDCA & Procurement Non Material Staff',
                        'Procurement Administration',
                        'Inventory Section Head',
                    ]);
                });
            }
        }

        $positions = TcJobPosition::all(); // Mengambil semua data posisi
        $employees = User::all(); // Mengambil semua data karyawan

        // Menampilkan halaman penilaian dan mengirimkan data yang telah diambil ke view
        return view('tc_penilaian.penilaian_index_dept', compact('penilaianData', 'positions', 'employees'));
    }

    public function createPenilaian()
    {
        $id_user = DB::table('users')->pluck('id')->first();
        $id_tc = DB::table('mst_tcs')->pluck('id')->first();
        $id_sk = DB::table('mst_soft_skills')->pluck('id')->first();
        $id_ad = DB::table('mst_additionals')->pluck('id')->first();

        // Ambil data employee dan posisi untuk form
        $users = User::all(); // Ambil semua users atau sesuai kebutuhan

        // Ambil role_id dan nama user yang sedang login
        $roleId = auth()->user()->role_id;
        $userName = auth()->user()->name;

        // Inisialisasi query untuk job positions
        $jobPositionsQuery = TcJobPosition::select(DB::raw('MIN(id) as id'), 'job_position')
            ->groupBy('job_position');

        // Cek apakah role_id adalah 1, 14, atau 15
        if (in_array($roleId, [1, 14, 15])) {
            // Jika ya, tampilkan semua data job_position
            $jobPositions = $jobPositionsQuery->get();
        } else {
            // Jika tidak, tentukan job_position berdasarkan nama user
            if ($userName == 'ABDUR RAHMAN AL FAAIZ') {
                $jobPositions = $jobPositionsQuery->whereIn('job_position', [
                    'Logistic Foreman',
                    'Feeder',
                    'Delivery Staff',
                    'Admin Cutting Sheet (ACS)',
                    'Logistic Admin',
                    'PPIC Staff',
                ])->get();
            } elseif ($userName == 'RAGIL ISHA RAHMANTO') {
                $jobPositions = $jobPositionsQuery->whereIn('job_position', [
                    'Machining Custom Sec. Head',
                        'Leader MC',
                        'Operator Mc. Custom',
                        'MC Custom Staff',
                        'Operator Machining',
                        'Operator Bubut',
                        'Foreman Machining Custom',
                        'Foreman MC',

                ])->get();
            } elseif ($userName == 'MUGI PRAMONO') {
                $jobPositions = $jobPositionsQuery->whereIn('job_position', [
                     'Produksi HT Sec. Head',
                        'Admin HT & PPC',
                        'Foreman CT',
                        'Foreman QC',
                        'Leader Cutting',
                        'Leader HT',
                        'Operator CT',
                        'Operator HT',
                        'Operator MTN',
                        'Foreman QC',

                ])->get();
            } elseif ($userName == 'JESSICA PAUNE') {
                $jobPositions = $jobPositionsQuery->whereIn('job_position', [
                    'Inventory Staff',
                    'IT Staff',
                    'PDCA & Procurement Non Material Staff',
                    'PDCA, Inventory, Procurement & IT Sec. Head',
                    'Procurement Administration',
                    'Procurement Material Staff',
                ])->get();
            } elseif ($userName == 'ADMINSTRATOR' || $userName == 'SITI MARIA ULFA') {
                $jobPositions = $jobPositionsQuery->whereIn('job_position', [
                    'Finance & Accounting Sec. Head',
                    'Finance & Treasury Sec. Head',
                    'HRGA & CSR Staff',
                    'HR & Legal Staff',
                    'HR, GA, Legal, PDCA, Procurement & IT Se. Head',
                    'IT Staff',
                    'Procurement Staff',
                    'Accounting Staff & Kasir',
                    'AR Staff',
                    'Invoicing Staff',
                    'Kurir',
                    'Machining Custom Sec. Head',
                    'Foreman Machining Custom',
                    'Leader MC',
                    'Operator Bubut',
                    'Operator Mc. Custom',
                    'MC Custom Staff',
                    'Operator Machining',
                    'Produksi HT Sec. Head',
                    'Leader HT',
                    'Operator HT',
                    'Admin HT & PPC',
                    'Operator MTN',
                    'Produksi CT & MC Sec. Head',
                    'Foreman CT & MC',
                    'Foreman QC',
                    'Leader CT',
                    'PPIC Staff',
                    'Operator CT',
                ])->get();
            } elseif ($userName == 'ADHI PRASETIYO' ||  $userName == 'RICHARDUS') {
                $jobPositions = $jobPositionsQuery->whereIn('job_position', [
                    'Finance & Accounting Sec. Head',
                        'Finance & Treasury Sec. Head',
                        'AR Staff',
                        'Invoicing Staff',
                        'Finance Admin'

                ])->get();
            } elseif ($userName == 'ILHAM CHOLID') {
                $jobPositions = $jobPositionsQuery->whereIn('job_position', [
                   'SOH Region 1',
                        'Sales Engineer Reg 1',
                        'SOH Region 2',
                        'Sales Engineer Reg 2',
                        'Sales Admin',

                ])->get();
            } elseif ($userName == 'JUN JOHAMIN PD') {
                $jobPositions = $jobPositionsQuery->whereIn('job_position', [
                      'SOH Region 3',
                        'Sales Engineer Reg 3',
                        'SOH Region 4',
                        'Sales Engineer Reg 4'

                ])->get();
            } else {
                // Jika nama user tidak cocok dengan yang ditentukan, tampilkan semua data job_position
                $jobPositions = $jobPositionsQuery->get();
            }
        }

        $trsPenilaian = TrsPenilaianTc::all();
        $idJobPosition = optional($trsPenilaian->first())->id_job_position;

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

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
        $jobPosition = $request->input('id'); // Ambil parameter id dari request

        // Log nilai jobPosition yang diterima
        Log::info('Received jobPosition:', ['jobPosition' => $jobPosition]);

        // Query pertama untuk data TC
        $tcResults = DB::select('
            SELECT jp.id, jp.id_user, jp.id AS id_job_position, jp.job_position, u.name, 
                tcs.id AS id_tc, NULL AS id_sk, NULL AS id_ad, 
                tcs.keterangan_tc AS keterangan,
                tcs.id_poin_kategori, 
                COALESCE(tcs.nilai, \'N/A\') AS nilai, 
                \'tc\' AS type
            FROM tc_job_positions jp
            JOIN users u ON jp.id_user = u.id
            LEFT JOIN mst_tcs tcs ON jp.id = tcs.id_job_position 
            WHERE jp.job_position = ?', [$jobPosition]);

        // Log hasil dari query TC
        Log::info('TC Results:', ['tcResults' => $tcResults]);

        // Query kedua untuk data SK
        $skResults = DB::select('
            SELECT jp.id, jp.id_user, jp.id AS id_job_position, jp.job_position, u.name, 
                NULL AS id_tc, sk.id AS id_sk, NULL AS id_ad, 
                sk.keterangan_sk AS keterangan, 
                sk.id_poin_kategori,
                COALESCE(sk.nilai, \'N/A\') AS nilai, 
                \'sk\' AS type
            FROM tc_job_positions jp
            JOIN users u ON jp.id_user = u.id
            LEFT JOIN mst_soft_skills sk ON jp.id = sk.id_job_position 
            WHERE jp.job_position = ?', [$jobPosition]);

        // Log hasil dari query SK
        Log::info('SK Results:', ['skResults' => $skResults]);

        // Query ketiga untuk data AD
        $adResults = DB::select('
            SELECT jp.id, jp.id_user, jp.id AS id_job_position, jp.job_position, u.name, 
                NULL AS id_tc, NULL AS id_sk, ad.id AS id_ad, 
                ad.keterangan_ad AS keterangan, 
                ad.id_poin_kategori,
                COALESCE(ad.nilai, \'N/A\') AS nilai, 
                \'ad\' AS type
            FROM tc_job_positions jp
            JOIN users u ON jp.id_user = u.id
            LEFT JOIN mst_additionals ad ON jp.id = ad.id_job_position 
            WHERE jp.job_position = ?', [$jobPosition]);

        // Log hasil dari query AD
        Log::info('AD Results:', ['adResults' => $adResults]);

        // Gabungkan hasil dari ketiga query
        $results = array_merge($tcResults, $skResults, $adResults);

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
            SELECT jp.id, jp.id_user, jp.job_position, u.name, 
                tcs.id AS id_tc, NULL AS id_sk, NULL AS id_ad, 
                tcs.keterangan_tc AS keterangan, 
                COALESCE(trs.nilai_tc, 0) AS nilai_tc,  
                NULL AS nilai_sk,  
                NULL AS nilai_ad,  
                "tc" AS type
            FROM tc_job_positions jp
            JOIN users u ON jp.id_user = u.id
            LEFT JOIN mst_tcs tcs ON jp.id = tcs.id_job_position
            LEFT JOIN trs_penilaian_tcs trs ON tcs.id = trs.id_tc AND trs.id_job_position = jp.id
            WHERE jp.job_position = ?
        )
        UNION ALL
        (
            SELECT jp.id, jp.id_user, jp.job_position, u.name, 
                NULL AS id_tc, sk.id AS id_sk, NULL AS id_ad, 
                sk.keterangan_sk AS keterangan, 
                NULL AS nilai_tc,  
                COALESCE(trs.nilai_sk, 0) AS nilai_sk,  
                NULL AS nilai_ad,  
                "sk" AS type
            FROM tc_job_positions jp
            JOIN users u ON jp.id_user = u.id
            LEFT JOIN mst_soft_skills sk ON jp.id = sk.id_job_position
            LEFT JOIN trs_penilaian_tcs trs ON sk.id = trs.id_sk AND trs.id_job_position = jp.id
            WHERE jp.job_position = ?
        )
        UNION ALL
        (
            SELECT jp.id, jp.id_user, jp.job_position, u.name, 
                NULL AS id_tc, NULL AS id_sk, ad.id AS id_ad, 
                ad.keterangan_ad AS keterangan, 
                NULL AS nilai_tc,  
                NULL AS nilai_sk,  
                COALESCE(trs.nilai_ad, 0) AS nilai_ad,  
                "ad" AS type
            FROM tc_job_positions jp
            JOIN users u ON jp.id_user = u.id
            LEFT JOIN mst_additionals ad ON jp.id = ad.id_job_position
            LEFT JOIN trs_penilaian_tcs trs ON ad.id = trs.id_ad AND trs.id_job_position = jp.id
            WHERE jp.job_position = ?
        )
        ', [$jobPosition, $jobPosition, $jobPosition]);

        return response()->json($results);
    }

    public function getNilaiDataEdit(Request $request)
    {
        // Ambil nilai id_job_position dari input request
        $jobPosition = $request->input('id');

        // Query untuk mengambil data berdasarkan id_job_position
        $results = DB::table('trs_penilaian_tcs')
            ->select('id', 'id_tc', 'id_sk', 'id_ad', 'nilai_tc', 'nilai_sk', 'nilai_ad')
            ->where('id_job_position', $jobPosition)
            ->get();

        return response()->json($results);
    }

    public function getJobPointKategori(Request $request)
    {
        $jobPosition = $request->input('id'); // Ambil job_position dari request

        // Query untuk mengambil data TC
        $tcResults = DB::select('
            SELECT jp.id, pk.id AS id_poin_kategori, pk.id_tc, pk.standar_poin AS standar_nilai, pk.tujuan,
                pk.deskripsi AS deskripsi, "tc" AS type
            FROM tc_job_positions jp
            LEFT JOIN tc_poin_kategoris pk ON jp.id = pk.id_job_position
            WHERE jp.job_position = ? AND pk.id_tc IS NOT NULL
        ', [$jobPosition]);

        // Query untuk mengambil data SK
        $skResults = DB::select('
            SELECT jp.id, pk.id AS id_poin_kategori, pk.id_sk, pk.standar_poin AS standar_nilai, pk.tujuan,
                pk.deskripsi AS deskripsi, "sk" AS type
            FROM tc_job_positions jp
            LEFT JOIN tc_poin_kategoris pk ON jp.id = pk.id_job_position
            WHERE jp.job_position = ? AND pk.id_sk IS NOT NULL
        ', [$jobPosition]);

        // Query untuk mengambil data AD
        $adResults = DB::select('
            SELECT jp.id, pk.id AS id_poin_kategori, pk.id_ad, pk.standar_poin AS standar_nilai, pk.tujuan,
                pk.deskripsi AS deskripsi, "ad" AS type
            FROM tc_job_positions jp
            LEFT JOIN tc_poin_kategoris pk ON jp.id = pk.id_job_position
            WHERE jp.job_position = ? AND pk.id_ad IS NOT NULL
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

            foreach ($userIds as $userId) {
                if (!User::find($userId)) {
                    Log::warning("User ID $userId not found, skipping.");
                    continue;
                }

                Log::info("Processing User ID: $userId");

                // Iterasi melalui setiap nilai tc, sk, dan ad untuk menyimpannya
                for ($index = 0; $index < count($nilaiTc[$userId]); $index++) {
                    $nilaiTcValue = isset($nilaiTc[$userId][$index]) ? (int)$nilaiTc[$userId][$index] : null;
                    $nilaiSkValue = isset($nilaiSk[$userId][$index]) ? (int)$nilaiSk[$userId][$index] : null;
                    $nilaiAdValue = isset($nilaiAd[$userId][$index]) ? (int)$nilaiAd[$userId][$index] : null;

                    // Ambil id_tc, id_sk, id_ad
                    $idTcValue = isset($idTc[$userId][$index]) ? (int)$idTc[$userId][$index] : null;
                    $idSkValue = isset($idSk[$userId][$index]) ? (int)$idSk[$userId][$index] : null;
                    $idAdValue = isset($idAd[$userId][$index]) ? (int)$idAd[$userId][$index] : null;

                    // Simpan data ke database
                    $dataToSave = [
                        'id_user' => $userId,
                        'nilai_tc' => $nilaiTcValue,
                        'nilai_sk' => $nilaiSkValue,
                        'nilai_ad' => $nilaiAdValue,
                        'id_tc' => $idTcValue,
                        'id_sk' => $idSkValue,
                        'id_ad' => $idAdValue,
                        'id_job_position' => $idJobPosition ?? null,
                        'status' => 1,
                        'modified_at' => auth()->user()->id,
                        'modified_updated' => auth()->user()->name,
                    ];

                    Log::info('Data to save:', ['data_to_save' => $dataToSave]);

                    // Insert data ke database
                    $result = TrsPenilaianTc::create($dataToSave);

                    if ($result) {
                        Log::info('Data berhasil disimpan untuk user ID: ' . $userId, ['saved_data' => $result->toArray()]);
                    } else {
                        Log::error('Failed to save data for user ID: ' . $userId);
                    }
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
        // Ambil satu data penilaian berdasarkan id_job_position
        $penilaian = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user'])
            ->where('id_job_position', $id_job_position)
            ->first(); // Mengambil satu record

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
        // Ambil satu data penilaian berdasarkan id_job_position
        $penilaian = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user'])
            ->where('id_job_position', $id_job_position)
            ->first(); // Mengambil satu record

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

        $detailPenilaian = DetailTcPenilaian::where('id_job_position', $id_job_position)
            ->orderBy('created_at', 'desc') // Urutkan berdasarkan waktu terbaru
            ->get();

        return view('tc_penilaian.dept_penilaian', compact('penilaian', 'dataTc1', 'dataTc2', 'dataTc3', 'detailPenilaian'));
    }

    public function viewTrs($id_job_position)
    {
        // Ambil satu data penilaian berdasarkan id_job_position
        $penilaian = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user'])
            ->where('id_job_position', $id_job_position)
            ->first(); // Mengambil satu record

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
        $penilaian = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user'])
            ->where('id_job_position', $id_job_position)
            ->first(); // Mengambil satu record

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

        // Ambil data JSON yang dikirim dari AJAX
        $data = $request->json()->all();

        // Log data yang diterima untuk pengecekan
        Log::info('Received data:', [
            'nilai_tc' => $data['nilai_tc'],
            'keterangan_tc' => $data['keterangan_tc'],
            'nilai_sk' => $data['nilai_sk'],
            'keterangan_sk' => $data['keterangan_sk'],
            'nilai_ad' => $data['nilai_ad'],
            'keterangan_ad' => $data['keterangan_ad'],
            'names' => $data['names']
        ]);

        // Update status dari penilaian
        TrsPenilaianTc::where('id_job_position', $decoded_job_position)
            ->where('status', 3)
            ->update(['status' => 2]);

        // Ambil semua data penilaian terkait berdasarkan id_job_position
        $penilaians = TrsPenilaianTc::where('id_job_position', $decoded_job_position)->get();

        // Array untuk mengumpulkan perubahan keterangan_detail per name
        $changesByName = [];

        foreach ($penilaians as $index => $penilaian) {
            $hasChanged = false;
            $userName = isset($data['names'][$index]) ? $data['names'][$index] : 'Unknown'; // Ambil nama sesuai indeks
            $currentKeteranganDetail = [];

            // Proses nilai_tc
            if (isset($data['nilai_tc'][$index]) && $penilaian->nilai_tc != $data['nilai_tc'][$index]) {
                $penilaian->nilai_tc = $data['nilai_tc'][$index];
                $hasChanged = true;
                $currentKeteranganDetail[] = "Technical Competency: {$data['keterangan_tc'][$index]} = {$data['nilai_tc'][$index]}";
            }

            // Proses nilai_sk
            if (isset($data['nilai_sk'][$index]) && $penilaian->nilai_sk != $data['nilai_sk'][$index]) {
                $penilaian->nilai_sk = $data['nilai_sk'][$index];
                $hasChanged = true;
                $currentKeteranganDetail[] = "Non-Competency (Soft Skills): {$data['keterangan_sk'][$index]} = {$data['nilai_sk'][$index]}";
            }

            // Proses nilai_ad
            if (isset($data['nilai_ad'][$index]) && $penilaian->nilai_ad != $data['nilai_ad'][$index]) {
                $penilaian->nilai_ad = $data['nilai_ad'][$index];
                $hasChanged = true;
                $currentKeteranganDetail[] = "Additional: {$data['keterangan_ad'][$index]} = {$data['nilai_ad'][$index]}";
            }

            // Simpan perubahan penilaian jika ada
            if ($hasChanged) {
                $penilaian->save();

                // Gabungkan perubahan berdasarkan nama
                if (!isset($changesByName[$userName])) {
                    $changesByName[$userName] = [];
                }
                $changesByName[$userName] = array_merge($changesByName[$userName], $currentKeteranganDetail);
            }
        }

        // Simpan ke DetailTcPenilaian dengan menggabungkan keterangan_detail per nama
        foreach ($changesByName as $userName => $keteranganDetails) {
            DetailTcPenilaian::create([
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails), // Gabungkan detail dengan pemisah
                'catatan' => $data['alasan_perubahan'], // Alasan perubahan
                'modified_at' => auth()->user()->name,
            ]);

            Log::info('DetailTcPenilaian created for:', [
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails),
                'catatan' => $data['alasan_perubahan']
            ]);
        }

        // Kembalikan respon sukses
        return response()->json(['success' => true, 'message' => 'Nilai berhasil diupdate']);
    }


    public function updateTrs2(Request $request, $id_job_position)
    {
        // Decode HTML entities pada $id_job_position untuk menghindari perubahan karakter
        $decoded_job_position = html_entity_decode($id_job_position);

        // Ambil data JSON yang dikirim dari AJAX
        $data = $request->json()->all();

        // Log data yang diterima untuk pengecekan
        Log::info('Received data:', [
            'nilai_tc' => $data['nilai_tc'],
            'keterangan_tc' => $data['keterangan_tc'],
            'nilai_sk' => $data['nilai_sk'],
            'keterangan_sk' => $data['keterangan_sk'],
            'nilai_ad' => $data['nilai_ad'],
            'keterangan_ad' => $data['keterangan_ad'],
            'names' => $data['names']
        ]);

        // Ambil semua data penilaian terkait berdasarkan id_job_position
        $penilaians = TrsPenilaianTc::where('id_job_position', $decoded_job_position)->get();

        // Array untuk mengumpulkan perubahan keterangan_detail per nama
        $changesByName = [];

        foreach ($penilaians as $index => $penilaian) {
            $hasChanged = false;
            $userName = isset($data['names'][$index]) ? $data['names'][$index] : 'Unknown'; // Ambil nama sesuai indeks
            $currentKeteranganDetail = [];

            // Proses nilai_tc
            if (isset($data['nilai_tc'][$index]) && $penilaian->nilai_tc != $data['nilai_tc'][$index]) {
                $penilaian->nilai_tc = $data['nilai_tc'][$index];
                $hasChanged = true;
                $currentKeteranganDetail[] = "Technical Competency: {$data['keterangan_tc'][$index]} = {$data['nilai_tc'][$index]}";
            }

            // Proses nilai_sk
            if (isset($data['nilai_sk'][$index]) && $penilaian->nilai_sk != $data['nilai_sk'][$index]) {
                $penilaian->nilai_sk = $data['nilai_sk'][$index];
                $hasChanged = true;
                $currentKeteranganDetail[] = "Non-Competency (Soft Skills): {$data['keterangan_sk'][$index]} = {$data['nilai_sk'][$index]}";
            }

            // Proses nilai_ad
            if (isset($data['nilai_ad'][$index]) && $penilaian->nilai_ad != $data['nilai_ad'][$index]) {
                $penilaian->nilai_ad = $data['nilai_ad'][$index];
                $hasChanged = true;
                $currentKeteranganDetail[] = "Additional: {$data['keterangan_ad'][$index]} = {$data['nilai_ad'][$index]}";
            }

            // Simpan perubahan penilaian jika ada
            if ($hasChanged) {
                $penilaian->save();

                // Gabungkan perubahan berdasarkan nama
                if (!isset($changesByName[$userName])) {
                    $changesByName[$userName] = [];
                }
                $changesByName[$userName] = array_merge($changesByName[$userName], $currentKeteranganDetail);
            }
        }

        // Simpan ke DetailTcPenilaian dengan menggabungkan keterangan_detail per nama
        foreach ($changesByName as $userName => $keteranganDetails) {
            DetailTcPenilaian::create([
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails), // Gabungkan detail dengan pemisah
                'catatan' => $data['alasan_perubahan'], // Alasan perubahan
                'modified_at' => auth()->user()->name,
            ]);

            Log::info('DetailTcPenilaian created for:', [
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails),
                'catatan' => $data['alasan_perubahan']
            ]);
        }

        // Kembalikan respon sukses
        return response()->json(['success' => true, 'message' => 'Nilai berhasil diupdate']);
    }

    public function updateCatatan(Request $request, $id)
    {
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
        // Temukan semua entri dengan id_job_position yang sesuai
        $penilaians = TrsPenilaianTc::where('id_job_position', $id_job_position)->get();

        if ($penilaians->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data not found.'], 404);
        }

        // Ubah status menjadi 2 untuk semua entri yang ditemukan
        foreach ($penilaians as $penilaian) {
            $penilaian->status = 2;
            $penilaian->modified_at = auth()->user()->id;
            $penilaian->save();
        }

        return response()->json(['success' => true, 'message' => 'Data Competency Telah Dikirim.']);
    }

    public function kirimDept(Request $request, $id_job_position)
    {
        // Temukan semua entri dengan id_job_position yang sesuai
        $penilaians = TrsPenilaianTc::where('id_job_position', $id_job_position)->get();

        if ($penilaians->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data not found.'], 404);
        }

        // Ubah status menjadi 2 untuk semua entri yang ditemukan
        foreach ($penilaians as $penilaian) {
            $penilaian->status = 3;
            $penilaian->save();
        }

        return response()->json(['success' => true, 'message' => 'Data Competency Telah Dikirim.']);
    }
    //chartRadar
    public function getCompetencyData(Request $request)
    {
        $selectedJobPosition = $request->input('job_position');

        $competencyData = DB::table('trs_penilaian_tcs as tpt')
            ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
            ->leftJoin('mst_tcs as tc', 'tpt.id_tc', '=', 'tc.id')
            ->leftJoin('mst_soft_skills as sk', 'tpt.id_sk', '=', 'sk.id')
            ->leftJoin('mst_additionals as ad', 'tpt.id_ad', '=', 'ad.id')
            ->select(
                'tpt.id_job_position',
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
                DB::raw('SUM(ad.nilai) AS standar_nilai_ad') // Pastikan ini diperbaiki
            )
            ->where('tpt.id_job_position', $selectedJobPosition)
            ->groupBy('tpt.id_user', 'tpt.id_job_position', 'u.name')
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
            ->where('tpt.id_user', $id_user)
            ->groupBy(
                'tpt.id_user',
                'tpt.id_job_position',
                'u.name',
                'tpt.id_tc',
                'tc.keterangan_tc'
            )
            ->get();

        // Query untuk data yang berhubungan dengan SK
        $skData = DB::table('trs_penilaian_tcs as tpt')
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
            ->where('tpt.id_user', $id_user)
            ->groupBy(
                'tpt.id_user',
                'tpt.id_job_position',
                'u.name',
                'tpt.id_sk',
                'sk.keterangan_sk'
            )
            ->get();

        // Query untuk data yang berhubungan dengan AD
        $adData = DB::table('trs_penilaian_tcs as tpt')
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
            ->where('tpt.id_user', $id_user)
            ->groupBy(
                'tpt.id_user',
                'tpt.id_job_position',
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
}

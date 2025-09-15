<?php

namespace App\Http\Controllers;

use App\Models\Preventive;
use App\Models\Mesin;
use App\Models\DetailPreventive;
use App\Models\JadwalPreventif;
use App\Models\TrsPreventive;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;


class PreventiveController extends Controller
{
    public function dashboardPreventive(Request $request)
    {
        // Mengambil data mesin beserta jadwal preventif, diurutkan berdasarkan section dan statusnya 0
        $mesins = Mesin::with('preventifs')
            ->where('status', 0)
            ->orderBy('section')
            ->get();

        // Mengirimkan data ke tampilan
        return view('deptmtce.tabelpreventive', compact('mesins'));
    }

    public function dashboardPreventiveMaintenance(Request $request, JadwalPreventif $preventive)
    {
        // Mengambil data mesin beserta jadwal preventif, diurutkan berdasarkan section dan statusnya 0
        $mesins = Mesin::with('preventifs')
            ->where('status', 0)
            ->orderBy('section')
            ->get();

        $preventives = JadwalPreventif::latest()->get();

        // Mengirimkan data ke tampilan
        return view('maintenance.tabelpreventive', compact('mesins', 'preventives', 'preventive'));
    }

    public function dashboardPreventiveMaintenanceGA(Request $request, JadwalPreventif $preventive)
    {
        // Mengambil data mesin beserta jadwal preventif, diurutkan berdasarkan section dan statusnya 0
        $mesins = Mesin::with('preventifs')
            ->where('status', 0)
            ->orderBy('section')
            ->get();

        $preventives = JadwalPreventif::latest()->get();

        // Mengirimkan data ke tampilan
        return view('ga.dashpreventivemaintenance', compact('mesins', 'preventives', 'preventive'));
    }


    // public function dashboardPreventiveMaintenance(Request $request)
    // {
    //     // Ambil nilai issues dan checkedIssues dari sesi jika ada
    //     $issues = $request->session()->get('issues', []);
    //     // Mengambil data mesin beserta jadwal preventif
    //     $mesins = Mesin::with('preventifs')->get();

    //     // Mengirimkan data ke tampilan
    //     return view('maintenance.tabelpreventive', compact('issues', 'mesins'));
    // }

    public function create(Request $request)
    {
        // Ambil nilai issues dan checkedIssues dari sesi jika ada
        $issues = $request->session()->get('issues', []);
        // Ambil daftar data mesin dari database
        $mesins = Mesin::with('preventifs')
            ->where('status', 0)
            ->orderBy('section')
            ->get();

        // Kemudian, Anda dapat mengirimkan nilai-nilai ini ke view
        return view('deptmtce.createpreventive', compact('issues', 'mesins'));
    }

    // public function edit()
    // {
    //     $mesins = Mesin::orderBy('updated_at', 'desc')->get();
    //     $preventives = JadwalPreventif::orderBy('updated_at', 'desc')->get();
    //     return view('maintenance.editpreventive', compact('mesins', 'preventives'));
    // }

    public function lihatIssue(JadwalPreventif $preventive, Mesin $mesin, DetailPreventive $detailpreventive)
    {
        // Ambil detail preventive berdasarkan nomor mesin dan jadwal rencana dari preventive
        $issues = $detailpreventive->where('nomor_mesin', $preventive->nomor_mesin)
            ->where('jadwal_rencana', $preventive->jadwal_rencana)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->pluck('issue')
            ->toArray();

        $checkedIssues = $detailpreventive->where('nomor_mesin', $preventive->nomor_mesin)
            ->where('jadwal_rencana', $preventive->jadwal_rencana)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->pluck('issue_checked')
            ->toArray();

        $logs = TrsPreventive::with('jadwalPreventif', 'userprev')
            ->where('prev_id', $preventive->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Ambil daftar data mesin dari database
        $mesins = Mesin::all();
        $preventives = JadwalPreventif::all();
        $selected_mesin_nomor = $preventive->nomor_mesin;

        return view('deptmtce.lihatpreventive', compact('logs', 'preventive', 'issues', 'mesins', 'checkedIssues', 'selected_mesin_nomor'));
    }


    public function editIssue(JadwalPreventif $preventive, Mesin $mesin, DetailPreventive $detailpreventive)
    {
        // Ambil detail preventive berdasarkan nomor mesin dan jadwal rencana dari preventive
        // Ambil semua issue sesuai filter
        $issues = $detailpreventive
            ->where('nomor_mesin', $preventive->nomor_mesin)
            ->where('jadwal_rencana', $preventive->jadwal_rencana)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->orderBy('id')                     // memastikan urutan konsisten
            ->pluck('issue')
            ->toArray();

        // Ambil tanggal updated_at dengan filter yang **sama persis**
        $updatedAts = $detailpreventive
            ->where('nomor_mesin', $preventive->nomor_mesin)
            ->where('jadwal_rencana', $preventive->jadwal_rencana)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->orderBy('id')                     // urutan harus sama dengan $issues
            ->pluck('updated_at')
            ->toArray();


        $checkedIssues = $detailpreventive->where('nomor_mesin', $preventive->nomor_mesin)
            ->where('jadwal_rencana', $preventive->jadwal_rencana)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->pluck('issue_checked')
            ->toArray();

        // Periksa apakah ada event dengan status 1 untuk preventive yang sedang diedit
        $existingEventStatus1 = JadwalPreventif::where('nomor_mesin', $preventive->nomor_mesin)
            ->where('jadwal_rencana', $preventive->jadwal_rencana)
            ->where('jadwal_aktual', $preventive->jadwal_aktual)
            ->where('status', 1)
            ->exists();
        
        $logs = TrsPreventive::with('jadwalPreventif', 'userprev')
        ->where('prev_id', $preventive->id)
        ->orderBy('created_at', 'desc')
        ->get();

        // Ambil daftar data mesin dari database
        $mesins = Mesin::all();
        $preventives = JadwalPreventif::all();
        $selected_mesin_nomor = $preventive->nomor_mesin;

        // Tampilkan view sesuai dengan kondisi
        if (!$existingEventStatus1) {
            // Jika tidak ada event dengan status 1, tampilkan form edit event
            return view('maintenance.editpreventive', compact('logs', 'preventives', 'issues', 'mesins', 'checkedIssues', 'selected_mesin_nomor', 'preventive', 'updatedAts'));
        } else {
            // Jika ada event dengan status 1, tampilkan detail event
            return view('maintenance.lihatpreventive', compact('logs', 'preventive', 'issues', 'mesins', 'checkedIssues', 'selected_mesin_nomor', 'updatedAts'));
        }
    }


    public function updateIssue(
    Request $request,
    JadwalPreventif $preventive,
    DetailPreventive $detailPreventive
): RedirectResponse {
    $issues        = $request->input('issue', []);
    $checkedIssues = $request->input('checked', []);
    $jadwalAktual  = $request->input('jadwal_aktual');
    $keterangan    = $request->input('keterangan');

    // -------------------------------------------------
    // 1. Cek perubahan di jadwal_aktual dan keterangan
    // -------------------------------------------------
    $updateData = [];

    if ($jadwalAktual && $jadwalAktual !== $preventive->jadwal_aktual) {
        $updateData['jadwal_aktual'] = $jadwalAktual;
        TrsPreventive::create([
            'prev_id'     => $preventive->id,
            'keterangan'  => "jadwal aktual: {$jadwalAktual}",
            'modified_at' => auth()->id()
        ]);
    }

    if ($keterangan && $keterangan !== $preventive->keterangan) {
        $updateData['keterangan'] = $keterangan;
        TrsPreventive::create([
            'prev_id'     => $preventive->id,
            'keterangan'  => "keterangan: {$keterangan}",
            'modified_at' => auth()->id()
        ]);
    }

    if (!empty($updateData)) {
        $preventive->update($updateData);
    }

    // -------------------------------------------------
    // 2. Proses issue checklist
    // -------------------------------------------------
    foreach ($issues as $key => $issue) {
    $isChecked = in_array($key, $checkedIssues); // true jika dicentang
    $newStatus = $isChecked ? '1' : '0';

    $existing = DetailPreventive::where('nomor_mesin', $preventive->nomor_mesin)
        ->where('issue', $issue)
        ->where('jadwal_rencana', $preventive->jadwal_rencana)
        ->first();

    if ($existing) {
        // Jika status berbeda, update
        if ($existing->issue_checked !== $newStatus) {
            $existing->update(['issue_checked' => $newStatus]);

            TrsPreventive::create([
                'prev_id'     => $preventive->id,
                'keterangan'  => "Issue \"{$issue}\" " . ($isChecked ? "dicentang" : "tidak dicentang"),
                'modified_at' => auth()->id()
            ]);
        }
        // Jika status sama, tidak lakukan apa-apa
    } else {
        // Jika belum ada data dan sekarang dicentang (1), simpan
        if ($newStatus === '1') {
            DetailPreventive::create([
                'nomor_mesin'    => $preventive->nomor_mesin,
                'issue'          => $issue,
                'issue_checked'  => $newStatus,
                'jadwal_rencana' => $preventive->jadwal_rencana,
            ]);

            TrsPreventive::create([
                'prev_id'     => $preventive->id,
                'keterangan'  => "Issue \"{$issue}\" dicentang",
                'modified_at' => auth()->id()
            ]);
        }
        // Jika belum ada dan tidak dicentang (0), tidak perlu simpan apa-apa
    }
}


    // -------------------------------------------------
    // 3. Konfirmasi Selesai Preventive
    // -------------------------------------------------
    if ($request->confirmed_event === '1') {
        if ($preventive->status != 1) {
            $preventive->update([
                'status'        => 1,
                'jadwal_aktual' => now(),
            ]);

            TrsPreventive::create([
                'prev_id'     => $preventive->id,
                'keterangan'  => "Status preventive diubah menjadi selesai",
                'modified_at' => auth()->id()
            ]);
        }

        if (is_null($detailPreventive->jadwal_aktual)) {
            $detailPreventive->update(['jadwal_aktual' => now()]);

            TrsPreventive::create([
                'prev_id'     => $preventive->id,
                'keterangan'  => "Jadwal aktual pada detail preventive diperbarui",
                'modified_at' => auth()->id()
            ]);
        }
    } else {
        if ($preventive->status != 0) {
            $preventive->update(['status' => 0]);

            TrsPreventive::create([
                'prev_id'     => $preventive->id,
                'keterangan'  => "Status preventive direset menjadi 0",
                'modified_at' => auth()->id()
            ]);
        }
    }

    return redirect()->route('dashboardPreventiveMaintenance');
}








    // public function store(Request $request)
    // {
    //     $request->merge(['status' => 0]);

    //     // Buat entri baru untuk setiap bulan yang belum ada
    //     $jadwal_rencana = \Carbon\Carbon::createFromFormat('Y-m-d', $request->jadwal_rencana);

    //     $existingJadwals = JadwalPreventif::where('nomor_mesin', $request->mesin)
    //         ->get()
    //         ->groupBy(function ($item) {
    //             return \Carbon\Carbon::createFromFormat('Y-m-d', $item->jadwal_rencana)->format('Y-m');
    //         });

    //     $bulan = $jadwal_rencana->format('Y-m');
    //     if ($existingJadwals->has($bulan)) {
    //         // Jika jadwal untuk bulan yang sama sudah ada, tidak lakukan apa-apa
    //         return redirect()->route('dashboardPreventive')->with('success', 'Jadwal already exists for this month');
    //     }

    //     // Jika tidak, buat entri baru
    //     JadwalPreventif::create([
    //         'nomor_mesin' => $request->mesin,
    //         'tipe' => $request->tipe,
    //         'jadwal_rencana' => $jadwal_rencana,
    //         'status' => $request->status
    //     ]);

    //     return redirect()->route('dashboardPreventive')->with('success', 'Jadwal created successfully');
    // }

    public function store(Request $request, JadwalPreventif $preventive, DetailPreventive $detailPreventive): RedirectResponse
    {
        // Mengubah status menjadi 0
        $request->merge(['status' => 0]);

        // Buat entri baru untuk setiap bulan yang belum ada
        $jadwal_rencana = \Carbon\Carbon::createFromFormat('Y-m-d', $request->jadwal_rencana);

        // Simpan data mesin beserta path foto dan sparepart ke database
        $preventive = JadwalPreventif::create([
            'nomor_mesin' => $request->mesin,
            'tipe' => $request->tipe,
            'jadwal_rencana' => $jadwal_rencana,
            'status' => $request->status
        ]);

        // Ambil semua nilai issue dan perbaikan dari request
        $issues = $request->input('issue');
        $checkedIssues = $request->input('checked') ?? [];

        foreach ($issues as $key => $issue) {
            // Buat detail preventive baru dan hubungkan dengan Event yang baru saja dibuat
            $detailPreventive->create([
                'nomor_mesin' => $request->mesin, // Menggunakan nomor_mesin yang disimpan dalam form
                'issue' => $issue,
                'issue_checked' => (in_array($key, $checkedIssues) ? '1' : '0'),
                'jadwal_rencana' => $request->jadwal_rencana,
            ]);
        }



        return redirect()->route('dashboardPreventive')->with('success', 'Mesin created successfully');
    }

    // public function storeMaintenance(Request $request)
    // {
    //     $request->validate([
    //         'mesin' => 'required',
    //         'jadwal_aktual' => 'required|date',
    //     ]);

    //     $preventive = JadwalPreventif::where('nomor_mesin', $request->mesin)
    //         ->where('jadwal_rencana', $request->jadwal_rencana)
    //         ->first();

    //     if ($preventive) {
    //         // Jika entri ditemukan, lakukan pembaruan
    //         $preventive->update([
    //             'jadwal_aktual' => $request->jadwal_aktual,
    //             'status' => 0, // Jika Anda ingin mengatur status secara default
    //         ]);
    //     } else {
    //         // Jika tidak ditemukan, buat entri baru
    //         JadwalPreventif::create([
    //             'nomor_mesin' => $request->mesin,
    //             'tipe' => $request->tipe,
    //             'jadwal_rencana' => $request->jadwal_rencana,
    //             'jadwal_aktual' => $request->jadwal_aktual,
    //             'status' => $request->status
    //         ]);
    //     }

    //     return redirect()->route('dashboardPreventiveMaintenance')->with('success', 'Jadwal updated successfully');
    // }


    // public function maintenanceDashPreventive()
    // {
    //     // Mengambil semua data Mesin
    //     $mesins = Mesin::latest()->get();
    //     // Mengambil semua data Preventive
    //     $detailpreventives = DetailPreventive::latest()->get();
    //     // Variabel $i didefinisikan di sini
    //     $i = 0;
    //     // Kembalikan view dengan data mesins, preventives, dan $i
    //     return view('maintenance.dashpreventive', compact('mesins', 'detailpreventives', 'i'));
    // }

    // public function maintenanceDashBlockPreventive()
    // {
    //     // Mengambil semua data Mesin
    //     $mesins = Mesin::latest()->get();

    //     // Mengambil semua data Preventive
    //     $detailpreventives = DetailPreventive::latest()->get();

    //     // Variabel $i didefinisikan di sini
    //     $i = 0;

    //     // Kembalikan view dengan data mesins, preventives, dan $i
    //     return view('maintenance.blokpreventive', compact('mesins', 'detailpreventives', 'i'));
    // }

    // public function deptmtceDashPreventive()
    // {
    //     $mesins = Mesin::latest()->get();
    //     return view('deptmtce.dashpreventive', compact('mesins'))->with('i', (request()->input('page', 1) - 1) * 5);
    // }

    // public function EditDeptMTCEPreventive(Mesin $mesin, DetailPreventive $detailPreventive)
    // {
    //     $detailPreventives = DetailPreventive::where('nomor_mesin', $mesin->id)
    //         ->select('perbaikan_checked', 'perbaikan') // Memilih kolom perbaikan_checked dan perbaikan
    //         ->get();

    //     $mesins = Mesin::latest()->get();
    //     // Mendapatkan status mesin
    //     $status = $mesin->status;

    //     // Tentukan tampilan berdasarkan status
    //     if ($status == 1 || $status == 0) {
    //         return view('deptmtce.lihatpreventive', compact('mesin', 'mesins', 'detailPreventives'))->with('i', (request()->input('page', 1) - 1) * 5);
    //     } else {
    //         return view('deptmtce.dashpreventive', compact('mesins'))->with('i', (request()->input('page', 1) - 1) * 5);
    //     }
    // }

    // public function EditMaintenancePreventive(Mesin $mesin, DetailPreventive $detailPreventive)
    // {
    //     $detailPreventives = DetailPreventive::where('nomor_mesin', $mesin->id)
    //         ->select('perbaikan_checked', 'perbaikan') // Memilih kolom perbaikan_checked dan perbaikan
    //         ->get();

    //     $mesins = Mesin::latest()->get();
    //     // Mendapatkan status mesin
    //     $status = $mesin->status;
    //     // Determine view based on status
    //     if ($status === 1) {
    //         return view('maintenance.lihatpreventive', compact('mesin', 'mesins', 'detailPreventives'))->with('i', (request()->input('page', 1) - 1) * 5);
    //     } else if ($status === 0) {
    //         return view('maintenance.editpreventive', compact('mesin', 'mesins', 'detailPreventives'))->with('i', (request()->input('page', 1) - 1) * 5);
    //     } else {
    //         return view('maintenance.dashpreventive', compact('mesins'))->with('i', (request()->input('page', 1) - 1) * 5);
    //     }
    // }
}

<?php

namespace App\Http\Controllers;

use App\Imports\EventsImport;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Mesin;
use App\Models\FormFPP;
use App\Models\DetailPreventive;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EventController extends Controller
{
    /**
     * Write code on Method
     *
     * @return response()
     */

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $start = $request->start;
            $end = $request->end;

            $data = Event::whereBetween('start', [$start, $end])
                ->orWhereBetween('end', [$start, $end])
                ->get(['id', 'nama_mesin', 'type', 'no_mesin', 'start', 'end', 'status']);

            // Menambahkan warna berdasarkan status dan id
            foreach ($data as $event) {
                if ($event->status === 0) {
                    $event->color = 'yellow'; // Warna kuning untuk status 0
                    $event->textColor = 'black'; // Teks hitam untuk warna kuning
                } else {
                    $event->color = '#2ecc71'; // Warna hitam untuk status 1
                    $event->textColor = 'black'; // Teks putih untuk warna hitam
                }
            }

            return response()->json($data);
        }

        return view('maintenance.blokpreventive');
    }

    public function indexDeptMTCE(Request $request)
    {
        if ($request->ajax()) {
            $start = $request->start;
            $end = $request->end;

            $data = Event::whereBetween('start', [$start, $end])
                ->get(['id', 'nama_mesin', 'type', 'no_mesin', 'start', 'end', 'status']);

            // Menambahkan warna berdasarkan status dan id
            foreach ($data as $event) {
                if ($event->status === 0) {
                    $event->color = 'yellow'; // Warna kuning untuk status 0
                    $event->textColor = 'black'; // Teks hitam untuk warna kuning
                } else {
                    $event->color = '#2ecc71'; // Warna hitam untuk status 1
                    $event->textColor = 'black'; // Teks putih untuk warna hitam
                }
            }

            return response()->json($data);
        }

        return view('deptmtce.blokpreventive');
    }

    public function blokMaintanence(Request $request)
    {
        if ($request->ajax()) {
            $start = $request->start;
            $end = $request->end;

            $data = Event::whereBetween('start', [$start, $end])
                ->orWhereBetween('end', [$start, $end])
                ->get(['id', 'nama_mesin', 'type', 'no_mesin', 'start', 'end', 'status']);

            // Menambahkan warna berdasarkan status dan id
            foreach ($data as $event) {
                if ($event->status === 0) {
                    $event->color = 'yellow'; // Warna kuning untuk status 0
                    $event->textColor = 'black'; // Teks hitam untuk warna kuning
                } else {
                    $event->color = '#2ecc71'; // Warna hitam untuk status 1
                    $event->textColor = 'black'; // Teks putih untuk warna hitam
                }
            }

            return response()->json($data);
        }

        return view('maintenance.blokpreventive');
    }

    public function blokDeptMaintenance(Request $request)
    {
        if ($request->ajax()) {
            $start = $request->start;
            $end = $request->end;

            $data = Event::whereBetween('start', [$start, $end])
                ->get(['id', 'nama_mesin', 'type', 'no_mesin', 'start', 'end', 'status']);

            // Menambahkan warna berdasarkan status dan id
            foreach ($data as $event) {
                if ($event->status === 0) {
                    $event->color = 'yellow'; // Warna kuning untuk status 0
                    $event->textColor = 'black'; // Teks hitam untuk warna kuning
                } else {
                    $event->color = '#2ecc71'; // Warna hitam untuk status 1
                    $event->textColor = 'black'; // Teks putih untuk warna hitam
                }
            }

            return response()->json($data);
        }

        return view('deptmtce.blokpreventive');
    }

    public function create(Request $request)
    {
        // Ambil nilai issues dan checkedIssues dari sesi jika ada
        $issues = $request->session()->get('issues', []);
        // Ambil daftar data mesin dari database
        $mesins = Mesin::all();

        // Kemudian, Anda dapat mengirimkan nilai-nilai ini ke view
        return view('deptmtce.createblok', compact('issues', 'mesins'));
    }

    public function store(Request $request, Mesin $mesin, DetailPreventive $detailPreventive)
    {
        // Mengubah status menjadi 0
        $request->merge(['status' => 0]);

        // Simpan data mesin beserta path foto dan sparepart ke database
        $event = Event::create([
            'id_mesin' => $request->nama_mesin, // Menggunakan id_mesin yang disimpan dalam form
            'nama_mesin' => $request->nama_mesin2, // Simpan nama_mesin sesuai dengan yang dipilih
            'type' => $request->type,
            'no_mesin' => $request->no_mesin,
            'mfg_date' => $request->mfg_date,
            'start' => $request->start,
            'status' => $request->status
        ]);

        // Ambil semua nilai issue dan perbaikan dari request
        $issues = $request->input('issue');
        $checkedIssues = $request->input('checked') ?? [];

        foreach ($issues as $key => $issue) {
            // Buat detail preventive baru dan hubungkan dengan Event yang baru saja dibuat
            $detailPreventive->create([
                'id_mesin' => $request->nama_mesin, // Menggunakan id_mesin yang disimpan dalam form
                'issue' => $issue,
                'issue_checked' => (in_array($key, $checkedIssues) ? '1' : '0'),
                'start' => $request->start,
            ]);
        }

        return redirect()->route('blokDeptMaintenance')->with('success', 'Mesin created successfully');
    }

    public function edit(Event $event, Mesin $mesin, DetailPreventive $detailpreventive)
    {
        $issues = $detailpreventive->where('id_mesin', $event->id_mesin)
            ->where('start', $event->start) // Misalkan 'start' adalah nama kolom tanggal start
            ->pluck('issue')
            ->toArray();

        // Ambil daftar data mesin dari database
        $mesins = Mesin::all();

        // Tentukan ID mesin yang telah dipilih sebelumnya (misalnya dari data event)
        $selected_mesin_id = $event->id_mesin; // Misalkan ID mesin disimpan dalam event dengan nama 'mesin_id'

        // Kemudian, Anda dapat mengirimkan nilai-nilai ini ke view
        return view('deptmtce.editblok', compact('event', 'issues', 'mesins', 'selected_mesin_id'));
    }

    public function editIssue(Event $event, Mesin $mesin, DetailPreventive $detailpreventive)
    {
        $existingEvent = Event::where('id_mesin', $event->id_mesin)
            ->where('start', $event->start)
            ->where('end', $event->end)
            ->whereIn('status', [0, 1])
            ->get();

        // Pisahkan event berdasarkan status
        $existingEventStatus0 = $existingEvent->where('status', 0)->first();
        $existingEventStatus1 = $existingEvent->where('status', 1)->first();

        // Ambil nilai issues dari tabel detailpreventive berdasarkan id_mesin dari event
        $issues = $detailpreventive->where('id_mesin', $event->id_mesin)
            ->where('end', $event->end)
            ->pluck('issue')
            ->toArray();

        $checkedIssues = $detailpreventive->where('id_mesin', $event->id_mesin)
            ->where('end', $event->end)
            ->pluck('issue_checked')
            ->toArray();

        // Ambil daftar data mesin dari database
        $mesins = Mesin::all();

        // Tentukan ID mesin yang telah dipilih sebelumnya (misalnya dari data event)
        $selected_mesin_id = $event->id_mesin; // Misalkan ID mesin disimpan dalam event dengan nama 'mesin_id'

        if ($existingEventStatus0 && !$existingEventStatus1) {
            // Jika ada event dengan status 0 tapi tidak ada event dengan status 1, tampilkan form edit event
            return view('maintenance.editblok', compact('event', 'issues', 'mesins', 'selected_mesin_id', 'checkedIssues'));
        } elseif ($existingEventStatus0 && $existingEventStatus1) {
            // Jika ada event dengan status 0 dan ada juga event dengan status 1, tampilkan detail event
            return view('maintenance.lihatblok', compact('event', 'issues', 'mesins', 'selected_mesin_id', 'checkedIssues'));
        } else {
            return view('maintenance.lihatblok', compact('event', 'issues', 'mesins', 'selected_mesin_id', 'checkedIssues'));
        }
    }

    public function updateIssue(Request $request, Event $event, DetailPreventive $detailPreventive)
    {
        // Ambil semua nilai issue dan perbaikan dari request
        $issues = $request->input('issue');
        $checkedIssues = $request->input('checked') ?? [];

        // Loop melalui setiap issue dari request
        foreach ($issues as $key => $issue) {
            // Cek apakah issue saat ini sudah diceklis atau tidak
            $isChecked = in_array($key, $checkedIssues);

            // Cari jika ada detail preventive sebelumnya dengan issue yang sama
            $existingDetailPreventive = DetailPreventive::where('id_mesin', $event->id_mesin)
                ->where('issue', $issue)
                ->first();

            if ($existingDetailPreventive) {
                // Jika sudah ada, update status checklist
                $existingDetailPreventive->update([
                    'issue_checked' => $isChecked ? '1' : '0'
                ]);
            } else {
                // Jika belum ada, buat detail preventive baru
                DetailPreventive::create([
                    'id_mesin' => $event->id_mesin,
                    'issue' => $issue,
                    'issue_checked' => $isChecked ? '1' : '0'
                ]);
            }
        }

        // Cek apakah confirmed_event bernilai 1 (confirmed finish)
        if ($request->confirmed_event === '1') {
            // Membuat event baru dengan status 1 (confirmed finish)
            $newEvent = Event::create([
                'id_mesin' => $event->id_mesin, // Menggunakan id_mesin yang disimpan dalam form
                'nama_mesin' => $event->nama_mesin, // Simpan nama_mesin sesuai dengan yang dipilih
                'type' => $event->type,
                'no_mesin' => $event->no_mesin,
                'mfg_date' => $event->mfg_date,
                'start' => $request->start,
                'end' => $request->end, // Tetapkan nilai end dari permintaan
                'status' => 1 // Tetapkan status 1 (confirmed finish)
            ]);
        } else {
            // Jika tidak confirmed_event bernilai 1, perbarui event yang ada dengan nilai end baru
            $event->update([
                'end' => $request->end
            ]);
        }

        // Redirect atau response sesuai kebutuhan Anda
        return redirect()->route('blokMaintanence');
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return redirect()->route('maintenance.blokpreventive')->with('success', 'Event deleted successfully');
    }

    // public function dashboardMaintenance(DetailPreventive $detailpreventive, Event $event)
    // {
    //     // // Mengambil semua data FormFPP diurutkan berdasarkan updated_at terbaru
    //     // $formperbaikans = FormFPP::orderBy('updated_at', 'desc')->get();
    //     // $events = Event::latest()->get();

    //     // // Menghitung jumlah form FPP berdasarkan status
    //     // $openCount = $formperbaikans->where('status', 0)->count();
    //     // $onProgressCount = $formperbaikans->where('status', 1)->count();
    //     // $finishCount = $formperbaikans->where('status', 2)->count();
    //     // $closedCount = $formperbaikans->where('status', 3)->count();

    //     // // Ambil detail preventive untuk setiap event dan issue
    //     // $issues = $event->detailPreventives()->pluck('issue')->toArray();

    //     return view('dashboard.dashboardMaintenance');
    // }

    // public function import(Request $request)
    // {
    //     Excel::import(
    //         new EventsImport,
    //         $request->file('file')->store('files')
    //     );
    //     return redirect()->back();
    // }
}

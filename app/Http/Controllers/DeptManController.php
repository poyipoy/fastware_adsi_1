<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Handling;
use App\Models\ScheduleVisit;
use App\Models\TypeMaterial;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon; // Pastikan ini ada di sini
// import Facade "Storage"
use Illuminate\View\View;

class DeptManController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // viewSubmission
    public function submission()
    {
        $view1 = Handling::with(['customers', 'type_materials', 'user']) // Menggunakan array untuk parameter with
            ->where('status', 0) // Menggunakan operator default '=' secara implisit
            ->orderBy('created_at', 'desc') // Urutkan secara descending berdasarkan kolom 'created_at'
            ->get(); // Ambil semua data tanpa pagination

        $view2 = Handling::with('customers', 'type_materials', 'user')
        ->whereIn('status', [1, 2, 3]) // Filter berdasarkan status 1, 2, dan 3
        ->orderByRaw('FIELD(status, 1, 2, 3)') // Urutkan berdasarkan urutan status yang diinginkan
        ->orderByDesc('created_at') // Jika perlu, urutkan secara descending berdasarkan kolom 'created_at' atau sesuaikan dengan kolom yang sesuai
        ->get(); // Ambil semua data tanpa pagination

        return view('deptman.submission', compact('view1', 'view2'));
    }

    public function scheduleVisit()
    {
        // Ambil semua data ScheduleVisit dari database
        // $scheduleVisits = ScheduleVisit::all();
        $scheduleVisits = DB::table('handlings')
                            ->join('customers', 'handlings.customer_id', '=', 'customers.id')
                            ->join('schedule_visits', 'handlings.id', '=', 'schedule_visits.handling_id')
                            ->select(
                                'handlings.id AS handling_id',
                                'customers.id AS customer_id',
                                'customers.name_customer',
                                'schedule_visits.schedule',
                                'schedule_visits.results',
                                'schedule_visits.due_date',
                                'schedule_visits.pic'
                            )
                            ->get();

        return view('deptman.scheduleVisit', compact('scheduleVisits'));
    }

    public function showHistoryClaimComplain()
    {
        // Menggunakan Eloquent dengan eager loading
        $data2 = Handling::with(['schedule_viist', 'customers', 'type_materials', 'user'])
        ->where('status', 3)
        ->orderByDesc('created_at')
        ->get();

        return view('deptman.historyClaimComplain', compact('data2'));
    }

    /**
     * edit.
     *
     * @param mixed $id
     */
    public function showConfirm(string $id): View
    {
        // Mengambil data handling berdasarkan ID
        $handlings = Handling::with(['customers', 'type_materials', 'user'])->findOrFail($id);

        // Mengambil semua data pelanggan
        $customers = Customer::all();

        // Mengambil semua data tipe bahan
        $type_materials = TypeMaterial::all();

        $user = User::all();

        // render view with handlings
        return view('deptman.confirm', compact('handlings', 'customers', 'type_materials', 'user'));
    }

    public function showFollowUp(string $id): View
    {
        // Mengambil data handling berdasarkan ID
        $handlings = Handling::with(['customers', 'type_materials', 'user'])->findOrFail($id);

        // Mengambil semua data pelanggan
        $customers = Customer::all();

        // Mengambil semua data tipe bahan
        $type_materials = TypeMaterial::all();

        $user = User::all();

        // Mengambil data schedule visit berdasarkan handling_id
        $data = ScheduleVisit::where('handling_id', $id)->get();

        // Cek apakah handling memiliki type_1 atau type_2
        $hasType1 = $handlings->type_1 != null;
        $hasType2 = $handlings->type_2 != null;

        // Mengambil semua data schedule visit
        $scheduleVisit = ScheduleVisit::all();

        // render view with handlings
        return view('deptman.followup', compact('handlings', 'customers', 'type_materials', 'data', 'user', 'scheduleVisit', 'hasType1', 'hasType2'));
    }

    public function showHistoryProgres(string $id): View
    {
        // Mengambil data handling berdasarkan ID
        $handling = Handling::with(['customers', 'type_materials', 'user'])->findOrFail($id);

        // Mengambil semua data pelanggan
        $customers = Customer::all();

        // Mengambil semua data tipe bahan
        $type_materials = TypeMaterial::all();

        $user = User::all();

        // Mengambil data schedule visit berdasarkan handling_id
        $data = ScheduleVisit::where('handling_id', $id)->get();

        // Mengembalikan view 'deptman.historyProgres' dengan data yang dibutuhkan
        return view('deptman.historyProgres', compact('handling', 'customers', 'type_materials', 'data', 'user'));
    }

    public function showCloseProgres(string $id): View
    {
        $handlings = Handling::findOrFail($id);
        $customers = Customer::all();
        $type_materials = TypeMaterial::all();
        $data = ScheduleVisit::where('handling_id', $id)
                            ->whereHas('handlings', function ($query) {
                                $query->where('type_1', 'Komplain')
                                      ->orWhere('type_2', 'Klaim');
                            })
                            ->with('handlings')
                            ->get();

        return view('deptman.showCloseProgres', compact('handlings', 'customers', 'type_materials', 'data'));
    }

    /**
     * update.
     *
     * @param mixed $request
     */
    public function updateConfirm(Request $request, $id): RedirectResponse
    {
        // Dapatkan post berdasarkan ID dan muat relasi yang diperlukan
        $handling = Handling::with(['customers', 'type_materials'])->findOrFail($id);

        // Update status post
        $handling->update([
            'status' => 1,
        ]);

        // Ambil pengguna yang mengisi data tersebut berdasarkan user_id
        $user = User::find($handling->user_id);

        // Kirim email ke pengguna yang mengisi data tersebut
        if ($user && !empty($user->email)) {
            Mail::send('emails.confirmation', ['handling' => $handling], function ($message) use ($user, $handling) {
                $message->to($user->email)
                        ->subject($handling->no_wo.' telah di konfirmasi');
            });
        }

        // Redirect ke index dengan pesan sukses
        return redirect()->route('submission')->with(['success' => 'Data Berhasil Diubah!']);
    }

    public function updateFollowUp(Request $request, $id)
    {
        // Validasi untuk semua tindakan
        $validationRules = [
            'results' => 'required|string|max:255',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,xlsx,xls,ppt,pptx|max:15360', // 15360 KB = 15 MB
        ];

        // Jalankan validasi berdasarkan tindakan
        $request->validate($validationRules);

        // Ambil jadwal kunjungan yang ada berdasarkan handling_id
        $existingScheduleVisit = ScheduleVisit::where('handling_id', $request->handling_id)->orderBy('schedule', 'desc')->first();

        if ($existingScheduleVisit) {
            $existingSchedule = strtotime($existingScheduleVisit->schedule);
        } else {
            $existingSchedule = null;
        }

        // Periksa apakah batas akhir lebih awal dari jadwal kunjungan
        if (isset($request->due_date)) {
            $dueDate = strtotime($request->due_date);
            if ($existingSchedule && $dueDate < $existingSchedule) {
                return response()->json(['message' => 'Batas Akhir tidak boleh lebih awal dari Jadwal Kunjungan!'], 400);
            }
        }

        // Simpan file dengan nama hash
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->hashName();
            $originalFilename = $file->getClientOriginalName();
            $path = $file->move(public_path('assets/image'), $filename);
        } else {
            $filename = null;
            $originalFilename = null;
        }

        $scheduleVisit = new ScheduleVisit();
        if (isset($request->schedule)) {
            $scheduleVisit->schedule = date('Y-m-d H:i:s', strtotime($request->schedule));
        }
        $scheduleVisit->results = $request->results;
        if (isset($request->due_date)) {
            $scheduleVisit->due_date = date('Y-m-d H:i:s', strtotime($request->due_date));
        }
        $scheduleVisit->pic = $request->pic;
        $scheduleVisit->file = $filename;
        $scheduleVisit->file_name = $originalFilename;
        $scheduleVisit->handling_id = $request->handling_id;
        $scheduleVisit->users_id = $request->user()->id;

        if ($request->action == 'save') {
            $scheduleVisit->status = '1';
            $scheduleVisit->save();

            return response()->json(['message' => 'Data Berhasil Disimpan!', 'refresh' => true]);
        } elseif ($request->action == 'finish') {
            $scheduleVisit->status = '3';
            $scheduleVisit->save();

            $handling = Handling::with(['customers', 'type_materials'])->findOrFail($id);
            $handling->update(['status' => 2]);

            $user = User::find($handling->user_id);
            if ($user && !empty($user->email)) {
                Mail::send('emails.finish', ['handling' => $handling, 'scheduleVisit' => $scheduleVisit], function ($message) use ($user, $handling) {
                    $message->to($user->email)
                            ->subject($handling->no_wo.' telah Finish');
                });
            }

            return response()->json(['message' => 'Proses telah Finish!', 'redirect' => route('submission')]);
        } elseif ($request->action == 'claim') {
            $scheduleVisit->status = '1';
            $scheduleVisit->history_type = '1';
            $scheduleVisit->save();

            $handling = Handling::findOrFail($request->handling_id);
            $handling->update([
                'type_1' => '',
                'type_2' => 'Klaim',
                'status' => 1,
            ]);

            return response()->json(['message' => 'Data Berhasil Diklaim!', 'refresh' => true]);
        }
    }

    public function updateNotes(Request $request)
    {
        $id = $request->input('id');
        $newNotes = $request->input('notes');

        // Cari data berdasarkan id
        $row = ScheduleVisit::find($id);
        
        if ($row) {
            // Ganti catatan lama dengan catatan baru
            $row->notes = $newNotes;
            $row->save();

            return response()->json(['success' => true, 'message' => 'Notes updated']);
        }

        return response()->json(['success' => false, 'message' => 'Data not found'], 404);
    }


}

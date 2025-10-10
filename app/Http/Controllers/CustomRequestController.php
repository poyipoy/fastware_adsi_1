<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MstPengajuanSubcont;
use Illuminate\Auth\Events\Validated;
use App\Models\Customer;
use App\Models\MstPoPengajuan;
use App\Models\User;
use App\Models\TrsAttcCstm;
use App\Models\TrsPengajuanSubcont;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Calculation\TextData\Format;
use Svg\Tag\Rect;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CustomRequestExport;


class CustomRequestController extends Controller
{
    public function showCstmReq(Request $request)
    {
        $perPageOptions = [10, 25, 50, 100];
        $requestedPerPage = (int) $request->input('per_page', 25);
        $perPage = in_array($requestedPerPage, $perPageOptions, true) ? $requestedPerPage : 25;

        $materials = MstPengajuanSubcont::with(['sales', 'marketing', 'production', 'finance'])
            ->whereIn('sec_line', [1, 2])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $today = Carbon::now()->startOfDay();
        $date = [];
        $sincedays = [];

        $materialIds = $materials->pluck('id');

        $activityLogs = $materialIds->isNotEmpty()
            ? TrsPengajuanSubcont::whereIn('id_subcont', $materialIds)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('id_subcont')
            : collect();

        foreach ($materials as $item) {
            if ($item->confirm_prod && $item->harga_akhir && is_null($item->approval_1)) {
                $date[$item->id] = Carbon::parse($item->updated_at)->startOfDay()->diffInDays($today);
            } elseif ($item->approval_1 && $item->date_app_1) {
                $date[$item->id] = Carbon::parse($item->date_app_1)->startOfDay()->diffInDays($today);
            } else {
                $date[$item->id] = null;
            }

            $createdAt = Carbon::parse($item->created_at)->startOfDay();

            if ($item->status_1 == 5) {
                $logsForItem = $activityLogs->get($item->id, collect());
                $latestLog = $logsForItem->first();

                if ($latestLog) {
                    $logUpdatedAt = Carbon::parse($latestLog->updated_at)->startOfDay();
                    $sincedays[$item->id] = $createdAt->diffInDays($logUpdatedAt);
                } else {
                    $sincedays[$item->id] = $createdAt->diffInDays($today);
                }
            } else {
                $sincedays[$item->id] = $createdAt->diffInDays($today);
            }
        }

        return view('custom_req.index_cstm_req', [
            'materials' => $materials,
            'date' => $date,
            'sincedays' => $sincedays,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
        ]);
    }

    public function exportCustomRequests(Request $request)
    {
        $selectedIds = $request->input('selected_ids', []);
        if (!is_array($selectedIds)) {
            $selectedIds = [];
        }

        $selectedIds = array_values(array_unique(array_filter(array_map('intval', $selectedIds))));

        $fileName = 'custom_request_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new CustomRequestExport($selectedIds), $fileName);
    }





    public function showApprovalMarketing()
{
    $materials = MstPengajuanSubcont::with(['sales', 'marketing', 'production', 'finance'])
        ->where('status_1', 3)
        ->whereIn('sec_line', [1, 2])
        ->where('confirm_prod', '!=', '')
        ->orderBy('created_at', 'desc')
        ->get();

    $today = Carbon::now()->startOfDay(); // Format: tanggal tanpa jam
    $date = [];
    $sincedays = [];

    // Hitung durasi proses approval
    foreach ($materials as $item) {
        if ($item->confirm_prod && $item->harga_akhir && is_null($item->approval_1)) {
            $updatedAt = Carbon::parse($item->updated_at)->startOfDay();
            $date[$item->id] = $updatedAt->diffInDays($today);
        } elseif ($item->approval_1 && $item->date_app_1 && is_null($item->approval_2)) {
            $dateApp1 = Carbon::parse($item->date_app_1)->startOfDay();
            $date[$item->id] = $dateApp1->diffInDays($today);
        } else {
            $date[$item->id] = null;
        }
    }

    // Ambil dan kelompokkan log aktivitas
    $activity_logs = TrsPengajuanSubcont::whereIn('id_subcont', $materials->pluck('id'))
        ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy('id_subcont');

    // Hitung sincedays berdasarkan tanggal (bukan jam)
    foreach ($materials as $item) {
            $createdAt = Carbon::parse($item->created_at)->startOfDay();

            if ($item->status_1 == 5) {
                $latestLog = $activity_logs[$item->id]->sortByDesc('created_at')->first() ?? null;

                if ($latestLog) {
                    $logUpdatedAt = Carbon::parse($latestLog->updated_at)->startOfDay();
                    $sincedays[$item->id] = $createdAt->diffInDays($logUpdatedAt);
                } else {
                    $sincedays[$item->id] = $createdAt->diffInDays($today); // fallback jika log kosong
                }
            } else {
                $sincedays[$item->id] = $createdAt->diffInDays($today);
            }
        }

    $files = TrsAttcCstm::where('status', 3)
        ->whereIn('mst_id', $materials->pluck('id'))
        ->get()
        ->keyBy('mst_id');

    $users = User::all();

    $updates = TrsPengajuanSubcont::whereIn('id_subcont', $materials->pluck('id'))
        ->get()
        ->keyBy('id_subcont');

    return view('custom_req.showApprovalMarketing', compact(
        'materials', 'users', 'files', 'updates', 'date', 'sincedays'
    ));
}




    public function approveMarketing(Request $request, $id)
    {
        $materials = MstPengajuanSubcont::findOrFail($id);

        $materials->status_1 = 4;
        $materials->status_2 = 4;
        $materials->approval_1 = auth()->user()->id;
        $materials->date_app_1 = now();
        $materials->save();

        $userName = auth()->user()->name;

        TrsPengajuanSubcont::create([
            'id_subcont' => $materials->id,
            'keterangan' => $request->keterangan, 
            'status' => '4', 
            'modified_at' => $userName 
        ]);

        TrsPengajuanSubcont::create([
            'id_subcont' => $materials->id,
            'keterangan' => 'Approved by Marketing', 
            'status' => '4', 
            'modified_at' => $userName 
        ]);

       return response()->json(['message' => 'Berhasil disetujui Marketing.'], 200);
        
    }

    public function rejectMarketing(Request $request, $id)
    {
        $materials = MstPengajuanSubcont::findOrFail($id);

        $materials->status_1 = 2;
        $materials->status_2 = 2;
        $materials->approval_1 = Null;
        $materials->date_app_1 = Null;
        $materials->confirm_prod = Null;
        $materials->save();

        $files = TrsAttcCstm::where('mst_id', $id)->get();
        $changefiles = $files->filter(function ($file) {
            return $file->status == 4; // Hanya ambil file dengan status 4
        }); 

        foreach ($changefiles as $file) {
            $file->status = 1; // Ubah status menjadi "rejected"
            $file->save(); // Simpan perubahan ke database
        }

        $userName = auth()->user()->name;

        TrsPengajuanSubcont::create([
            'id_subcont' => $materials->id,
            'keterangan' => 'Rejected by Marketing', 
            'status' => '3', 
            'modified_at' => $userName 
        ]);

        TrsPengajuanSubcont::create([
            'id_subcont' => $materials->id,
            'keterangan' => $request->keterangan, 
            'status' => '3', 
            'modified_at' => $userName 
        ]);

       return response()->json(['message' => 'rejected Marketing Berhasil'], 200);
        
    }

    public function approvemarketing2($id)
    {
        $materials = MstPengajuanSubcont::findOrFail($id);

        $materials->status_1 = 5;
        $materials->status_2 = 5;
        $materials->approval_1 = auth()->user()->id;
        $materials->date_app_1 = now();
        $materials->is_active = 0;
        $materials->save();

        $userName = auth()->user()->name;

        TrsPengajuanSubcont::create([
            'id_subcont' => $materials->id,
            'keterangan' => 'Approved by Marketing', 
            'status' => '5', 
            'modified_at' => $userName 
        ]);

       return response()->json(['message' => 'Berhasil disetujui Marketing.'], 200);
        
    }

    public function showApprovalFinance()
{
    $materials = MstPengajuanSubcont::with(['sales', 'marketing', 'production', 'finance'])
        ->whereIn('status_1', [4, 5])
        ->whereIn('sec_line', [1, 2])
        ->where('confirm_prod', '!=', '')
        ->orderBy('created_at', 'desc')
        ->get();

    $today = Carbon::now()->startOfDay(); // hanya tanggal, tanpa jam
    $date = [];
    $sincedays = [];

    // Hitung durasi approval berdasarkan progress
    foreach ($materials as $item) {
        if ($item->confirm_prod && $item->harga_akhir && is_null($item->approval_1)) {
            $date[$item->id] = Carbon::parse($item->updated_at)->startOfDay()->diffInDays($today);
        } elseif ($item->approval_1 && $item->date_app_1) {
            $date[$item->id] = Carbon::parse($item->date_app_1)->startOfDay()->diffInDays($today);
        } else {
            $date[$item->id] = null;
        }
    }

    // Ambil log activity dan group by id_subcont
    $activity_logs = TrsPengajuanSubcont::whereIn('id_subcont', $materials->pluck('id'))
        ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy('id_subcont');

    // Hitung selisih hari antara created_at dan log updated_at (atau today)
    foreach ($materials as $item) {
        $createdAt = Carbon::parse($item->created_at)->startOfDay();

        if ($item->status_1 == 5) {
            $latestLog = $activity_logs[$item->id]->sortByDesc('created_at')->first() ?? null;

            if ($latestLog) {
                $logUpdatedAt = Carbon::parse($latestLog->updated_at)->startOfDay();
                $sincedays[$item->id] = $createdAt->diffInDays($logUpdatedAt);
            } else {
                $sincedays[$item->id] = $createdAt->diffInDays($today);
            }
        } else {
            $sincedays[$item->id] = $createdAt->diffInDays($today);
        }
    }

    $files = TrsAttcCstm::where('status', 3)
        ->whereIn('mst_id', $materials->pluck('id'))
        ->get()
        ->keyBy('mst_id');

    $users = User::all();

    $customunfinished = MstPengajuanSubcont::where('status_1', 4)
        ->whereIn('sec_line', [1, 2])
        ->first();

    $CSTMTerbaru = $customunfinished ? $customunfinished->no_ref : null;

    $updates = TrsPengajuanSubcont::whereIn('id_subcont', $materials->pluck('id'))
        ->get()
        ->keyBy('id_subcont');

    return view('custom_req.showApprovalFinance', compact(
        'materials', 'users', 'files', 'updates', 'date', 'CSTMTerbaru', 'sincedays'
    ));
}

    
    

    public function approveFinance(Request $request, $id)
    {
        $materials = MstPengajuanSubcont::findOrFail($id);

        $materials->status_1 = 5;
        $materials->status_2 = 5;
        $materials->approval_2 = auth()->user()->id;
        $materials->date_app_2 = now();
        $materials->is_active = 0;
        $materials->save();

        $userName = auth()->user()->name;

        

        TrsPengajuanSubcont::create([
            'id_subcont' => $materials->id,
            'keterangan' => $request->keterangan, 
            'status' => '5', 
            'modified_at' => $userName 
        ]);

        TrsPengajuanSubcont::create([
            'id_subcont' => $materials->id,
            'keterangan' => 'Approved by Finance', 
            'status' => '5', 
            'modified_at' => $userName 
        ]);
        
        return response()->json(['message' => 'Berhasil di selesaikan'], 200);
    }

    public function approveketeranganFinance(Request $request, $id)
    {
        $materials = MstPengajuanSubcont::findOrFail($id);
        
        $keterangan = $request->keterangan;

        $userName = auth()->user()->name;

        TrsPengajuanSubcont::create([
            'id_subcont' => $materials->id,
            'keterangan' => $keterangan, 
            'status' => '5', 
            'modified_at' => $userName 
        ]);
        
        return response()->json(['message' => 'Berhasil di selesaikan'], 200);
    }

    public function rejectFinance(Request $request,$id)
    {

        $materials = MstPengajuanSubcont::findOrFail($id);

        $materials->status_1 = 2;
        $materials->status_2 = 2;
        $materials->approval_1 = '';
        $materials->date_app_1 = null;
        $materials->save();

        $files = TrsAttcCstm::where('mst_id', $id)->get();
        $changefiles = $files->filter(function ($file) {
            return $file->status == 4; // Hanya ambil file dengan status 4
        }); 

        foreach ($changefiles as $file) {
            $file->status = 1; // Ubah status menjadi "rejected"
            $file->save(); // Simpan perubahan ke database
        }

        $userName = auth()->user()->name;

        TrsPengajuanSubcont::create([
            'id_subcont' => $materials->id,
            'keterangan' => 'Rejected by finance karena : '. $request->keterangan, 
            'status' => '3', 
            'modified_at' => $userName 
        ]);

       return response()->json(['message' => 'rejected Finance Berhasil'], 200);
        
    }

    public function createCstmReq(Request $request)
    {
        $userName = auth()->user()->name;

        // Ambil no_so langsung dari input tanpa modifikasi
        $no_so = 'SO/' . now()->year . '/' . $request->no_so;

        // Hitung semua data yang pernah ada (tanpa filter tahun)
        $count = MstPengajuanSubcont::count();
        $nextNumber = str_pad($count + 1, 6, '0', STR_PAD_LEFT);

        // Format no_ref menjadi QCU-0001, QCU-0002, ...
        $no_ref = 'QCU-' . $nextNumber;

        // Simpan pengajuan baru
        $pengajuan = MstPengajuanSubcont::create([
            'nama_customer' => $request->customer,
            'nama_project' => $request->nama_project,
            'so' => $no_so,
            'note_sales' => $request->note_sales,
            'qty' => 1,
            'tgl_permintaan' => now(),
            'status_1' => 1,
            'part_name' => $request->part_name,
            'sec_line' => 1,
            'is_active' => 1,
            'jenis_proses_subcont' => 'Null',
            'file' => "Null",
            'file_name' => "Null",
            'status_2' => 1,
            'modified_at' => $userName,
            'no_ref' => $no_ref
        ]);

        // Simpan log
        TrsPengajuanSubcont::create([
            'id_subcont' => $pengajuan->id,
            'keterangan' => 'Pengajuan dibuat',
            'status' => '1',
            'modified_at' => $userName
        ]);

        return redirect()->back()->with('success', 'Material berhasil ditambahkan.');
    }



    public function deleteCstmReq($id)
    {
        $material = MstPengajuanSubcont::findOrFail($id);
        $material->delete();

        return redirect()->back()->with('success', 'Material berhasil dihapus.');
    }

    public function updateMarketing(Request $request, $id)
    {
        $material = MstPengajuanSubcont::findOrFail($id);

        $material->ref_so = $request->ref_so;
        $material->save();
        
        return redirect()->back()->with('success', 'Data berhasil diperbarui.');
    }

    public function inputCstmReq(Request $request, $id)
    {
        // Temukan material berdasarkan ID
        $material = MstPengajuanSubcont::findOrFail($id);
        
        // Simpan nilai sebelumnya
        $originalKeterangan = $material->keterangan;
        $originalHargaAwal = $material->harga_awal;

        // Update data material dengan data dari request hanya untuk harga_awal
        $material->harga_awal = $request->harga_awal;

        // Simpan perubahan
        $material->save();
        
        // Ambil username pengguna yang sedang login
        $userName = auth()->user()->name;

        // Menyusun keterangan aktivitas mencakup semua perubahan
        $changes = [];
        if ($originalKeterangan !== $material->keterangan) {
            $changes[] = "Keterangan diubah dari '$originalKeterangan' menjadi '{$material->keterangan}'";
        }
        if ($originalHargaAwal !== $material->harga_awal) {
            $changes[] = "Harga awal diubah dari '$originalHargaAwal' menjadi '{$material->harga_awal}'";
        }

        // Menggabungkan semua perubahan menjadi satu string
        $keteranganLog = implode(', ', $changes);

        // Menyimpan log aktivitas ke dalam tabel trs_pengajuan_subconts
        TrsPengajuanSubcont::create([
            'id_subcont' => $id, // ID subcont yang relevan
            'keterangan' => $keteranganLog ?: 'Data diupdate tanpa perubahan yang terdeteksi', // Deskripsi log aktivitas
            'status' => '2', // Status atau keterangan tambahan
            'modified_at' => $userName // Menyimpan nama user yang melakukan edit
        ]);

        return redirect()->back()->with('success', 'Material berhasil ditambahkan.');
    }

    public function inputhrgakhr(Request $request, $id)
    {
        // Temukan material berdasarkan ID
        $material = MstPengajuanSubcont::findOrFail($id);
        
        // Simpan nilai sebelumnya
        $originalhargaakhir = $material->harga_akhir;

        // Update data material dengan data dari request
        $material->harga_akhir = $request->harga_akhir;

        // Simpan perubahan
        $material->save();

        // Ambil username pengguna yang sedang login
        $userName = auth()->user()->name;

        // Menyusun keterangan aktivitas mencakup semua perubahan
        $changes = [];
        if ($originalhargaakhir !== $material->harga_akhir) {
            $changes[] = "Selling price diubah dari '$originalhargaakhir' menjadi '{$material->harga_akhir}'";
        }

        // Menggabungkan semua perubahan menjadi satu string
        $keteranganLog = implode(', ', $changes);

        // Menyimpan log aktivitas ke dalam tabel trs_pengajuan_subconts
        TrsPengajuanSubcont::create([
            'id_subcont' => $id, // ID subcont yang relevan
            'keterangan' => $keteranganLog ?: 'Data diupdate tanpa perubahan yang terdeteksi', // Deskripsi log aktivitas
            'status' => '4', // Status atau keterangan tambahan
            'modified_at' => $userName // Menyimpan nama user yang melakukan edit
        ]);

        return redirect()->back()->with('success', 'Material berhasil ditambahkan.');
    }


    public function updateCstmReq(Request $request, $id)
    {
        $material = MstPengajuanSubcont::findOrFail($id);
        $userId = auth()->user()->name;

        $material->keterangan = $request->keterangan;
        $material->jenis_proses_subcont = $request->jenis_proses_subcont;
        $material->harga_awal = $request->harga_awal;
        

        $material->save();

        return redirect()->back()->with('success', 'Data berhasil diperbarui.');
    }

    public function formCstmReq($id)
    {
        // Ambil data utama
        $materials = MstPengajuanSubcont::with(['sales', 'production', 'marketing', 'finance'])->findOrFail($id);

        // Hitung jumlah hari berdasarkan kondisi status_1
        $createdAtDate = $materials->created_at->toDateString();

        if ($materials->status_1 == 5) {
            // Jika status finish, hitung sampai updated_at
            $endDate = $materials->updated_at->toDateString();
        } else {
            // Jika belum finish, hitung sampai hari ini
            $endDate = Carbon::now()->toDateString();
        }

        $daysnow = Carbon::parse($createdAtDate)->diffInDays(Carbon::parse($endDate)) . ' hari';

        // Mengambil daftar file yang terkait
        $files = TrsAttcCstm::where('mst_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $filesquotation = TrsAttcCstm::where('mst_id', $id)
            ->where('status', 4)
            ->get();

        // Mengambil log aktivitas yang terkait
        $activity_logs = TrsPengajuanSubcont::where('id_subcont', $id)
            ->with('mstPengajuanSubcont')
            ->orderBy('created_at', 'desc')
            ->get();

        // Hitung selisih hari kalender antara created_at dan log created_at
        $mstCreatedAt = $materials->created_at->toDateString();
        $sincedays = $activity_logs->map(function ($log) use ($mstCreatedAt) {
            $logDate = $log->created_at->toDateString();
            return Carbon::parse($mstCreatedAt)->diffInDays(Carbon::parse($logDate)) . ' hari';
        });

        // Cek apakah ada file dengan status 3
        $hasStatusThree = $files->contains(fn($file) => $file->status == 3);

        return view('custom_req.viewCstmReq', compact(
            'materials',
            'files',
            'filesquotation',
            'activity_logs',
            'hasStatusThree',
            'sincedays',
            'daysnow'
        ));
    }



    public function submitData(Request $request, $id)
    {
        // Mengambil data pengajuan dari model MstPengajuanSubcont
        $pengajuan = MstPengajuanSubcont::findOrFail($id);
        
        // Cek apakah ada file yang di-upload
        $quotationFilePath = $pengajuan->quotation_file; // Tetap simpan path sebelumnya jika tidak ada file baru
        if ($request->hasFile('quotation_file')) {
            $quotationFile = $request->file('quotation_file');
            $fileName = $quotationFile->getClientOriginalName();
            // Simpan file ke folder public/assets/subcont
            $savePath = $quotationFile->move(public_path('assets/subcont'), $fileName);
            // Simpan path file ke variabel
            $quotationFilePath = 'assets/subcont/' . $fileName;
        }
        
        // Update status_1, status_2, quotation_file, dan keterangan 
        $pengajuan->update([
            'status_1' => 3,
            'status_2' => 3,
            'quotation_file' => $quotationFilePath,
            'keterangan' => $request->input('keterangan'), // Update kolom keterangan
        ]);
        
        // Simpan data ke model TrsPengajuanSubcont
        TrsPengajuanSubcont::create([
            'id_subcont' => $pengajuan->id, // Mengambil id dari MstPengajuanSubcont
            'status' => 3, // Status baru
            'keterangan' => 'Keterangan : ' . $request->input('keterangan'), // Menyimpan keterangan
            'modified_at' => Auth::user()->name, // Mengambil nama user yang sedang login
        ]);

        // Redirect kembali ke halaman form dengan id
        return redirect()->route('CustomRequest.form', ['id' => $id])->with('message', 'Status dan keterangan berhasil disimpan.');
    }

    public function formCstmReqSales($id)
    {
        // Mengambil data MstPengajuanSubcont berdasarkan ID
        $materials = MstPengajuanSubcont::with(['sales', 'production', 'marketing', 'finance'])->findOrFail($id);
        
        // Mengambil daftar file yang terkait
        $files = TrsAttcCstm::where('mst_id', $id)
        ->orderBy('created_at', 'desc')
        ->get();

        

        // Mengambil log aktivitas yang terkait
        $activity_logs = TrsPengajuanSubcont::where('id_subcont', $id)
            ->with('mstPengajuanSubcont')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('custom_req.viewCstmReqSales', compact('materials', 'files', 'activity_logs'));
    }

    public function cancel($id)
    {
        // Ambil record beserta relasi (jika perlu untuk view), tapi tidak mengubah relasi/field lain
        $materials = MstPengajuanSubcont::with(['sales', 'production', 'marketing', 'finance'])->findOrFail($id);

        // Jika sudah non-aktif, cukup kembali dan reload halaman tanpa mengubah field lain
        if ($materials->is_active == 0) {
            return redirect()->back()->with('info', 'sudah tidak-aktif.');
        }

        // Ubah hanya field is_active
        $materials->is_active = 0;

        // Simpan perubahan (hanya is_active yang berubah)
        $materials->save();

        // Redirect back untuk me-reload halaman
        return redirect()->back()->with('success', 'Pengajuan berhasil dibatalkan.');
    }


    public function upload(Request $request, $id)
    {
        // Validasi file upload
        $request->validate([
            'file' => 'required|mimes:pdf',
            'harga_awal' => 'nullable|numeric', // Validasi untuk harga_awal sebagai angka
        ]);

        $userName = auth()->user()->name;

        // Cek apakah ada file dengan mst_id yang sama dengan status = 3
        $hasStatusThree = TrsAttcCstm::where('mst_id', $id)->where('status', 3)->exists();
        
        // Simpan file yang di-upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();

            // Mendapatkan waktu saat ini dalam format yang diinginkan
            $timestamp = now()->format('dmy_His'); // Format: hari_bulan_tahun_jam_menit_detik
            // Membuat nama file baru
            $filename = $originalFilename . '_' . $timestamp . '.' . $extension;
            
            // Tentukan path penyimpanan
            $destinationPath = public_path('assets/custom_request');
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            // Pindahkan file ke direktori tujuan
            if ($file->move($destinationPath, $filename)) {
                // Menyimpan data ke TrsAttcCstm dengan status yang telah ditentukan
                TrsAttcCstm::create([
                    'mst_id' => $id,
                    'file_name' => $filename,
                    'status' => $hasStatusThree ? 4 : 2, // Menggunakan status yang ditentukan
                    'create_by' => $userName
                ]);

                // Menambahkan catatan aktivitas ke dalam tabel trs_pengajuan_subconts
                TrsPengajuanSubcont::create([
                    'id_subcont' => $id,
                    'keterangan' => 'Uploaded file: ' . $filename,
                    'status' => '1', // Status atau keterangan tambahan
                    'modified_at' => $userName
                ]);

                // Jika ada file dengan status 3, simpan harga_awal dari input
                if ($request->harga_awal !== null) {
                    $material = MstPengajuanSubcont::findOrFail($id);
                    $material->harga_awal = $request->harga_awal; // Simpan nilai harga_awal dari input
                    $material->save(); // Simpan perubahan

                    TrsPengajuanSubcont::create([
                    'id_subcont' => $id,
                    'keterangan' => 'Cost Process : ' . $request->harga_awal,
                    'status' => '2', // Status atau keterangan tambahan
                    'modified_at' => $userName
                ]);
                }
            } else {
                return response()->json(['message' => 'File upload failed'], 500);
            }
        } else {
            return response()->json(['message' => 'No file uploaded'], 400);
        }

        // Ambil semua file yang terkait dengan mst_id
        $allFiles = TrsAttcCstm::where('mst_id', $id)->pluck('file_name')->toArray();

        // Return ke halaman sebelumnya dengan pesan dan data
        return redirect()->back()->with([
            'message' => 'File uploaded successfully',
            'uploadedFiles' => $allFiles
        ]);
    }

    public function fileapprove($id)
    {

        $file = TrsAttcCstm::findOrFail($id);
        $file->status = 3;
        $file->save();

        $filename = $file->file_name;
        

        $userName = auth()->user()->name;


        TrsPengajuanSubcont::create([
            'id_subcont' => $id,
            'keterangan' => 'disetujui Produksi' . ' file : '. $filename,
            'status' => '3',
            'modified_at' => $userName
        ]);
        
        return redirect()->back()->with('message', 'File berhasil di approve');
    }

    public function filerejected($id)
    {
        $file = TrsAttcCstm::findOrFail($id);
        $file->status = 1; // Ubah status menjadi "rejected"
        $file->save(); // Simpan perubahan ke database

        $mstid = $file->mst_id; // Ambil mst_id dari file

        $filename = $file->file_name;

        $userName = auth()->user()->name;

        $pengajuan = MstPengajuanSubcont::findOrFail($mstid);
        $pengajuan->status_1 = 1;
        $pengajuan->status_2 = 1;
        $pengajuan->confirm_prod = '';
        $pengajuan->save();

        TrsPengajuanSubcont::create([
            'id_subcont' => $mstid, // Atau ID yang relevan sesuai dengan konteks Anda
            'keterangan' => 'ditolak Custom,' . ' file : '. $filename, // Deskripsi aktivitas
            'status' => '1', // Status atau keterangan tambahan
            'modified_at' => $userName // Menyimpan nama user
        ]);

        return redirect()->back()->with('message', 'File berhasil di reject');
    }

    public function filerejectedsales($id)
    {
        $file = TrsAttcCstm::findOrFail($id);
        $file->status = 1; // Ubah status menjadi "rejected"
        $file->save(); // Simpan perubahan ke database

        $mstid = $file->mst_id; // Ambil mst_id dari file

        $filename = $file->file_name;

        $userName = auth()->user()->name;

        $pengajuan = MstPengajuanSubcont::findOrFail($mstid);
        $pengajuan->status_1 = 2;
        $pengajuan->status_2 = 2;
        $pengajuan->confirm_prod = '';
        $pengajuan->save();

        TrsPengajuanSubcont::create([
            'id_subcont' => $mstid, // Atau ID yang relevan sesuai dengan konteks Anda
            'keterangan' => 'ditolak Sales,' . ' file : '. $filename, // Deskripsi aktivitas
            'status' => '2', // Status atau keterangan tambahan
            'modified_at' => $userName // Menyimpan nama user
        ]);

        return redirect()->back()->with('message', 'File berhasil di reject');
    }

    public function filehapussales($id)
    {
        $file = TrsAttcCstm::findOrFail($id);

        // Path lengkap file di public
        $filePath = public_path('assets/custom_request/' . $file->file_name);

        // Hapus file fisik jika ada
        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        // Hapus data dari database
        $file->delete();

        return redirect()->back()->with('message', 'File berhasil dihapus');
    }


    public function download($id)
    {
        $file = TrsAttcCstm::findOrFail($id);
        
        // Path ke file yang disimpan
        $path = public_path('assets/custom_request/' . $file->file_name);
        
        // Cek apakah file ada
        if (!file_exists($path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return response()->download($path);
    }

    public function kirimproduction($id)
    {
        $pengajuan = MstPengajuanSubcont::findOrFail($id);
        $pengajuan->status_1 = 2;
        $pengajuan->status_2 = 2;
        $pengajuan->save();

        $userName = auth()->user()->name;


        TrsPengajuanSubcont::create([
            'id_subcont' => $id,
            'keterangan' => 'dikirim ke Custom',
            'status' => '2',
            'modified_at' => $userName
        ]);

        return response()->json(['message' => 'Berhasil dikirim ke Custom.'], 200);
    }

    public function rejectproduction($id)
    {
        $pengajuan = MstPengajuanSubcont::findOrFail($id);
        $pengajuan->status_1 = 1;
        $pengajuan->status_2 = 1;
        $pengajuan->save();

        $userName = auth()->user()->name;


        TrsPengajuanSubcont::create([
            'id_subcont' => $id,
            'keterangan' => 'Di reject oleh Custom',
            'status' => '1',
            'modified_at' => $userName
        ]);

        return response()->json(['message' => 'Rejected'], 200);
    }

    public function submitproduction($id)
    {
        $userName = auth()->user()->id;

        $pengajuan = MstPengajuanSubcont::findOrFail($id);
        $pengajuan->status_1 = 3;
        $pengajuan->status_2 = 3;
        $pengajuan->confirm_prod = auth()->user()->id;
        $pengajuan->date_confirm_prod = now();
        $pengajuan->save();

        $userName = auth()->user()->name;


        TrsPengajuanSubcont::create([
            'id_subcont' => $id,
            'keterangan' => 'disubmit ke marketing',
            'status' => '3',
            'modified_at' => $userName
        ]);

        return response()->json(['message' => 'Berhasil dikirim ke Marketing.'], 200);
    }

    public function kirimsubcont(Request $request, $id)
    {
        $pengajuan = MstPengajuanSubcont::findOrFail($id);

        // Validasi input
        $validated = $request->validate([
            'keterangan' => 'required|string',
            'jenis_process_subcont' => 'required|string',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // Default folder penyimpanan
        $uploadPath = public_path('assets/subcont');

        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        // Proses file jika ada
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();

            $fileName = $originalName . '.' . $extension;
            $counter = 1;

            // Tambahkan angka jika file dengan nama sama sudah ada
            while (File::exists($uploadPath . '/' . $fileName)) {
                $fileName = $originalName . '(' . $counter . ').' . $extension;
                $counter++;
            }

            // Simpan file ke folder publik
            $file->move($uploadPath, $fileName);

            // Simpan nama dan path ke database
            $pengajuan->file = 'assets/subcont/' . $fileName;
            $pengajuan->file_name = $fileName;
        }

        // Update data lainnya
        $pengajuan->status_1 = 2;
        $pengajuan->status_2 = 2;
        $pengajuan->sec_line = 2;
        $pengajuan->jenis_proses_subcont = $request->jenis_process_subcont;
        $pengajuan->keterangan = $request->keterangan;
        $pengajuan->save();

        // Simpan log proses
        $userName = auth()->user()->name;
        TrsPengajuanSubcont::create([
            'id_subcont' => $id,
            'keterangan' => 'Menginput Keterangan :' . $request->keterangan . ' dan jenis proses: ' . $request->jenis_process_subcont,
            'status' => '2',
            'modified_at' => $userName,
        ]);

        return response()->json(['message' => 'Berhasil dikirim ke Subcont'], 200);
    }

    public function updateNoSo(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'no_so_suffix' => 'required|digits:6',
        ]);

        $suffix = $request->no_so_suffix;
        $prefix = 'SO/' . date('Y') . '/'; // Atau sesuaikan jika tahun tidak dinamis
        $fullNoSo = $prefix . $suffix;

        $requestModel = MstPengajuanSubcont::findOrFail($request->id);
        $requestModel->so = $fullNoSo;
        $requestModel->save();

        return redirect()->back()->with('success', 'Nomor SO berhasil diperbarui.');
    }




    public function kirimsales(Request $request, $id)
    {
        $pengajuan = MstPengajuanSubcont::findOrFail($id);

        // Simpan jenis proses subcont
        $pengajuan->approval_1 = 'Waiting'; // Simpan keterangan
        $pengajuan->save();


        // Simpan informasi ke TrsPengajuanSubcont
        $userName = auth()->user()->name;
        TrsPengajuanSubcont::create([
            'id_subcont' => $id,
            'keterangan' => 'mengirim ke Dept Head Marketing',
            'status' => '3',
            'modified_at' => $userName 
        ]);

        return response()->json(['message' => 'Berhasil dikirim ke dept head marketing'], 200);
    }

    public function approveProduction($id)
    {

        $userName = auth()->user()->name;

        $pengajuan = MstPengajuanSubcont::findOrFail($id);
        $pengajuan->status_1 = 3;
        $pengajuan->status_2 = 3;
        $pengajuan->confirm_prod = auth()->user()->id;
        $pengajuan->save();

        TrsPengajuanSubcont::create([
            'id_subcont' => $id, // Atau ID yang relevan sesuai dengan konteks Anda
            'keterangan' => 'disetujui Custom', // Deskripsi aktivitas
            'status' => '3', // Status atau keterangan tambahan
            'modified_at' => $userName // Menyimpan nama user
        ]);


        return redirect()->back()->with('message', 'Berhasil disetujui oleh produksi');
    }

}
